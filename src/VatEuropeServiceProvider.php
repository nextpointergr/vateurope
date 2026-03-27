<?php
namespace NextPointer\VatEurope;

use Illuminate\Support\ServiceProvider;
use NextPointer\VatEurope\Services\VatEuropeService;

class VatEuropeServiceProvider extends ServiceProvider {
    public function register() {
        $this->app->singleton('vat-europe', function () {
            return new VatEuropeService();
        });
    }

    public function boot() {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/vat-europe.php' => config_path('vat-europe.php')], 'config');
        }
    }
}