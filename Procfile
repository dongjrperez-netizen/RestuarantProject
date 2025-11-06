release: php artisan migrate --force && php artisan db:seed --class=AdminSeeder --force
web: php artisan queue:work --daemon --tries=3 --timeout=90 & php artisan serve --host=0.0.0.0 --port=$PORT
