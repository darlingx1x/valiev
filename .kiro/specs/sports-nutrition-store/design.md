# Дизайн системы интернет-магазина спортивного питания

## Обзор

Система интернет-магазина спортивного питания представляет собой современное веб-приложение с акцентом на работу с MySQL базой данных. Архитектура следует принципам MVC (Model-View-Controller) с четким разделением слоев данных, бизнес-логики и представления. Дизайн интерфейса соответствует трендам 2025 года с использованием нейроморфизма, градиентов и микроанимаций.

**Автор проекта:** Валиев И. Б., группа 036-22 SMMr

## Архитектура

### Общая архитектура системы

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend       │    │   Database      │
│   (HTML/CSS/JS) │◄──►│   (PHP/Node.js) │◄──►│   (MySQL)       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Слои архитектуры

1. **Слой представления (Frontend)**
   - Современный отзывчивый интерфейс
   - Интерактивные элементы с микроанимациями
   - Адаптивная верстка для всех устройств

2. **Слой бизнес-логики (Backend)**
   - API для работы с данными
   - Валидация и обработка запросов
   - Управление сессиями и безопасностью

3. **Слой данных (Database)**
   - MySQL база данных
   - Оптимизированные запросы
   - Транзакционная целостность

## Компоненты и интерфейсы

### Основные компоненты

#### 1. Модуль каталога товаров
- **ProductController**: Управление отображением товаров
- **CategoryController**: Управление категориями
- **SearchController**: Поиск по товарам

#### 2. Модуль корзины и заказов
- **CartController**: Управление корзиной покупок
- **OrderController**: Обработка заказов
- **PaymentController**: Обработка платежей

#### 3. Административный модуль
- **AdminController**: Панель администратора
- **ProductManager**: Управление товарами
- **OrderManager**: Управление заказами

#### 4. Модуль базы данных
- **DatabaseConnection**: Подключение к MySQL
- **ProductRepository**: Работа с товарами
- **OrderRepository**: Работа с заказами
- **UserRepository**: Работа с пользователями

### API интерфейсы

#### REST API endpoints
```
GET    /api/products          - Получить список товаров
GET    /api/products/{id}     - Получить товар по ID
POST   /api/products          - Создать новый товар (админ)
PUT    /api/products/{id}     - Обновить товар (админ)
DELETE /api/products/{id}     - Удалить товар (админ)

GET    /api/categories        - Получить категории
POST   /api/cart/add          - Добавить в корзину
GET    /api/cart              - Получить корзину
POST   /api/orders            - Создать заказ
GET    /api/orders            - Получить заказы (админ)
```

## Модели данных

### Схема базы данных MySQL

#### Таблица products (товары)
```sql
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL COMMENT 'Цена в узбекских сумах',
    category_id INT,
    stock_quantity INT DEFAULT 0,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

#### Таблица categories (категории)
```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Таблица orders (заказы)
```sql
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255),
    customer_phone VARCHAR(20),
    total_amount DECIMAL(12,2) NOT NULL COMMENT 'Общая сумма в узбекских сумах',
    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Таблица order_items (товары в заказе)
```sql
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(12,2) NOT NULL COMMENT 'Цена на момент заказа в узбекских сумах',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

#### Таблица cart_items (корзина)
```sql
CREATE TABLE cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

### Объектные модели

#### Product Model
```javascript
class Product {
    constructor(id, name, description, price, categoryId, stockQuantity, imageUrl) {
        this.id = id;
        this.name = name;
        this.description = description;
        this.price = price; // в узбекских сумах
        this.categoryId = categoryId;
        this.stockQuantity = stockQuantity;
        this.imageUrl = imageUrl;
    }
}
```

#### Order Model
```javascript
class Order {
    constructor(id, customerName, customerEmail, customerPhone, totalAmount, status) {
        this.id = id;
        this.customerName = customerName;
        this.customerEmail = customerEmail;
        this.customerPhone = customerPhone;
        this.totalAmount = totalAmount; // в узбекских сумах
        this.status = status;
        this.items = [];
    }
}
```

## Свойства корректности

*Свойство - это характеристика или поведение, которое должно выполняться во всех допустимых выполнениях системы - по сути, формальное утверждение о том, что система должна делать. Свойства служат мостом между человекочитаемыми спецификациями и машинно-проверяемыми гарантиями корректности.*

### Рефлексия свойств

После анализа всех критериев приемки выявлены следующие области для объединения избыточных свойств:

**Объединяемые свойства:**
- Свойства 6.2 и 6.3 (сериализация/десериализация) объединяются в одно round-trip свойство
- Свойства 2.1 и 2.2 (добавление в корзину и отображение) можно объединить в одно свойство синхронизации корзины
- Свойства 7.1, 7.3, 7.5 (форматирование цен) объединяются в одно свойство валютного форматирования

### Основные свойства корректности

**Свойство 1: Фильтрация по категориям**
*Для любой* категории товаров, при выборе категории все возвращаемые товары должны принадлежать только этой категории
**Validates: Requirements 1.2**

**Свойство 2: Поиск товаров**
*Для любого* поискового запроса, все найденные товары должны содержать искомый текст в названии или описании
**Validates: Requirements 1.3**

**Свойство 3: Отображение детальной информации товара**
*Для любого* товара, детальная страница должна содержать всю информацию из базы данных (название, описание, цену, наличие)
**Validates: Requirements 1.4**

**Свойство 4: Синхронизация корзины с базой данных**
*Для любого* товара, добавление в корзину должно создать запись в БД, и отображение корзины должно точно соответствовать данным в БД
**Validates: Requirements 2.1, 2.2**

**Свойство 5: Обновление количества в корзине**
*Для любого* изменения количества товара в корзине, данные в базе данных должны обновиться соответствующим образом
**Validates: Requirements 2.3**

**Свойство 6: Создание заказа и обновление склада**
*Для любого* заказа, создание заказа должно уменьшить количество товаров на складе на заказанное количество
**Validates: Requirements 2.4, 2.5**

**Свойство 7: CRUD операции с товарами**
*Для любого* товара, операции создания, чтения, обновления и удаления должны корректно отражаться в базе данных
**Validates: Requirements 3.1, 3.2, 3.3**

**Свойство 8: Отображение заказов администратором**
*Для любого* запроса списка заказов, отображаемые данные должны точно соответствовать данным в базе данных
**Validates: Requirements 3.4**

**Свойство 9: Обновление статуса заказа**
*Для любого* изменения статуса заказа администратором, новый статус должен корректно сохраняться в базе данных
**Validates: Requirements 3.5**

**Свойство 10: Использование подготовленных SQL-запросов**
*Для любой* операции с базой данных, система должна использовать подготовленные SQL-запросы для предотвращения SQL-инъекций
**Validates: Requirements 4.1**

**Свойство 11: Обработка ошибок базы данных**
*Для любой* ошибки базы данных, система должна корректно обработать ошибку и вернуть понятное сообщение пользователю
**Validates: Requirements 4.2**

**Свойство 12: Транзакционная целостность заказов**
*Для любого* создания заказа, операция должна выполняться в рамках транзакции для обеспечения целостности данных
**Validates: Requirements 4.3**

**Свойство 13: Логирование критических операций**
*Для любой* критической операции с базой данных, система должна создать соответствующую запись в логах
**Validates: Requirements 4.5**

**Свойство 14: Мгновенная обратная связь UI**
*Для любого* действия пользователя, система должна предоставить обратную связь в течение разумного времени (< 200ms для UI операций)
**Validates: Requirements 5.5**

**Свойство 15: Валидация JSON данных**
*Для любых* JSON данных, отправляемых на сервер, система должна валидировать их согласно определенной схеме
**Validates: Requirements 6.1**

**Свойство 16: Сериализация round-trip**
*Для любого* сложного объекта, сериализация в JSON и последующая десериализация должна восстановить эквивалентный объект
**Validates: Requirements 6.2, 6.3**

**Свойство 17: Валидация файлов изображений**
*Для любого* загружаемого файла изображения товара, система должна валидировать формат и размер файла
**Validates: Requirements 6.5**

**Свойство 18: Форматирование узбекских сум**
*Для любой* цены в системе, отображение должно быть в правильном формате узбекских сум с соответствующими разделителями
**Validates: Requirements 7.1, 7.3, 7.5**

**Свойство 19: Корректное суммирование цен**
*Для любого* набора товаров в заказе, общая стоимость должна равняться сумме цен всех товаров с учетом количества
**Validates: Requirements 7.2**

**Свойство 20: Валидация ввода цен**
*Для любой* цены, вводимой администратором, система должна принимать только корректные значения в формате узбекских сум
**Validates: Requirements 7.4**

## Обработка ошибок

### Стратегия обработки ошибок

1. **Ошибки базы данных**
   - Логирование всех ошибок БД
   - Возврат понятных сообщений пользователю
   - Откат транзакций при ошибках

2. **Ошибки валидации**
   - Проверка данных на клиенте и сервере
   - Детальные сообщения об ошибках валидации
   - Предотвращение некорректных данных

3. **Ошибки сети**
   - Повторные попытки для критических операций
   - Индикаторы загрузки для пользователя
   - Graceful degradation функциональности

### Коды ошибок

```javascript
const ERROR_CODES = {
    DATABASE_ERROR: 'DB_001',
    VALIDATION_ERROR: 'VAL_001',
    PRODUCT_NOT_FOUND: 'PRD_001',
    INSUFFICIENT_STOCK: 'STK_001',
    INVALID_PRICE_FORMAT: 'PRC_001'
};
```

## Стратегия тестирования

### Двойной подход к тестированию

Система использует комплексный подход, включающий как модульные тесты, так и property-based тестирование:

**Модульные тесты:**
- Проверяют конкретные примеры и граничные случаи
- Тестируют интеграционные точки между компонентами
- Фокусируются на специфических сценариях использования

**Property-based тестирование:**
- Проверяют универсальные свойства на множестве входных данных
- Используют библиотеку **fast-check** для JavaScript/Node.js
- Каждый property-based тест выполняется минимум **100 итераций**
- Каждый тест помечается комментарием в формате: **Feature: sports-nutrition-store, Property {number}: {property_text}**

### Требования к тестированию

1. **Каждое свойство корректности должно быть реализовано ОДНИМ property-based тестом**
2. **Все property-based тесты должны быть помечены ссылкой на соответствующее свойство в дизайне**
3. **Минимум 100 итераций для каждого property-based теста**
4. **Использование библиотеки fast-check для генерации тестовых данных**

### Примеры тестовых сценариев

#### Модульные тесты
- Создание товара с корректными данными
- Обработка некорректного формата цены
- Создание заказа с пустой корзиной

#### Property-based тесты
- Фильтрация товаров по случайным категориям
- Round-trip сериализация случайных объектов товаров
- Корректность суммирования случайных наборов товаров

### Покрытие тестами

- **Модели данных**: 100% покрытие всех методов
- **API endpoints**: Все REST endpoints должны иметь тесты
- **Бизнес-логика**: Все критические операции покрыты тестами
- **Обработка ошибок**: Все типы ошибок должны быть протестированы
## Технический стек

### Frontend (Современные тренды 2025)
- **HTML5** с семантической разметкой
- **CSS3** с использованием:
  - CSS Grid и Flexbox для адаптивной верстки
  - CSS Custom Properties (переменные)
  - Нейроморфизм и glassmorphism эффекты
  - Плавные градиенты и микроанимации
  - Container queries для истинно адаптивного дизайна
- **Vanilla JavaScript** или **Alpine.js** для интерактивности
- **CSS-in-JS** подход для компонентного стилизования

### Backend
- **PHP 8.2+** или **Node.js 18+**
- **MySQL 8.0+** для базы данных
- **PDO** или **Sequelize** для работы с БД
- **JWT** для аутентификации администратора
- **Composer** или **npm** для управления зависимостями

### Дизайн-система 2025

#### Цветовая палитра
```css
:root {
  /* Основные цвета */
  --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  --accent: #00d4aa;
  
  /* Нейтральные */
  --surface: rgba(255, 255, 255, 0.1);
  --background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
  --text-primary: #2d3748;
  --text-secondary: #718096;
  
  /* Glassmorphism */
  --glass: rgba(255, 255, 255, 0.25);
  --glass-border: rgba(255, 255, 255, 0.18);
}
```

#### Типографика
```css
/* Современные шрифты 2025 */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap');

:root {
  --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  --font-mono: 'JetBrains Mono', 'Fira Code', monospace;
}
```

#### Компоненты UI

**Карточки товаров (Нейроморфизм)**
```css
.product-card {
  background: linear-gradient(145deg, #ffffff, #f0f0f0);
  border-radius: 20px;
  box-shadow: 
    20px 20px 60px #d9d9d9,
    -20px -20px 60px #ffffff;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.product-card:hover {
  transform: translateY(-8px);
  box-shadow: 
    25px 25px 80px #d9d9d9,
    -25px -25px 80px #ffffff;
}
```

**Кнопки (Glassmorphism)**
```css
.btn-glass {
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 16px;
  transition: all 0.3s ease;
}

.btn-glass:hover {
  background: rgba(255, 255, 255, 0.35);
  transform: scale(1.05);
}
```

### Архитектура файлов

```
sports-nutrition-store/
├── public/
│   ├── index.php
│   ├── admin/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
├── src/
│   ├── models/
│   │   ├── Product.php
│   │   ├── Order.php
│   │   └── Category.php
│   ├── controllers/
│   │   ├── ProductController.php
│   │   ├── OrderController.php
│   │   └── AdminController.php
│   ├── repositories/
│   │   ├── ProductRepository.php
│   │   └── OrderRepository.php
│   ├── services/
│   │   ├── DatabaseService.php
│   │   └── ValidationService.php
│   └── config/
│       └── database.php
├── tests/
│   ├── unit/
│   └── property/
├── database/
│   ├── migrations/
│   └── seeds/
└── docs/
```

### Безопасность

1. **SQL Injection Prevention**
   - Использование подготовленных запросов
   - Валидация всех входных данных
   - Экранирование специальных символов

2. **XSS Protection**
   - Санитизация пользовательского ввода
   - Content Security Policy (CSP)
   - Использование htmlspecialchars()

3. **CSRF Protection**
   - CSRF токены для форм
   - Проверка referrer headers
   - SameSite cookies

### Производительность

1. **База данных**
   - Индексы на часто используемые поля
   - Оптимизированные запросы
   - Connection pooling

2. **Frontend**
   - Lazy loading изображений
   - Минификация CSS/JS
   - Сжатие gzip/brotli

3. **Кэширование**
   - Redis для сессий
   - Кэширование запросов к БД
   - Browser caching для статических ресурсов

### Мониторинг и логирование

```php
// Пример системы логирования
class Logger {
    public static function logDatabaseOperation($operation, $table, $data = null) {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'operation' => $operation,
            'table' => $table,
            'data' => $data,
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        file_put_contents(
            'logs/database.log', 
            json_encode($log) . PHP_EOL, 
            FILE_APPEND | LOCK_EX
        );
    }
}
```

### Интернационализация

Поддержка узбекского и русского языков:

```php
// Система локализации
class Localization {
    private static $strings = [
        'uz' => [
            'add_to_cart' => 'Savatga qo\'shish',
            'price' => 'Narx',
            'currency' => 'so\'m'
        ],
        'ru' => [
            'add_to_cart' => 'Добавить в корзину',
            'price' => 'Цена',
            'currency' => 'сум'
        ]
    ];
}
```

### Развертывание

1. **Требования к серверу**
   - PHP 8.2+ или Node.js 18+
   - MySQL 8.0+
   - Apache/Nginx
   - SSL сертификат

2. **Environment переменные**
   ```env
   DB_HOST=localhost
   DB_NAME=sports_nutrition_store
   DB_USER=root
   DB_PASS=password
   APP_ENV=production
   ```

3. **Автоматическая установка**
   - Скрипт создания таблиц
   - Начальные данные (категории, тестовые товары)
   - Проверка зависимостей

Этот дизайн обеспечивает создание современного, безопасного и производительного интернет-магазина спортивного питания с акцентом на работу с базой данных MySQL и соответствие трендам веб-дизайна 2025 года.