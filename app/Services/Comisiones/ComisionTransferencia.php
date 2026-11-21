<?php

namespace App\Services\Comisiones;

/** Transferencia: comisión fija de 1.50, sin importar el monto */
class ComisionTransferencia implements ReglaComision
{
    public function aplica(string $metodo): bool
    {
        return $metodo === 'transferencia';
    }

    public function calcular(float $monto): float
    {
        return 1.50;
    }
}
