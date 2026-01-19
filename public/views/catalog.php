<!DOCTYPE html>
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
                    <?php foreach ($categories as $category): ?>
                        <button class="category-btn" onclick="filterByCategory(<?= $category->getId() ?>)">
                            <?= htmlspecialchars($category->getName()) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="products-grid" id="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-category="<?= $product->getCategoryId() ?>">
                        <div class="product-image">
                            <img src="<?= htmlspecialchars($product->getImageUrl()) ?>" 
                                 alt="<?= htmlspecialchars($product->getName()) ?>"
                                 onerror="this.src='/assets/images/placeholder.jpg'">
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($product->getName()) ?></h3>
                            <p class="product-description"><?= htmlspecialchars($product->getDescription()) ?></p>
                            
                            <div class="product-price">
                                <span class="price"><?= $product->getFormattedPrice() ?></span>
                            </div>
                            
                            <div class="product-stock">
                                <?php if ($product->getStockQuantity() > 0): ?>
                                    <span class="in-stock">В наличии: <?= $product->getStockQuantity() ?> шт.</span>
                                <?php else: ?>
                                    <span class="out-of-stock">Нет в наличии</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($product->getStockQuantity() > 0): ?>
                                <button class="add-to-cart-btn" onclick="addToCart(<?= $product->getId() ?>)">
                                    Добавить в корзину
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
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

    <script src="/assets/js/app.js"></script>
</body>
</html>