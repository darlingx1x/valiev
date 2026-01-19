/**
 * JavaScript для интернет-магазина спортивного питания
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

class SportNutritionApp {
    constructor() {
        this.cart = [];
        this.currentCategory = null;
        this.searchTerm = '';
        
        this.init();
    }
    
    init() {
        this.loadCart();
        this.updateCartDisplay();
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Закрытие модальных окон при клике вне их
        window.onclick = (event) => {
            const cartModal = document.getElementById('cart-modal');
            const checkoutModal = document.getElementById('checkout-modal');
            
            if (event.target === cartModal) {
                this.toggleCart();
            }
            if (event.target === checkoutModal) {
                this.hideCheckout();
            }
        };
        
        // Обработка Enter в поиске
        document.getElementById('search-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.searchProducts();
            }
        });
    }
    
    // Управление корзиной
    addToCart(productId) {
        console.log('Добавляем товар в корзину:', productId);
        
        fetch('/api/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        })
        .then(response => {
            console.log('Ответ сервера:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Данные от сервера:', data);
            if (data.error) {
                this.showNotification(data.error, 'error');
            } else {
                this.showNotification('Товар добавлен в корзину!', 'success');
                if (data.cart) {
                    this.updateCartFromResponse(data.cart);
                } else {
                    // Если нет данных корзины в ответе, загружаем корзину отдельно
                    this.loadCart();
                }
            }
        })
        .catch(error => {
            console.error('Ошибка при добавлении товара:', error);
            this.showNotification('Ошибка при добавлении товара: ' + error.message, 'error');
        });
    }
    
    loadCart() {
        fetch('/api/cart')
        .then(response => response.json())
        .then(data => {
            this.updateCartFromResponse(data);
        })
        .catch(error => {
            console.error('Error loading cart:', error);
        });
    }
    
    updateCartFromResponse(cartData) {
        console.log('Обновляем корзину с данными:', cartData);
        this.cart = cartData.items || [];
        const totalItems = cartData.total_items || 0;
        console.log('Общее количество товаров:', totalItems);
        document.getElementById('cart-count').textContent = totalItems;
        this.updateCartModal();
    }
    
    updateCartDisplay() {
        document.getElementById('cart-count').textContent = this.cart.length;
    }
    
    updateCartModal() {
        const cartItemsContainer = document.getElementById('cart-items');
        const cartTotalElement = document.getElementById('cart-total');
        
        if (this.cart.length === 0) {
            cartItemsContainer.innerHTML = '<p>Корзина пуста</p>';
            cartTotalElement.textContent = '0 сум';
            return;
        }
        
        let cartHTML = '';
        let total = 0;
        
        this.cart.forEach(item => {
            const subtotal = item.product.price * item.quantity;
            total += subtotal;
            
            cartHTML += `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <h4>${item.product.name}</h4>
                        <p>${item.product.formatted_price} × ${item.quantity}</p>
                    </div>
                    <div class="cart-item-controls">
                        <button onclick="app.updateCartQuantity(${item.product_id}, ${item.quantity - 1})">-</button>
                        <span>${item.quantity}</span>
                        <button onclick="app.updateCartQuantity(${item.product_id}, ${item.quantity + 1})">+</button>
                        <button onclick="app.removeFromCart(${item.product_id})" class="remove-btn">×</button>
                    </div>
                    <div class="cart-item-total">
                        ${this.formatPrice(subtotal)}
                    </div>
                </div>
            `;
        });
        
        cartItemsContainer.innerHTML = cartHTML;
        cartTotalElement.textContent = this.formatPrice(total);
    }
    
    updateCartQuantity(productId, newQuantity) {
        if (newQuantity <= 0) {
            this.removeFromCart(productId);
            return;
        }
        
        fetch('/api/cart/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: newQuantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                this.showNotification(data.error, 'error');
            } else {
                this.updateCartFromResponse(data.cart);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('Ошибка при обновлении корзины', 'error');
        });
    }
    
    removeFromCart(productId) {
        fetch('/api/cart/remove', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                this.showNotification(data.error, 'error');
            } else {
                this.updateCartFromResponse(data.cart);
                this.showNotification('Товар удален из корзины', 'info');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('Ошибка при удалении товара', 'error');
        });
    }
    
    // Управление модальными окнами
    toggleCart() {
        const modal = document.getElementById('cart-modal');
        if (modal.style.display === 'block') {
            modal.style.display = 'none';
        } else {
            this.loadCart(); // Обновляем корзину при открытии
            modal.style.display = 'block';
        }
    }
    
    showCheckout() {
        if (this.cart.length === 0) {
            this.showNotification('Корзина пуста', 'error');
            return;
        }
        
        document.getElementById('cart-modal').style.display = 'none';
        document.getElementById('checkout-modal').style.display = 'block';
    }
    
    hideCheckout() {
        document.getElementById('checkout-modal').style.display = 'none';
    }
    
    // Оформление заказа
    submitOrder() {
        const form = document.getElementById('checkout-form');
        const formData = new FormData(form);
        
        const orderData = {
            customer_name: formData.get('customer_name'),
            customer_email: formData.get('customer_email'),
            customer_phone: formData.get('customer_phone')
        };
        
        if (!orderData.customer_name.trim()) {
            this.showNotification('Укажите имя', 'error');
            return;
        }
        
        fetch('/api/orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                this.showNotification(data.error, 'error');
            } else {
                this.showNotification(`Заказ #${data.order_id} успешно создан!`, 'success');
                this.hideCheckout();
                this.cart = [];
                this.updateCartDisplay();
                form.reset();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showNotification('Ошибка при создании заказа', 'error');
        });
    }
    
    // Фильтрация и поиск
    filterByCategory(categoryId) {
        this.currentCategory = categoryId;
        
        // Обновляем активную кнопку
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        this.applyFilters();
    }
    
    searchProducts() {
        const searchInput = document.getElementById('search-input');
        this.searchTerm = searchInput.value.toLowerCase().trim();
        this.applyFilters();
    }
    
    applyFilters() {
        const products = document.querySelectorAll('.product-card');
        
        products.forEach(product => {
            let show = true;
            
            // Фильтр по категории
            if (this.currentCategory !== null) {
                const productCategory = parseInt(product.dataset.category);
                if (productCategory !== this.currentCategory) {
                    show = false;
                }
            }
            
            // Фильтр по поиску
            if (this.searchTerm && show) {
                const productName = product.querySelector('.product-name').textContent.toLowerCase();
                const productDescription = product.querySelector('.product-description').textContent.toLowerCase();
                
                if (!productName.includes(this.searchTerm) && !productDescription.includes(this.searchTerm)) {
                    show = false;
                }
            }
            
            // Показываем/скрываем товар с анимацией
            if (show) {
                product.style.display = 'block';
                product.classList.add('fade-in');
            } else {
                product.style.display = 'none';
                product.classList.remove('fade-in');
            }
        });
    }
    
    // Утилиты
    formatPrice(price) {
        return new Intl.NumberFormat('uz-UZ').format(price) + ' сум';
    }
    
    showNotification(message, type = 'info') {
        // Создаем уведомление
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Стили для уведомления
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            max-width: 300px;
        `;
        
        // Цвета в зависимости от типа
        const colors = {
            success: '#48bb78',
            error: '#f56565',
            info: '#4299e1',
            warning: '#ed8936'
        };
        
        notification.style.backgroundColor = colors[type] || colors.info;
        
        // Добавляем на страницу
        document.body.appendChild(notification);
        
        // Удаляем через 3 секунды
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
}

// Глобальные функции для вызова из HTML
function addToCart(productId) {
    app.addToCart(productId);
}

function toggleCart() {
    app.toggleCart();
}

function showCheckout() {
    app.showCheckout();
}

function hideCheckout() {
    app.hideCheckout();
}

function submitOrder() {
    app.submitOrder();
}

function filterByCategory(categoryId) {
    app.filterByCategory(categoryId);
}

function searchProducts() {
    app.searchProducts();
}

// Инициализация приложения
const app = new SportNutritionApp();

// CSS для анимаций уведомлений
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);