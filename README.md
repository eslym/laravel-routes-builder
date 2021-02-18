# eslym/laravel-routes-builder
Routes builder powered by PHP 8.0 Attributes

**Note:** Please use this package with `php artisan route:cache`

## Installation
```shell
composer require eslym/laravel-routes-builder ^1.0
```

## Usage
```php
use Eslym\Laravel\RoutesBuilder\Facades\Routes;

Routes::discover(app_path('Http/Controllers'))
    ->commit();
```
