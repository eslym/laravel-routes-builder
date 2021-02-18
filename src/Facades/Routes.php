<?php


namespace Eslym\Laravel\RoutesBuilder\Facades;


use Eslym\Laravel\RoutesBuilder\RoutesBuilder;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Facade;
use ReflectionClass;

/**
 * Class Routes
 * @package Eslym\Laravel\RoutesBuilder\Facades
 *
 * @method static RoutesBuilder register(ReflectionClass|string $class)
 * @method static RoutesBuilder discover(string $path)
 * @method static RoutesBuilder commit(Router $router)
 */
class Routes extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RoutesBuilder::class;
    }
}
