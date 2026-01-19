<?php
/**
 * Административный контроллер
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../repositories/OrderRepository.php';
require_once __DIR__ . '/../repositories/CategoryRepository.php';

class AdminController {
    private $productRepo;
    private $orderRepo;
    private $categoryRepo;
    
    public function __construct() {
        $this->productRepo = new ProductRepository();
        $this->orderRepo = new OrderRepository();
        $this->categoryRepo = new CategoryRepository();
        
        // Запускаем сессию
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Главная страница админ-панели
     */
    public function dashboard() {
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        try {
            $stats = [
                'total_products' => $this->productRepo->getCount(),
                'total_orders' => $this->orderRepo->getCount(),
                'total_categories' => $this->categoryRepo->getCount(),
                'order_stats' => $this->orderRepo->getOrderStatistics()
            ];
            
            include __DIR__ . '/../../public/admin/dashboard.php';
            
        } catch (Exception $e) {
            echo "Ошибка: " . $e->getMessage();
        }
    }
    
    /**
     * Управление товарами
     */
    public function manageProducts() {
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        try {
            $products = $this->productRepo->getAll();
            $categories = $this->categoryRepo->getAll();
            
            include __DIR__ . '/../../public/admin/products.php';
            
        } catch (Exception $e) {
            echo "Ошибка: " . $e->getMessage();
        }
    }
    
    /**
     * Управление заказами
     */
    public function manageOrders() {
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        try {
            $status = $_GET['status'] ?? null;
            
            if ($status) {
                $orders = $this->orderRepo->getByStatus($status);
            } else {
                $orders = $this->orderRepo->getAll();
            }
            
            include __DIR__ . '/../../public/admin/orders.php';
            
        } catch (Exception $e) {
            echo "Ошибка: " . $e->getMessage();
        }
    }
    
    /**
     * Простая аутентификация администратора
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // Упрощенная проверка (в реальном приложении пароли должны быть хешированы)
            if ($username === 'admin' && $password === 'admin123') {
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_username'] = $username;
                
                header('Location: /admin/dashboard');
                exit;
            } else {
                $error = 'Неверные учетные данные';
            }
        }
        
        include __DIR__ . '/../../public/admin/login.php';
    }
    
    /**
     * Выход из админ-панели
     */
    public function logout() {
        session_destroy();
        header('Location: /admin/login');
        exit;
    }
    
    /**
     * API: Создание/обновление товара
     */
    public function saveProduct() {
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ запрещен']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Некорректные данные']);
                return;
            }
            
            $product = new Product($input);
            
            if (isset($input['id']) && $input['id']) {
                // Обновление существующего товара
                $result = $this->productRepo->update($product);
                $message = 'Товар обновлен';
            } else {
                // Создание нового товара
                $productId = $this->productRepo->create($product);
                $result = $productId > 0;
                $message = 'Товар создан';
            }
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $result,
                'message' => $message
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * API: Удаление товара
     */
    public function deleteProduct() {
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ запрещен']);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID товара']);
                return;
            }
            
            $result = $this->productRepo->delete($input['id']);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $result,
                'message' => 'Товар удален'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Проверяет права администратора
     */
    private function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
    
    /**
     * Перенаправляет на страницу входа
     */
    private function redirectToLogin() {
        header('Location: /admin/login');
        exit;
    }
}
?>