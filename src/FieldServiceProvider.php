<?php

namespace Kongulov\NovaTabTranslatable;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Kongulov\NovaTabTranslatable\Http\Middleware\Authorize;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Nova::serving(function (ServingNova $event) {
            Nova::script('nova-tab-translatable', __DIR__.'/../dist/js/field.js');
            Nova::style('nova-tab-translatable', __DIR__.'/../dist/css/field.css');
        });

        $this->app->booted(function () {
            $this->routes();
        });

        $this->publishes([
            __DIR__ . '/../config/tab-translatable.php' => config_path('tab-translatable.php'),
        ], 'tab-translatable-config');
    }

    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova'])
            ->namespace('Kongulov\NovaTabTranslatable\Http\Controllers')
            ->group(__DIR__.'/../routes/api.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
