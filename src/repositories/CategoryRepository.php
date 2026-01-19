<?php
/**
 * Репозиторий для работы с категориями
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../models/Category.php';

class CategoryRepository {
    private $dbService;
    
    public function __construct() {
        $this->dbService = new DatabaseService();
    }
    
    /**
     * Получает все категории
     * @return array Массив объектов Category
     */
    public function getAll() {
        $sql = "SELECT * FROM categories ORDER BY name";
        $results = $this->dbService->select($sql);
        
        return array_map(function($row) {
            return new Category($row);
        }, $results);
    }
    
    /**
     * Получает категорию по ID
     * @param int $id ID категории
     * @return Category|null Объект категории или null
     */
    public function getById($id) {
        $sql = "SELECT * FROM categories WHERE id = ?";
        $result = $this->dbService->selectOne($sql, [$id]);
        
        return $result ? new Category($result) : null;
    }
    
    /**
     * Получает категорию по названию
     * @param string $name Название категории
     * @return Category|null Объект категории или null
     */
    public function getByName($name) {
        $sql = "SELECT * FROM categories WHERE name = ?";
        $result = $this->dbService->selectOne($sql, [$name]);
        
        return $result ? new Category($result) : null;
    }
    
    /**
     * Создает новую категорию
     * @param Category $category Объект категории
     * @return int ID созданной категории
     */
    public function create(Category $category) {
        $errors = $category->validate();
        if (!empty($errors)) {
            throw new Exception('Ошибки валидации: ' . implode(', ', $errors));
        }
        
        // Проверяем уникальность названия
        if ($this->getByName($category->getName())) {
            throw new Exception('Категория с таким названием уже существует');
        }
        
        $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $params = [$category->getName(), $category->getDescription()];
        
        $id = $this->dbService->insert($sql, $params);
        $category->setId($id);
        
        return $id;
    }
    
    /**
     * Обновляет категорию
     * @param Category $category Объект категории
     * @return bool Успешность операции
     */
    public function update(Category $category) {
        if (!$category->getId()) {
            throw new Exception('ID категории не указан');
        }
        
        $errors = $category->validate();
        if (!empty($errors)) {
            throw new Exception('Ошибки валидации: ' . implode(', ', $errors));
        }
        
        // Проверяем уникальность названия (исключая текущую категорию)
        $existing = $this->getByName($category->getName());
        if ($existing && $existing->getId() !== $category->getId()) {
            throw new Exception('Категория с таким названием уже существует');
        }
        
        $sql = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
        $params = [$category->getName(), $category->getDescription(), $category->getId()];
        
        $affectedRows = $this->dbService->update($sql, $params);
        
        return $affectedRows > 0;
    }
    
    /**
     * Удаляет категорию
     * @param int $id ID категории
     * @return bool Успешность операции
     */
    public function delete($id) {
        // Проверяем есть ли товары в этой категории
        $checkSql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $result = $this->dbService->selectOne($checkSql, [$id]);
        
        if ($result['count'] > 0) {
            throw new Exception('Нельзя удалить категорию, в которой есть товары');
        }
        
        $sql = "DELETE FROM categories WHERE id = ?";
        $affectedRows = $this->dbService->delete($sql, [$id]);
        
        return $affectedRows > 0;
    }
    
    /**
     * Получает категории с количеством товаров
     * @return array Массив категорий с количеством товаров
     */
    public function getCategoriesWithProductCount() {
        $sql = "SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id 
                GROUP BY c.id 
                ORDER BY c.name";
        
        $results = $this->dbService->select($sql);
        
        return array_map(function($row) {
            $category = new Category($row);
            $categoryData = $category->toArray();
            $categoryData['product_count'] = (int)$row['product_count'];
            return $categoryData;
        }, $results);
    }
    
    /**
     * Получает количество категорий
     * @return int Общее количество категорий
     */
    public function getCount() {
        $sql = "SELECT COUNT(*) as count FROM categories";
        $result = $this->dbService->selectOne($sql);
        
        return (int)$result['count'];
    }
}
?>