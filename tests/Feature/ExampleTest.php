<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La prueba que trae Laravel esperaba 200 en "/". Desde la Semana 12
     * toda la web exige sesión, así que un visitante sin login debe ser
     * enviado a la pantalla de login.
     */
    public function test_visitante_sin_sesion_es_enviado_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
