<?php
/**
 * Контроллер для работы с корзиной
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once __DIR__ . '/../services/CartService.php';

class CartController {
    private $cartService;
    
    public function __construct() {
        $this->cartService = new CartService();
    }
    
    /**
     * API: Добавляет товар в корзину
     */
    public function addToCart() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['product_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID товара']);
                return;
            }
            
            $productId = (int)$input['product_id'];
            $quantity = (int)($input['quantity'] ?? 1);
            
            $this->cartService->addToCart($productId, $quantity);
            
            $cartData = $this->cartService->getCartData();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'message' => 'Товар добавлен в корзину',
                'cart' => $cartData
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * API: Получает содержимое корзины
     */
    public function getCart() {
        try {
            $cartData = $this->cartService->getCartData();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($cartData, JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * API: Обновляет количество товара в корзине
     */
    public function updateQuantity() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['product_id']) || !isset($input['quantity'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указаны обязательные параметры']);
                return;
            }
            
            $productId = (int)$input['product_id'];
            $quantity = (int)$input['quantity'];
            
            $this->cartService->updateQuantity($productId, $quantity);
            
            $cartData = $this->cartService->getCartData();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'message' => 'Количество обновлено',
                'cart' => $cartData
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * API: Удаляет товар из корзины
     */
    public function removeFromCart() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['product_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Не указан ID товара']);
                return;
            }
            
            $productId = (int)$input['product_id'];
            
            $this->cartService->removeFromCart($productId);
            
            $cartData = $this->cartService->getCartData();
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'message' => 'Товар удален из корзины',
                'cart' => $cartData
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }
}
?>