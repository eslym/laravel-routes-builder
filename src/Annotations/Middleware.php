<?php


namespace Eslym\Laravel\RoutesBuilder\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Middleware
{
    public array $params;

    public function __construct(public string $middleware, mixed ...$params)
    {
        $this->params = $params;
    }

    public function constructMiddleware(): string
    {
        return empty($this->params) ?
            $this->middleware :
            $this->middleware . ":" . join(',', $this->params);
    }
}
