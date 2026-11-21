<?php

namespace Tests\Feature;

use App\Models\Comercio;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Red de seguridad de PATCH /api/transacciones/{id}/estado (Lab 4).
 * Escritas antes de mover la lógica a TransaccionService::cambiarEstado.
 */
class CambiarEstadoTest extends TestCase
{
    use RefreshDatabase;

    /** Una transacción en el estado indicado, de un comercio del usuario con sesión */
    private function transaccionPropia(string $estado): Transaccion
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $comercio = Comercio::factory()->create(['user_id' => $user->id]);

        return $comercio->transacciones()->create([
            'cliente_nombre' => 'Luis Mena',
            'monto' => 100,
            'metodo' => 'tarjeta',
            'comision' => 3.50,
            'estado' => $estado,
        ]);
    }

    public function test_iniciada_pasa_a_completada_y_registra_el_evento(): void
    {
        $t = $this->transaccionPropia('Iniciada');

        $this->patchJson("/api/transacciones/{$t->id}/estado", ['estado' => 'Completada'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'Completada');

        $this->assertDatabaseHas('eventos_transaccion', [
            'transaccion_id' => $t->id,
            'tipo_evento' => 'cambio_estado',
            'detalle' => 'Iniciada -> Completada',
        ]);
    }

    public function test_completada_no_se_mueve_y_responde_422(): void
    {
        $t = $this->transaccionPropia('Completada');

        $this->patchJson("/api/transacciones/{$t->id}/estado", ['estado' => 'Fallida'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede mover de Completada a Fallida.');

        $this->assertDatabaseHas('transacciones', ['id' => $t->id, 'estado' => 'Completada']);
    }

    public function test_transaccion_ajena_devuelve_404(): void
    {
        $t = $this->transaccionPropia('Iniciada');

        // Otro usuario intenta mover la transacción
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson("/api/transacciones/{$t->id}/estado", ['estado' => 'Completada'])
            ->assertNotFound();

        $this->assertDatabaseHas('transacciones', ['id' => $t->id, 'estado' => 'Iniciada']);
    }

    public function test_estado_inexistente_devuelve_422(): void
    {
        $t = $this->transaccionPropia('Iniciada');

        $this->patchJson("/api/transacciones/{$t->id}/estado", ['estado' => 'Pagada'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('estado');
    }
}
