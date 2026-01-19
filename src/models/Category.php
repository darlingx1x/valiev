<?php
/**
 * Модель категории товаров
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

class Category {
    private $id;
    private $name;
    private $description;
    private $createdAt;
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->fillFromArray($data);
        }
    }
    
    public function fillFromArray($data) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->createdAt = $data['created_at'] ?? null;
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->createdAt
        ];
    }
    
    public function validate() {
        $errors = [];
        
        if (empty($this->name)) {
            $errors[] = 'Название категории обязательно';
        }
        
        if (strlen($this->name) > 100) {
            $errors[] = 'Название категории не должно превышать 100 символов';
        }
        
        return $errors;
    }
    
    // Геттеры
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getDescription() { return $this->description; }
    public function getCreatedAt() { return $this->createdAt; }
    
    // Сеттеры
    public function setId($id) { $this->id = $id; }
    public function setName($name) { $this->name = $name; }
    public function setDescription($description) { $this->description = $description; }
}
?>