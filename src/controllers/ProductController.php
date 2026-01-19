<?php
/**
 * Контроллер для работы с товарами
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../repositories/CategoryRepository.php';

class ProductController {
    private $productRepo;
    private $categoryRepo;
    
    public function __construct() {
        $this->productRepo = new ProductRepository();
        $this->categoryRepo = new CategoryRepository();
    }
    
    /**
     * Главная страница с каталогом товаров
     */
    public function index() {
        try {
            $categoryId = $_GET['category'] ?? null;
            $search = $_GET['search'] ?? '';
            
            if ($categoryId) {
                $products = $this->productRepo->getByCategory($categoryId);
            } elseif ($search) {
                $products = $this->productRepo->searchByName($search);
            } else {
                $products = $this->productRepo->getAll();
            }
            
            $categories = $this->categoryRepo->getAll();
            
            // Отображаем HTML страницу
            include __DIR__ . '/../../public/views/catalog.php';
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * API: Получает список товаров
     */
    public function getProducts() {
        try {
            $categoryId = $_GET['category'] ?? null;
            $search = $_GET['search'] ?? '';
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            if ($categoryId) {
                $products = $this->productRepo->getByCategory($categoryId);
            } elseif ($search) {
                $products = $this->productRepo->searchByName($search);
            } else {
                $products = $this->productRepo->getAll($limit, $offset);
            }
            
            $result = array_map(function($product) {
                return $product->toArray();
            }, $products);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * API: Создает новый товар (только для администратора)
     */
    public function createProduct() {
        try {
            // Проверяем права администратора (упрощенная проверка)
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Доступ запрещен']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Некорректные данные']);
                return;
            }
            
            $product = new Product($input);
            $productId = $this->productRepo->create($product);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['id' => $productId, 'message' => 'Товар создан'], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Упрощенная проверка прав администратора
     */
    private function isAdmin() {
        // В реальном приложении здесь была бы проверка JWT токена или сессии
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
}
?>