<?php
/**
 * Модель товара
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

class Product {
    private $id;
    private $name;
    private $description;
    private $price; // в узбекских сумах
    private $categoryId;
    private $stockQuantity;
    private $imageUrl;
    private $createdAt;
    private $updatedAt;
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }
    
    public function fillFromArray($data) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->price = isset($data['price']) ? (float)$data['price'] : 0.0;
        $this->categoryId = isset($data['category_id']) ? (int)$data['category_id'] : null;
        $this->stockQuantity = isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0;
        $this->imageUrl = $data['image_url'] ?? '';
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->categoryId,
            'stock_quantity' => $this->stockQuantity,
            'image_url' => $this->imageUrl,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'formatted_price' => $this->getFormattedPrice()
        ];
    }
    
    public function validate() {
        $errors = [];
        
        if (empty($this->name)) {
            $errors[] = 'Название товара обязательно';
        }
        
        if (strlen($this->name) > 255) {
            $errors[] = 'Название товара не должно превышать 255 символов';
        }
        
        if ($this->price <= 0) {
            $errors[] = 'Цена должна быть больше нуля';
        }
        
        if (!$this->isValidUzbekistanPrice($this->price)) {
            $errors[] = 'Некорректный формат цены в узбекских сумах';
        }
        
        if ($this->categoryId === null || $this->categoryId <= 0) {
            $errors[] = 'Категория товара обязательна';
        }
        
        if ($this->stockQuantity < 0) {
            $errors[] = 'Количество на складе не может быть отрицательным';
        }
        
        if (!empty($this->imageUrl) && !$this->isValidImageUrl($this->imageUrl)) {
            $errors[] = 'Некорректный URL изображения';
        }
        
        return $errors;
    }
    
    /**
     * Проверяет корректность цены в узбекских сумах
     */
    private function isValidUzbekistanPrice($price) {
        // Цена должна быть положительной и не превышать разумные пределы
        // Максимальная цена 100,000,000 сум (примерно $10,000)
        return is_numeric($price) && $price > 0 && $price <= 100000000;
    }
    
    /**
     * Проверяет корректность URL изображения
     */
    private function isValidImageUrl($url) {
        if (empty($url)) return true;
        
        // Проверяем что это валидный URL
        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^\//', $url)) {
            return false;
        }
        
        // Проверяем расширение файла
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        
        return in_array($extension, $allowedExtensions);
    }
    
    /**
     * Форматирует цену в узбекских сумах
     */
    public function getFormattedPrice() {
        return number_format($this->price, 0, ',', ' ') . ' сум';
    }
    
    /**
     * Проверяет доступность товара для заказа
     */
    public function isAvailable($quantity = 1) {
        return $this->stockQuantity >= $quantity;
    }
    
    /**
     * Уменьшает количество на складе
     */
    public function decreaseStock($quantity) {
        if (!$this->isAvailable($quantity)) {
            throw new Exception('Недостаточно товара на складе');
        }
        
        $this->stockQuantity -= $quantity;
    }
    
    /**
     * Увеличивает количество на складе
     */
    public function increaseStock($quantity) {
        if ($quantity <= 0) {
            throw new Exception('Количество должно быть положительным');
        }
        
        $this->stockQuantity += $quantity;
    }
    
    // Геттеры
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getPrice() { return $this->price; }
    public function getCategoryId() { return $this->categoryId; }
    public function getStockQuantity() { return $this->stockQuantity; }
    public function getImageUrl() { return $this->imageUrl; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    
    // Сеттеры
    public function setId($id) { $this->id = $id; }
    public function setName($name) { $this->name = $name; }
    public function setDescription($description) { $this->description = $description; }
    public function setPrice($price) { $this->price = (float)$price; }
    public function setCategoryId($categoryId) { $this->categoryId = (int)$categoryId; }
    public function setStockQuantity($quantity) { $this->stockQuantity = (int)$quantity; }
    public function setImageUrl($url) { $this->imageUrl = $url; }
}
?>