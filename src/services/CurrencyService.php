<?php
/**
 * Сервис для работы с узбекскими сумами
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

class CurrencyService {
    
    /**
     * Форматирует цену в узбекских сумах
     * @param float $amount Сумма
     * @return string Отформатированная цена
     */
    public static function formatUzbekistanSum($amount) {
        if (!is_numeric($amount)) {
            throw new Exception('Сумма должна быть числом');
        }
        
        $amount = (float)$amount;
        
        if ($amount < 0) {
            throw new Exception('Сумма не может быть отрицательной');
        }
        
        // Форматируем с разделителями тысяч
        return number_format($amount, 0, ',', ' ') . ' сум';
    }
    
    /**
     * Валидирует ввод цены в узбекских сумах
     * @param mixed $input Пользовательский ввод
     * @return array Результат валидации
     */
    public static function validatePriceInput($input) {
        $errors = [];
        
        // Очищаем ввод от лишних символов
        $cleaned = self::cleanPriceInput($input);
        
        if (empty($cleaned)) {
            $errors[] = 'Цена не может быть пустой';
            return ['valid' => false, 'errors' => $errors, 'value' => null];
        }
        
        if (!is_numeric($cleaned)) {
            $errors[] = 'Цена должна быть числом';
            return ['valid' => false, 'errors' => $errors, 'value' => null];
        }
        
        $value = (float)$cleaned;
        
        if ($value < 0) {
            $errors[] = 'Цена не может быть отрицательной';
        }
        
        if ($value > 100000000) { // 100 миллионов сум
            $errors[] = 'Цена слишком большая (максимум 100,000,000 сум)';
        }
        
        // Проверяем разумность цены (минимум 100 сум)
        if ($value > 0 && $value < 100) {
            $errors[] = 'Минимальная цена 100 сум';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'value' => $value
        ];
    }
    
    /**
     * Очищает пользовательский ввод цены
     * @param string $input Пользовательский ввод
     * @return string Очищенное значение
     */
    public static function cleanPriceInput($input) {
        if (!is_string($input)) {
            $input = (string)$input;
        }
        
        // Удаляем слово "сум" и различные пробелы
        $cleaned = preg_replace('/\s*сум\s*$/ui', '', $input);
        
        // Удаляем пробелы, используемые как разделители тысяч
        $cleaned = str_replace(' ', '', $cleaned);
        
        // Заменяем запятые на точки для десятичных дробей
        $cleaned = str_replace(',', '.', $cleaned);
        
        // Удаляем все нечисловые символы кроме точки
        $cleaned = preg_replace('/[^0-9.]/', '', $cleaned);
        
        return trim($cleaned);
    }
    
    /**
     * Конвертирует цену из строки в число
     * @param string $priceString Строка с ценой
     * @return float Числовое значение
     */
    public static function parsePrice($priceString) {
        $validation = self::validatePriceInput($priceString);
        
        if (!$validation['valid']) {
            throw new Exception('Некорректная цена: ' . implode(', ', $validation['errors']));
        }
        
        return $validation['value'];
    }
    
    /**
     * Суммирует массив цен
     * @param array $prices Массив цен
     * @return float Общая сумма
     */
    public static function sumPrices($prices) {
        if (!is_array($prices)) {
            throw new Exception('Ожидается массив цен');
        }
        
        $total = 0;
        
        foreach ($prices as $price) {
            if (!is_numeric($price)) {
                throw new Exception('Все элементы должны быть числами');
            }
            
            $total += (float)$price;
        }
        
        return $total;
    }
    
    /**
     * Вычисляет подытог (цена × количество)
     * @param float $price Цена за единицу
     * @param int $quantity Количество
     * @return float Подытог
     */
    public static function calculateSubtotal($price, $quantity) {
        if (!is_numeric($price) || !is_numeric($quantity)) {
            throw new Exception('Цена и количество должны быть числами');
        }
        
        $price = (float)$price;
        $quantity = (int)$quantity;
        
        if ($price < 0) {
            throw new Exception('Цена не может быть отрицательной');
        }
        
        if ($quantity < 0) {
            throw new Exception('Количество не может быть отрицательным');
        }
        
        return $price * $quantity;
    }
    
    /**
     * Форматирует подытог с указанием количества
     * @param float $price Цена за единицу
     * @param int $quantity Количество
     * @return string Отформатированный подытог
     */
    public static function formatSubtotal($price, $quantity) {
        $subtotal = self::calculateSubtotal($price, $quantity);
        $formattedPrice = self::formatUzbekistanSum($price);
        $formattedSubtotal = self::formatUzbekistanSum($subtotal);
        
        if ($quantity === 1) {
            return $formattedSubtotal;
        }
        
        return "{$formattedPrice} × {$quantity} = {$formattedSubtotal}";
    }
    
    /**
     * Проверяет корректность суммы в узбекских сумах
     * @param float $amount Сумма для проверки
     * @return bool Корректность суммы
     */
    public static function isValidUzbekistanAmount($amount) {
        if (!is_numeric($amount)) {
            return false;
        }
        
        $amount = (float)$amount;
        
        return $amount >= 0 && $amount <= 100000000;
    }
}
?>