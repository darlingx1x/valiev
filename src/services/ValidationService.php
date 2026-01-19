<?php
/**
 * Сервис валидации данных
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

class ValidationService {
    
    /**
     * Валидирует JSON данные согласно схеме
     * @param string $jsonData JSON строка
     * @param array $schema Схема валидации
     * @return array Результат валидации
     */
    public static function validateJson($jsonData, $schema) {
        $errors = [];
        
        // Проверяем что это валидный JSON
        $data = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Некорректный JSON: ' . json_last_error_msg();
            return ['valid' => false, 'errors' => $errors, 'data' => null];
        }
        
        // Валидируем по схеме
        $schemaErrors = self::validateDataAgainstSchema($data, $schema);
        $errors = array_merge($errors, $schemaErrors);
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $data
        ];
    }
    
    /**
     * Валидирует данные против схемы
     * @param mixed $data Данные для валидации
     * @param array $schema Схема валидации
     * @return array Массив ошибок
     */
    private static function validateDataAgainstSchema($data, $schema) {
        $errors = [];
        
        // Проверяем обязательные поля
        if (isset($schema['required'])) {
            foreach ($schema['required'] as $field) {
                if (!isset($data[$field])) {
                    $errors[] = "Обязательное поле '$field' отсутствует";
                }
            }
        }
        
        // Проверяем типы полей
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $field => $fieldSchema) {
                if (isset($data[$field])) {
                    $fieldErrors = self::validateField($data[$field], $fieldSchema, $field);
                    $errors = array_merge($errors, $fieldErrors);
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Валидирует отдельное поле
     * @param mixed $value Значение поля
     * @param array $schema Схема поля
     * @param string $fieldName Имя поля
     * @return array Массив ошибок
     */
    private static function validateField($value, $schema, $fieldName) {
        $errors = [];
        
        // Проверяем тип
        if (isset($schema['type'])) {
            if (!self::checkType($value, $schema['type'])) {
                $errors[] = "Поле '$fieldName' должно быть типа {$schema['type']}";
            }
        }
        
        // Проверяем минимальную длину
        if (isset($schema['minLength']) && is_string($value)) {
            if (strlen($value) < $schema['minLength']) {
                $errors[] = "Поле '$fieldName' должно содержать минимум {$schema['minLength']} символов";
            }
        }
        
        // Проверяем максимальную длину
        if (isset($schema['maxLength']) && is_string($value)) {
            if (strlen($value) > $schema['maxLength']) {
                $errors[] = "Поле '$fieldName' не должно превышать {$schema['maxLength']} символов";
            }
        }
        
        // Проверяем минимальное значение
        if (isset($schema['minimum']) && is_numeric($value)) {
            if ($value < $schema['minimum']) {
                $errors[] = "Поле '$fieldName' должно быть не менее {$schema['minimum']}";
            }
        }
        
        // Проверяем максимальное значение
        if (isset($schema['maximum']) && is_numeric($value)) {
            if ($value > $schema['maximum']) {
                $errors[] = "Поле '$fieldName' должно быть не более {$schema['maximum']}";
            }
        }
        
        // Проверяем паттерн
        if (isset($schema['pattern']) && is_string($value)) {
            if (!preg_match($schema['pattern'], $value)) {
                $errors[] = "Поле '$fieldName' не соответствует требуемому формату";
            }
        }
        
        return $errors;
    }
    
    /**
     * Проверяет тип значения
     * @param mixed $value Значение
     * @param string $expectedType Ожидаемый тип
     * @return bool
     */
    private static function checkType($value, $expectedType) {
        switch ($expectedType) {
            case 'string':
                return is_string($value);
            case 'number':
                return is_numeric($value);
            case 'integer':
                return is_int($value) || (is_string($value) && ctype_digit($value));
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            case 'object':
                return is_array($value) || is_object($value);
            default:
                return true;
        }
    }
    
    /**
     * Валидирует файл изображения
     * @param array $file Данные файла из $_FILES
     * @return array Результат валидации
     */
    public static function validateImageFile($file) {
        $errors = [];
        
        // Проверяем что файл загружен
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Файл не был загружен';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Проверяем размер файла (максимум 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $errors[] = 'Размер файла не должен превышать 5MB';
        }
        
        // Проверяем тип файла
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Недопустимый тип файла. Разрешены: JPEG, PNG, GIF, WebP';
        }
        
        // Проверяем расширение файла
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Недопустимое расширение файла';
        }
        
        // Проверяем что это действительно изображение
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $errors[] = 'Файл не является изображением';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $file['size']
        ];
    }
    
    /**
     * Сериализует объект в JSON
     * @param mixed $object Объект для сериализации
     * @return string JSON строка
     */
    public static function serializeToJson($object) {
        if (is_object($object) && method_exists($object, 'toArray')) {
            $data = $object->toArray();
        } else {
            $data = $object;
        }
        
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    /**
     * Десериализует JSON в объект
     * @param string $json JSON строка
     * @param string $className Имя класса для создания объекта
     * @return mixed Объект или массив
     */
    public static function deserializeFromJson($json, $className = null) {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ошибка десериализации JSON: ' . json_last_error_msg());
        }
        
        if ($className && class_exists($className)) {
            return new $className($data);
        }
        
        return $data;
    }
    
    /**
     * Экранирует специальные символы для предотвращения XSS
     * @param string $input Входная строка
     * @return string Экранированная строка
     */
    public static function escapeHtml($input) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Очищает и валидирует пользовательский ввод
     * @param string $input Пользовательский ввод
     * @return string Очищенный ввод
     */
    public static function sanitizeInput($input) {
        // Удаляем пробелы в начале и конце
        $input = trim($input);
        
        // Удаляем обратные слеши
        $input = stripslashes($input);
        
        // Экранируем HTML символы
        $input = self::escapeHtml($input);
        
        return $input;
    }
}
?>