<?php

namespace App\Services;

use App\Services\Comisiones\ReglaComision;
use InvalidArgumentException;

/**
 * Busca la regla que corresponde al método de pago y le delega el
 * cálculo. No sabe qué métodos existen: las reglas se le pasan desde
 * afuera (AppServiceProvider), así que no se modifica para crecer.
 */
class CalculadoraComision
{
    /** @param ReglaComision[] $reglas */
    public function __construct(private array $reglas) {}

    public function calcular(string $metodo, float $monto): float
    {
        foreach ($this->reglas as $regla) {
            if ($regla->aplica($metodo)) {
                return $regla->calcular($monto);
            }
        }

        throw new InvalidArgumentException("Método de pago no soportado: {$metodo}");
    }
}
