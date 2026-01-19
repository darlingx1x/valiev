<?php
/**
 * Property-based тест для подготовленных SQL-запросов
 * Feature: sports-nutrition-store, Property 10: Использование подготовленных SQL-запросов
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once '../../src/services/DatabaseService.php';
require_once '../../database/install.php';

class PreparedStatementsPropertyTest {
    private $dbService;
    private $testIterations = 100;
    
    public function __construct() {
        // Инициализируем базу данных для тестов
        $installer = new DatabaseInstaller();
        if (!$installer->checkInstallation()) {
            $installer->install();
        }
        
        $this->dbService = new DatabaseService();
    }
    
    /**
     * Property: Все операции с данными должны использовать подготовленные SQL-запросы
     * Validates: Requirements 4.1
     */
    public function testPreparedStatementsProperty() {
        echo "Запуск property теста подготовленных запросов (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Генерируем случайные данные для тестирования
                $testData = $this->generateRandomTestData();
                
                // Тестируем SELECT запросы
                $this->testSelectQueries($testData);
                
                // Тестируем INSERT запросы
                $this->testInsertQueries($testData);
                
                // Тестируем UPDATE запросы
                $this->testUpdateQueries($testData);
                
                // Тестируем DELETE запросы
                $this->testDeleteQueries($testData);
                
                // Тестируем защиту от SQL-инъекций
                $this->testSqlInjectionProtection($testData);
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест подготовленных запросов прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Тестирует SELECT запросы с подготовленными выражениями
     */
    private function testSelectQueries($testData) {
        // Тест поиска товаров по категории
        $sql = "SELECT * FROM products WHERE category_id = ?";
        $results = $this->dbService->select($sql, [$testData['category_id']]);
        
        // Проверяем что запрос использует подготовленные выражения
        if (!$this->dbService->usesPreparedStatement($sql)) {
            throw new Exception("SELECT запрос не использует подготовленные выражения");
        }
        
        // Проверяем что все результаты соответствуют фильтру
        foreach ($results as $product) {
            if ($product['category_id'] != $testData['category_id']) {
                throw new Exception("Результат SELECT не соответствует параметрам");
            }
        }
        
        // Тест поиска по названию
        $sql = "SELECT * FROM products WHERE name LIKE ?";
        $results = $this->dbService->select($sql, ['%' . $testData['search_term'] . '%']);
        
        if (!$this->dbService->usesPreparedStatement($sql)) {
            throw new Exception("LIKE запрос не использует подготовленные выражения");
        }
    }
    
    /**
     * Тестирует INSERT запросы с подготовленными выражениями
     */
    private function testInsertQueries($testData) {
        $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $params = [$testData['category_name'], $testData['category_description']];
        
        if (!$this->dbService->usesPreparedStatement($sql)) {
            throw new Exception("INSERT запрос не использует подготовленные выражения");
        }
        
        $insertId = $this->dbService->insert($sql, $params);
        
        if (!$insertId) {
            throw new Exception("INSERT запрос не вернул ID");
        }
        
        // Проверяем что данные корректно вставлены
        $checkSql = "SELECT * FROM categories WHERE id = ?";
        $result = $this->dbService->selectOne($checkSql, [$insertId]);
        
        if (!$result || $result['name'] !== $testData['category_name']) {
            throw new Exception("Данные INSERT запроса не соответствуют ожидаемым");
        }
        
        // Очищаем тестовые данные
        $this->dbService->delete("DELETE FROM categories WHERE id = ?", [$insertId]);
    }
    
    /**
     * Тестирует UPDATE запросы с подготовленными выражениями
     */
    private function testUpdateQueries($testData) {
        // Сначала создаем тестовую запись
        $insertSql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $insertId = $this->dbService->insert($insertSql, [
            $testData['category_name'], 
            $testData['category_description']
        ]);
        
        // Обновляем запись
        $updateSql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
        $newName = $testData['category_name'] . '_updated';
        $params = [$newName, $testData['category_description'] . '_updated', $insertId];
        
        if (!$this->dbService->usesPreparedStatement($updateSql)) {
            throw new Exception("UPDATE запрос не использует подготовленные выражения");
        }
        
        $affectedRows = $this->dbService->update($updateSql, $params);
        
        if ($affectedRows !== 1) {
            throw new Exception("UPDATE запрос обновил неожиданное количество строк: $affectedRows");
        }
        
        // Проверяем что данные обновлены
        $checkSql = "SELECT * FROM categories WHERE id = ?";
        $result = $this->dbService->selectOne($checkSql, [$insertId]);
        
        if (!$result || $result['name'] !== $newName) {
            throw new Exception("Данные UPDATE запроса не соответствуют ожидаемым");
        }
        
        // Очищаем тестовые данные
        $this->dbService->delete("DELETE FROM categories WHERE id = ?", [$insertId]);
    }
    
    /**
     * Тестирует DELETE запросы с подготовленными выражениями
     */
    private function testDeleteQueries($testData) {
        // Создаем тестовую запись
        $insertSql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $insertId = $this->dbService->insert($insertSql, [
            $testData['category_name'], 
            $testData['category_description']
        ]);
        
        // Удаляем запись
        $deleteSql = "DELETE FROM categories WHERE id = ?";
        
        if (!$this->dbService->usesPreparedStatement($deleteSql)) {
            throw new Exception("DELETE запрос не использует подготовленные выражения");
        }
        
        $affectedRows = $this->dbService->delete($deleteSql, [$insertId]);
        
        if ($affectedRows !== 1) {
            throw new Exception("DELETE запрос удалил неожиданное количество строк: $affectedRows");
        }
        
        // Проверяем что запись удалена
        $checkSql = "SELECT * FROM categories WHERE id = ?";
        $result = $this->dbService->selectOne($checkSql, [$insertId]);
        
        if ($result !== null) {
            throw new Exception("Запись не была удалена DELETE запросом");
        }
    }
    
    /**
     * Тестирует защиту от SQL-инъекций
     */
    private function testSqlInjectionProtection($testData) {
        // Генерируем потенциально опасные входные данные
        $maliciousInputs = [
            "'; DROP TABLE products; --",
            "1' OR '1'='1",
            "1; DELETE FROM categories; --",
            "' UNION SELECT * FROM categories --",
            "<script>alert('xss')</script>",
            "1' AND (SELECT COUNT(*) FROM categories) > 0 --"
        ];
        
        foreach ($maliciousInputs as $maliciousInput) {
            // Тестируем что подготовленные запросы защищают от инъекций
            $sql = "SELECT * FROM products WHERE name = ?";
            
            try {
                $results = $this->dbService->select($sql, [$maliciousInput]);
                
                // Проверяем что результат безопасен (не содержит все товары)
                $allProductsSql = "SELECT COUNT(*) as total FROM products";
                $totalProducts = $this->dbService->selectOne($allProductsSql)['total'];
                
                if (count($results) === (int)$totalProducts && $totalProducts > 0) {
                    throw new Exception("Возможная SQL-инъекция: запрос вернул все товары");
                }
                
            } catch (Exception $e) {
                // Исключения от базы данных допустимы при попытках инъекций
                if (strpos($e->getMessage(), 'syntax error') !== false) {
                    // Это нормально - база данных отклонила некорректный запрос
                    continue;
                }
                throw $e;
            }
        }
    }
    
    /**
     * Генерирует случайные тестовые данные
     */
    private function generateRandomTestData() {
        $categories = ['Протеины', 'Аминокислоты', 'Креатин', 'Витамины'];
        $searchTerms = ['Whey', 'BCAA', 'Creatine', 'Vitamin', 'Protein'];
        
        return [
            'category_id' => rand(1, 8),
            'search_term' => $searchTerms[array_rand($searchTerms)],
            'category_name' => 'Test_Category_' . rand(1000, 9999),
            'category_description' => 'Test description ' . rand(1000, 9999),
            'product_name' => 'Test Product ' . rand(1000, 9999),
            'product_price' => rand(10000, 500000) / 100, // Цена в сумах
            'quantity' => rand(1, 10)
        ];
    }
    
    /**
     * Запускает все тесты
     */
    public function runAllTests() {
        echo "=== Property-based тесты подготовленных запросов ===\n";
        
        $result = $this->testPreparedStatementsProperty();
        
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
    $test = new PreparedStatementsPropertyTest();
    $test->runAllTests();
}
?>