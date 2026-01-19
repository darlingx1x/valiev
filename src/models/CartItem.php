<?php
/**
 * Модель товара в корзине
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

class CartItem {
    private $id;
    private $sessionId;
    private $productId;
    private $quantity;
    private $createdAt;
    private $product; // объект Product для удобства
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }
    
    public function fillFromArray($data) {
        $this->id = $data['id'] ?? null;
        $this->sessionId = $data['session_id'] ?? '';
        $this->productId = isset($data['product_id']) ? (int)$data['product_id'] : null;
        $this->quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
        $this->createdAt = $data['created_at'] ?? null;
    }
    
    public function toArray() {
        $result = [
            'id' => $this->id,
            'session_id' => $this->sessionId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'created_at' => $this->createdAt
        ];
        
        if ($this->product) {
            $result['product'] = $this->product->toArray();
            $result['subtotal'] = $this->getSubtotal();
            $result['formatted_subtotal'] = $this->getFormattedSubtotal();
        }
        
        return $result;
    }
    
    public function validate() {
        $errors = [];
        
        if (empty($this->sessionId)) {
            $errors[] = 'ID сессии обязателен';
        }
        
        if ($this->productId === null || $this->productId <= 0) {
            $errors[] = 'ID товара обязателен';
        }
        
        if ($this->quantity <= 0) {
            $errors[] = 'Количество должно быть больше нуля';
        }
        
        return $errors;
    }
    
    /**
     * Вычисляет подытог (цена товара * количество)
     */
    public function getSubtotal() {
        if (!$this->product) {
            return 0;
        }
        
        return $this->product->getPrice() * $this->quantity;
    }
    
    /**
     * Форматирует подытог в узбекских сумах
     */
    public function getFormattedSubtotal() {
        return number_format($this->getSubtotal(), 0, ',', ' ') . ' сум';
    }
    
    /**
     * Увеличивает количество товара в корзине
     */
    public function increaseQuantity($amount = 1) {
        if ($amount <= 0) {
            throw new Exception('Количество должно быть положительным');
        }
        
        $this->quantity += $amount;
    }
    
    /**
     * Уменьшает количество товара в корзине
     */
    public function decreaseQuantity($amount = 1) {
        if ($amount <= 0) {
            throw new Exception('Количество должно быть положительным');
        }
        
        if ($this->quantity <= $amount) {
            $this->quantity = 0;
        } else {
            $this->quantity -= $amount;
        }
    }
    
    /**
     * Проверяет доступность товара в нужном количестве
     */
    public function isAvailable() {
        if (!$this->product) {
            return false;
        }
        
        return $this->product->isAvailable($this->quantity);
    }
    
    // Геттеры
    public function getId() { return $this->id; }
    public function getSessionId() { return $this->sessionId; }
    public function getProductId() { return $this->productId; }
    public function getQuantity() { return $this->quantity; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getProduct() { return $this->product; }
    
    // Сеттеры
    public function setId($id) { $this->id = $id; }
    public function setSessionId($sessionId) { $this->sessionId = $sessionId; }
    public function setProductId($productId) { $this->productId = (int)$productId; }
    public function setQuantity($quantity) { $this->quantity = (int)$quantity; }
    public function setProduct(Product $product) { $this->product = $product; }
}
?>