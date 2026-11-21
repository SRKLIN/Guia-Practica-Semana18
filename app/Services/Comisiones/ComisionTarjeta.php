<?php

namespace App\Services\Comisiones;

/** Tarjeta: 3.5 % del monto, redondeado a centavos */
class ComisionTarjeta implements ReglaComision
{
    public function aplica(string $metodo): bool
    {
        return $metodo === 'tarjeta';
    }

    public function calcular(float $monto): float
    {
        return round($monto * 0.035, 2);
    }
}
