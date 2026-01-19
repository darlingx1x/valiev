<?php
/**
 * Контроллер для работы с заказами
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once __DIR__ . '/../services/OrderService.php';

class OrderController {
    private $orderService;
    
    public function __construct() {
        $this->orderService = new OrderService();
    }
    
    /**
     * API: Создает заказ из корзины
     */
    public function createOrder() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['customer_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указано имя покупателя']);
                return;
            }
            
            $customerData = [
                'name' => $input['customer_name'],
                'email' => $input['customer_email'] ?? '',
                'phone' => $input['customer_phone'] ?? ''
            ];
            
            $orderId = $this->orderService->createOrderFromCart($customerData);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'order_id' => $orderId,
                'message' => 'Заказ успешно создан'
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * API: Получает заказы (для администратора)
     */
    public function getOrders() {
        try {
            // Проверяем права администратора
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Доступ запрещен']);
                return;
            }
            
            $status = $_GET['status'] ?? null;
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            if ($status) {
                $orders = $this->orderService->getOrdersByStatus($status);
            } else {
                $orders = $this->orderService->getAllOrders($limit, $offset);
            }
            
            $result = array_map(function($order) {
                return $order->toArray();
            }, $orders);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * API: Обновляет статус заказа (для администратора)
     */
    public function updateOrderStatus() {
        try {
            // Проверяем права администратора
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Доступ запрещен']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['order_id']) || !isset($input['status'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указаны обязательные параметры']);
                return;
            }
            
            $orderId = (int)$input['order_id'];
            $newStatus = $input['status'];
            
            $result = $this->orderService->updateOrderStatus($orderId, $newStatus);
            
            if ($result) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['message' => 'Статус заказа обновлен'], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Не удалось обновить статус']);
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Упрощенная проверка прав администратора
     */
    private function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
}
?>