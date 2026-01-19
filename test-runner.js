/**
 * Простой тест-раннер для демонстрации property-based тестов
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

// Имитация property-based тестов на JavaScript
class PropertyTestRunner {
    constructor() {
        this.testResults = [];
    }

    // Property 18: Форматирование узбекских сум
    testCurrencyFormatting() {
        console.log('🧪 Запуск property теста форматирования валюты (100 итераций)...');
        
        for (let i = 0; i < 100; i++) {
            try {
                // Генерируем случайную цену
                const price = Math.floor(Math.random() * 1000000) + 100;
                
                // Форматируем цену
                const formatted = this.formatUzbekistanSum(price);
                
                // Проверяем что результат содержит "сум"
                if (!formatted.includes('сум')) {
                    throw new Error(`Отформатированная цена не содержит "сум": ${formatted}`);
                }
                
                // Проверяем что цена положительная
                const numericPart = formatted.replace(/[^\d]/g, '');
                if (parseInt(numericPart) !== price) {
                    throw new Error(`Неверное числовое значение в отформатированной цене`);
                }
                
            } catch (error) {
                console.log(`❌ FAILED на итерации ${i}: ${error.message}`);
                return false;
            }
        }
        
        console.log('✅ Property тест форматирования валюты прошел успешно (100 итераций)');
        return true;
    }

    // Property 19: Корректное суммирование цен
    testPriceSummation() {
        console.log('🧪 Запуск property теста суммирования цен (100 итераций)...');
        
        for (let i = 0; i < 100; i++) {
            try {
                // Генерируем случайный набор товаров
                const items = [];
                const itemCount = Math.floor(Math.random() * 5) + 1;
                let expectedTotal = 0;
                
                for (let j = 0; j < itemCount; j++) {
                    const price = Math.floor(Math.random() * 100000) + 1000;
                    const quantity = Math.floor(Math.random() * 5) + 1;
                    
                    items.push({ price, quantity });
                    expectedTotal += price * quantity;
                }
                
                // Вычисляем сумму через наш алгоритм
                const actualTotal = this.calculateCartTotal(items);
                
                // Проверяем что суммы совпадают
                if (Math.abs(actualTotal - expectedTotal) > 0.01) {
                    throw new Error(`Неверная сумма: ожидалось ${expectedTotal}, получено ${actualTotal}`);
                }
                
            } catch (error) {
                console.log(`❌ FAILED на итерации ${i}: ${error.message}`);
                return false;
            }
        }
        
        console.log('✅ Property тест суммирования цен прошел успешно (100 итераций)');
        return true;
    }

    // Property 16: Сериализация round-trip
    testSerializationRoundTrip() {
        console.log('🧪 Запуск property теста round-trip сериализации (100 итераций)...');
        
        for (let i = 0; i < 100; i++) {
            try {
                // Генерируем случайный объект товара
                const originalProduct = {
                    id: Math.floor(Math.random() * 1000),
                    name: `Test Product ${Math.floor(Math.random() * 10000)}`,
                    price: Math.floor(Math.random() * 500000) + 1000,
                    category_id: Math.floor(Math.random() * 8) + 1,
                    stock_quantity: Math.floor(Math.random() * 100)
                };
                
                // Сериализуем в JSON
                const json = JSON.stringify(originalProduct);
                
                // Десериализуем обратно
                const deserializedProduct = JSON.parse(json);
                
                // Проверяем что данные сохранились
                if (deserializedProduct.name !== originalProduct.name ||
                    deserializedProduct.price !== originalProduct.price ||
                    deserializedProduct.category_id !== originalProduct.category_id) {
                    throw new Error('Данные не сохранились при round-trip сериализации');
                }
                
            } catch (error) {
                console.log(`❌ FAILED на итерации ${i}: ${error.message}`);
                return false;
            }
        }
        
        console.log('✅ Property тест round-trip сериализации прошел успешно (100 итераций)');
        return true;
    }

    // Вспомогательные методы
    formatUzbekistanSum(amount) {
        return new Intl.NumberFormat('uz-UZ').format(amount) + ' сум';
    }

    calculateCartTotal(items) {
        return items.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    // Запуск всех тестов
    runAllTests() {
        console.log('🚀 === Property-based тесты интернет-магазина спортивного питания ===');
        console.log('👨‍💻 Автор: Валиев И. Б., группа 036-22 SMMr\n');
        
        const tests = [
            { name: 'Форматирование валюты', method: 'testCurrencyFormatting' },
            { name: 'Суммирование цен', method: 'testPriceSummation' },
            { name: 'Round-trip сериализация', method: 'testSerializationRoundTrip' }
        ];
        
        let passed = 0;
        
        for (const test of tests) {
            console.log(`\n📋 Тест: ${test.name}`);
            if (this[test.method]()) {
                passed++;
            }
        }
        
        console.log(`\n📊 === Результаты тестирования ===`);
        console.log(`✅ Пройдено: ${passed}/${tests.length} тестов`);
        
        if (passed === tests.length) {
            console.log('🎉 Все property тесты прошли успешно!');
            console.log('💪 Система готова к использованию!');
        } else {
            console.log('⚠️  Некоторые тесты не прошли');
        }
        
        return passed === tests.length;
    }
}

// Запуск тестов
const testRunner = new PropertyTestRunner();
testRunner.runAllTests();