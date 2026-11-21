<?php

namespace App\Services\Comisiones;

/**
 * El contrato que cumple cada método de pago.
 *
 * Para agregar un método nuevo se crea una clase que implemente esto;
 * no se toca el código que ya funciona (principio abierto/cerrado).
 */
interface ReglaComision
{
    /** ¿Esta regla corresponde al método de pago indicado? */
    public function aplica(string $metodo): bool;

    /** La comisión que cobra la pasarela por ese monto */
    public function calcular(float $monto): float;
}
