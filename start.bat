@echo off
echo 🏋️ Интернет-магазин спортивного питания
echo 👨‍💻 Автор: Валиев И. Б., группа 036-22 SMMr
echo.

if "%1"=="" (
    echo 🚀 Запуск на порту 8080 по умолчанию...
    set PORT=8080
) else (
    echo 🚀 Запуск на порту %1...
    set PORT=%1
)

echo 📍 Адрес будет: http://localhost:%PORT%
echo.

node server.js

pause