<?php

namespace Database\Factories;

use App\Models\Comercio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Comercios de prueba para PHPUnit. Por defecto crea también su dueño;
 * en las pruebas se le pasa el user_id cuando importa quién es.
 *
 * @extends Factory<Comercio>
 */
class ComercioFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nombre_comercio' => fake()->company(),
            'rubro' => 'Restaurante',
            'telefono' => '2222-0000',
        ];
    }
}
