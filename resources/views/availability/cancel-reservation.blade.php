@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-lg">
    <div class="flex items-center justify-between mb-10">
        <a href="{{ route('home') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition">
            ⬅️ Volver al inicio
        </a>
    </div>
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Cancelar Reserva</h1>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-800 p-4 rounded mb-4">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-800 p-4 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>❌ {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('availability.cancel.submit') }}" class="bg-white p-6 rounded-lg shadow">
        @csrf

        <div class="mb-4">
            <label for="locata" class="block text-gray-700 font-semibold mb-2">Localizador (LOCATA):</label>
            <input type="text" name="locata" id="locata" required
                   class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring focus:border-blue-300">
        </div>

        <div class="mb-4">
            <label for="accion" class="block text-gray-700 font-semibold mb-2">Acción:</label>
            <select name="accion" id="accion"
                    class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring focus:border-blue-300">
                <option value="C">Cancelar y calcular precio (por defecto)</option>
                <option value="T">Simular cancelación (solo ver importe)</option>
                <option value="P">Solicitud de cancelación (manual)</option>
            </select>
        </div>

        <button type="submit"
                class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700 transition">
            🗑️ Cancelar Reserva
        </button>
    </form>
</div>
@endsection
