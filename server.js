/**
 * Простой веб-сервер для демонстрации интернет-магазина спортивного питания
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');
const DatabaseSimulator = require('./database-simulator');

// Инициализируем симулятор базы данных
const db = new DatabaseSimulator();

// Функция форматирования цены
function formatPrice(price) {
    return new Intl.NumberFormat('uz-UZ').format(price) + ' сум';
}

// MIME типы
const mimeTypes = {
    '.html': 'text/html',
    '.css': 'text/css',
    '.js': 'application/javascript',
    '.json': 'application/json',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.png': 'image/png',
    '.gif': 'image/gif',
    '.svg': 'image/svg+xml'
};

const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;
    const query = parsedUrl.query;

    // CORS headers
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
        res.writeHead(200);
        res.end();
        return;
    }

    console.log(`${req.method} ${pathname}`);

    // API Routes
    if (pathname.startsWith('/api/')) {
        handleApiRequest(req, res, pathname, query);
        return;
    }

    // Static files
    if (pathname.startsWith('/assets/')) {
        serveStaticFile(req, res, pathname);
        return;
    }

    // Database viewer
    if (pathname === '/database' || pathname === '/db') {
        serveFile(req, res, 'database-viewer.html');
        return;
    }

    // Direct access to data files
    if (pathname.startsWith('/data/')) {
        const fileName = pathname.substring(6); // Remove '/data/'
        const filePath = path.join(__dirname, 'data', fileName);
        
        if (fs.existsSync(filePath) && fileName.endsWith('.json')) {
            fs.readFile(filePath, 'utf8', (err, data) => {
                if (err) {
                    res.writeHead(404);
                    res.end('File not found');
                } else {
                    res.writeHead(200, { 
                        'Content-Type': 'application/json; charset=utf-8',
                        'Access-Control-Allow-Origin': '*'
                    });
                    res.end(data);
                }
            });
        } else {
            res.writeHead(404);
            res.end('File not found');
        }
        return;
    }

    // Admin panel
    if (pathname === '/admin' || pathname === '/admin.html') {
        serveFile(req, res, 'admin-panel.html');
        return;
    }

    // Main page
    if (pathname === '/' || pathname === '/index.php') {
        serveMainPage(req, res);
        return;
    }

    // 404
    res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end('<h1>404 - Страница не найдена</h1>');
});

function handleApiRequest(req, res, pathname, query) {
    res.setHeader('Content-Type', 'application/json; charset=utf-8');

    if (pathname === '/api/products') {
        if (req.method === 'GET') {
            try {
                let conditions = {};
                let options = {};
                
                // Фильтр по категории
                if (query.category) {
                    conditions.category_id = parseInt(query.category);
                }
                
                // Поиск
                if (query.search) {
                    // Имитируем FULLTEXT поиск
                    const searchTerm = query.search.toLowerCase();
                    const allProducts = db.select('products');
                    const filteredProducts = allProducts.filter(p => 
                        p.name.toLowerCase().includes(searchTerm) || 
                        p.description.toLowerCase().includes(searchTerm)
                    );
                    
                    // Добавляем форматированную цену
                    const products = filteredProducts.map(p => ({
                        ...p,
                        formatted_price: formatPrice(p.price)
                    }));
                    
                    res.writeHead(200);
                    res.end(JSON.stringify(products));
                    return;
                }
                
                let products = db.select('products', conditions, options);
                
                // Добавляем форматированную цену
                products = products.map(p => ({
                    ...p,
                    formatted_price: formatPrice(p.price)
                }));
                
                res.writeHead(200);
                res.end(JSON.stringify(products));
                
            } catch (error) {
                res.writeHead(500);
                res.end(JSON.stringify({ error: 'Ошибка базы данных: ' + error.message }));
            }
        }
        else if (req.method === 'POST') {
            // Добавление нового товара
            let body = '';
            req.on('data', chunk => body += chunk);
            req.on('end', () => {
                try {
                    const data = JSON.parse(body);
                    const newProduct = db.insert('products', data);
                    
                    res.writeHead(201);
                    res.end(JSON.stringify({
                        message: 'Товар добавлен',
                        product: {
                            ...newProduct,
                            formatted_price: formatPrice(newProduct.price)
                        }
                    }));
                } catch (error) {
                    res.writeHead(400);
                    res.end(JSON.stringify({ error: 'Ошибка добавления товара: ' + error.message }));
                }
            });
        }
        else if (req.method === 'PUT') {
            // Обновление товара
            const productId = parseInt(query.id);
            let body = '';
            req.on('data', chunk => body += chunk);
            req.on('end', () => {
                try {
                    const data = JSON.parse(body);
                    const updatedProduct = db.update('products', productId, data);
                    
                    res.writeHead(200);
                    res.end(JSON.stringify({
                        message: 'Товар обновлен',
                        product: {
                            ...updatedProduct,
                            formatted_price: formatPrice(updatedProduct.price)
                        }
                    }));
                } catch (error) {
                    res.writeHead(400);
                    res.end(JSON.stringify({ error: 'Ошибка обновления товара: ' + error.message }));
                }
            });
        }
        else if (req.method === 'DELETE') {
            // Удаление товара
            const productId = parseInt(query.id);
            try {
                const deletedProduct = db.delete('products', productId);
                
                res.writeHead(200);
                res.end(JSON.stringify({
                    message: 'Товар удален',
                    product: deletedProduct
                }));
            } catch (error) {
                res.writeHead(400);
                res.end(JSON.stringify({ error: 'Ошибка удаления товара: ' + error.message }));
            }
        }
    }
    else if (pathname === '/api/cart') {
        if (req.method === 'GET') {
            try {
                const sessionId = 'demo-session';
                const cartItems = db.select('cart_items', { session_id: sessionId });
                
                const items = cartItems.map(item => {
                    const products = db.select('products', { id: item.product_id });
                    const product = products[0];
                    
                    return {
                        ...item,
                        product: {
                            ...product,
                            formatted_price: formatPrice(product.price)
                        }
                    };
                });
                
                const totalItems = items.length;
                const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);
                const totalAmount = items.reduce((sum, item) => sum + (item.product.price * item.quantity), 0);
                
                res.writeHead(200);
                res.end(JSON.stringify({
                    items,
                    total_items: totalItems,
                    total_quantity: totalQuantity,
                    total_amount: totalAmount,
                    formatted_total: formatPrice(totalAmount)
                }));
                
            } catch (error) {
                res.writeHead(500);
                res.end(JSON.stringify({ error: 'Ошибка загрузки корзины: ' + error.message }));
            }
        }
    }
    else if (pathname === '/api/cart/add') {
        if (req.method === 'POST') {
            let body = '';
            req.on('data', chunk => body += chunk);
            req.on('end', () => {
                try {
                    const data = JSON.parse(body);
                    const sessionId = 'demo-session';
                    
                    // Проверяем существование товара
                    const products = db.select('products', { id: data.product_id });
                    if (products.length === 0) {
                        res.writeHead(404);
                        res.end(JSON.stringify({ error: 'Товар не найден' }));
                        return;
                    }
                    
                    const product = products[0];
                    
                    // Проверяем наличие на складе
                    if (product.stock_quantity < (data.quantity || 1)) {
                        res.writeHead(400);
                        res.end(JSON.stringify({ error: 'Недостаточно товара на складе' }));
                        return;
                    }
                    
                    // Проверяем, есть ли уже этот товар в корзине
                    const existingItems = db.select('cart_items', { 
                        session_id: sessionId, 
                        product_id: data.product_id 
                    });
                    
                    if (existingItems.length > 0) {
                        // Обновляем количество
                        const existingItem = existingItems[0];
                        const newQuantity = existingItem.quantity + (data.quantity || 1);
                        
                        if (newQuantity > product.stock_quantity) {
                            res.writeHead(400);
                            res.end(JSON.stringify({ error: 'Недостаточно товара на складе' }));
                            return;
                        }
                        
                        db.update('cart_items', existingItem.id, { quantity: newQuantity });
                    } else {
                        // Добавляем новый товар в корзину
                        db.insert('cart_items', {
                            session_id: sessionId,
                            product_id: data.product_id,
                            quantity: data.quantity || 1
                        });
                    }
                    
                    // Возвращаем обновленную корзину
                    const cartItems = db.select('cart_items', { session_id: sessionId });
                    const items = cartItems.map(item => {
                        const products = db.select('products', { id: item.product_id });
                        const product = products[0];
                        
                        return {
                            ...item,
                            product: {
                                ...product,
                                formatted_price: formatPrice(product.price)
                            }
                        };
                    });
                    
                    const totalItems = items.length;
                    const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);
                    const totalAmount = items.reduce((sum, item) => sum + (item.product.price * item.quantity), 0);
                    
                    res.writeHead(200);
                    res.end(JSON.stringify({
                        message: 'Товар добавлен в корзину',
                        cart: {
                            items,
                            total_items: totalItems,
                            total_quantity: totalQuantity,
                            total_amount: totalAmount,
                            formatted_total: formatPrice(totalAmount)
                        }
                    }));
                    
                } catch (error) {
                    console.error('Ошибка добавления в корзину:', error);
                    res.writeHead(400);
                    res.end(JSON.stringify({ error: 'Некорректные данные: ' + error.message }));
                }
            });
        }
    }
    else if (pathname === '/api/cart/update') {
        if (req.method === 'POST') {
            let body = '';
            req.on('data', chunk => body += chunk);
            req.on('end', () => {
                try {
                    const data = JSON.parse(body);
                    const sessionId = 'demo-session';
                    
                    // Находим товар в корзине
                    const existingItems = db.select('cart_items', { 
                        session_id: sessionId, 
                        product_id: data.product_id 
                    });
                    
                    if (existingItems.length === 0) {
                        res.writeHead(400);
                        res.end(JSON.stringify({ error: 'Товар не найден в корзине' }));
                        return;
                    }
                    
                    const existingItem = existingItems[0];
                    
                    // Проверяем наличие на складе
                    const products = db.select('products', { id: data.product_id });
                    const product = products[0];
                    
                    if (data.quantity > product.stock_quantity) {
                        res.writeHead(400);
                        res.end(JSON.stringify({ error: 'Недостаточно товара на складе' }));
                        return;
                    }
                    
                    // Обновляем количество
                    db.update('cart_items', existingItem.id, { quantity: data.quantity });
                    
                    // Возвращаем обновленную корзину
                    const cartItems = db.select('cart_items', { session_id: sessionId });
                    const items = cartItems.map(item => {
                        const products = db.select('products', { id: item.product_id });
                        const product = products[0];
                        
                        return {
                            ...item,
                            product: {
                                ...product,
                                formatted_price: formatPrice(product.price)
                            }
                        };
                    });
                    
                    const totalItems = items.length;
                    const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);
                    const totalAmount = items.reduce((sum, item) => sum + (item.product.price * item.quantity), 0);
                    
                    res.writeHead(200);
                    res.end(JSON.stringify({
                        message: 'Корзина обновлена',
                        cart: {
                            items,
                            total_items: totalItems,
                            total_quantity: totalQuantity,
                            total_amount: totalAmount,
                            formatted_total: formatPrice(totalAmount)
                        }
                    }));
                } catch (error) {
                    console.error('Ошибка обновления корзины:', error);
                    res.writeHead(400);
                    res.end(JSON.stringify({ error: 'Ошибка обновления корзины: ' + error.message }));
                }
            });
        }
    }
    else if (pathname === '/api/cart/remove') {
        if (req.method === 'POST') {
            let body = '';
            req.on('data', chunk => body += chunk);
            req.on('end', () => {
                try {
                    const data = JSON.parse(body);
                    const sessionId = 'demo-session';
                    
                    // Находим товар в корзине
                    const existingItems = db.select('cart_items', { 
                        session_id: sessionId, 
                        product_id: data.product_id 
                    });
                    
                    if (existingItems.length === 0) {
                        res.writeHead(400);
                        res.end(JSON.stringify({ error: 'Товар не найден в корзине' }));
                        return;
                    }
                    
                    const existingItem = existingItems[0];
                    
                    // Удаляем товар из корзины
                    db.delete('cart_items', existingItem.id);
                    
                    // Возвращаем обновленную корзину
                    const cartItems = db.select('cart_items', { session_id: sessionId });
                    const items = cartItems.map(item => {
                        const products = db.select('products', { id: item.product_id });
                        const product = products[0];
                        
                        return {
                            ...item,
                            product: {
                                ...product,
                                formatted_price: formatPrice(product.price)
                            }
                        };
                    });
                    
                    const totalItems = items.length;
                    const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);
                    const totalAmount = items.reduce((sum, item) => sum + (item.product.price * item.quantity), 0);
                    
                    res.writeHead(200);
                    res.end(JSON.stringify({
                        message: 'Товар удален из корзины',
                        cart: {
                            items,
                            total_items: totalItems,
                            total_quantity: totalQuantity,
                            total_amount: totalAmount,
                            formatted_total: formatPrice(totalAmount)
                        }
                    }));
                } catch (error) {
                    console.error('Ошибка удаления из корзины:', error);
                    res.writeHead(400);
                    res.end(JSON.stringify({ error: 'Ошибка удаления из корзины: ' + error.message }));
                }
            });
        }
    }

    else if (pathname === '/api/stats') {
        if (req.method === 'GET') {
            try {
                const dbStats = db.getStats();
                const products = db.select('products');
                const orders = db.select('orders');
                const cartItems = db.select('cart_items', { session_id: 'demo-session' });
                
                const stats = {
                    total_products: products.length,
                    total_categories: db.select('categories').length,
                    total_orders: orders.length,
                    total_revenue: orders.reduce((sum, order) => sum + order.total_amount, 0),
                    cart_items: cartItems.length,
                    low_stock_products: products.filter(p => p.stock_quantity < 20).length,
                    recent_orders: orders.slice(-5).reverse(),
                    database_stats: dbStats
                };
                
                res.writeHead(200);
                res.end(JSON.stringify(stats));
                
            } catch (error) {
                res.writeHead(500);
                res.end(JSON.stringify({ error: 'Ошибка получения статистики: ' + error.message }));
            }
        }
    }
    else if (pathname === '/api/categories') {
        if (req.method === 'GET') {
            try {
                const categories = db.select('categories');
                const products = db.select('products');
                
                // Добавляем количество товаров в каждой категории
                const categoriesWithCount = categories.map(category => ({
                    ...category,
                    product_count: products.filter(p => p.category_id === category.id).length
                }));
                
                res.writeHead(200);
                res.end(JSON.stringify(categoriesWithCount));
                
            } catch (error) {
                res.writeHead(500);
                res.end(JSON.stringify({ error: 'Ошибка загрузки категорий: ' + error.message }));
            }
        }
        else if (req.method === 'POST') {
            let body = '';
            req.on('data', chunk => body += chunk);
            req.on('end', () => {
                try {
                    const data = JSON.parse(body);
                    const newCategory = db.insert('categories', data);
                    
                    res.writeHead(201);
                    res.end(JSON.stringify({
                        message: 'Категория добавлена',
                        category: newCategory
                    }));
                } catch (error) {
                    res.writeHead(400);
                    res.end(JSON.stringify({ error: 'Ошибка добавления категории: ' + error.message }));
                }
            });
        }
    }
    else if (pathname === '/api/orders') {
        if (req.method === 'GET') {
            try {
                const orders = db.select('orders', {}, { orderBy: 'created_at DESC' });
                
                // Добавляем детали заказов
                const ordersWithDetails = orders.map(order => {
                    const orderItems = db.select('order_items', { order_id: order.id });
                    return {
                        ...order,
                        items: orderItems,
                        formatted_total: formatPrice(order.total_amount)
                    };
                });
                
                res.writeHead(200);
                res.end(JSON.stringify(ordersWithDetails));
                
            } catch (error) {
                console.error('Ошибка загрузки заказов:', error);
                res.writeHead(500);
                res.end(JSON.stringify({ error: 'Ошибка загрузки заказов: ' + error.message }));
            }
        }
        else if (req.method === 'POST') {
            let body = '';
            req.on('data', chunk => body += chunk);
            req.on('end', () => {
                try {
                    console.log('Получен запрос на создание заказа');
                    const data = JSON.parse(body);
                    console.log('Данные заказа:', data);
                    
                    const sessionId = 'demo-session';
                    const cartItems = db.select('cart_items', { session_id: sessionId });
                    console.log('Товары в корзине:', cartItems.length);
                    
                    if (cartItems.length === 0) {
                        console.log('Корзина пуста!');
                        res.writeHead(400);
                        res.end(JSON.stringify({ error: 'Корзина пуста' }));
                        return;
                    }
                    
                    // Используем транзакцию для создания заказа
                    const operations = [];
                    
                    // Рассчитываем общую сумму
                    let totalAmount = 0;
                    const orderItemsData = [];
                    
                    cartItems.forEach(item => {
                        const products = db.select('products', { id: item.product_id });
                        const product = products[0];
                        const itemTotal = product.price * item.quantity;
                        totalAmount += itemTotal;
                        
                        orderItemsData.push({
                            product_id: item.product_id,
                            quantity: item.quantity,
                            price: product.price,
                            product_name: product.name
                        });
                    });
                    
                    console.log('Общая сумма заказа:', totalAmount);
                    
                    // Создаем заказ
                    const orderData = {
                        customer_name: data.customer_name,
                        customer_email: data.customer_email || '',
                        customer_phone: data.customer_phone || '',
                        total_amount: totalAmount,
                        status: 'pending'
                    };
                    
                    console.log('Создаем заказ с данными:', orderData);
                    
                    operations.push({
                        type: 'INSERT',
                        table: 'orders',
                        data: orderData
                    });
                    
                    const results = db.transaction(operations);
                    const newOrder = results[0];
                    
                    console.log('Заказ создан с ID:', newOrder.id);
                    
                    // Добавляем товары заказа
                    orderItemsData.forEach(itemData => {
                        db.insert('order_items', {
                            ...itemData,
                            order_id: newOrder.id
                        });
                    });
                    
                    console.log('Товары заказа добавлены');
                    
                    // Очищаем корзину
                    cartItems.forEach(item => {
                        db.delete('cart_items', item.id);
                    });
                    
                    console.log('Корзина очищена');
                    
                    res.writeHead(200);
                    res.end(JSON.stringify({
                        order_id: newOrder.id,
                        message: 'Заказ успешно создан'
                    }));
                    
                } catch (error) {
                    console.error('Ошибка при создании заказа:', error);
                    console.error('Stack trace:', error.stack);
                    res.writeHead(400);
                    res.end(JSON.stringify({ error: 'Ошибка при создании заказа: ' + error.message }));
                }
            });
        }
    }
    else if (pathname === '/api/database/export') {
        if (req.method === 'GET') {
            try {
                const sqlExport = db.exportToSQL();
                
                res.writeHead(200, {
                    'Content-Type': 'application/sql',
                    'Content-Disposition': 'attachment; filename="sports_nutrition_export.sql"'
                });
                res.end(sqlExport);
                
            } catch (error) {
                res.writeHead(500);
                res.end(JSON.stringify({ error: 'Ошибка экспорта: ' + error.message }));
            }
        }
    }
    else if (pathname === '/api/database/logs') {
        if (req.method === 'GET') {
            try {
                const logs = db.select('operation_logs', {}, { 
                    orderBy: 'timestamp DESC',
                    limit: parseInt(query.limit) || 100
                });
                
                res.writeHead(200);
                res.end(JSON.stringify(logs));
                
            } catch (error) {
                res.writeHead(500);
                res.end(JSON.stringify({ error: 'Ошибка загрузки логов: ' + error.message }));
            }
        }
    }
    else {
        res.writeHead(404);
        res.end(JSON.stringify({ error: 'API endpoint не найден' }));
    }
}

function serveStaticFile(req, res, pathname) {
    const filePath = path.join(__dirname, 'public', pathname);
    const ext = path.extname(filePath);
    const contentType = mimeTypes[ext] || 'application/octet-stream';

    fs.readFile(filePath, (err, data) => {
        if (err) {
            res.writeHead(404);
            res.end('File not found');
        } else {
            res.writeHead(200, { 'Content-Type': contentType });
            res.end(data);
        }
    });
}

function serveFile(req, res, fileName) {
    fs.readFile(path.join(__dirname, fileName), 'utf8', (err, data) => {
        if (err) {
            res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' });
            res.end('<h1>404 - Файл не найден</h1>');
        } else {
            res.writeHead(200, { 
                'Content-Type': 'text/html; charset=utf-8',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            });
            res.end(data);
        }
    });
}

function serveAdminPanel(req, res) {
    serveFile(req, res, 'admin-panel.html');
}

function serveMainPage(req, res) {
    // Генерируем HTML страницу с данными из базы данных
    const categories = db.select('categories');
    const products = db.select('products').map(p => ({
        ...p,
        formatted_price: formatPrice(p.price)
    }));

    const html = `<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Интернет-магазин спортивного питания</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo">🏋️ SportNutrition</h1>
                <div class="author-info">
                    <span>Выполнил: <strong>Валиев И. Б.</strong>, группа <strong>036-22 SMMr</strong></span>
                </div>
                <div class="cart-info">
                    <a href="/database" class="admin-link" style="margin-right: 1rem; color: #00d4aa; text-decoration: none;">🗄️ БД</a>
                    <a href="/admin" class="admin-link" style="margin-right: 1rem; color: #00d4aa; text-decoration: none;">⚙️ Админ</a>
                    <button class="cart-btn" onclick="toggleCart()">
                        🛒 Корзина (<span id="cart-count">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <div class="filters">
                <div class="search-box">
                    <input type="text" id="search-input" placeholder="Поиск товаров..." onkeyup="searchProducts()">
                </div>
                
                <div class="categories">
                    <button class="category-btn active" onclick="filterByCategory(null)">Все товары</button>
                    ${categories.map(cat => 
                        `<button class="category-btn" onclick="filterByCategory(${cat.id})">${cat.name}</button>`
                    ).join('')}
                </div>
            </div>

            <div class="products-grid" id="products-grid">
                ${products.map(product => `
                    <div class="product-card" data-category="${product.category_id}">
                        <div class="product-image">
                            <div class="product-emoji">${product.emoji}</div>
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-name">${product.name}</h3>
                            <p class="product-description">${product.description}</p>
                            
                            <div class="product-price">
                                <span class="price">${product.formatted_price}</span>
                            </div>
                            
                            <div class="product-stock">
                                ${product.stock_quantity > 0 ? 
                                    `<span class="in-stock">В наличии: ${product.stock_quantity} шт.</span>` :
                                    `<span class="out-of-stock">Нет в наличии</span>`
                                }
                            </div>
                            
                            ${product.stock_quantity > 0 ? 
                                `<button class="add-to-cart-btn" onclick="addToCart(${product.id})">Добавить в корзину</button>` :
                                ''
                            }
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    </main>

    <!-- Корзина (модальное окно) -->
    <div id="cart-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Корзина покупок</h2>
                <span class="close" onclick="toggleCart()">&times;</span>
            </div>
            
            <div class="modal-body">
                <div id="cart-items"></div>
                <div class="cart-total">
                    <strong>Итого: <span id="cart-total">0 сум</span></strong>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn-secondary" onclick="toggleCart()">Продолжить покупки</button>
                <button class="btn-primary" onclick="showCheckout()">Оформить заказ</button>
            </div>
        </div>
    </div>

    <!-- Форма оформления заказа -->
    <div id="checkout-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Оформление заказа</h2>
                <span class="close" onclick="hideCheckout()">&times;</span>
            </div>
            
            <div class="modal-body">
                <form id="checkout-form">
                    <div class="form-group">
                        <label for="customer-name">Имя *</label>
                        <input type="text" id="customer-name" name="customer_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer-email">Email</label>
                        <input type="email" id="customer-email" name="customer_email">
                    </div>
                    
                    <div class="form-group">
                        <label for="customer-phone">Телефон</label>
                        <input type="tel" id="customer-phone" name="customer_phone" placeholder="+998901234567">
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button class="btn-secondary" onclick="hideCheckout()">Отмена</button>
                <button class="btn-primary" onclick="submitOrder()">Подтвердить заказ</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/app.js?v=2"></script>
</body>
</html>`;

    res.writeHead(200, { 
        'Content-Type': 'text/html; charset=utf-8',
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache',
        'Expires': '0'
    });
    res.end(html);
}

const PORT = process.env.PORT || 8080;
server.listen(PORT, () => {
    console.log(`🏋️ Интернет-магазин спортивного питания запущен!`);
    console.log(`📍 Адрес: http://localhost:${PORT}`);
    console.log(`👨‍💻 Автор: Валиев И. Б., группа 036-22 SMMr`);
    console.log(`🚀 Сервер готов к работе на порту ${PORT}!`);
});