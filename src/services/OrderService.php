<?php
/**
 * Сервис для управления заказами
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../services/CartService.php';
require_once __DIR__ . '/../repositories/OrderRepository.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';

class OrderService {
    private $dbService;
    private $cartService;
    private $orderRepo;
    
    public function __construct() {
        $this->dbService = new DatabaseService();
        $this->cartService = new CartService();
        $this->orderRepo = new OrderRepository();
    }
    
    /**
     * Создает заказ из корзины
     * @param array $customerData Данные покупателя
     * @return int ID созданного заказа
     */
    public function createOrderFromCart($customerData) {
        // Валидируем данные покупателя
        $this->validateCustomerData($customerData);
        
        // Получаем товары из корзины
        $cartItems = $this->cartService->getCartItems();
        
        if (empty($cartItems)) {
            throw new Exception('Корзина пуста');
        }
        
        // Проверяем доступность товаров
        $unavailableItems = $this->cartService->validateCartAvailability();
        if (!empty($unavailableItems)) {
            throw new Exception('Некоторые товары недоступны в нужном количестве');
        }
        
        // Создаем заказ в транзакции
        return $this->dbService->transaction(function($db) use ($customerData, $cartItems) {
            // Создаем объект заказа
            $order = new Order([
                'customer_name' => $customerData['name'],
                'customer_email' => $customerData['email'] ?? '',
                'customer_phone' => $customerData['phone'] ?? '',
                'total_amount' => $this->cartService->getCartTotal(),
                'status' => Order::STATUS_PENDING
            ]);
            
            // Добавляем товары в заказ
            foreach ($cartItems as $cartItem) {
                $orderItem = new OrderItem([
                    'product_id' => $cartItem->getProductId(),
                    'quantity' => $cartItem->getQuantity(),
                    'price' => $cartItem->getProduct()->getPrice()
                ]);
                
                $order->addItem($orderItem);
            }
            
            // Сохраняем заказ
            $orderId = $this->orderRepo->create($order);
            
            // Очищаем корзину после успешного создания заказа
            $this->cartService->clearCart();
            
            return $orderId;
        });
    }
    
    /**
     * Обновляет статус заказа
     * @param int $orderId ID заказа
     * @param string $newStatus Новый статус
     * @return bool Успешность операции
     */
    public function updateOrderStatus($orderId, $newStatus) {
        return $this->orderRepo->updateStatus($orderId, $newStatus);
    }
    
    /**
     * Получает заказ по ID
     * @param int $orderId ID заказа
     * @return Order|null Объект заказа
     */
    public function getOrder($orderId) {
        return $this->orderRepo->getById($orderId);
    }
    
    /**
     * Получает все заказы
     * @param int $limit Лимит записей
     * @param int $offset Смещение
     * @return array Массив заказов
     */
    public function getAllOrders($limit = 50, $offset = 0) {
        return $this->orderRepo->getAll($limit, $offset);
    }
    
    /**
     * Получает заказы по статусу
     * @param string $status Статус заказа
     * @return array Массив заказов
     */
    public function getOrdersByStatus($status) {
        return $this->orderRepo->getByStatus($status);
    }
    
    /**
     * Валидирует данные покупателя
     * @param array $customerData Данные покупателя
     */
    private function validateCustomerData($customerData) {
        if (empty($customerData['name'])) {
            throw new Exception('Имя покупателя обязательно');
        }
        
        if (strlen($customerData['name']) > 255) {
            throw new Exception('Имя покупателя слишком длинное');
        }
        
        if (!empty($customerData['email']) && !filter_var($customerData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Некорректный email адрес');
        }
        
        if (!empty($customerData['phone']) && !$this->isValidUzbekistanPhone($customerData['phone'])) {
            throw new Exception('Некорректный номер телефона');
        }
    }
    
    /**
     * Проверяет корректность номера телефона Узбекистана
     */
    private function isValidUzbekistanPhone($phone) {
        $pattern = '/^(\+?998)?[0-9]{9}$/';
        return preg_match($pattern, preg_replace('/[\s\-\(\)]/', '', $phone));
    }
}
?>