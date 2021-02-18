<?php


namespace Eslym\Laravel\RoutesBuilder\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Fallback
{
    public array $action = [];

    public function __construct(
        public array|string|null $middleware = null,
        public array $where = [],
        public ?string $name = null
    ) {}
}
