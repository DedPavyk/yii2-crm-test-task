Write-Host "Запуск Yii2 приложения на встроенном PHP-сервере..." -ForegroundColor Green
Write-Host "Откройте браузер и перейдите по адресу: http://localhost:8080" -ForegroundColor Yellow
Write-Host "Для остановки сервера нажмите Ctrl+C" -ForegroundColor Red
Write-Host ""
php -S localhost:8080 -t web
