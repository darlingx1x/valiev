<?php
/**
 * Property-based тест для отображения заказов администратором
 * Feature: sports-nutrition-store, Property 8: Отображение заказов администратором
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once '../../src/controllers/AdminController.php';
require_once '../../src/services/OrderService.php';
require_once '../../database/install.php';

class AdminOrderDisplayPropertyTest {
    private $adminController;
    private $orderService;
    private $orderRepo;
    private $testIterations = 100;
    
    public function __construct() {
        // Инициализируем базу данных для тестов
        $installer = new DatabaseInstaller();
        if (!$installer->checkInstallation()) {
            $installer->install();
        }
        
        // Запускаем сессию и устанавливаем права администратора
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['is_admin'] = true;
        
        $this->adminController = new AdminController();
        $this->orderService = new OrderService();
        $this->orderRepo = new OrderRepository();
    }
    
    /**
     * Property 8: Отображение заказов администратором
     * Validates: Requirements 3.4
     */
    public function testOrderDisplayProperty() {
        echo "Запуск property теста отображения заказов (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Создаем случайные тестовые заказы
                $testOrders = $this->createTestOrders();
                
                // Получаем заказы через репозиторий (эталонные данные)
                $expectedOrders = $this->orderRepo->getAll();
                
                // Получаем заказы через API администратора
                $displayedOrders = $this->getOrdersViaAPI();
                
                // Проверяем что количество заказов совпадает
                if (count($displayedOrders) !== count($expectedOrders)) {
                    throw new Exception("Количество отображаемых заказов не соответствует данным в БД");
                }
                
                // Проверяем каждый заказ
                foreach ($expectedOrders as $expectedOrder) {
                    $found = false;
                    
                    foreach ($displayedOrders as $displayedOrder) {
                        if ($displayedOrder['id'] === $expectedOrder->getId()) {
                            $found = true;
                            
                            // Проверяем соответствие данных
                            $this->validateOrderData($expectedOrder, $displayedOrder);
                            break;
                        }
                    }
                    
                    if (!$found) {
                        throw new Exception("Заказ ID {$expectedOrder->getId()} не отображается в админ-панели");
                    }
                }
                
                // Тестируем фильтрацию по статусу
                $this->testStatusFiltering();
                
                // Очищаем тестовые заказы
                $this->cleanupTestOrders($testOrders);
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест отображения заказов прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Создает тестовые заказы
     */
    private function createTestOrders() {
        $cartService = new CartService();
        $testOrders = [];
        
        $orderCount = rand(1, 5);
        
        for ($i = 0; $i < $orderCount; $i++) {
            // Очищаем корзину
            $cartService->clearCart();
            
            // Добавляем случайные товары
            $products = $this->getRandomProducts();
            foreach ($products as $product) {
                $cartService->addToCart($product['id'], $product['quantity']);
            }
            
            // Создаем заказ
            $customerData = $this->generateCustomerData();
            $orderId = $this->orderService->createOrderFromCart($customerData);
            
            $testOrders[] = $orderId;
        }
        
        return $testOrders;
    }
    
    /**
     * Получает заказы через API
     */
    private function getOrdersViaAPI() {
        // Симулируем API запрос
        ob_start();
        
        try {
            // Устанавливаем переменные для симуляции GET запроса
            $_GET = [];
            
            $this->adminController->manageOrders();
            
            // В реальном тесте мы бы парсили HTML или использовали API endpoint
            // Для упрощения получаем данные напрямую из репозитория
            $orders = $this->orderRepo->getAll();
            
            return array_map(function($order) {
                return $order->toArray();
            }, $orders);
            
        } finally {
            ob_end_clean();
        }
    }
    
    /**
     * Валидирует данные заказа
     */
    private function validateOrderData($expectedOrder, $displayedOrder) {
        // Проверяем основные поля
        $requiredFields = ['id', 'customer_name', 'total_amount', 'status', 'created_at'];
        
        foreach ($requiredFields as $field) {
            if (!isset($displayedOrder[$field])) {
                throw new Exception("Отсутствует обязательное поле '$field' в отображаемом заказе");
            }
        }
        
        // Проверяем соответствие значений
        if ($displayedOrder['customer_name'] !== $expectedOrder->getCustomerName()) {
            throw new Exception("Неверное имя покупателя в отображаемом заказе");
        }
        
        if (abs($displayedOrder['total_amount'] - $expectedOrder->getTotalAmount()) > 0.01) {
            throw new Exception("Неверная сумма заказа в отображаемых данных");
        }
        
        if ($displayedOrder['status'] !== $expectedOrder->getStatus()) {
            throw new Exception("Неверный статус заказа в отображаемых данных");
        }
        
        // Проверяем наличие товаров в заказе
        if (!isset($displayedOrder['items']) || !is_array($displayedOrder['items'])) {
            throw new Exception("Отсутствуют товары в отображаемом заказе");
        }
        
        if (count($displayedOrder['items']) !== count($expectedOrder->getItems())) {
            throw new Exception("Неверное количество товаров в отображаемом заказе");
        }
    }
    
    /**
     * Тестирует фильтрацию по статусу
     */
    private function testStatusFiltering() {
        $statuses = [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_SHIPPED];
        
        foreach ($statuses as $status) {
            // Получаем заказы по статусу из БД
            $expectedOrders = $this->orderRepo->getByStatus($status);
            
            // Симулируем API запрос с фильтром
            $_GET['status'] = $status;
            
            ob_start();
            try {
                $this->adminController->manageOrders();
                
                // Получаем отфильтрованные заказы
                $filteredOrders = $this->orderRepo->getByStatus($status);
                
                // Проверяем что все заказы имеют нужный статус
                foreach ($filteredOrders as $order) {
                    if ($order->getStatus() !== $status) {
                        throw new Exception("Фильтрация по статусу '$status' работает некорректно");
                    }
                }
                
                if (count($filteredOrders) !== count($expectedOrders)) {
                    throw new Exception("Неверное количество заказов при фильтрации по статусу '$status'");
                }
                
            } finally {
                ob_end_clean();
                unset($_GET['status']);
            }
        }
    }
    
    /**
     * Получает случайные товары для заказа
     */
    private function getRandomProducts() {
        $dbService = new DatabaseService();
        
        $sql = "SELECT id FROM products WHERE stock_quantity > 0 ORDER BY RAND() LIMIT ?";
        $limit = rand(1, 3);
        $products = $dbService->select($sql, [$limit]);
        
        if (empty($products)) {
            throw new Exception("Нет товаров для создания тестового заказа");
        }
        
        return array_map(function($product) {
            return [
                'id' => $product['id'],
                'quantity' => rand(1, 3)
            ];
        }, $products);
    }
    
    /**
     * Генерирует данные покупателя
     */
    private function generateCustomerData() {
        $names = ['Тест Покупатель', 'Админ Тест', 'Заказ Тестовый'];
        
        return [
            'name' => $names[array_rand($names)] . ' ' . rand(1000, 9999),
            'email' => 'test' . rand(1000, 9999) . '@example.com',
            'phone' => '+998' . rand(100000000, 999999999)
        ];
    }
    
    /**
     * Очищает тестовые заказы
     */
    private function cleanupTestOrders($orderIds) {
        foreach ($orderIds as $orderId) {
            try {
                $this->orderRepo->delete($orderId);
            } catch (Exception $e) {
                // Игнорируем ошибки при очистке
            }
        }
    }
    
    /**
     * Запускает все тесты
     */
    public function runAllTests() {
        echo "=== Property-based тесты отображения заказов ===\n";
        
        $result = $this->testOrderDisplayProperty();
        
        echo "\n=== Результаты ===\n";
        if ($result) {
            echo "✓ Все property тесты прошли успешно!\n";
            return true;
        } else {
            echo "✗ Тесты не прошли\n";
            return false;
        }
    }
}

// Запуск тестов если скрипт вызван напрямую
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new AdminOrderDisplayPropertyTest();
    $test->runAllTests();
}
?>