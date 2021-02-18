<?php


namespace Eslym\Laravel\RoutesBuilder\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Route
{
    public array $action = [];

    public function __construct(
        public string $path = '/',
        public string|array $method = 'GET',
        public array|string|null $middleware = null,
        public ?string $name = null,
        public ?string $domain = null,
        public array $where = [],
        public array $defaults = []
    ) {}
}
