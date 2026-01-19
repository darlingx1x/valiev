<?php
/**
 * Конфигурация базы данных
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

class DatabaseConfig {
    private static $host = 'localhost';
    private static $dbname = 'sports_nutrition_store';
    private static $username = 'root';
    private static $password = '';
    private static $charset = 'utf8mb4';
    
    public static function getConnection() {
        try {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=" . self::$charset;
            $pdo = new PDO($dsn, self::$username, self::$password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Ошибка подключения к базе данных");
        }
    }
    
    public static function getDatabaseName() {
        return self::$dbname;
    }
}

// Автозагрузка классов
spl_autoload_register(function ($class) {
    $paths = [
        '../src/models/',
        '../src/controllers/',
        '../src/repositories/',
        '../src/services/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
?>