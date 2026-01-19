<?php
/**
 * Property-based тест для системы логирования
 * Feature: sports-nutrition-store, Property 13: Логирование критических операций
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once '../../src/services/DatabaseService.php';
require_once '../../src/services/Logger.php';
require_once '../../database/install.php';

class LoggingPropertyTest {
    private $dbService;
    private $logger;
    private $testIterations = 100;
    private $logDir;
    
    public function __construct() {
        // Инициализируем базу данных для тестов
        $installer = new DatabaseInstaller();
        if (!$installer->checkInstallation()) {
            $installer->install();
        }
        
        $this->dbService = new DatabaseService();
        $this->logger = new Logger();
        $this->logDir = __DIR__ . '/../../logs';
    }
    
    /**
     * Property: Все критические операции с БД должны логироваться
     * Validates: Requirements 4.5
     */
    public function testDatabaseLoggingProperty() {
        echo "Запуск property теста логирования БД операций (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Очищаем логи перед тестом
                $this->clearTestLogs();
                
                // Генерируем случайные данные для тестирования
                $testData = $this->generateRandomTestData();
                
                // Выполняем различные операции с БД
                $operations = $this->performDatabaseOperations($testData);
                
                // Проверяем что все операции залогированы
                $this->verifyOperationsLogged($operations);
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест логирования БД операций прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Property: Ошибки должны корректно логироваться с контекстом
     * Validates: Requirements 4.2, 4.5
     */
    public function testErrorLoggingProperty() {
        echo "Запуск property теста логирования ошибок (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Очищаем логи перед тестом
                $this->clearTestLogs();
                
                // Генерируем случайные ошибочные ситуации
                $errorScenarios = $this->generateErrorScenarios();
                
                foreach ($errorScenarios as $scenario) {
                    $this->simulateErrorScenario($scenario);
                }
                
                // Проверяем что ошибки залогированы
                $this->verifyErrorsLogged($errorScenarios);
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест логирования ошибок прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Property: Логи должны содержать всю необходимую информацию
     * Validates: Requirements 4.5
     */
    public function testLogContentProperty() {
        echo "Запуск property теста содержимого логов (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Очищаем логи перед тестом
                $this->clearTestLogs();
                
                // Генерируем случайные данные
                $testData = $this->generateRandomTestData();
                
                // Выполняем операцию с логированием
                $this->performLoggedOperation($testData);
                
                // Проверяем содержимое логов
                $this->verifyLogContent($testData);
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест содержимого логов прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Выполняет различные операции с базой данных
     */
    private function performDatabaseOperations($testData) {
        $operations = [];
        
        // INSERT операция
        $insertSql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $insertId = $this->dbService->insert($insertSql, [
            $testData['category_name'], 
            $testData['category_description']
        ]);
        $operations[] = ['type' => 'INSERT', 'table' => 'categories', 'id' => $insertId];
        
        // SELECT операция
        $selectSql = "SELECT * FROM categories WHERE id = ?";
        $this->dbService->selectOne($selectSql, [$insertId]);
        $operations[] = ['type' => 'SELECT_ONE', 'table' => 'categories'];
        
        // UPDATE операция
        $updateSql = "UPDATE categories SET name = ? WHERE id = ?";
        $this->dbService->update($updateSql, [$testData['category_name'] . '_updated', $insertId]);
        $operations[] = ['type' => 'UPDATE', 'table' => 'categories', 'id' => $insertId];
        
        // DELETE операция
        $deleteSql = "DELETE FROM categories WHERE id = ?";
        $this->dbService->delete($deleteSql, [$insertId]);
        $operations[] = ['type' => 'DELETE', 'table' => 'categories', 'id' => $insertId];
        
        return $operations;
    }
    
    /**
     * Проверяет что все операции залогированы
     */
    private function verifyOperationsLogged($operations) {
        $logs = $this->logger->readLogs('database.log');
        
        if (empty($logs)) {
            throw new Exception("Логи базы данных пусты");
        }
        
        foreach ($operations as $operation) {
            $found = false;
            
            foreach ($logs as $log) {
                if ($log['operation'] === $operation['type'] && 
                    $log['table'] === $operation['table']) {
                    $found = true;
                    
                    // Проверяем обязательные поля лога
                    $requiredFields = ['timestamp', 'type', 'operation', 'table', 'user_ip'];
                    foreach ($requiredFields as $field) {
                        if (!isset($log[$field])) {
                            throw new Exception("Отсутствует обязательное поле '$field' в логе");
                        }
                    }
                    
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception("Операция {$operation['type']} на таблице {$operation['table']} не залогирована");
            }
        }
    }
    
    /**
     * Генерирует сценарии ошибок для тестирования
     */
    private function generateErrorScenarios() {
        return [
            [
                'type' => 'invalid_sql',
                'message' => 'Invalid SQL syntax error',
                'context' => ['sql' => 'INVALID SQL QUERY']
            ],
            [
                'type' => 'connection_error',
                'message' => 'Database connection failed',
                'context' => ['host' => 'invalid_host']
            ],
            [
                'type' => 'validation_error',
                'message' => 'Data validation failed',
                'context' => ['field' => 'price', 'value' => -100]
            ]
        ];
    }
    
    /**
     * Симулирует ошибочный сценарий
     */
    private function simulateErrorScenario($scenario) {
        $this->logger->logError($scenario['message'], $scenario['context']);
    }
    
    /**
     * Проверяет что ошибки залогированы
     */
    private function verifyErrorsLogged($errorScenarios) {
        $logs = $this->logger->readLogs('error.log');
        
        if (count($logs) < count($errorScenarios)) {
            throw new Exception("Не все ошибки залогированы");
        }
        
        foreach ($errorScenarios as $scenario) {
            $found = false;
            
            foreach ($logs as $log) {
                if ($log['message'] === $scenario['message']) {
                    $found = true;
                    
                    // Проверяем обязательные поля лога ошибок
                    $requiredFields = ['timestamp', 'type', 'message', 'context', 'user_ip'];
                    foreach ($requiredFields as $field) {
                        if (!isset($log[$field])) {
                            throw new Exception("Отсутствует обязательное поле '$field' в логе ошибок");
                        }
                    }
                    
                    break;
                }
            }
            
            if (!$found) {
                throw new Exception("Ошибка '{$scenario['message']}' не залогирована");
            }
        }
    }
    
    /**
     * Выполняет операцию с логированием
     */
    private function performLoggedOperation($testData) {
        // Логируем действие пользователя
        $this->logger->logUserAction('add_to_cart', [
            'product_id' => $testData['product_id'],
            'quantity' => $testData['quantity']
        ]);
        
        // Логируем информационное сообщение
        $this->logger->logInfo('Product added to cart', [
            'product_id' => $testData['product_id'],
            'session_id' => 'test_session_' . rand(1000, 9999)
        ]);
    }
    
    /**
     * Проверяет содержимое логов
     */
    private function verifyLogContent($testData) {
        // Проверяем лог действий пользователя
        $userLogs = $this->logger->readLogs('user_actions.log');
        
        if (empty($userLogs)) {
            throw new Exception("Логи действий пользователя пусты");
        }
        
        $lastUserLog = end($userLogs);
        
        if ($lastUserLog['action'] !== 'add_to_cart') {
            throw new Exception("Неверное действие в логе пользователя");
        }
        
        if ($lastUserLog['data']['product_id'] !== $testData['product_id']) {
            throw new Exception("Неверные данные в логе пользователя");
        }
        
        // Проверяем информационные логи
        $infoLogs = $this->logger->readLogs('info.log');
        
        if (empty($infoLogs)) {
            throw new Exception("Информационные логи пусты");
        }
        
        $lastInfoLog = end($infoLogs);
        
        if ($lastInfoLog['message'] !== 'Product added to cart') {
            throw new Exception("Неверное сообщение в информационном логе");
        }
    }
    
    /**
     * Очищает тестовые логи
     */
    private function clearTestLogs() {
        $logFiles = ['database.log', 'error.log', 'info.log', 'user_actions.log'];
        
        foreach ($logFiles as $logFile) {
            $filePath = $this->logDir . '/' . $logFile;
            if (file_exists($filePath)) {
                file_put_contents($filePath, '');
            }
        }
    }
    
    /**
     * Генерирует случайные тестовые данные
     */
    private function generateRandomTestData() {
        return [
            'category_name' => 'Test_Category_' . rand(1000, 9999),
            'category_description' => 'Test description ' . rand(1000, 9999),
            'product_id' => rand(1, 100),
            'quantity' => rand(1, 10),
            'user_id' => rand(1, 1000)
        ];
    }
    
    /**
     * Запускает все тесты
     */
    public function runAllTests() {
        echo "=== Property-based тесты логирования ===\n";
        
        $results = [
            'database_logging' => $this->testDatabaseLoggingProperty(),
            'error_logging' => $this->testErrorLoggingProperty(),
            'log_content' => $this->testLogContentProperty()
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
    $test = new LoggingPropertyTest();
    $test->runAllTests();
}
?>