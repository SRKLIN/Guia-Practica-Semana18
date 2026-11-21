{{-- Semana 7 · Blade — Componente reutilizable --}}
{{-- Uso: <x-badge-estado :estado="$transaccion->estado" /> --}}
@use('App\Enums\EstadoTransaccion')
@props(['estado'])

@php
    // Desde la Semana 18 llega un Enum; si alguien pasa el texto, también sirve
    $estado = $estado instanceof EstadoTransaccion ? $estado : EstadoTransaccion::from($estado);
@endphp

@if ($estado === EstadoTransaccion::Completada)
    <span class="badge verde">✔ Completada</span>
@elseif ($estado === EstadoTransaccion::Fallida)
    <span class="badge rojo">✗ Fallida</span>
@else
    <span class="badge amarillo">⏳ {{ $estado->value }}</span>
@endif
