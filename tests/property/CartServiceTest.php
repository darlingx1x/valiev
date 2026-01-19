<?php
/**
 * Property-based тесты для CartService
 * Feature: sports-nutrition-store, Property 4: Синхронизация корзины с базой данных
 * Feature: sports-nutrition-store, Property 5: Обновление количества в корзине
 * Feature: sports-nutrition-store, Property 19: Корректное суммирование цен
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once '../../src/services/CartService.php';
require_once '../../database/install.php';

class CartServicePropertyTest {
    private $cartService;
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
        
        $this->cartService = new CartService();
    }
    
    /**
     * Property 4: Синхронизация корзины с базой данных
     * Validates: Requirements 2.1, 2.2
     */
    public function testCartSynchronizationProperty() {
        echo "Запуск property теста синхронизации корзины (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Очищаем корзину перед тестом
                $this->cartService->clearCart();
                
                // Генерируем случайные товары для добавления
                $testProducts = $this->generateRandomCartProducts();
                
                // Добавляем товары в корзину
                foreach ($testProducts as $productData) {
                    $this->cartService->addToCart($productData['id'], $productData['quantity']);
                }
                
                // Получаем содержимое корзины
                $cartItems = $this->cartService->getCartItems();
                
                // Проверяем что все добавленные товары есть в корзине
                if (count($cartItems) !== count($testProducts)) {
                    throw new Exception("Количество товаров в корзине не соответствует добавленным");
                }
                
                // Проверяем каждый товар
                foreach ($testProducts as $expectedProduct) {
                    $found = false;
                    
                    foreach ($cartItems as $cartItem) {
                        if ($cartItem->getProductId() === $expectedProduct['id']) {
                            $found = true;
                            
                            if ($cartItem->getQuantity() !== $expectedProduct['quantity']) {
                                throw new Exception("Количество товара в корзине не соответствует добавленному");
                            }
                            
                            // Проверяем что данные товара загружены из БД
                            if (!$cartItem->getProduct() || 
                                !$cartItem->getProduct()->getName()) {
                                throw new Exception("Данные товара не загружены из БД");
                            }
                            
                            break;
                        }
                    }
                    
                    if (!$found) {
                        throw new Exception("Товар ID {$expectedProduct['id']} не найден в корзине");
                    }
                }
                
                // Проверяем счетчики корзины
                $expectedItemsCount = count($testProducts);
                $expectedTotalQuantity = array_sum(array_column($testProducts, 'quantity'));
                
                if ($this->cartService->getCartItemsCount() !== $expectedItemsCount) {
                    throw new Exception("Неверный счетчик товаров в корзине");
                }
                
                if ($this->cartService->getTotalQuantity() !== $expectedTotalQuantity) {
                    throw new Exception("Неверное общее количество в корзине");
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест синхронизации корзины прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Property 5: Обновление количества в корзине
     * Validates: Requirements 2.3
     */
    public function testQuantityUpdateProperty() {
        echo "Запуск property теста обновления количества (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Очищаем корзину
                $this->cartService->clearCart();
                
                // Добавляем случайный товар
                $productData = $this->getRandomAvailableProduct();
                $initialQuantity = rand(1, 3);
                
                $this->cartService->addToCart($productData['id'], $initialQuantity);
                
                // Генерируем новое количество
                $newQuantity = rand(1, min(5, $productData['stock_quantity']));
                
                // Обновляем количество
                $this->cartService->updateQuantity($productData['id'], $newQuantity);
                
                // Проверяем что количество обновилось в БД
                $cartItems = $this->cartService->getCartItems();
                $found = false;
                
                foreach ($cartItems as $item) {
                    if ($item->getProductId() === $productData['id']) {
                        $found = true;
                        
                        if ($item->getQuantity() !== $newQuantity) {
                            throw new Exception("Количество не обновилось в БД: ожидалось $newQuantity, получено {$item->getQuantity()}");
                        }
                        
                        break;
                    }
                }
                
                if (!$found) {
                    throw new Exception("Товар не найден после обновления количества");
                }
                
                // Тестируем обновление до нуля (удаление)
                $this->cartService->updateQuantity($productData['id'], 0);
                
                $cartItemsAfterZero = $this->cartService->getCartItems();
                foreach ($cartItemsAfterZero as $item) {
                    if ($item->getProductId() === $productData['id']) {
                        throw new Exception("Товар не удалился при обновлении количества до 0");
                    }
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест обновления количества прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Property 19: Корректное суммирование цен
     * Validates: Requirements 7.2
     */
    public function testPriceSummationProperty() {
        echo "Запуск property теста суммирования цен (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Очищаем корзину
                $this->cartService->clearCart();
                
                // Генерируем случайные товары
                $testProducts = $this->generateRandomCartProducts();
                $expectedTotal = 0;
                
                // Добавляем товары и вычисляем ожидаемую сумму
                foreach ($testProducts as $productData) {
                    $this->cartService->addToCart($productData['id'], $productData['quantity']);
                    $expectedTotal += $productData['price'] * $productData['quantity'];
                }
                
                // Получаем сумму от сервиса корзины
                $actualTotal = $this->cartService->getCartTotal();
                
                // Проверяем что суммы совпадают (с учетом погрешности float)
                if (abs($actualTotal - $expectedTotal) > 0.01) {
                    throw new Exception("Неверная сумма корзины: ожидалось $expectedTotal, получено $actualTotal");
                }
                
                // Проверяем форматирование суммы в узбекских сумах
                $formattedTotal = $this->cartService->getFormattedCartTotal();
                $expectedFormatted = number_format($expectedTotal, 0, ',', ' ') . ' сум';
                
                if ($formattedTotal !== $expectedFormatted) {
                    throw new Exception("Неверное форматирование суммы: ожидалось '$expectedFormatted', получено '$formattedTotal'");
                }
                
                // Проверяем суммы отдельных товаров
                $cartItems = $this->cartService->getCartItems();
                foreach ($cartItems as $item) {
                    $expectedSubtotal = $item->getProduct()->getPrice() * $item->getQuantity();
                    $actualSubtotal = $item->getSubtotal();
                    
                    if (abs($actualSubtotal - $expectedSubtotal) > 0.01) {
                        throw new Exception("Неверная сумма товара: ожидалось $expectedSubtotal, получено $actualSubtotal");
                    }
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест суммирования цен прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Генерирует случайные товары для корзины
     */
    private function generateRandomCartProducts() {
        $dbService = new DatabaseService();
        
        // Получаем случайные товары с наличием
        $sql = "SELECT id, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY RAND() LIMIT ?";
        $limit = rand(1, 5);
        $products = $dbService->select($sql, [$limit]);
        
        if (empty($products)) {
            throw new Exception("Нет товаров в наличии для тестирования");
        }
        
        $testProducts = [];
        foreach ($products as $product) {
            $maxQuantity = min($product['stock_quantity'], 5);
            $testProducts[] = [
                'id' => $product['id'],
                'price' => (float)$product['price'],
                'quantity' => rand(1, $maxQuantity)
            ];
        }
        
        return $testProducts;
    }
    
    /**
     * Получает случайный доступный товар
     */
    private function getRandomAvailableProduct() {
        $dbService = new DatabaseService();
        
        $sql = "SELECT id, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY RAND() LIMIT 1";
        $product = $dbService->selectOne($sql);
        
        if (!$product) {
            throw new Exception("Нет товаров в наличии для тестирования");
        }
        
        return [
            'id' => $product['id'],
            'price' => (float)$product['price'],
            'stock_quantity' => (int)$product['stock_quantity']
        ];
    }
    
    /**
     * Запускает все тесты
     */
    public function runAllTests() {
        echo "=== Property-based тесты CartService ===\n";
        
        $results = [
            'cart_synchronization' => $this->testCartSynchronizationProperty(),
            'quantity_update' => $this->testQuantityUpdateProperty(),
            'price_summation' => $this->testPriceSummationProperty()
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
    $test = new CartServicePropertyTest();
    $test->runAllTests();
}
?>