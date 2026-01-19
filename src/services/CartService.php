<?php
/**
 * Сервис для управления корзиной покупок
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../models/CartItem.php';
require_once __DIR__ . '/../repositories/ProductRepository.php';

class CartService {
    private $dbService;
    private $productRepo;
    
    public function __construct() {
        $this->dbService = new DatabaseService();
        $this->productRepo = new ProductRepository();
        
        // Запускаем сессию если не запущена
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Добавляет товар в корзину
     * @param int $productId ID товара
     * @param int $quantity Количество
     * @return bool Успешность операции
     */
    public function addToCart($productId, $quantity = 1) {
        if ($quantity <= 0) {
            throw new Exception('Количество должно быть положительным');
        }
        
        // Проверяем существование товара
        $product = $this->productRepo->getById($productId);
        if (!$product) {
            throw new Exception('Товар не найден');
        }
        
        // Проверяем наличие на складе
        if (!$product->isAvailable($quantity)) {
            throw new Exception('Недостаточно товара на складе');
        }
        
        $sessionId = $this->getSessionId();
        
        // Проверяем есть ли товар уже в корзине
        $existingItem = $this->getCartItem($sessionId, $productId);
        
        if ($existingItem) {
            // Обновляем количество
            $newQuantity = $existingItem->getQuantity() + $quantity;
            
            // Проверяем доступность нового количества
            if (!$product->isAvailable($newQuantity)) {
                throw new Exception('Недостаточно товара на складе для добавления');
            }
            
            return $this->updateCartItemQuantity($sessionId, $productId, $newQuantity);
        } else {
            // Создаем новый элемент корзины
            $cartItem = new CartItem([
                'session_id' => $sessionId,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
            
            return $this->createCartItem($cartItem);
        }
    }
    
    /**
     * Обновляет количество товара в корзине
     * @param int $productId ID товара
     * @param int $quantity Новое количество
     * @return bool Успешность операции
     */
    public function updateQuantity($productId, $quantity) {
        if ($quantity < 0) {
            throw new Exception('Количество не может быть отрицательным');
        }
        
        $sessionId = $this->getSessionId();
        
        if ($quantity === 0) {
            return $this->removeFromCart($productId);
        }
        
        // Проверяем доступность товара
        $product = $this->productRepo->getById($productId);
        if (!$product || !$product->isAvailable($quantity)) {
            throw new Exception('Недостаточно товара на складе');
        }
        
        return $this->updateCartItemQuantity($sessionId, $productId, $quantity);
    }
    
    /**
     * Удаляет товар из корзины
     * @param int $productId ID товара
     * @return bool Успешность операции
     */
    public function removeFromCart($productId) {
        $sessionId = $this->getSessionId();
        
        $sql = "DELETE FROM cart_items WHERE session_id = ? AND product_id = ?";
        $affectedRows = $this->dbService->delete($sql, [$sessionId, $productId]);
        
        return $affectedRows > 0;
    }
    
    /**
     * Получает содержимое корзины
     * @return array Массив объектов CartItem с товарами
     */
    public function getCartItems() {
        $sessionId = $this->getSessionId();
        
        $sql = "SELECT ci.*, p.name, p.description, p.price, p.image_url, p.stock_quantity
                FROM cart_items ci 
                JOIN products p ON ci.product_id = p.id 
                WHERE ci.session_id = ? 
                ORDER BY ci.created_at DESC";
        
        $results = $this->dbService->select($sql, [$sessionId]);
        
        $cartItems = [];
        foreach ($results as $row) {
            $cartItem = new CartItem($row);
            
            // Создаем объект товара
            $product = new Product([
                'id' => $row['product_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'price' => $row['price'],
                'image_url' => $row['image_url'],
                'stock_quantity' => $row['stock_quantity']
            ]);
            
            $cartItem->setProduct($product);
            $cartItems[] = $cartItem;
        }
        
        return $cartItems;
    }
    
    /**
     * Вычисляет общую стоимость корзины в узбекских сумах
     * @return float Общая стоимость
     */
    public function getCartTotal() {
        $cartItems = $this->getCartItems();
        $total = 0;
        
        foreach ($cartItems as $item) {
            $total += $item->getSubtotal();
        }
        
        return $total;
    }
    
    /**
     * Форматирует общую стоимость корзины
     * @return string Отформатированная стоимость
     */
    public function getFormattedCartTotal() {
        $total = $this->getCartTotal();
        return number_format($total, 0, ',', ' ') . ' сум';
    }
    
    /**
     * Получает количество товаров в корзине
     * @return int Количество уникальных товаров
     */
    public function getCartItemsCount() {
        $sessionId = $this->getSessionId();
        
        $sql = "SELECT COUNT(*) as count FROM cart_items WHERE session_id = ?";
        $result = $this->dbService->selectOne($sql, [$sessionId]);
        
        return (int)$result['count'];
    }
    
    /**
     * Получает общее количество единиц товаров в корзине
     * @return int Общее количество
     */
    public function getTotalQuantity() {
        $sessionId = $this->getSessionId();
        
        $sql = "SELECT SUM(quantity) as total FROM cart_items WHERE session_id = ?";
        $result = $this->dbService->selectOne($sql, [$sessionId]);
        
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Очищает корзину
     * @return bool Успешность операции
     */
    public function clearCart() {
        $sessionId = $this->getSessionId();
        
        $sql = "DELETE FROM cart_items WHERE session_id = ?";
        $this->dbService->delete($sql, [$sessionId]);
        
        return true;
    }
    
    /**
     * Проверяет доступность всех товаров в корзине
     * @return array Массив недоступных товаров
     */
    public function validateCartAvailability() {
        $cartItems = $this->getCartItems();
        $unavailableItems = [];
        
        foreach ($cartItems as $item) {
            if (!$item->isAvailable()) {
                $unavailableItems[] = [
                    'product_id' => $item->getProductId(),
                    'product_name' => $item->getProduct()->getName(),
                    'requested_quantity' => $item->getQuantity(),
                    'available_quantity' => $item->getProduct()->getStockQuantity()
                ];
            }
        }
        
        return $unavailableItems;
    }
    
    /**
     * Получает элемент корзины
     * @param string $sessionId ID сессии
     * @param int $productId ID товара
     * @return CartItem|null
     */
    private function getCartItem($sessionId, $productId) {
        $sql = "SELECT * FROM cart_items WHERE session_id = ? AND product_id = ?";
        $result = $this->dbService->selectOne($sql, [$sessionId, $productId]);
        
        return $result ? new CartItem($result) : null;
    }
    
    /**
     * Создает элемент корзины
     * @param CartItem $cartItem Объект элемента корзины
     * @return bool Успешность операции
     */
    private function createCartItem(CartItem $cartItem) {
        $errors = $cartItem->validate();
        if (!empty($errors)) {
            throw new Exception('Ошибки валидации: ' . implode(', ', $errors));
        }
        
        $sql = "INSERT INTO cart_items (session_id, product_id, quantity) VALUES (?, ?, ?)";
        $params = [
            $cartItem->getSessionId(),
            $cartItem->getProductId(),
            $cartItem->getQuantity()
        ];
        
        $id = $this->dbService->insert($sql, $params);
        return $id > 0;
    }
    
    /**
     * Обновляет количество элемента корзины
     * @param string $sessionId ID сессии
     * @param int $productId ID товара
     * @param int $quantity Новое количество
     * @return bool Успешность операции
     */
    private function updateCartItemQuantity($sessionId, $productId, $quantity) {
        $sql = "UPDATE cart_items SET quantity = ? WHERE session_id = ? AND product_id = ?";
        $affectedRows = $this->dbService->update($sql, [$quantity, $sessionId, $productId]);
        
        return $affectedRows > 0;
    }
    
    /**
     * Получает ID сессии
     * @return string ID сессии
     */
    private function getSessionId() {
        return session_id();
    }
    
    /**
     * Преобразует корзину в массив для JSON
     * @return array Данные корзины
     */
    public function getCartData() {
        $items = $this->getCartItems();
        
        return [
            'items' => array_map(function($item) {
                return $item->toArray();
            }, $items),
            'total_items' => $this->getCartItemsCount(),
            'total_quantity' => $this->getTotalQuantity(),
            'total_amount' => $this->getCartTotal(),
            'formatted_total' => $this->getFormattedCartTotal()
        ];
    }
}
?>