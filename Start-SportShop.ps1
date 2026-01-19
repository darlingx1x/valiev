# Скрипт запуска интернет-магазина спортивного питания
# Автор: Валиев И. Б., группа 036-22 SMMr

param(
    [int]$Port = 8080,
    [switch]$OpenBrowser
)

Write-Host "🏋️ Интернет-магазин спортивного питания" -ForegroundColor Cyan
Write-Host "👨‍💻 Автор: Валиев И. Б., группа 036-22 SMMr" -ForegroundColor Yellow
Write-Host ""

Write-Host "🚀 Запуск сервера на порту $Port..." -ForegroundColor Green
Write-Host "📍 Адрес: http://localhost:$Port" -ForegroundColor White

# Устанавливаем переменную окружения
$env:PORT = $Port

# Запускаем сервер
$serverProcess = Start-Process -FilePath "node" -ArgumentList "server.js" -PassThru -NoNewWindow

if ($OpenBrowser) {
    Start-Sleep -Seconds 2
    Write-Host "🌐 Открываем браузер..." -ForegroundColor Magenta
    Start-Process "http://localhost:$Port"
}

Write-Host "✅ Сервер запущен! Нажмите Ctrl+C для остановки." -ForegroundColor Green
Write-Host ""

# Ожидаем завершения процесса
try {
    $serverProcess.WaitForExit()
} catch {
    Write-Host "❌ Ошибка: $($_.Exception.Message)" -ForegroundColor Red
} finally {
    Write-Host "🛑 Сервер остановлен." -ForegroundColor Yellow
}