@echo off
echo Запуск Yii2 приложения на встроенном PHP-сервере...
echo Откройте браузер и перейдите по адресу: http://localhost:8080
echo Для остановки сервера нажмите Ctrl+C
echo.
php -S localhost:8080 -t web
pause
