@props(['type' => null, 'height' => 18])
{{-- Team colour code: a short rounded bar before a name (Engineer blue · Supervisor orange · Technician teal · Manager purple). --}}
@php $color = \App\Models\User::ROLE_COLORS[$type] ?? null; @endphp
@if ($color)
    <span {{ $attributes->merge(['class' => 'role-bar']) }} style="--role: {{ $color }}; height: {{ (int) $height }}px;" title="{{ $type }}" aria-hidden="true"></span>
@endif
