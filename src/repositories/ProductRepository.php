<?php
/**
 * Репозиторий для работы с товарами
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../models/Product.php';

class ProductRepository {
    private $dbService;
    
    public function __construct() {
        $this->dbService = new DatabaseService();
    }
    
    /**
     * Получает все товары
     * @param int $limit Лимит записей
     * @param int $offset Смещение
     * @return array Массив объектов Product
     */
    public function getAll($limit = 50, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $results = $this->dbService->select($sql, [$limit, $offset]);
        
        return array_map(function($row) {
            return new Product($row);
        }, $results);
    }
    
    /**
     * Получает товар по ID
     * @param int $id ID товара
     * @return Product|null Объект товара или null
     */
    public function getById($id) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ?";
        
        $result = $this->dbService->selectOne($sql, [$id]);
        
        return $result ? new Product($result) : null;
    }
    
    /**
     * Получает товары по категории
     * @param int $categoryId ID категории
     * @return array Массив объектов Product
     */
    public function getByCategory($categoryId) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? 
                ORDER BY p.name";
        
        $results = $this->dbService->select($sql, [$categoryId]);
        
        return array_map(function($row) {
            return new Product($row);
        }, $results);
    }
    
    /**
     * Поиск товаров по названию
     * @param string $searchTerm Поисковый запрос
     * @return array Массив объектов Product
     */
    public function searchByName($searchTerm) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.name LIKE ? OR p.description LIKE ?
                ORDER BY p.name";
        
        $searchPattern = '%' . $searchTerm . '%';
        $results = $this->dbService->select($sql, [$searchPattern, $searchPattern]);
        
        return array_map(function($row) {
            return new Product($row);
        }, $results);
    }
    
    /**
     * Создает новый товар
     * @param Product $product Объект товара
     * @return int ID созданного товара
     */
    public function create(Product $product) {
        $errors = $product->validate();
        if (!empty($errors)) {
            throw new Exception('Ошибки валидации: ' . implode(', ', $errors));
        }
        
        $sql = "INSERT INTO products (name, description, price, category_id, stock_quantity, image_url) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $product->getName(),
            $product->getDescription(),
            $product->getPrice(),
            $product->getCategoryId(),
            $product->getStockQuantity(),
            $product->getImageUrl()
        ];
        
        $id = $this->dbService->insert($sql, $params);
        $product->setId($id);
        
        return $id;
    }
    
    /**
     * Обновляет товар
     * @param Product $product Объект товара
     * @return bool Успешность операции
     */
    public function update(Product $product) {
        if (!$product->getId()) {
            throw new Exception('ID товара не указан');
        }
        
        $errors = $product->validate();
        if (!empty($errors)) {
            throw new Exception('Ошибки валидации: ' . implode(', ', $errors));
        }
        
        $sql = "UPDATE products 
                SET name = ?, description = ?, price = ?, category_id = ?, 
                    stock_quantity = ?, image_url = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $params = [
            $product->getName(),
            $product->getDescription(),
            $product->getPrice(),
            $product->getCategoryId(),
            $product->getStockQuantity(),
            $product->getImageUrl(),
            $product->getId()
        ];
        
        $affectedRows = $this->dbService->update($sql, $params);
        
        return $affectedRows > 0;
    }
    
    /**
     * Удаляет товар
     * @param int $id ID товара
     * @return bool Успешность операции
     */
    public function delete($id) {
        // Проверяем есть ли товар в заказах
        $checkSql = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
        $result = $this->dbService->selectOne($checkSql, [$id]);
        
        if ($result['count'] > 0) {
            throw new Exception('Нельзя удалить товар, который есть в заказах');
        }
        
        $sql = "DELETE FROM products WHERE id = ?";
        $affectedRows = $this->dbService->delete($sql, [$id]);
        
        return $affectedRows > 0;
    }
    
    /**
     * Обновляет количество товара на складе
     * @param int $productId ID товара
     * @param int $quantity Новое количество
     * @return bool Успешность операции
     */
    public function updateStock($productId, $quantity) {
        if ($quantity < 0) {
            throw new Exception('Количество не может быть отрицательным');
        }
        
        $sql = "UPDATE products SET stock_quantity = ? WHERE id = ?";
        $affectedRows = $this->dbService->update($sql, [$quantity, $productId]);
        
        return $affectedRows > 0;
    }
    
    /**
     * Уменьшает количество товара на складе
     * @param int $productId ID товара
     * @param int $quantity Количество для уменьшения
     * @return bool Успешность операции
     */
    public function decreaseStock($productId, $quantity) {
        if ($quantity <= 0) {
            throw new Exception('Количество должно быть положительным');
        }
        
        $sql = "UPDATE products 
                SET stock_quantity = stock_quantity - ? 
                WHERE id = ? AND stock_quantity >= ?";
        
        $affectedRows = $this->dbService->update($sql, [$quantity, $productId, $quantity]);
        
        if ($affectedRows === 0) {
            throw new Exception('Недостаточно товара на складе');
        }
        
        return true;
    }
    
    /**
     * Увеличивает количество товара на складе
     * @param int $productId ID товара
     * @param int $quantity Количество для увеличения
     * @return bool Успешность операции
     */
    public function increaseStock($productId, $quantity) {
        if ($quantity <= 0) {
            throw new Exception('Количество должно быть положительным');
        }
        
        $sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
        $affectedRows = $this->dbService->update($sql, [$quantity, $productId]);
        
        return $affectedRows > 0;
    }
    
    /**
     * Получает товары с низким остатком
     * @param int $threshold Пороговое значение
     * @return array Массив объектов Product
     */
    public function getLowStockProducts($threshold = 10) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.stock_quantity <= ? 
                ORDER BY p.stock_quantity ASC";
        
        $results = $this->dbService->select($sql, [$threshold]);
        
        return array_map(function($row) {
            return new Product($row);
        }, $results);
    }
    
    /**
     * Получает количество товаров
     * @return int Общее количество товаров
     */
    public function getCount() {
        $sql = "SELECT COUNT(*) as count FROM products";
        $result = $this->dbService->selectOne($sql);
        
        return (int)$result['count'];
    }
    
    /**
     * Получает товары по ценовому диапазону
     * @param float $minPrice Минимальная цена
     * @param float $maxPrice Максимальная цена
     * @return array Массив объектов Product
     */
    public function getByPriceRange($minPrice, $maxPrice) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.price BETWEEN ? AND ? 
                ORDER BY p.price ASC";
        
        $results = $this->dbService->select($sql, [$minPrice, $maxPrice]);
        
        return array_map(function($row) {
            return new Product($row);
        }, $results);
    }
}
?>