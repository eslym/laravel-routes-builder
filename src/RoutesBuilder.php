<?php


namespace Eslym\Laravel\RoutesBuilder;


use Composer\Autoload\ClassMapGenerator;
use Eslym\Laravel\RoutesBuilder\Annotations\Fallback;
use Eslym\Laravel\RoutesBuilder\Annotations\Group;
use Eslym\Laravel\RoutesBuilder\Annotations\Middleware;
use Eslym\Laravel\RoutesBuilder\Annotations\Route;
use Eslym\LightStream\Invoke;
use Eslym\LightStream\Stream;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use JetBrains\PhpStorm\Pure;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

class RoutesBuilder
{
    protected array $groups = [];

    #[Pure]
    public function __construct(protected Router $router)
    {
        $this->groups[BaseController::class] = [new Group()];
    }

    public function register(string|ReflectionClass $class): static {
        $this->__register($class);
        return $this;
    }

    public function commit(Router $router = null): static {
        $this->__commit($this->groups[BaseController::class], $router ?? $this->router);
        return $this;
    }

    public function discover(?string $path = null, bool $restrictControllerClasses = true): static {
        $path = $path ?? app_path('Http/Controllers');
        $classes = array_keys(ClassMapGenerator::createMap($path));
        if($restrictControllerClasses) {
            foreach ($classes as $class) {
                $kelas = new ReflectionClass($class);
                if($kelas->isSubclassOf(BaseController::class)) {
                    $this->__register($class);
                }
            }
        } else {
            foreach ($classes as $class) {
                $this->__register($class);
            }
        }
        return $this;
    }

    private function __register(string|ReflectionClass $class): string {
        $kelas = $class instanceof ReflectionClass ? $class : new ReflectionClass($class);
        if($kelas->name === BaseController::class){
            return $kelas->name;
        }
        $parent = $kelas->getParentClass() ? $this->__register($kelas->getParentClass()) : BaseController::class;
        if(isset($this->groups[$kelas->name])){
            return $kelas->name;
        }
        $groups = &$this->groups[$kelas->name];
        $groups = static::getAllGroups($kelas);
        while(is_string($this->groups[$parent])){
            $parent = $this->groups[$parent];
        }
        foreach ($this->groups[$parent] as $node){
            /** @var Group $node */
            $node->children = array_merge($node->children, $groups);
        }
        $routes = static::getAllRoutes($kelas);
        if(count($groups) == 0){
            foreach ($this->groups[$parent] as $node){
                /** @var Group $node */
                $node->children = array_merge($node->children, $routes);
            }
            $groups = $parent;
            return $parent;
        }
        foreach ($groups as $node){
            /** @var Group $node */
            $node->children = array_merge($node->children, $routes);
        }
        return $kelas->name;
    }

    private function __commit(array $nodes, Router $router){
        foreach ($nodes as $node){
            if($node instanceof Group){
                $registrar = new RouteRegistrar($router);
                if(!empty($node->middleware)){
                    $registrar->middleware($node->middleware);
                }
                if ($node->prefix !== null) {
                    $registrar->prefix($node->prefix);
                }
                if ($node->domain !== null) {
                    $registrar->domain($node->domain);
                }
                if ($node->name !== null) {
                    $registrar->name($node->name);
                }
                if (!empty($node->where)) {
                    $registrar->where($node->where);
                }
                $registrar->group(function(Router $router) use ($node){
                    $this->__commit($node->children, $router);
                });
            } elseif ($node instanceof Route) {
                $route = $router->addRoute($node->method, $node->path, $node->action);
                if(!empty($node->middleware)){
                    $route->middleware($node->middleware);
                }
                if($node->name !== null){
                    $route->name($node->name);
                }
                if(!empty($node->defaults)){
                    $route->setDefaults($node->defaults);
                }
                if($node->domain !== null){
                    $route->domain($node->domain);
                }
                if(!empty($node->where)){
                    $route->where($node->where);
                }
            } elseif ($node instanceof Fallback) {
                $route = $router->fallback($node->action);
                if(!empty($node->middleware)){
                    $route->middleware($node->middleware);
                }
                if($node->name !== null){
                    $route->name($node->name);
                }
            }
        }
    }

    /** @noinspection PhpUndefinedMethodInspection */
    private static function getAllGroups(ReflectionClass $class): array {
        $middlewares = Stream::of($class->getAttributes(Middleware::class))
            ->map(Invoke::newInstance())
            ->map(fn(Middleware $middleware) => $middleware->middleware)
            ->collect();
        return Stream::of($class->getAttributes(Group::class))
            ->map(Invoke::newInstance())
            ->map(function(Group $group) use ($middlewares){
                $group->middleware = self::mergeMiddleware($middlewares, $group);
                return $group;
            })
            ->collect();
    }

    private static function getAllRoutes(ReflectionClass $class): array {
        return Stream::of($class->getMethods())
            ->map(function (ReflectionMethod $method) use ($class) {
                if($method->getDeclaringClass()->name !== $class->name) return;
                $middlewares = Stream::of($method->getAttributes(Middleware::class))
                    ->map(fn(ReflectionAttribute $attr) => $attr->newInstance())
                    ->map(fn(Middleware $middleware) => $middleware->middleware)
                    ->collect();
                $fallback = Stream::of($method->getAttributes(Fallback::class))
                    ->map(function (ReflectionAttribute $attr) use ($class, $method, $middlewares){
                        /** @var Fallback $route */
                        $route = $attr->newInstance();
                        $route->action = [$class->name, $method->name];
                        $route->middleware = static::mergeMiddleware($middlewares, $route);
                        return $route;
                    });
                /** @noinspection PhpParamsInspection */
                return Stream::of($method->getAttributes(Route::class))
                    ->map(function (ReflectionAttribute $attr) use ($class, $method, $middlewares){
                        /** @var Route $route */
                        $route = $attr->newInstance();
                        $route->action = [$class->name, $method->name];
                        $route->middleware = static::mergeMiddleware($middlewares, $route);
                        return $route;
                    })->concat($fallback);
            })
            ->flatten()
            ->collect();
    }

    #[Pure]
    private static function mergeMiddleware(array $middlewares, Route|Group|Fallback $src): array{
        if (is_array($src->middleware)) {
            return array_merge($src->middleware, $middlewares);
        } elseif (is_string($src->middleware)) {
            return array_merge([$src->middleware], $middlewares);
        } elseif (is_null($src->middleware)){
            return $middlewares;
        } else {
            return array_merge([$src->middleware], $middlewares);
        }
    }
}
