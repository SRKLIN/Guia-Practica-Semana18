<?php

namespace App\Models;

use App\Enums\EstadoTransaccion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaccion extends Model
{
    use HasFactory;

    // Le indica a Eloquent el nombre exacto de la tabla 
    protected $table = 'transacciones';

    protected $fillable = [
        'comercio_id',
        'monto',
        'metodo',
        'comision',
        'cliente_nombre',
        'estado',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'comision' => 'decimal:2',
        // Al leer, $transaccion->estado devuelve un EstadoTransaccion
        'estado' => EstadoTransaccion::class,
    ];

    /**
     * Una Transacción pertenece a un Comercio.
     */
    public function comercio(): BelongsTo
    {
        return $this->belongsTo(Comercio::class);
    }

    /**
     * Una Transacción tiene muchos Eventos
     */
    public function eventos(): HasMany
    {
        return $this->hasMany(EventoTransaccion::class);
    }
}