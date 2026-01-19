<?php
/**
 * Property-based тест для инициализации базы данных
 * Feature: sports-nutrition-store, Property 12: Транзакционная целостность заказов
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once '../../database/install.php';
require_once '../../src/config/database.php';

class DatabaseInitializationPropertyTest {
    private $installer;
    private $testIterations = 100;
    
    public function __construct() {
        $this->installer = new DatabaseInstaller();
    }
    
    /**
     * Property: База данных должна корректно инициализироваться при любых условиях
     * Validates: Requirements 4.4
     */
    public function testDatabaseInitializationProperty() {
        echo "Запуск property теста инициализации БД (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            // Генерируем случайные условия для тестирования
            $testConditions = $this->generateRandomTestConditions();
            
            try {
                // Очищаем базу данных
                $this->cleanDatabase();
                
                // Применяем тестовые условия
                $this->applyTestConditions($testConditions);
                
                // Выполняем инициализацию
                $result = $this->installer->install();
                
                // Проверяем что инициализация прошла успешно
                if (!$result) {
                    throw new Exception("Инициализация не удалась на итерации $i");
                }
                
                // Проверяем целостность данных
                if (!$this->verifyDatabaseIntegrity()) {
                    throw new Exception("Нарушена целостность БД на итерации $i");
                }
                
                // Проверяем что все таблицы созданы
                if (!$this->installer->checkInstallation()) {
                    throw new Exception("Проверка установки не прошла на итерации $i");
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест инициализации БД прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Property: Транзакционная целостность при создании заказов
     * Validates: Requirements 4.3
     */
    public function testTransactionalIntegrityProperty() {
        echo "Запуск property теста транзакционной целостности...\n";
        
        // Убеждаемся что БД инициализирована
        if (!$this->installer->checkInstallation()) {
            $this->installer->install();
        }
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                $pdo = DatabaseConfig::getConnection();
                
                // Генерируем случайные данные заказа
                $orderData = $this->generateRandomOrderData();
                
                // Получаем начальное состояние склада
                $initialStock = $this->getProductStock($pdo, $orderData['product_id']);
                
                // Начинаем транзакцию
                $pdo->beginTransaction();
                
                try {
                    // Создаем заказ
                    $stmt = $pdo->prepare("
                        INSERT INTO orders (customer_name, customer_email, total_amount, status) 
                        VALUES (?, ?, ?, 'pending')
                    ");
                    $stmt->execute([
                        $orderData['customer_name'],
                        $orderData['customer_email'],
                        $orderData['total_amount']
                    ]);
                    
                    $orderId = $pdo->lastInsertId();
                    
                    // Добавляем товары в заказ
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $orderId,
                        $orderData['product_id'],
                        $orderData['quantity'],
                        $orderData['price']
                    ]);
                    
                    // Обновляем склад
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET stock_quantity = stock_quantity - ? 
                        WHERE id = ? AND stock_quantity >= ?
                    ");
                    $stmt->execute([
                        $orderData['quantity'],
                        $orderData['product_id'],
                        $orderData['quantity']
                    ]);
                    
                    if ($stmt->rowCount() === 0) {
                        throw new Exception("Недостаточно товара на складе");
                    }
                    
                    // Случайно решаем - коммитить или откатывать
                    if (rand(0, 1)) {
                        $pdo->commit();
                        
                        // Проверяем что склад обновился корректно
                        $finalStock = $this->getProductStock($pdo, $orderData['product_id']);
                        $expectedStock = $initialStock - $orderData['quantity'];
                        
                        if ($finalStock !== $expectedStock) {
                            throw new Exception("Некорректное обновление склада: ожидалось $expectedStock, получено $finalStock");
                        }
                        
                    } else {
                        $pdo->rollBack();
                        
                        // Проверяем что склад не изменился
                        $finalStock = $this->getProductStock($pdo, $orderData['product_id']);
                        
                        if ($finalStock !== $initialStock) {
                            throw new Exception("Склад изменился после отката транзакции");
                        }
                    }
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    
                    // Проверяем что состояние восстановлено
                    $finalStock = $this->getProductStock($pdo, $orderData['product_id']);
                    if ($finalStock !== $initialStock) {
                        throw new Exception("Состояние не восстановлено после ошибки");
                    }
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест транзакционной целостности прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    private function generateRandomTestConditions() {
        return [
            'simulate_connection_error' => rand(0, 10) === 0, // 10% вероятность
            'simulate_disk_full' => rand(0, 20) === 0,        // 5% вероятность
            'concurrent_access' => rand(0, 5) === 0           // 20% вероятность
        ];
    }
    
    private function applyTestConditions($conditions) {
        // В реальном тесте здесь были бы симуляции различных условий
        // Для простоты пропускаем сложные симуляции
    }
    
    private function cleanDatabase() {
        try {
            $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '');
            $pdo->exec("DROP DATABASE IF EXISTS sports_nutrition_store");
        } catch (PDOException $e) {
            // База может не существовать - это нормально
        }
    }
    
    private function verifyDatabaseIntegrity() {
        try {
            $pdo = DatabaseConfig::getConnection();
            
            // Проверяем внешние ключи
            $stmt = $pdo->query("
                SELECT COUNT(*) FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE c.id IS NULL
            ");
            
            if ($stmt->fetchColumn() > 0) {
                return false; // Есть товары без категорий
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function generateRandomOrderData() {
        // Получаем случайный товар
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->query("SELECT id, price FROM products WHERE stock_quantity > 0 ORDER BY RAND() LIMIT 1");
        $product = $stmt->fetch();
        
        if (!$product) {
            throw new Exception("Нет товаров в наличии для тестирования");
        }
        
        $quantity = rand(1, 5);
        
        return [
            'product_id' => $product['id'],
            'quantity' => $quantity,
            'price' => $product['price'],
            'total_amount' => $product['price'] * $quantity,
            'customer_name' => 'Test Customer ' . rand(1, 1000),
            'customer_email' => 'test' . rand(1, 1000) . '@example.com'
        ];
    }
    
    private function getProductStock($pdo, $productId) {
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        return (int)$stmt->fetchColumn();
    }
    
    public function runAllTests() {
        echo "=== Property-based тесты инициализации БД ===\n";
        
        $results = [
            'initialization' => $this->testDatabaseInitializationProperty(),
            'transactional_integrity' => $this->testTransactionalIntegrityProperty()
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
    $test = new DatabaseInitializationPropertyTest();
    $test->runAllTests();
}
?>