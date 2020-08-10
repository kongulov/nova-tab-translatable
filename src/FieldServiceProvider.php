<?php

namespace Kongulov\NovaTabTranslatable;

use Illuminate\Support\ServiceProvider;
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
        $this->publishes([
            __DIR__ . '/../config/tab-translatable.php' => config_path('tab-translatable.php'),
        ], 'tab-translatable-config');
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
