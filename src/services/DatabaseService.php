<?php
/**
 * Сервис для работы с базой данных
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Logger.php';

class DatabaseService {
    private $pdo;
    private $logger;
    
    public function __construct() {
        $this->pdo = DatabaseConfig::getConnection();
        $this->logger = new Logger();
    }
    
    /**
     * Выполняет подготовленный SELECT запрос
     * @param string $sql SQL запрос с плейсхолдерами
     * @param array $params Параметры для подстановки
     * @return array Результаты запроса
     */
    public function select($sql, $params = []) {
        try {
            $this->logger->logDatabaseOperation('SELECT', $this->extractTableName($sql), $params);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $this->logger->logError('Database SELECT error', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Ошибка при выполнении запроса к базе данных');
        }
    }
    
    /**
     * Выполняет подготовленный SELECT запрос и возвращает одну строку
     * @param string $sql SQL запрос с плейсхолдерами
     * @param array $params Параметры для подстановки
     * @return array|null Результат запроса или null
     */
    public function selectOne($sql, $params = []) {
        try {
            $this->logger->logDatabaseOperation('SELECT_ONE', $this->extractTableName($sql), $params);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
            
        } catch (PDOException $e) {
            $this->logger->logError('Database SELECT_ONE error', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Ошибка при выполнении запроса к базе данных');
        }
    }
    
    /**
     * Выполняет подготовленный INSERT запрос
     * @param string $sql SQL запрос с плейсхолдерами
     * @param array $params Параметры для подстановки
     * @return int ID вставленной записи
     */
    public function insert($sql, $params = []) {
        try {
            $this->logger->logDatabaseOperation('INSERT', $this->extractTableName($sql), $params);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $insertId = $this->pdo->lastInsertId();
            
            $this->logger->logDatabaseOperation('INSERT_SUCCESS', $this->extractTableName($sql), [
                'insert_id' => $insertId
            ]);
            
            return $insertId;
            
        } catch (PDOException $e) {
            $this->logger->logError('Database INSERT error', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Ошибка при добавлении данных в базу данных');
        }
    }
    
    /**
     * Выполняет подготовленный UPDATE запрос
     * @param string $sql SQL запрос с плейсхолдерами
     * @param array $params Параметры для подстановки
     * @return int Количество обновленных строк
     */
    public function update($sql, $params = []) {
        try {
            $this->logger->logDatabaseOperation('UPDATE', $this->extractTableName($sql), $params);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $rowCount = $stmt->rowCount();
            
            $this->logger->logDatabaseOperation('UPDATE_SUCCESS', $this->extractTableName($sql), [
                'affected_rows' => $rowCount
            ]);
            
            return $rowCount;
            
        } catch (PDOException $e) {
            $this->logger->logError('Database UPDATE error', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Ошибка при обновлении данных в базе данных');
        }
    }
    
    /**
     * Выполняет подготовленный DELETE запрос
     * @param string $sql SQL запрос с плейсхолдерами
     * @param array $params Параметры для подстановки
     * @return int Количество удаленных строк
     */
    public function delete($sql, $params = []) {
        try {
            $this->logger->logDatabaseOperation('DELETE', $this->extractTableName($sql), $params);
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $rowCount = $stmt->rowCount();
            
            $this->logger->logDatabaseOperation('DELETE_SUCCESS', $this->extractTableName($sql), [
                'affected_rows' => $rowCount
            ]);
            
            return $rowCount;
            
        } catch (PDOException $e) {
            $this->logger->logError('Database DELETE error', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Ошибка при удалении данных из базы данных');
        }
    }
    
    /**
     * Начинает транзакцию
     */
    public function beginTransaction() {
        try {
            $this->logger->logDatabaseOperation('BEGIN_TRANSACTION', 'transaction', []);
            $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            $this->logger->logError('Begin transaction error', ['error' => $e->getMessage()]);
            throw new Exception('Ошибка при начале транзакции');
        }
    }
    
    /**
     * Фиксирует транзакцию
     */
    public function commit() {
        try {
            $this->pdo->commit();
            $this->logger->logDatabaseOperation('COMMIT_TRANSACTION', 'transaction', []);
        } catch (PDOException $e) {
            $this->logger->logError('Commit transaction error', ['error' => $e->getMessage()]);
            throw new Exception('Ошибка при фиксации транзакции');
        }
    }
    
    /**
     * Откатывает транзакцию
     */
    public function rollback() {
        try {
            $this->pdo->rollBack();
            $this->logger->logDatabaseOperation('ROLLBACK_TRANSACTION', 'transaction', []);
        } catch (PDOException $e) {
            $this->logger->logError('Rollback transaction error', ['error' => $e->getMessage()]);
            throw new Exception('Ошибка при откате транзакции');
        }
    }
    
    /**
     * Проверяет находится ли соединение в транзакции
     */
    public function inTransaction() {
        return $this->pdo->inTransaction();
    }
    
    /**
     * Выполняет операцию в транзакции
     * @param callable $callback Функция для выполнения
     * @return mixed Результат выполнения функции
     */
    public function transaction(callable $callback) {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Извлекает имя таблицы из SQL запроса
     * @param string $sql SQL запрос
     * @return string Имя таблицы
     */
    private function extractTableName($sql) {
        // Простое извлечение имени таблицы из запроса
        if (preg_match('/(?:FROM|INTO|UPDATE|TABLE)\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }
        
        return 'unknown';
    }
    
    /**
     * Возвращает объект PDO для прямого доступа (использовать осторожно)
     */
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * Проверяет использует ли запрос подготовленные выражения
     * @param string $sql SQL запрос
     * @return bool
     */
    public function usesPreparedStatement($sql) {
        // Проверяем наличие плейсхолдеров ? или :name
        return preg_match('/\?|:\w+/', $sql) > 0;
    }
}
?>