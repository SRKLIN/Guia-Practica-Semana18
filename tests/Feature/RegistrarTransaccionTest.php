<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Red de seguridad del refactor (Semana 18, Lab 2).
 *
 * Fijan el comportamiento de POST /api/transacciones. Se escribieron
 * antes de mover código a TransaccionService y tienen que seguir en
 * verde después, sin tocarlas.
 */
class RegistrarTransaccionTest extends TestCase
{
    use RefreshDatabase;

    /** Un comercio que pertenece a un usuario con sesión de API */
    private function comercioPropio(): Comercio
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return Comercio::factory()->create(['user_id' => $user->id]);
    }

    public function test_registra_transaccion_con_comision_de_tarjeta(): void
    {
        $comercio = $this->comercioPropio();

        $this->postJson('/api/transacciones', [
            'comercio_id' => $comercio->id,
            'cliente_nombre' => 'Luis Mena',
            'monto' => 100,
            'metodo' => 'tarjeta',
        ])->assertCreated()
            ->assertJsonPath('data.estado', 'Iniciada');

        $this->assertDatabaseHas('transacciones', [
            'comercio_id' => $comercio->id,
            'comision' => 3.50,
        ]);
    }

    public function test_comision_fija_en_transferencia(): void
    {
        $comercio = $this->comercioPropio();

        $this->postJson('/api/transacciones', [
            'comercio_id' => $comercio->id,
            'cliente_nombre' => 'Luis Mena',
            'monto' => 100,
            'metodo' => 'transferencia',
        ])->assertCreated();

        // Fija: no depende del monto
        $this->assertDatabaseHas('transacciones', [
            'comercio_id' => $comercio->id,
            'comision' => 1.50,
        ]);
    }

    /** Reto del Lab 5: método nuevo agregado sin tocar las reglas existentes */
    public function test_comision_de_billetera_es_2_por_ciento(): void
    {
        $comercio = $this->comercioPropio();

        $this->postJson('/api/transacciones', [
            'comercio_id' => $comercio->id,
            'cliente_nombre' => 'Luis Mena',
            'monto' => 100,
            'metodo' => 'billetera',
        ])->assertCreated();

        $this->assertDatabaseHas('transacciones', [
            'comercio_id' => $comercio->id,
            'comision' => 2.00,
        ]);
    }

    /**
     * La guía espera 404 aquí. En TaskBoard el Form Request ya rechaza
     * un comercio ajeno con 422 desde la Semana 12, y eso es lo que la
     * prueba fija: cambiarlo a 404 sería cambiar el comportamiento.
     */
    public function test_comercio_ajeno_devuelve_422(): void
    {
        $this->comercioPropio();
        $ajeno = Comercio::factory()->create();

        $this->postJson('/api/transacciones', [
            'comercio_id' => $ajeno->id,
            'cliente_nombre' => 'Luis Mena',
            'monto' => 100,
            'metodo' => 'tarjeta',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('comercio_id');

        $this->assertDatabaseCount('transacciones', 0);
    }

    public function test_datos_invalidos_devuelven_422(): void
    {
        $comercio = $this->comercioPropio();

        $this->postJson('/api/transacciones', [
            'comercio_id' => $comercio->id,
            'cliente_nombre' => 'Luis Mena',
            'monto' => -5,
            'metodo' => 'cheque',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['monto', 'metodo']);
    }

    public function test_sin_token_devuelve_401(): void
    {
        $comercio = Comercio::factory()->create();

        $this->postJson('/api/transacciones', [
            'comercio_id' => $comercio->id,
            'cliente_nombre' => 'Luis Mena',
            'monto' => 100,
            'metodo' => 'tarjeta',
        ])->assertUnauthorized();
    }
}
