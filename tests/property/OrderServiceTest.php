<?php
/**
 * Property-based тесты для OrderService
 * Feature: sports-nutrition-store, Property 6: Создание заказа и обновление склада
 * Feature: sports-nutrition-store, Property 9: Обновление статуса заказа
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once '../../src/services/OrderService.php';
require_once '../../database/install.php';

class OrderServicePropertyTest {
    private $orderService;
    private $cartService;
    private $productRepo;
    private $testIterations = 100;
    
    public function __construct() {
        // Инициализируем базу данных для тестов
        $installer = new DatabaseInstaller();
        if (!$installer->checkInstallation()) {
            $installer->install();
        }
        
        // Запускаем сессию для тестов
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->orderService = new OrderService();
        $this->cartService = new CartService();
        $this->productRepo = new ProductRepository();
    }
    
    /**
     * Property 6: Создание заказа и обновление склада
     * Validates: Requirements 2.4, 2.5
     */
    public function testOrderCreationAndStockUpdateProperty() {
        echo "Запуск property теста создания заказов и обновления склада (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Очищаем корзину
                $this->cartService->clearCart();
                
                // Получаем случайные товары с достаточным количеством
                $testProducts = $this->getProductsWithStock();
                
                // Запоминаем начальные остатки на складе
                $initialStock = [];
                foreach ($testProducts as $productData) {
                    $product = $this->productRepo->getById($productData['id']);
                    $initialStock[$productData['id']] = $product->getStockQuantity();
                    
                    // Добавляем товар в корзину
                    $this->cartService->addToCart($productData['id'], $productData['quantity']);
                }
                
                // Генерируем данные покупателя
                $customerData = $this->generateCustomerData();
                
                // Создаем заказ
                $orderId = $this->orderService->createOrderFromCart($customerData);
                
                if (!$orderId) {
                    throw new Exception("Не удалось создать заказ");
                }
                
                // Проверяем что заказ создан
                $order = $this->orderService->getOrder($orderId);
                if (!$order) {
                    throw new Exception("Созданный заказ не найден");
                }
                
                // Проверяем данные заказа
                if ($order->getCustomerName() !== $customerData['name']) {
                    throw new Exception("Неверное имя покупателя в заказе");
                }
                
                if (count($order->getItems()) !== count($testProducts)) {
                    throw new Exception("Неверное количество товаров в заказе");
                }
                
                // Проверяем что склад обновился корректно
                foreach ($testProducts as $productData) {
                    $product = $this->productRepo->getById($productData['id']);
                    $expectedStock = $initialStock[$productData['id']] - $productData['quantity'];
                    
                    if ($product->getStockQuantity() !== $expectedStock) {
                        throw new Exception("Неверное обновление склада для товара {$productData['id']}: ожидалось $expectedStock, получено {$product->getStockQuantity()}");
                    }
                }
                
                // Проверяем что корзина очищена
                $cartItems = $this->cartService->getCartItems();
                if (!empty($cartItems)) {
                    throw new Exception("Корзина не очищена после создания заказа");
                }
                
                // Восстанавливаем склад для следующих тестов
                foreach ($testProducts as $productData) {
                    $this->productRepo->increaseStock($productData['id'], $productData['quantity']);
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест создания заказов и обновления склада прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Property 9: Обновление статуса заказа
     * Validates: Requirements 3.5
     */
    public function testOrderStatusUpdateProperty() {
        echo "Запуск property теста обновления статуса заказа (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Создаем тестовый заказ
                $orderId = $this->createTestOrder();
                
                // Тестируем различные переходы статусов
                $statusTransitions = [
                    [Order::STATUS_PENDING, Order::STATUS_CONFIRMED],
                    [Order::STATUS_CONFIRMED, Order::STATUS_SHIPPED],
                    [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED]
                ];
                
                $currentStatus = Order::STATUS_PENDING;
                
                foreach ($statusTransitions as $transition) {
                    list($fromStatus, $toStatus) = $transition;
                    
                    // Проверяем текущий статус
                    $order = $this->orderService->getOrder($orderId);
                    if ($order->getStatus() !== $currentStatus) {
                        throw new Exception("Неверный текущий статус заказа: ожидался $currentStatus, получен {$order->getStatus()}");
                    }
                    
                    // Обновляем статус
                    $result = $this->orderService->updateOrderStatus($orderId, $toStatus);
                    
                    if (!$result) {
                        throw new Exception("Не удалось обновить статус с $fromStatus на $toStatus");
                    }
                    
                    // Проверяем что статус обновился в БД
                    $updatedOrder = $this->orderService->getOrder($orderId);
                    if ($updatedOrder->getStatus() !== $toStatus) {
                        throw new Exception("Статус не обновился в БД: ожидался $toStatus, получен {$updatedOrder->getStatus()}");
                    }
                    
                    $currentStatus = $toStatus;
                }
                
                // Тестируем недопустимые переходы
                try {
                    $this->orderService->updateOrderStatus($orderId, Order::STATUS_PENDING);
                    throw new Exception("Недопустимый переход статуса не был заблокирован");
                } catch (Exception $e) {
                    // Это ожидаемое поведение
                    if (strpos($e->getMessage(), 'Нельзя изменить статус') === false) {
                        throw $e; // Неожиданная ошибка
                    }
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест обновления статуса заказа прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Получает товары с достаточным количеством на складе
     */
    private function getProductsWithStock() {
        $dbService = new DatabaseService();
        
        $sql = "SELECT id, price, stock_quantity FROM products WHERE stock_quantity >= 5 ORDER BY RAND() LIMIT ?";
        $limit = rand(1, 3);
        $products = $dbService->select($sql, [$limit]);
        
        if (empty($products)) {
            throw new Exception("Нет товаров с достаточным количеством для тестирования");
        }
        
        $testProducts = [];
        foreach ($products as $product) {
            $testProducts[] = [
                'id' => $product['id'],
                'price' => (float)$product['price'],
                'quantity' => rand(1, min(3, $product['stock_quantity']))
            ];
        }
        
        return $testProducts;
    }
    
    /**
     * Генерирует данные покупателя
     */
    private function generateCustomerData() {
        $names = ['Алишер Навоий', 'Фарида Каримова', 'Бахтиёр Усманов', 'Нилуфар Рахимова'];
        $emails = ['test1@example.com', 'test2@example.com', 'customer@test.uz'];
        $phones = ['+998901234567', '998971234567', '+998331234567'];
        
        return [
            'name' => $names[array_rand($names)] . ' ' . rand(100, 999),
            'email' => $emails[array_rand($emails)],
            'phone' => $phones[array_rand($phones)]
        ];
    }
    
    /**
     * Создает тестовый заказ
     */
    private function createTestOrder() {
        // Очищаем корзину
        $this->cartService->clearCart();
        
        // Добавляем товар в корзину
        $testProducts = $this->getProductsWithStock();
        foreach ($testProducts as $productData) {
            $this->cartService->addToCart($productData['id'], $productData['quantity']);
        }
        
        // Создаем заказ
        $customerData = $this->generateCustomerData();
        return $this->orderService->createOrderFromCart($customerData);
    }
    
    /**
     * Запускает все тесты
     */
    public function runAllTests() {
        echo "=== Property-based тесты OrderService ===\n";
        
        $results = [
            'order_creation_stock_update' => $this->testOrderCreationAndStockUpdateProperty(),
            'order_status_update' => $this->testOrderStatusUpdateProperty()
        ];
        
        $passed = array_sum($results);
        $total = count($results);
        
        echo "\n=== Результаты ===\n";
        echo "Пройдено: $passed/$total тестов\n";
        
        if ($passed === $total) {
            echo "✓ Все property тесты прошли успешно!\n";
            return true;
        } else {
            echo "✗ Некоторые тесты не прошли\n";
            return false;
        }
    }
}

// Запуск тестов если скрипт вызван напрямую
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new OrderServicePropertyTest();
    $test->runAllTests();
}
?>