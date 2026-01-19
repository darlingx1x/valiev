<?php
/**
 * Интернет-магазин спортивного питания
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once '../src/config/database.php';
require_once '../src/controllers/ProductController.php';
require_once '../src/controllers/CartController.php';
require_once '../src/controllers/OrderController.php';

// Простой роутер
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

switch ($path) {
    case '/':
    case '/index.php':
        $controller = new ProductController();
        $controller->index();
        break;
    
    case '/api/products':
        $controller = new ProductController();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller->getProducts();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->createProduct();
        }
        break;
    
    case '/api/cart/add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new CartController();
            $controller->addToCart();
        }
        break;
    
    case '/api/cart':
        $controller = new CartController();
        $controller->getCart();
        break;
    
    case '/api/orders':
        $controller = new OrderController();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller->getOrders();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->createOrder();
        }
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Страница не найдена']);
        break;
}
?>