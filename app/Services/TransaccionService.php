<?php

namespace App\Services;

use App\Enums\EstadoTransaccion;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio de las transacciones.
 *
 * No sabe nada de HTTP: no recibe Request ni devuelve respuestas. Lo
 * usan igual el controlador de la API y el de la web, así que una regla
 * se cambia en un solo lugar. Cuando algo no se puede hacer, lanza una
 * excepción y Laravel la convierte en el código HTTP que corresponde.
 */
class TransaccionService
{
    public function __construct(private CalculadoraComision $calculadora) {}

    /**
     * Registra una transacción nueva con su comisión y su evento.
     *
     * El comercio se busca DENTRO de los del usuario: si no es suyo,
     * findOrFail lanza la excepción que Laravel convierte en 404.
     */
    public function registrar(User $usuario, array $datos): Transaccion
    {
        $comercio = $usuario->comercios()->findOrFail($datos['comercio_id']);

        $comision = $this->calculadora->calcular($datos['metodo'], $datos['monto']);

        // Todo o nada: la transacción y su evento se guardan juntos
        $transaccion = DB::transaction(function () use ($comercio, $datos, $comision) {
            $transaccion = $comercio->transacciones()->create([
                'cliente_nombre' => $datos['cliente_nombre'],
                'monto' => $datos['monto'],
                'metodo' => $datos['metodo'],
                'comision' => $comision,
                'estado' => EstadoTransaccion::Iniciada,
            ]);

            $transaccion->eventos()->create([
                'tipo_evento' => 'creacion',
                'detalle' => 'Transacción iniciada',
            ]);

            return $transaccion;
        });

        Log::info("Transacción {$transaccion->id} registrada");

        return $transaccion;
    }

    /**
     * Guarda los cambios de una transacción. Si cambió el monto o el
     * método, la comisión se vuelve a calcular: nunca la manda el cliente.
     */
    public function actualizar(Transaccion $transaccion, array $datos): Transaccion
    {
        $transaccion->update([
            ...$datos,
            'comision' => $this->calculadora->calcular($datos['metodo'], $datos['monto']),
        ]);

        return $transaccion;
    }

    /**
     * Cambia el estado respetando las transiciones permitidas.
     *
     * - Si la transacción no es del usuario: ModelNotFoundException (404).
     * - Si la transición no tiene sentido: ValidationException (422).
     *   La regla de qué transiciones valen vive en el Enum.
     * - El cambio y su evento van en una sola transacción de base de datos.
     */
    public function cambiarEstado(User $usuario, Transaccion $transaccion, string $nuevo): Transaccion
    {
        if ($transaccion->comercio->user_id !== $usuario->id) {
            throw (new ModelNotFoundException)->setModel(Transaccion::class, [$transaccion->id]);
        }

        $anterior = $transaccion->estado;
        $destino = EstadoTransaccion::from($nuevo);

        if (! $anterior->puedePasarA($destino)) {
            throw ValidationException::withMessages([
                'estado' => "No se puede mover de {$anterior->value} a {$destino->value}.",
            ]);
        }

        DB::transaction(function () use ($transaccion, $anterior, $destino) {
            $transaccion->update(['estado' => $destino]);

            $transaccion->eventos()->create([
                'tipo_evento' => 'cambio_estado',
                'detalle' => "{$anterior->value} -> {$destino->value}",
            ]);
        });

        Log::info("Transacción {$transaccion->id}: {$anterior->value} -> {$destino->value}");

        return $transaccion;
    }
}
