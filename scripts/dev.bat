@echo off
set PHP_CLI_SERVER_WORKERS=4
php -S localhost:8000 -t public public\router.php