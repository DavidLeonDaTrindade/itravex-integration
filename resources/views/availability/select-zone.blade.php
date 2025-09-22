@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto py-10 px-6">
    <h1 class="text-2xl font-bold text-center mb-6">🔍 Buscar disponibilidad por zona</h1>

    <form action="{{ route('availability.zone.check') }}" method="POST" class="bg-white p-6 shadow rounded-lg border">
        @csrf

        <div class="mb-4">
            <label for="codzge" class="block font-medium mb-1">Zona</label>
            <select name="codzge" id="codzge" class="w-full border p-2 rounded">
                <option value="">Selecciona una zona</option>
                @foreach ($zones as $zone)
                    <option value="{{ $zone->code }}">{{ $zone->name }} ({{ $zone->code }})</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="fecini" class="block font-medium mb-1">Fecha de entrada</label>
            <input type="date" name="fecini" id="fecini" class="w-full border p-2 rounded" required>
        </div>

        <div class="mb-4">
            <label for="fecfin" class="block font-medium mb-1">Fecha de salida</label>
            <input type="date" name="fecfin" id="fecfin" class="w-full border p-2 rounded" required>
        </div>

        <div class="mb-6">
            <label for="numadl" class="block font-medium mb-1">Número de adultos</label>
            <input type="number" name="numadl" id="numadl" class="w-full border p-2 rounded" min="1" required>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
            Buscar hoteles
        </button>
    </form>
</div>
@endsection
