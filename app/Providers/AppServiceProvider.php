<?php

namespace App\Providers;

use App\Services\CalculadoraComision;
use App\Services\Comisiones\ComisionBilletera;
use App\Services\Comisiones\ComisionTarjeta;
use App\Services\Comisiones\ComisionTransferencia;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Laravel no puede adivinar qué va dentro del array $reglas, así
        // que se le dice cómo construir la calculadora. Este es el único
        // lugar donde se registra un método de pago nuevo.
        $this->app->singleton(CalculadoraComision::class, fn () => new CalculadoraComision([
            new ComisionTarjeta(),
            new ComisionTransferencia(),
            new ComisionBilletera(),
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
