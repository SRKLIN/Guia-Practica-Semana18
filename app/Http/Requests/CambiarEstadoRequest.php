<?php

namespace App\Http\Requests;

use App\Enums\EstadoTransaccion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida el dato que llega para cambiar el estado.
 *
 * Solo revisa que el estado exista. Si la transición tiene sentido
 * (por ejemplo, que una Completada no se mueva) es regla del negocio y
 * la decide TransaccionService, no la validación.
 */
class CambiarEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::enum(EstadoTransaccion::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'Debes indicar el estado.',
            'estado.enum' => 'El estado no es válido: usa Iniciada, Completada o Fallida.',
        ];
    }
}
