@echo off
:: Move into your project folder
cd /d "C:\Users\zerc_\Herd\bov5"

:: Run the Laravel scheduler using the PHP version provided by Herd
php artisan currency:update >> "C:\Users\zerc_\Herd\bov5\storage\logs\scheduler.log" 2>&1