<?php


namespace Eslym\Laravel\RoutesBuilder\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Group
{
    public array $children = [];

    public function __construct(
        public ?string $prefix = null,
        public array|string|null $middleware = null,
        public ?string $name = null,
        public ?string $domain = null,
        public array $where = []
    ) {}
}
