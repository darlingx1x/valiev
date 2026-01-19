<?php
/**
 * Property-based тесты для ProductRepository
 * Feature: sports-nutrition-store, Property 1: Фильтрация по категориям
 * Feature: sports-nutrition-store, Property 2: Поиск товаров  
 * Feature: sports-nutrition-store, Property 7: CRUD операции с товарами
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once '../../src/repositories/ProductRepository.php';
require_once '../../src/repositories/CategoryRepository.php';
require_once '../../database/install.php';

class ProductRepositoryPropertyTest {
    private $productRepo;
    private $categoryRepo;
    private $testIterations = 100;
    
    public function __construct() {
        // Инициализируем базу данных для тестов
        $installer = new DatabaseInstaller();
        if (!$installer->checkInstallation()) {
            $installer->install();
        }
        
        $this->productRepo = new ProductRepository();
        $this->categoryRepo = new CategoryRepository();
    }
    
    /**
     * Property 1: Фильтрация по категориям
     * Validates: Requirements 1.2
     */
    public function testCategoryFilteringProperty() {
        echo "Запуск property теста фильтрации по категориям (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Получаем случайную категорию
                $categories = $this->categoryRepo->getAll();
                if (empty($categories)) {
                    throw new Exception("Нет категорий для тестирования");
                }
                
                $randomCategory = $categories[array_rand($categories)];
                $categoryId = $randomCategory->getId();
                
                // Получаем товары по категории
                $products = $this->productRepo->getByCategory($categoryId);
                
                // Проверяем что все товары принадлежат выбранной категории
                foreach ($products as $product) {
                    if ($product->getCategoryId() !== $categoryId) {
                        throw new Exception("Товар ID {$product->getId()} не принадлежит категории $categoryId");
                    }
                }
                
                // Проверяем что мы не пропустили товары этой категории
                $allProducts = $this->productRepo->getAll();
                $expectedProducts = array_filter($allProducts, function($product) use ($categoryId) {
                    return $product->getCategoryId() === $categoryId;
                });
                
                if (count($products) !== count($expectedProducts)) {
                    throw new Exception("Количество отфильтрованных товаров не соответствует ожидаемому");
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест фильтрации по категориям прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Property 2: Поиск товаров
     * Validates: Requirements 1.3
     */
    public function testProductSearchProperty() {
        echo "Запуск property теста поиска товаров (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Генерируем случайный поисковый запрос
                $searchTerms = ['Whey', 'BCAA', 'Protein', 'Creatine', 'Vitamin', 'Bar'];
                $searchTerm = $searchTerms[array_rand($searchTerms)];
                
                // Выполняем поиск
                $searchResults = $this->productRepo->searchByName($searchTerm);
                
                // Проверяем что все результаты содержат поисковый термин
                foreach ($searchResults as $product) {
                    $name = strtolower($product->getName());
                    $description = strtolower($product->getDescription());
                    $searchLower = strtolower($searchTerm);
                    
                    if (strpos($name, $searchLower) === false && 
                        strpos($description, $searchLower) === false) {
                        throw new Exception("Товар '{$product->getName()}' не содержит поисковый термин '$searchTerm'");
                    }
                }
                
                // Проверяем что мы не пропустили подходящие товары
                $allProducts = $this->productRepo->getAll();
                $expectedCount = 0;
                
                foreach ($allProducts as $product) {
                    $name = strtolower($product->getName());
                    $description = strtolower($product->getDescription());
                    $searchLower = strtolower($searchTerm);
                    
                    if (strpos($name, $searchLower) !== false || 
                        strpos($description, $searchLower) !== false) {
                        $expectedCount++;
                    }
                }
                
                if (count($searchResults) !== $expectedCount) {
                    throw new Exception("Поиск вернул неполные результаты для '$searchTerm'");
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест поиска товаров прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Property 7: CRUD операции с товарами
     * Validates: Requirements 3.1, 3.2, 3.3
     */
    public function testCrudOperationsProperty() {
        echo "Запуск property теста CRUD операций (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Генерируем случайные данные товара
                $productData = $this->generateRandomProductData();
                
                // CREATE - создаем товар
                $product = new Product($productData);
                $productId = $this->productRepo->create($product);
                
                if (!$productId) {
                    throw new Exception("Не удалось создать товар");
                }
                
                // READ - читаем созданный товар
                $createdProduct = $this->productRepo->getById($productId);
                
                if (!$createdProduct) {
                    throw new Exception("Не удалось прочитать созданный товар");
                }
                
                // Проверяем что данные соответствуют
                if ($createdProduct->getName() !== $productData['name'] ||
                    $createdProduct->getPrice() !== $productData['price'] ||
                    $createdProduct->getCategoryId() !== $productData['category_id']) {
                    throw new Exception("Данные созданного товара не соответствуют исходным");
                }
                
                // UPDATE - обновляем товар
                $updatedData = $this->generateRandomProductData();
                $createdProduct->setName($updatedData['name']);
                $createdProduct->setPrice($updatedData['price']);
                $createdProduct->setDescription($updatedData['description']);
                
                $updateResult = $this->productRepo->update($createdProduct);
                
                if (!$updateResult) {
                    throw new Exception("Не удалось обновить товар");
                }
                
                // Проверяем что обновление применилось
                $updatedProduct = $this->productRepo->getById($productId);
                
                if ($updatedProduct->getName() !== $updatedData['name'] ||
                    $updatedProduct->getPrice() !== $updatedData['price']) {
                    throw new Exception("Обновление товара не применилось");
                }
                
                // DELETE - удаляем товар
                $deleteResult = $this->productRepo->delete($productId);
                
                if (!$deleteResult) {
                    throw new Exception("Не удалось удалить товар");
                }
                
                // Проверяем что товар удален
                $deletedProduct = $this->productRepo->getById($productId);
                
                if ($deletedProduct !== null) {
                    throw new Exception("Товар не был удален");
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест CRUD операций прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Генерирует случайные данные товара
     */
    private function generateRandomProductData() {
        $categories = $this->categoryRepo->getAll();
        if (empty($categories)) {
            throw new Exception("Нет категорий для создания товара");
        }
        
        $randomCategory = $categories[array_rand($categories)];
        
        $names = [
            'Test Whey Protein',
            'Test BCAA Complex',
            'Test Creatine Monohydrate',
            'Test Vitamin Complex',
            'Test Protein Bar'
        ];
        
        return [
            'name' => $names[array_rand($names)] . ' ' . rand(1000, 9999),
            'description' => 'Test product description ' . rand(1000, 9999),
            'price' => rand(50000, 500000) / 100, // Цена в сумах
            'category_id' => $randomCategory->getId(),
            'stock_quantity' => rand(0, 100),
            'image_url' => '/images/test-product-' . rand(1, 10) . '.jpg'
        ];
    }
    
    /**
     * Запускает все тесты
     */
    public function runAllTests() {
        echo "=== Property-based тесты ProductRepository ===\n";
        
        $results = [
            'category_filtering' => $this->testCategoryFilteringProperty(),
            'product_search' => $this->testProductSearchProperty(),
            'crud_operations' => $this->testCrudOperationsProperty()
        ];
        
        $passed = array_sum($results);
        $total = count($results);
        
        echo "\n=== Результаты ===\n";
        echo "Пройдено: $passed/$total тестов\n";
        
        if ($passed === $total) {
            echo "✓ Все property тесты прошли успешно!\n";
            return true;
        } else {
            echo "✗ Некоторые тесты не прошли\n";
            return false;
        }
    }
}

// Запуск тестов если скрипт вызван напрямую
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ProductRepositoryPropertyTest();
    $test->runAllTests();
}
?>