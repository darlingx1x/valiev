<?php
/**
 * Сервис логирования
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

class Logger {
    private $logDir;
    
    public function __construct() {
        $this->logDir = __DIR__ . '/../../logs';
        
        // Создаем директорию для логов если не существует
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * Логирует операцию с базой данных
     * @param string $operation Тип операции
     * @param string $table Имя таблицы
     * @param array $data Дополнительные данные
     */
    public function logDatabaseOperation($operation, $table, $data = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'database_operation',
            'operation' => $operation,
            'table' => $table,
            'data' => $data,
            'user_ip' => $this->getUserIp(),
            'memory_usage' => memory_get_usage(true),
            'execution_time' => microtime(true)
        ];
        
        $this->writeLog('database.log', $logEntry);
    }
    
    /**
     * Логирует ошибку
     * @param string $message Сообщение об ошибке
     * @param array $context Контекст ошибки
     */
    public function logError($message, $context = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'error',
            'message' => $message,
            'context' => $context,
            'user_ip' => $this->getUserIp(),
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];
        
        $this->writeLog('error.log', $logEntry);
    }
    
    /**
     * Логирует информационное сообщение
     * @param string $message Сообщение
     * @param array $context Контекст
     */
    public function logInfo($message, $context = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'info',
            'message' => $message,
            'context' => $context,
            'user_ip' => $this->getUserIp()
        ];
        
        $this->writeLog('info.log', $logEntry);
    }
    
    /**
     * Логирует действие пользователя
     * @param string $action Действие
     * @param array $data Данные действия
     */
    public function logUserAction($action, $data = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'user_action',
            'action' => $action,
            'data' => $data,
            'user_ip' => $this->getUserIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'session_id' => session_id()
        ];
        
        $this->writeLog('user_actions.log', $logEntry);
    }
    
    /**
     * Записывает лог в файл
     * @param string $filename Имя файла
     * @param array $logEntry Запись лога
     */
    private function writeLog($filename, $logEntry) {
        $logFile = $this->logDir . '/' . $filename;
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Получает IP адрес пользователя
     * @return string IP адрес
     */
    private function getUserIp() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Берем первый IP если их несколько
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Читает логи из файла
     * @param string $filename Имя файла
     * @param int $lines Количество строк (0 = все)
     * @return array Массив записей логов
     */
    public function readLogs($filename, $lines = 100) {
        $logFile = $this->logDir . '/' . $filename;
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $content = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($lines > 0) {
            $content = array_slice($content, -$lines);
        }
        
        $logs = [];
        foreach ($content as $line) {
            $decoded = json_decode($line, true);
            if ($decoded) {
                $logs[] = $decoded;
            }
        }
        
        return $logs;
    }
    
    /**
     * Очищает старые логи
     * @param int $days Количество дней для хранения
     */
    public function cleanOldLogs($days = 30) {
        $files = glob($this->logDir . '/*.log');
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $this->logInfo('Old log file deleted', ['file' => basename($file)]);
            }
        }
    }
}
?>