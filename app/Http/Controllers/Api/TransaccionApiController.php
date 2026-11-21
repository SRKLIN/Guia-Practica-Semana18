<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoTransaccion;
use App\Http\Controllers\Controller;
use App\Http\Requests\CambiarEstadoRequest;
use App\Http\Requests\GuardarTransaccionRequest;
use App\Http\Resources\TransaccionResource;
use App\Models\Transaccion;
use App\Services\TransaccionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Controlador de la API: devuelve JSON, nunca vistas.
 */
class TransaccionApiController extends Controller
{
    /**
     * Laravel construye el servicio solo y lo pasa aquí (inyección de
     * dependencias): el controlador no hace "new TransaccionService".
     */
    public function __construct(private TransaccionService $servicio) {}

    /**
     * Una transaccion es del usuario si su comercio lo es.
     * Se responde 404 para no confirmar siquiera que existe.
     */
    private function autorizar(Transaccion $transaccion): void
    {
        abort_unless($transaccion->comercio->user_id === auth()->id(), 404);
    }

    /**
     * Lista las transacciones de los comercios del dueno del token,
     * de la mas nueva a la mas vieja.
     * ?estado=Completada filtra; sin el parametro salen todas.
     *
     * MT-013: antes un estado inexistente devolvía 200 con la lista
     * vacía, y el cliente creía que no había cobros. Ahora se valida
     * contra la lista de estados y responde 422.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $datos = $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoTransaccion::class)],
        ]);

        $estado = $datos['estado'] ?? null;

        $transacciones = Transaccion::whereHas('comercio',
                fn ($q) => $q->where('user_id', $request->user()->id))
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->latest()
            ->get();

        return TransaccionResource::collection($transacciones);
    }

    /**
     * GET /api/transacciones/filtrar
     *
     * Filtra las transacciones del usuario del token por estado, rango
     * de fechas y rango de monto. Todos los parámetros son opcionales:
     * sin ninguno, devuelve lo mismo que index().
     *
     * Rule::in evita que un estado inventado pase como filtro, y de
     * paso corta cualquier intento de inyección: el valor nunca llega
     * a la consulta si no es uno de los tres estados.
     *
     * MT-014 agregó monto_min y monto_max. La regla gte va envuelta en
     * Rule::when porque, suelta, haría fallar ?monto_max=100 cuando no
     * viene monto_min.
     */
    public function filtrar(Request $request): AnonymousResourceCollection
    {
        $datos = $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoTransaccion::class)],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'monto_min' => ['nullable', 'numeric', 'min:0'],
            'monto_max' => ['nullable', 'numeric', 'min:0',
                Rule::when($request->filled('monto_min'), ['gte:monto_min'])],
        ]);

        $transacciones = Transaccion::whereHas('comercio',
                fn ($q) => $q->where('user_id', $request->user()->id))
            ->when($datos['estado'] ?? null,
                fn ($q, $estado) => $q->where('estado', $estado))
            // whereDate compara solo la fecha: así 'hasta' incluye todo
            // ese día. Con whereBetween quedarían fuera las de hoy,
            // porque la hora contaría como 00:00.
            ->when($datos['desde'] ?? null,
                fn ($q, $desde) => $q->whereDate('created_at', '>=', $desde))
            ->when($datos['hasta'] ?? null,
                fn ($q, $hasta) => $q->whereDate('created_at', '<=', $hasta))
            ->when($datos['monto_min'] ?? null,
                fn ($q, $monto) => $q->where('monto', '>=', $monto))
            ->when($datos['monto_max'] ?? null,
                fn ($q, $monto) => $q->where('monto', '<=', $monto))
            ->latest()
            ->get();

        return TransaccionResource::collection($transacciones);
    }

    /**
     * Crea una transacción. 201 Created con el objeto creado.
     *
     * Solo coordina: el Form Request valida, el servicio aplica las
     * reglas del negocio y el Resource arma el JSON. Si falla la
     * validación, Laravel responde 422 por su cuenta.
     */
    public function store(GuardarTransaccionRequest $request): JsonResponse
    {
        $transaccion = $this->servicio->registrar($request->user(), $request->validated());

        return (new TransaccionResource($transaccion))->response()->setStatusCode(201);
    }

    /**
     * Una transacción. Si el id no existe, Route Model Binding da 404.
     */
    public function show(Transaccion $transaccion): TransaccionResource
    {
        $this->autorizar($transaccion);

        return new TransaccionResource($transaccion);
    }

    /**
     * Actualiza y devuelve el objeto ya modificado.
     */
    public function update(
        GuardarTransaccionRequest $request,
        Transaccion $transaccion
    ): TransaccionResource {
        $this->autorizar($transaccion);

        $this->servicio->actualizar($transaccion, $request->validated());

        return new TransaccionResource($transaccion);
    }

    /**
     * Elimina. 204 No Content: se borró y no hay nada que devolver.
     */
    public function destroy(Transaccion $transaccion): Response
    {
        $this->autorizar($transaccion);

        $transaccion->delete();

        return response()->noContent();
    }

    /**
     * PATCH /api/transacciones/{transaccion}/estado
     *
     * El Form Request valida que el estado exista; el servicio decide si
     * la transición tiene sentido y si la transacción es del usuario.
     */
    public function cambiarEstado(CambiarEstadoRequest $request, Transaccion $transaccion): TransaccionResource
    {
        $t = $this->servicio->cambiarEstado($request->user(), $transaccion, $request->validated('estado'));

        return new TransaccionResource($t);
    }
}
