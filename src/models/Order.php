<?php
/**
 * Модель заказа
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

class Order {
    private $id;
    private $customerName;
    private $customerEmail;
    private $customerPhone;
    private $totalAmount; // в узбекских сумах
    private $status;
    private $createdAt;
    private $updatedAt;
    private $items = [];
    
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }
    
    public function fillFromArray($data) {
        $this->id = $data['id'] ?? null;
        $this->customerName = $data['customer_name'] ?? '';
        $this->customerEmail = $data['customer_email'] ?? '';
        $this->customerPhone = $data['customer_phone'] ?? '';
        $this->totalAmount = isset($data['total_amount']) ? (float)$data['total_amount'] : 0.0;
        $this->status = $data['status'] ?? self::STATUS_PENDING;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'total_amount' => $this->totalAmount,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'formatted_total' => $this->getFormattedTotal(),
            'status_text' => $this->getStatusText(),
            'items' => array_map(function($item) {
                return $item->toArray();
            }, $this->items)
        ];
    }
    
    public function validate() {
        $errors = [];
        
        if (empty($this->customerName)) {
            $errors[] = 'Имя покупателя обязательно';
        }
        
        if (strlen($this->customerName) > 255) {
            $errors[] = 'Имя покупателя не должно превышать 255 символов';
        }
        
        if (!empty($this->customerEmail) && !filter_var($this->customerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Некорректный email адрес';
        }
        
        if (!empty($this->customerPhone) && !$this->isValidUzbekistanPhone($this->customerPhone)) {
            $errors[] = 'Некорректный номер телефона';
        }
        
        if ($this->totalAmount <= 0) {
            $errors[] = 'Сумма заказа должна быть больше нуля';
        }
        
        if (!$this->isValidStatus($this->status)) {
            $errors[] = 'Некорректный статус заказа';
        }
        
        if (empty($this->items)) {
            $errors[] = 'Заказ должен содержать хотя бы один товар';
        }
        
        return $errors;
    }
    
    /**
     * Проверяет корректность номера телефона Узбекистана
     */
    private function isValidUzbekistanPhone($phone) {
        // Узбекские номера: +998XXXXXXXXX или 998XXXXXXXXX
        $pattern = '/^(\+?998)?[0-9]{9}$/';
        return preg_match($pattern, preg_replace('/[\s\-\(\)]/', '', $phone));
    }
    
    /**
     * Проверяет корректность статуса заказа
     */
    private function isValidStatus($status) {
        $validStatuses = [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED
        ];
        
        return in_array($status, $validStatuses);
    }
    
    /**
     * Форматирует общую сумму в узбекских сумах
     */
    public function getFormattedTotal() {
        return number_format($this->totalAmount, 0, ',', ' ') . ' сум';
    }
    
    /**
     * Возвращает текстовое описание статуса
     */
    public function getStatusText() {
        $statusTexts = [
            self::STATUS_PENDING => 'Ожидает подтверждения',
            self::STATUS_CONFIRMED => 'Подтвержден',
            self::STATUS_SHIPPED => 'Отправлен',
            self::STATUS_DELIVERED => 'Доставлен',
            self::STATUS_CANCELLED => 'Отменен'
        ];
        
        return $statusTexts[$this->status] ?? 'Неизвестный статус';
    }
    
    /**
     * Добавляет товар в заказ
     */
    public function addItem(OrderItem $item) {
        $this->items[] = $item;
        $this->recalculateTotal();
    }
    
    /**
     * Пересчитывает общую сумму заказа
     */
    public function recalculateTotal() {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getPrice() * $item->getQuantity();
        }
        $this->totalAmount = $total;
    }
    
    /**
     * Проверяет можно ли изменить статус
     */
    public function canChangeStatusTo($newStatus) {
        $allowedTransitions = [
            self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
            self::STATUS_SHIPPED => [self::STATUS_DELIVERED],
            self::STATUS_DELIVERED => [],
            self::STATUS_CANCELLED => []
        ];
        
        return in_array($newStatus, $allowedTransitions[$this->status] ?? []);
    }
    
    // Геттеры
    public function getId() { return $this->id; }
    public function getCustomerName() { return $this->customerName; }
    public function getCustomerEmail() { return $this->customerEmail; }
    public function getCustomerPhone() { return $this->customerPhone; }
    public function getTotalAmount() { return $this->totalAmount; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    public function getItems() { return $this->items; }
    
    // Сеттеры
    public function setId($id) { $this->id = $id; }
    public function setCustomerName($name) { $this->customerName = $name; }
    public function setCustomerEmail($email) { $this->customerEmail = $email; }
    public function setCustomerPhone($phone) { $this->customerPhone = $phone; }
    public function setTotalAmount($amount) { $this->totalAmount = (float)$amount; }
    public function setStatus($status) { $this->status = $status; }
    public function setItems($items) { $this->items = $items; }
}
?>