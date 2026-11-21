<?php

namespace App\Services\Comisiones;

/** Billetera digital: 2 % del monto, redondeado a centavos */
class ComisionBilletera implements ReglaComision
{
    public function aplica(string $metodo): bool
    {
        return $metodo === 'billetera';
    }

    public function calcular(float $monto): float
    {
        return round($monto * 0.02, 2);
    }
}
