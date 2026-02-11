@echo off
cd /d "c:\laragon\www\staff-reporting-management"
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
