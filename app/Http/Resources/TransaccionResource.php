<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Define qué campos de la transacción salen al exterior.
 */
class TransaccionResource extends JsonResource
{
    /**
     * El monto sale como número, no como "$25.50": el formato visual
     * lo decide quien muestra el dato, no la API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Lo necesita el cliente para agrupar por comercio.
            // No es dato sensible: solo salen transacciones propias.
            'comercio_id' => $this->comercio_id,
            'cliente' => $this->cliente_nombre,
            'monto' => (float) $this->monto,
            'metodo' => $this->metodo,
            'comision' => (float) $this->comision,
            // ->value: con el cast, estado es un Enum y aquí se quiere el texto
            'estado' => $this->estado->value,
            'fecha' => $this->created_at->toIso8601String(),
        ];
    }
}
