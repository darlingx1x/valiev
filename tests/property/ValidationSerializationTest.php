<?php
/**
 * Property-based тесты для валидации и сериализации
 * Feature: sports-nutrition-store, Property 15: Валидация JSON данных
 * Feature: sports-nutrition-store, Property 16: Сериализация round-trip
 * Feature: sports-nutrition-store, Property 17: Валидация файлов изображений
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

require_once '../../src/services/ValidationService.php';
require_once '../../src/models/Product.php';

class ValidationSerializationPropertyTest {
    private $testIterations = 100;
    
    /**
     * Property 15: Валидация JSON данных
     * Validates: Requirements 6.1
     */
    public function testJsonValidationProperty() {
        echo "Запуск property теста валидации JSON (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Генерируем случайную схему валидации
                $schema = $this->generateRandomSchema();
                
                // Генерируем валидные данные согласно схеме
                $validData = $this->generateValidData($schema);
                $validJson = json_encode($validData);
                
                // Тестируем валидацию валидных данных
                $result = ValidationService::validateJson($validJson, $schema);
                
                if (!$result['valid']) {
                    throw new Exception("Валидные данные не прошли валидацию: " . implode(', ', $result['errors']));
                }
                
                if ($result['data'] !== $validData) {
                    throw new Exception("Данные после валидации не соответствуют исходным");
                }
                
                // Генерируем невалидные данные
                $invalidData = $this->generateInvalidData($schema);
                $invalidJson = json_encode($invalidData);
                
                // Тестируем валидацию невалидных данных
                $invalidResult = ValidationService::validateJson($invalidJson, $schema);
                
                if ($invalidResult['valid']) {
                    throw new Exception("Невалидные данные прошли валидацию");
                }
                
                if (empty($invalidResult['errors'])) {
                    throw new Exception("Отсутствуют ошибки валидации для невалидных данных");
                }
                
                // Тестируем некорректный JSON
                $malformedJson = '{"invalid": json}';
                $malformedResult = ValidationService::validateJson($malformedJson, $schema);
                
                if ($malformedResult['valid']) {
                    throw new Exception("Некорректный JSON прошел валидацию");
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест валидации JSON прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Property 16: Сериализация round-trip
     * Validates: Requirements 6.2, 6.3
     */
    public function testSerializationRoundTripProperty() {
        echo "Запуск property теста round-trip сериализации (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Генерируем случайный объект товара
                $originalProduct = $this->generateRandomProduct();
                
                // Сериализуем в JSON
                $json = ValidationService::serializeToJson($originalProduct);
                
                if (empty($json)) {
                    throw new Exception("Сериализация вернула пустой результат");
                }
                
                // Проверяем что это валидный JSON
                $decoded = json_decode($json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Сериализация создала некорректный JSON");
                }
                
                // Десериализуем обратно в объект
                $deserializedProduct = ValidationService::deserializeFromJson($json, 'Product');
                
                if (!($deserializedProduct instanceof Product)) {
                    throw new Exception("Десериализация не создала объект Product");
                }
                
                // Проверяем что данные сохранились
                if ($deserializedProduct->getName() !== $originalProduct->getName()) {
                    throw new Exception("Имя товара не сохранилось при round-trip");
                }
                
                if (abs($deserializedProduct->getPrice() - $originalProduct->getPrice()) > 0.01) {
                    throw new Exception("Цена товара не сохранилась при round-trip");
                }
                
                if ($deserializedProduct->getCategoryId() !== $originalProduct->getCategoryId()) {
                    throw new Exception("ID категории не сохранился при round-trip");
                }
                
                // Тестируем round-trip с массивами
                $originalArray = $this->generateRandomArray();
                $arrayJson = ValidationService::serializeToJson($originalArray);
                $deserializedArray = ValidationService::deserializeFromJson($arrayJson);
                
                if ($deserializedArray !== $originalArray) {
                    throw new Exception("Массив не сохранился при round-trip");
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест round-trip сериализации прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Property 17: Валидация файлов изображений
     * Validates: Requirements 6.5
     */
    public function testImageFileValidationProperty() {
        echo "Запуск property теста валидации файлов изображений (100 итераций)...\n";
        
        for ($i = 0; $i < $this->testIterations; $i++) {
            try {
                // Тестируем различные сценарии файлов
                $testScenarios = $this->generateFileTestScenarios();
                
                foreach ($testScenarios as $scenario) {
                    $result = ValidationService::validateImageFile($scenario['file']);
                    
                    if ($scenario['should_be_valid'] && !$result['valid']) {
                        throw new Exception("Валидный файл не прошел валидацию: " . implode(', ', $result['errors']));
                    }
                    
                    if (!$scenario['should_be_valid'] && $result['valid']) {
                        throw new Exception("Невалидный файл прошел валидацию");
                    }
                    
                    // Проверяем наличие обязательных полей в результате
                    $requiredFields = ['valid', 'errors'];
                    foreach ($requiredFields as $field) {
                        if (!isset($result[$field])) {
                            throw new Exception("Отсутствует обязательное поле '$field' в результате валидации файла");
                        }
                    }
                }
                
            } catch (Exception $e) {
                echo "FAILED на итерации $i: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        echo "✓ Property тест валидации файлов изображений прошел успешно ($this->testIterations итераций)\n";
        return true;
    }
    
    /**
     * Генерирует случайную схему валидации
     */
    private function generateRandomSchema() {
        $properties = [
            'name' => [
                'type' => 'string',
                'minLength' => 1,
                'maxLength' => 255
            ],
            'price' => [
                'type' => 'number',
                'minimum' => 0,
                'maximum' => 1000000
            ],
            'category_id' => [
                'type' => 'integer',
                'minimum' => 1
            ]
        ];
        
        $required = ['name', 'price'];
        
        // Случайно добавляем дополнительные поля
        if (rand(0, 1)) {
            $required[] = 'category_id';
        }
        
        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required
        ];
    }
    
    /**
     * Генерирует валидные данные согласно схеме
     */
    private function generateValidData($schema) {
        $data = [];
        
        foreach ($schema['required'] as $field) {
            switch ($field) {
                case 'name':
                    $data['name'] = 'Test Product ' . rand(1000, 9999);
                    break;
                case 'price':
                    $data['price'] = rand(1000, 500000) / 100;
                    break;
                case 'category_id':
                    $data['category_id'] = rand(1, 10);
                    break;
            }
        }
        
        return $data;
    }
    
    /**
     * Генерирует невалидные данные
     */
    private function generateInvalidData($schema) {
        $invalidScenarios = [
            // Отсутствует обязательное поле
            [],
            // Неверный тип
            ['name' => 123, 'price' => 'invalid'],
            // Выход за границы
            ['name' => str_repeat('a', 300), 'price' => -100],
            // Пустые значения
            ['name' => '', 'price' => 0]
        ];
        
        return $invalidScenarios[array_rand($invalidScenarios)];
    }
    
    /**
     * Генерирует случайный товар
     */
    private function generateRandomProduct() {
        return new Product([
            'name' => 'Test Product ' . rand(1000, 9999),
            'description' => 'Test description ' . rand(1000, 9999),
            'price' => rand(1000, 500000) / 100,
            'category_id' => rand(1, 10),
            'stock_quantity' => rand(0, 100),
            'image_url' => '/images/test-' . rand(1, 100) . '.jpg'
        ]);
    }
    
    /**
     * Генерирует случайный массив
     */
    private function generateRandomArray() {
        return [
            'id' => rand(1, 1000),
            'name' => 'Test Item ' . rand(1000, 9999),
            'values' => [rand(1, 100), rand(1, 100), rand(1, 100)],
            'metadata' => [
                'created' => date('Y-m-d H:i:s'),
                'active' => (bool)rand(0, 1)
            ]
        ];
    }
    
    /**
     * Генерирует тестовые сценарии для файлов
     */
    private function generateFileTestScenarios() {
        return [
            // Отсутствующий файл
            [
                'file' => ['tmp_name' => '', 'size' => 0, 'name' => 'test.jpg'],
                'should_be_valid' => false
            ],
            // Слишком большой файл
            [
                'file' => [
                    'tmp_name' => __FILE__, // Используем текущий файл как заглушку
                    'size' => 10 * 1024 * 1024, // 10MB
                    'name' => 'large.jpg'
                ],
                'should_be_valid' => false
            ],
            // Недопустимое расширение
            [
                'file' => [
                    'tmp_name' => __FILE__,
                    'size' => 1024,
                    'name' => 'test.txt'
                ],
                'should_be_valid' => false
            ]
        ];
    }
    
    /**
     * Запускает все тесты
     */
    public function runAllTests() {
        echo "=== Property-based тесты валидации и сериализации ===\n";
        
        $results = [
            'json_validation' => $this->testJsonValidationProperty(),
            'serialization_roundtrip' => $this->testSerializationRoundTripProperty(),
            'image_file_validation' => $this->testImageFileValidationProperty()
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
    $test = new ValidationSerializationPropertyTest();
    $test->runAllTests();
}
?>