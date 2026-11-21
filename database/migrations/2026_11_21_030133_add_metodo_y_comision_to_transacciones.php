<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada transacción guarda con qué se pagó y cuánto cobra la pasarela.
     * La comisión se calcula al registrar; no la manda el cliente.
     */
    public function up(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            $table->string('metodo')->default('tarjeta')->after('monto');
            $table->decimal('comision', 10, 2)->default(0)->after('metodo');
        });
    }

    public function down(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            $table->dropColumn(['metodo', 'comision']);
        });
    }
};
