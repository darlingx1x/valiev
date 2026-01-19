<?php
/**
 * Модель товара в заказе
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

class OrderItem {
    private $id;
    private $orderId;
    private $productId;
    private $quantity;
    private $price; // цена на момент заказа в узбекских сумах
    private $product; // объект Product для удобства
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }
    
    public function fillFromArray($data) {
        $this->id = $data['id'] ?? null;
        $this->orderId = isset($data['order_id']) ? (int)$data['order_id'] : null;
        $this->productId = isset($data['product_id']) ? (int)$data['product_id'] : null;
        $this->quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
        $this->price = isset($data['price']) ? (float)$data['price'] : 0.0;
    }
    
    public function toArray() {
        $result = [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'formatted_price' => $this->getFormattedPrice(),
            'subtotal' => $this->getSubtotal(),
            'formatted_subtotal' => $this->getFormattedSubtotal()
        ];
        
        if ($this->product) {
            $result['product'] = $this->product->toArray();
        }
        
        return $result;
    }
    
    public function validate() {
        $errors = [];
        
        if ($this->orderId === null || $this->orderId <= 0) {
            $errors[] = 'ID заказа обязателен';
        }
        
        if ($this->productId === null || $this->productId <= 0) {
            $errors[] = 'ID товара обязателен';
        }
        
        if ($this->quantity <= 0) {
            $errors[] = 'Количество должно быть больше нуля';
        }
        
        if ($this->price <= 0) {
            $errors[] = 'Цена должна быть больше нуля';
        }
        
        return $errors;
    }
    
    /**
     * Форматирует цену в узбекских сумах
     */
    public function getFormattedPrice() {
        return number_format($this->price, 0, ',', ' ') . ' сум';
    }
    
    /**
     * Вычисляет подытог (цена * количество)
     */
    public function getSubtotal() {
        return $this->price * $this->quantity;
    }
    
    /**
     * Форматирует подытог в узбекских сумах
     */
    public function getFormattedSubtotal() {
        return number_format($this->getSubtotal(), 0, ',', ' ') . ' сум';
    }
    
    // Геттеры
    public function getId() { return $this->id; }
    public function getOrderId() { return $this->orderId; }
    public function getProductId() { return $this->productId; }
    public function getQuantity() { return $this->quantity; }
    public function getPrice() { return $this->price; }
    public function getProduct() { return $this->product; }
    
    // Сеттеры
    public function setId($id) { $this->id = $id; }
    public function setOrderId($orderId) { $this->orderId = (int)$orderId; }
    public function setProductId($productId) { $this->productId = (int)$productId; }
    public function setQuantity($quantity) { $this->quantity = (int)$quantity; }
    public function setPrice($price) { $this->price = (float)$price; }
    public function setProduct(Product $product) { $this->product = $product; }
}
?>