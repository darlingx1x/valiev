/**
 * Симулятор MySQL базы данных для демонстрации
 * Автор: Валиев И. Б., группа 036-22 SMMr
 */

const fs = require('fs');
const path = require('path');

class DatabaseSimulator {
    constructor() {
        this.dataPath = path.join(__dirname, 'data');
        this.logPath = path.join(__dirname, 'logs');
        
        // Создаем папки если их нет
        if (!fs.existsSync(this.dataPath)) {
            fs.mkdirSync(this.dataPath, { recursive: true });
        }
        if (!fs.existsSync(this.logPath)) {
            fs.mkdirSync(this.logPath, { recursive: true });
        }
        
        this.initializeDatabase();
    }
    
    initializeDatabase() {
        console.log('🗄️ Инициализация симулятора MySQL базы данных...');
        
        // Инициализируем таблицы
        this.initTable('categories', [
            { id: 1, name: '🥛 Протеины', description: 'Белковые добавки для роста мышечной массы', created_at: new Date().toISOString() },
            { id: 2, name: '💊 Аминокислоты', description: 'BCAA, глютамин и другие аминокислоты', created_at: new Date().toISOString() },
            { id: 3, name: '⚡ Креатин', description: 'Креатин моногидрат и другие формы креатина', created_at: new Date().toISOString() },
            { id: 4, name: '🌟 Витамины', description: 'Витаминно-минеральные комплексы', created_at: new Date().toISOString() },
            { id: 5, name: '📈 Гейнеры', description: 'Углеводно-белковые смеси для набора массы', created_at: new Date().toISOString() },
            { id: 6, name: '🔥 Жиросжигатели', description: 'Добавки для снижения веса', created_at: new Date().toISOString() },
            { id: 7, name: '⚡ Энергетики', description: 'Предтренировочные комплексы и энергетики', created_at: new Date().toISOString() },
            { id: 8, name: '🍫 Батончики', description: 'Протеиновые батончики и снеки', created_at: new Date().toISOString() }
        ]);
        
        this.initTable('products', [
            { id: 1, name: 'Whey Protein 2kg', description: 'Сывороточный протеин высокого качества, 80% белка', price: 450000, category_id: 1, stock_quantity: 50, emoji: '🥛', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
            { id: 2, name: 'Casein Protein 1.8kg', description: 'Казеиновый протеин медленного усвоения', price: 520000, category_id: 1, stock_quantity: 30, emoji: '🥛', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
            { id: 3, name: 'Isolate Protein 2kg', description: 'Изолят сывороточного протеина, 90% белка', price: 650000, category_id: 1, stock_quantity: 25, emoji: '🥛', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
            { id: 4, name: 'BCAA 2:1:1 500g', description: 'Комплекс незаменимых аминокислот', price: 280000, category_id: 2, stock_quantity: 60, emoji: '💊', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
            { id: 5, name: 'Glutamine 300g', description: 'L-глютамин для восстановления', price: 180000, category_id: 2, stock_quantity: 40, emoji: '💊', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
            { id: 6, name: 'Creatine Monohydrate 500g', description: 'Креатин моногидрат микронизированный', price: 150000, category_id: 3, stock_quantity: 80, emoji: '⚡', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
            { id: 7, name: 'Multivitamin Complex', description: 'Комплекс витаминов и минералов, 90 капсул', price: 120000, category_id: 4, stock_quantity: 100, emoji: '🌟', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
            { id: 8, name: 'Mass Gainer 3kg', description: 'Углеводно-белковая смесь для набора массы', price: 380000, category_id: 5, stock_quantity: 40, emoji: '📈', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
            { id: 9, name: 'L-Carnitine 500ml', description: 'Л-карнитин жидкий для жиросжигания', price: 140000, category_id: 6, stock_quantity: 55, emoji: '🔥', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
            { id: 10, name: 'Pre-Workout Extreme', description: 'Предтренировочный комплекс, 300g', price: 280000, category_id: 7, stock_quantity: 50, emoji: '⚡', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
            { id: 11, name: 'Protein Bar Chocolate', description: 'Протеиновый батончик шоколад, 20g белка', price: 15000, category_id: 8, stock_quantity: 200, emoji: '🍫', created_at: new Date().toISOString(), updated_at: new Date().toISOString() },
            { id: 12, name: 'Protein Bar Vanilla', description: 'Протеиновый батончик ваниль, 20g белка', price: 15000, category_id: 8, stock_quantity: 180, emoji: '🍦', created_at: new Date().toISOString(), updated_at: new Date().toISOString() }
        ]);
        
        this.initTable('orders', []);
        this.initTable('order_items', []);
        this.initTable('cart_items', []);
        this.initTable('operation_logs', []);
        
        console.log('✅ База данных инициализирована');
        this.logOperation('SYSTEM', 'DATABASE_INIT', null, { message: 'Database initialized successfully' });
    }
    
    initTable(tableName, initialData = []) {
        const filePath = path.join(this.dataPath, `${tableName}.json`);
        
        if (!fs.existsSync(filePath)) {
            fs.writeFileSync(filePath, JSON.stringify(initialData, null, 2));
            console.log(`📄 Создана таблица: ${tableName}`);
        }
    }
    
    // SELECT операции
    select(tableName, conditions = {}, options = {}) {
        try {
            const data = this.readTable(tableName);
            let result = [...data];
            
            // Применяем условия WHERE
            if (Object.keys(conditions).length > 0) {
                result = result.filter(row => {
                    return Object.entries(conditions).every(([key, value]) => {
                        if (typeof value === 'object' && value.operator) {
                            switch (value.operator) {
                                case 'LIKE':
                                    return row[key] && row[key].toLowerCase().includes(value.value.toLowerCase());
                                case '>':
                                    return row[key] > value.value;
                                case '<':
                                    return row[key] < value.value;
                                case '>=':
                                    return row[key] >= value.value;
                                case '<=':
                                    return row[key] <= value.value;
                                case '!=':
                                    return row[key] !== value.value;
                                default:
                                    return row[key] === value.value;
                            }
                        }
                        return row[key] === value;
                    });
                });
            }
            
            // Применяем ORDER BY
            if (options.orderBy) {
                const [field, direction = 'ASC'] = options.orderBy.split(' ');
                result.sort((a, b) => {
                    if (direction.toUpperCase() === 'DESC') {
                        return b[field] > a[field] ? 1 : -1;
                    }
                    return a[field] > b[field] ? 1 : -1;
                });
            }
            
            // Применяем LIMIT
            if (options.limit) {
                result = result.slice(0, options.limit);
            }
            
            this.logOperation('SELECT', tableName, null, { conditions, options, resultCount: result.length });
            return result;
            
        } catch (error) {
            this.logOperation('SELECT_ERROR', tableName, null, { error: error.message, conditions, options });
            throw error;
        }
    }
    
    // INSERT операции
    insert(tableName, data) {
        try {
            const table = this.readTable(tableName);
            
            // Генерируем ID если его нет
            if (!data.id) {
                const maxId = table.length > 0 ? Math.max(...table.map(row => row.id || 0)) : 0;
                data.id = maxId + 1;
            }
            
            // Добавляем временные метки
            data.created_at = new Date().toISOString();
            if (tableName === 'products') {
                data.updated_at = new Date().toISOString();
            }
            
            table.push(data);
            this.writeTable(tableName, table);
            
            this.logOperation('INSERT', tableName, data.id, data);
            return data;
            
        } catch (error) {
            this.logOperation('INSERT_ERROR', tableName, null, { error: error.message, data });
            throw error;
        }
    }
    
    // UPDATE операции
    update(tableName, id, data) {
        try {
            const table = this.readTable(tableName);
            const index = table.findIndex(row => row.id === id);
            
            if (index === -1) {
                throw new Error(`Record with id ${id} not found in ${tableName}`);
            }
            
            // Обновляем данные
            const oldData = { ...table[index] };
            table[index] = { ...table[index], ...data };
            
            // Обновляем временную метку
            if (tableName === 'products') {
                table[index].updated_at = new Date().toISOString();
            }
            
            this.writeTable(tableName, table);
            
            this.logOperation('UPDATE', tableName, id, { oldData, newData: table[index] });
            return table[index];
            
        } catch (error) {
            this.logOperation('UPDATE_ERROR', tableName, id, { error: error.message, data });
            throw error;
        }
    }
    
    // DELETE операции
    delete(tableName, id) {
        try {
            const table = this.readTable(tableName);
            const index = table.findIndex(row => row.id === id);
            
            if (index === -1) {
                throw new Error(`Record with id ${id} not found in ${tableName}`);
            }
            
            const deletedData = table[index];
            table.splice(index, 1);
            this.writeTable(tableName, table);
            
            this.logOperation('DELETE', tableName, id, deletedData);
            return deletedData;
            
        } catch (error) {
            this.logOperation('DELETE_ERROR', tableName, id, { error: error.message });
            throw error;
        }
    }
    
    // JOIN операции
    join(leftTable, rightTable, leftKey, rightKey, type = 'INNER') {
        try {
            const left = this.readTable(leftTable);
            const right = this.readTable(rightTable);
            const result = [];
            
            left.forEach(leftRow => {
                const matches = right.filter(rightRow => rightRow[rightKey] === leftRow[leftKey]);
                
                if (matches.length > 0) {
                    matches.forEach(rightRow => {
                        result.push({
                            ...leftRow,
                            [`${rightTable}_${rightKey}`]: rightRow
                        });
                    });
                } else if (type === 'LEFT') {
                    result.push({
                        ...leftRow,
                        [`${rightTable}_${rightKey}`]: null
                    });
                }
            });
            
            this.logOperation('JOIN', `${leftTable}_${rightTable}`, null, { 
                type, leftKey, rightKey, resultCount: result.length 
            });
            
            return result;
            
        } catch (error) {
            this.logOperation('JOIN_ERROR', `${leftTable}_${rightTable}`, null, { 
                error: error.message, type, leftKey, rightKey 
            });
            throw error;
        }
    }
    
    // Транзакции
    transaction(operations) {
        const backups = {};
        
        try {
            // Создаем бэкапы всех затронутых таблиц
            const tables = [...new Set(operations.map(op => op.table))];
            tables.forEach(table => {
                backups[table] = this.readTable(table);
            });
            
            // Выполняем операции
            const results = [];
            operations.forEach(operation => {
                switch (operation.type) {
                    case 'INSERT':
                        results.push(this.insert(operation.table, operation.data));
                        break;
                    case 'UPDATE':
                        results.push(this.update(operation.table, operation.id, operation.data));
                        break;
                    case 'DELETE':
                        results.push(this.delete(operation.table, operation.id));
                        break;
                    default:
                        throw new Error(`Unknown operation type: ${operation.type}`);
                }
            });
            
            this.logOperation('TRANSACTION_COMMIT', 'MULTIPLE', null, { 
                operationsCount: operations.length,
                tables: tables
            });
            
            return results;
            
        } catch (error) {
            // Откатываем изменения
            Object.entries(backups).forEach(([table, data]) => {
                this.writeTable(table, data);
            });
            
            this.logOperation('TRANSACTION_ROLLBACK', 'MULTIPLE', null, { 
                error: error.message,
                operationsCount: operations.length,
                tables: Object.keys(backups)
            });
            
            throw error;
        }
    }
    
    // Вспомогательные методы
    readTable(tableName) {
        const filePath = path.join(this.dataPath, `${tableName}.json`);
        if (!fs.existsSync(filePath)) {
            return [];
        }
        return JSON.parse(fs.readFileSync(filePath, 'utf8'));
    }
    
    writeTable(tableName, data) {
        const filePath = path.join(this.dataPath, `${tableName}.json`);
        fs.writeFileSync(filePath, JSON.stringify(data, null, 2));
    }
    
    logOperation(operation, table, recordId, data) {
        const logEntry = {
            id: Date.now(),
            timestamp: new Date().toISOString(),
            operation,
            table,
            record_id: recordId,
            data: JSON.stringify(data),
            ip: '127.0.0.1'
        };
        
        // Записываем в таблицу логов
        try {
            const logs = this.readTable('operation_logs');
            logs.push(logEntry);
            
            // Оставляем только последние 1000 записей
            if (logs.length > 1000) {
                logs.splice(0, logs.length - 1000);
            }
            
            this.writeTable('operation_logs', logs);
        } catch (error) {
            console.error('Ошибка записи лога:', error);
        }
        
        // Также записываем в файл логов
        const logFile = path.join(this.logPath, `database_${new Date().toISOString().split('T')[0]}.log`);
        const logLine = `${logEntry.timestamp} [${operation}] ${table} ${recordId || ''} ${JSON.stringify(data)}\n`;
        
        try {
            fs.appendFileSync(logFile, logLine);
        } catch (error) {
            console.error('Ошибка записи в файл лога:', error);
        }
    }
    
    // Статистика
    getStats() {
        try {
            const stats = {
                tables: {},
                totalRecords: 0,
                diskUsage: 0,
                lastOperation: null
            };
            
            // Статистика по таблицам
            const tables = ['categories', 'products', 'orders', 'order_items', 'cart_items', 'operation_logs'];
            tables.forEach(table => {
                const data = this.readTable(table);
                const filePath = path.join(this.dataPath, `${table}.json`);
                const fileSize = fs.existsSync(filePath) ? fs.statSync(filePath).size : 0;
                
                stats.tables[table] = {
                    records: data.length,
                    size: fileSize
                };
                stats.totalRecords += data.length;
                stats.diskUsage += fileSize;
            });
            
            // Последняя операция
            const logs = this.readTable('operation_logs');
            if (logs.length > 0) {
                stats.lastOperation = logs[logs.length - 1];
            }
            
            return stats;
            
        } catch (error) {
            console.error('Ошибка получения статистики:', error);
            return null;
        }
    }
    
    // Экспорт в SQL
    exportToSQL() {
        try {
            let sql = '-- Экспорт базы данных sports_nutrition_store\n';
            sql += `-- Создано: ${new Date().toISOString()}\n`;
            sql += `-- Автор: Валиев И. Б., группа 036-22 SMMr\n\n`;
            
            const tables = ['categories', 'products', 'orders', 'order_items', 'cart_items'];
            
            tables.forEach(table => {
                const data = this.readTable(table);
                if (data.length > 0) {
                    sql += `-- Данные для таблицы ${table}\n`;
                    
                    data.forEach(row => {
                        const columns = Object.keys(row).join(', ');
                        const values = Object.values(row).map(val => {
                            if (val === null) return 'NULL';
                            if (typeof val === 'string') return `'${val.replace(/'/g, "''")}'`;
                            return val;
                        }).join(', ');
                        
                        sql += `INSERT INTO ${table} (${columns}) VALUES (${values});\n`;
                    });
                    
                    sql += '\n';
                }
            });
            
            return sql;
            
        } catch (error) {
            console.error('Ошибка экспорта в SQL:', error);
            return null;
        }
    }
}

module.exports = DatabaseSimulator;