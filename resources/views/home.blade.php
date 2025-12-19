@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-4xl">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="px-6 py-8 md:px-10 md:py-12">

        {{-- Selector de base de datos --}}
        <div class="mb-6 flex items-center justify-between">
          <div class="text-xs text-slate-500">
            Conexión actual:
            <span class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs
                  @if(session('db_connection','mysql')==='mysql') border-blue-200 bg-blue-50 text-blue-700
                  @else border-amber-200 bg-amber-50 text-amber-700 @endif">
              {{ session('db_connection', 'mysql') }}
            </span>
          </div>

          <form method="POST" action="{{ route('db.switch') }}" class="inline-flex rounded-lg border border-slate-200 p-1 bg-slate-50">
            @csrf
            <input type="hidden" name="db_connection" id="db_connection" value="{{ session('db_connection','mysql') }}">
            @php $current = session('db_connection', 'mysql'); @endphp

            <button type="submit"
              onclick="document.getElementById('db_connection').value='mysql'"
              class="px-3 py-1.5 text-xs rounded-md transition
              {{ $current==='mysql' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
              itravex
            </button>

            <button type="submit"
              onclick="document.getElementById('db_connection').value='mysql_cli2'"
              class="px-3 py-1.5 text-xs rounded-md transition
              {{ $current==='mysql_cli2' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
              itravex_cliente2
            </button>
          </form>
        </div>


        <header class="mb-8">
          <h1 class="text-3xl font-semibold tracking-tight text-[#004665]">
            Bienvenido
          </h1>
          <p class="mt-2 text-slate-600">Consulta disponibilidad de hoteles y realiza tu reserva fácilmente.</p>
        </header>

        <div class="grid gap-4 sm:grid-cols-2">
          {{-- Formulario de disponibilidad --}}
          <a href="{{ route('availability.form') }}"
            class="group rounded-lg border border-slate-200 bg-white p-4 hover:border-slate-300 hover:shadow-sm transition">
            <div class="flex items-center gap-3">
              <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-blue-50 ring-1 ring-blue-100">
                {{-- icono calendario --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                    d="M8 7V4m8 3V4M5 11h14M5 6h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z" />
                </svg>
              </span>
              <div class="flex-1">
                <div class="flex items-center justify-between">
                  <h2 class="text-sm font-medium text-slate-900">Formulario de disponibilidad</h2>
                  <svg class="h-4 w-4 text-slate-400 transition group-hover:text-slate-600"
                    viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707A1 1 0 118.707 5.293l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
                <p class="mt-1 text-xs text-slate-600">Busca hoteles por fechas, zona o códigos y compara tarifas.</p>
              </div>
            </div>
          </a>

          {{-- Estado de peticiones por locata --}}
          <a href="{{ route('itravex.status') }}"
            class="group rounded-lg border border-slate-200 bg-white p-4 hover:border-slate-300 hover:shadow-sm transition">
            <div class="flex items-center gap-3">
              <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-amber-50 ring-1 ring-amber-100">
                {{-- icono status --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                    d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <div class="flex-1">
                <div class="flex items-center justify-between">
                  <h2 class="text-sm font-medium text-slate-900">Estado de peticiones (Locata)</h2>
                  <svg class="h-4 w-4 text-slate-400 transition group-hover:text-slate-600"
                    viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707A1 1 0 118.707 5.293l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
                <p class="mt-1 text-xs text-slate-600">Revisa el estado y trazas de reservas en curso.</p>
              </div>
            </div>
          </a>

          {{-- Visor de logs Itravex --}}
          <a href="{{ route('logs.itravex') }}"
            class="group sm:col-span-2 rounded-lg border border-slate-200 bg-white p-4 hover:border-slate-300 hover:shadow-sm transition">
            <div class="flex items-center gap-3">
              <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-slate-100 ring-1 ring-slate-200">
                {{-- icono logs --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                    d="M4 6h16M4 10h16M4 14h10m-6 4h6" />
                </svg>
              </span>
              <div class="flex-1">
                <div class="flex items-center justify-between">
                  <h2 class="text-sm font-medium text-slate-900">Ver logs de Itravex</h2>
                  <svg class="h-4 w-4 text-slate-400 transition group-hover:text-slate-600"
                    viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707A1 1 0 118.707 5.293l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
                <p class="mt-1 text-xs text-slate-600">Depura llamadas, tiempos, respuestas y errores.</p>
              </div>
            </div>
          </a>
        </div>

        <footer class="mt-10 pt-6 border-t border-slate-200 text-xs text-slate-500">
          © {{ date('Y') }} Plugandbeds Itravex Tools
        </footer>
      </div>
    </div>
  </div>
</div>
@endsection