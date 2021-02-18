<?php


namespace Eslym\Laravel\RoutesBuilder;


use Illuminate\Support\ServiceProvider;

class RoutesBuilderServiceProvider extends ServiceProvider
{
    public function boot(){
        $this->app->singleton('routes.builder', function (){
            return new RoutesBuilder($this->app->make('router'));
        });
        $this->app->alias('routes.builder', RoutesBuilder::class);
    }
}
