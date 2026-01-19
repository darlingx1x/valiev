<?php
/**
 * Скрипт установки базы данных
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once '../src/config/database.php';

class DatabaseInstaller {
    private $pdo;
    
    public function __construct() {
        // Подключение к MySQL без указания базы данных
        try {
            $dsn = "mysql:host=localhost;charset=utf8mb4";
            $this->pdo = new PDO($dsn, 'root', '');
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Ошибка подключения к MySQL: " . $e->getMessage());
        }
    }
    
    public function install() {
        try {
            echo "Начинаем установку базы данных...\n";
            
            // Выполняем схему
            $this->executeSqlFile('schema.sql');
            echo "✓ Схема базы данных создана\n";
            
            // Выполняем начальные данные
            $this->executeSqlFile('seeds.sql');
            echo "✓ Начальные данные загружены\n";
            
            echo "Установка завершена успешно!\n";
            
        } catch (Exception $e) {
            echo "Ошибка установки: " . $e->getMessage() . "\n";
            return false;
        }
        
        return true;
    }
    
    private function executeSqlFile($filename) {
        $sql = file_get_contents(__DIR__ . '/' . $filename);
        if ($sql === false) {
            throw new Exception("Не удалось прочитать файл: $filename");
        }
        
        // Разделяем на отдельные запросы
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->pdo->exec($statement);
            }
        }
    }
    
    public function checkInstallation() {
        try {
            $this->pdo->exec("USE sports_nutrition_store");
            
            // Проверяем наличие основных таблиц
            $tables = ['categories', 'products', 'orders', 'order_items', 'cart_items'];
            foreach ($tables as $table) {
                $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() === 0) {
                    return false;
                }
            }
            
            // Проверяем наличие данных в категориях
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM categories");
            $count = $stmt->fetchColumn();
            
            return $count > 0;
            
        } catch (PDOException $e) {
            return false;
        }
    }
}

// Запуск установки если скрипт вызван напрямую
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $installer = new DatabaseInstaller();
    
    if ($installer->checkInstallation()) {
        echo "База данных уже установлена.\n";
    } else {
        $installer->install();
    }
}
?>