<?php

namespace App\Enums;

/**
 * Los estados de una transacción, escritos en un solo lugar.
 *
 * Antes 'Iniciada', 'Completada' y 'Fallida' estaban escritos a mano en
 * validaciones, servicios, vistas y pruebas: un error de dedo pasaba
 * sin que nadie lo notara. Ahora PHP avisa si se usa uno que no existe.
 */
enum EstadoTransaccion: string
{
    case Iniciada = 'Iniciada';
    case Completada = 'Completada';
    case Fallida = 'Fallida';

    /**
     * Si se puede pasar de este estado al indicado.
     * Una Completada ya movió dinero: no se revierte. Una Fallida se
     * puede reintentar volviendo a Iniciada.
     */
    public function puedePasarA(self $nuevo): bool
    {
        $permitidos = match ($this) {
            self::Iniciada => [self::Completada, self::Fallida],
            self::Completada => [],
            self::Fallida => [self::Iniciada],
        };

        return in_array($nuevo, $permitidos, true);
    }
}
