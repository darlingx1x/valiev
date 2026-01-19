<?php
/**
 * Репозиторий для работы с заказами
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';

class OrderRepository {
    private $dbService;
    
    public function __construct() {
        $this->dbService = new DatabaseService();
    }
    
    /**
     * Получает все заказы
     * @param int $limit Лимит записей
     * @param int $offset Смещение
     * @return array Массив объектов Order
     */
    public function getAll($limit = 50, $offset = 0) {
        $sql = "SELECT * FROM orders ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $results = $this->dbService->select($sql, [$limit, $offset]);
        
        $orders = array_map(function($row) {
            return new Order($row);
        }, $results);
        
        // Загружаем товары для каждого заказа
        foreach ($orders as $order) {
            $order->setItems($this->getOrderItems($order->getId()));
        }
        
        return $orders;
    }
    
    /**
     * Получает заказ по ID
     * @param int $id ID заказа
     * @return Order|null Объект заказа или null
     */
    public function getById($id) {
        $sql = "SELECT * FROM orders WHERE id = ?";
        $result = $this->dbService->selectOne($sql, [$id]);
        
        if (!$result) {
            return null;
        }
        
        $order = new Order($result);
        $order->setItems($this->getOrderItems($id));
        
        return $order;
    }
    
    /**
     * Получает заказы по статусу
     * @param string $status Статус заказа
     * @return array Массив объектов Order
     */
    public function getByStatus($status) {
        $sql = "SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC";
        $results = $this->dbService->select($sql, [$status]);
        
        return array_map(function($row) {
            $order = new Order($row);
            $order->setItems($this->getOrderItems($order->getId()));
            return $order;
        }, $results);
    }
    
    /**
     * Создает новый заказ
     * @param Order $order Объект заказа
     * @return int ID созданного заказа
     */
    public function create(Order $order) {
        $errors = $order->validate();
        if (!empty($errors)) {
            throw new Exception('Ошибки валидации: ' . implode(', ', $errors));
        }
        
        return $this->dbService->transaction(function($db) use ($order) {
            // Создаем заказ
            $sql = "INSERT INTO orders (customer_name, customer_email, customer_phone, total_amount, status) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $params = [
                $order->getCustomerName(),
                $order->getCustomerEmail(),
                $order->getCustomerPhone(),
                $order->getTotalAmount(),
                $order->getStatus()
            ];
            
            $orderId = $db->insert($sql, $params);
            $order->setId($orderId);
            
            // Добавляем товары в заказ
            foreach ($order->getItems() as $item) {
                $item->setOrderId($orderId);
                $this->createOrderItem($item);
                
                // Уменьшаем количество на складе
                $this->decreaseProductStock($item->getProductId(), $item->getQuantity());
            }
            
            return $orderId;
        });
    }
    
    /**
     * Обновляет заказ
     * @param Order $order Объект заказа
     * @return bool Успешность операции
     */
    public function update(Order $order) {
        if (!$order->getId()) {
            throw new Exception('ID заказа не указан');
        }
        
        $errors = $order->validate();
        if (!empty($errors)) {
            throw new Exception('Ошибки валидации: ' . implode(', ', $errors));
        }
        
        $sql = "UPDATE orders 
                SET customer_name = ?, customer_email = ?, customer_phone = ?, 
                    total_amount = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $params = [
            $order->getCustomerName(),
            $order->getCustomerEmail(),
            $order->getCustomerPhone(),
            $order->getTotalAmount(),
            $order->getStatus(),
            $order->getId()
        ];
        
        $affectedRows = $this->dbService->update($sql, $params);
        
        return $affectedRows > 0;
    }
    
    /**
     * Обновляет статус заказа
     * @param int $orderId ID заказа
     * @param string $newStatus Новый статус
     * @return bool Успешность операции
     */
    public function updateStatus($orderId, $newStatus) {
        // Получаем текущий заказ для проверки возможности смены статуса
        $order = $this->getById($orderId);
        if (!$order) {
            throw new Exception('Заказ не найден');
        }
        
        if (!$order->canChangeStatusTo($newStatus)) {
            throw new Exception('Нельзя изменить статус с "' . $order->getStatus() . '" на "' . $newStatus . '"');
        }
        
        $sql = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $affectedRows = $this->dbService->update($sql, [$newStatus, $orderId]);
        
        return $affectedRows > 0;
    }
    
    /**
     * Удаляет заказ
     * @param int $id ID заказа
     * @return bool Успешность операции
     */
    public function delete($id) {
        return $this->dbService->transaction(function($db) use ($id) {
            // Получаем заказ для восстановления склада
            $order = $this->getById($id);
            if (!$order) {
                throw new Exception('Заказ не найден');
            }
            
            // Проверяем можно ли удалить заказ
            if ($order->getStatus() === Order::STATUS_DELIVERED) {
                throw new Exception('Нельзя удалить доставленный заказ');
            }
            
            // Восстанавливаем количество на складе
            foreach ($order->getItems() as $item) {
                $this->increaseProductStock($item->getProductId(), $item->getQuantity());
            }
            
            // Удаляем товары заказа (каскадное удаление настроено в БД)
            $deleteItemsSql = "DELETE FROM order_items WHERE order_id = ?";
            $db->delete($deleteItemsSql, [$id]);
            
            // Удаляем заказ
            $deleteOrderSql = "DELETE FROM orders WHERE id = ?";
            $affectedRows = $db->delete($deleteOrderSql, [$id]);
            
            return $affectedRows > 0;
        });
    }
    
    /**
     * Получает товары заказа
     * @param int $orderId ID заказа
     * @return array Массив объектов OrderItem
     */
    private function getOrderItems($orderId) {
        $sql = "SELECT oi.*, p.name as product_name, p.image_url as product_image 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
        
        $results = $this->dbService->select($sql, [$orderId]);
        
        return array_map(function($row) {
            $item = new OrderItem($row);
            
            // Создаем упрощенный объект товара для отображения
            if ($row['product_name']) {
                $productData = [
                    'id' => $row['product_id'],
                    'name' => $row['product_name'],
                    'image_url' => $row['product_image']
                ];
                $item->setProduct(new Product($productData));
            }
            
            return $item;
        }, $results);
    }
    
    /**
     * Создает товар в заказе
     * @param OrderItem $item Объект товара в заказе
     * @return int ID созданной записи
     */
    private function createOrderItem(OrderItem $item) {
        $errors = $item->validate();
        if (!empty($errors)) {
            throw new Exception('Ошибки валидации товара в заказе: ' . implode(', ', $errors));
        }
        
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $params = [$item->getOrderId(), $item->getProductId(), $item->getQuantity(), $item->getPrice()];
        
        return $this->dbService->insert($sql, $params);
    }
    
    /**
     * Уменьшает количество товара на складе
     * @param int $productId ID товара
     * @param int $quantity Количество
     */
    private function decreaseProductStock($productId, $quantity) {
        $sql = "UPDATE products 
                SET stock_quantity = stock_quantity - ? 
                WHERE id = ? AND stock_quantity >= ?";
        
        $affectedRows = $this->dbService->update($sql, [$quantity, $productId, $quantity]);
        
        if ($affectedRows === 0) {
            throw new Exception("Недостаточно товара на складе (ID: $productId)");
        }
    }
    
    /**
     * Увеличивает количество товара на складе
     * @param int $productId ID товара
     * @param int $quantity Количество
     */
    private function increaseProductStock($productId, $quantity) {
        $sql = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?";
        $this->dbService->update($sql, [$quantity, $productId]);
    }
    
    /**
     * Получает статистику заказов
     * @return array Статистика заказов
     */
    public function getOrderStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as average_order_value
                FROM orders";
        
        return $this->dbService->selectOne($sql);
    }
    
    /**
     * Получает количество заказов
     * @return int Общее количество заказов
     */
    public function getCount() {
        $sql = "SELECT COUNT(*) as count FROM orders";
        $result = $this->dbService->selectOne($sql);
        
        return (int)$result['count'];
    }
}
?>