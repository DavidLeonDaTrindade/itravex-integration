{{-- resources/views/dashboard.blade.php --}}
{{-- NO uses @extends ni @section --}}

<style>
  .card-hover {
    transition: background-color 180ms ease, box-shadow 180ms ease, transform 180ms ease, border-color 180ms ease;
  }
  .card-hover:hover {
    background-color: #f8fafc;
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.10);
    border-color: #cbd5e1;
  }

  /* Fuerza tamaño del hero (evita que lo pisen estilos del layout) */
  .hero-title {
    font-size: 64px !important;
    line-height: 1.02 !important;
    font-weight: 900 !important;
    letter-spacing: -0.04em !important;
    color: #004665 !important;
  }
  @media (min-width: 768px) {
    .hero-title { font-size: 48px !important; }
  }
  @media (min-width: 1024px) {
    .hero-title { font-size: 76px !important; }
  }

  .hero-subtitle {
    font-size: 18px !important;
  }
  @media (min-width: 768px) {
    .hero-subtitle { font-size: 22px !important; }
  }
</style>

@php
$db = session('db_connection', 'mysql');
$isCli2 = $db === 'mysql_cli2';

$cardClass = "group card-hover rounded-3xl border border-slate-200 bg-white/95 p-7 transition flex flex-col min-h-[190px]";
$arrow = '
<span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 transition group-hover:bg-slate-900 group-hover:text-white">
<svg class="h-4 w-4 shrink-0 transition"
  fill="currentColor" viewBox="0 0 20 20">
  <path fill-rule="evenodd"
    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707A1 1 0 118.707 5.293l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
    clip-rule="evenodd" />
</svg>
</span>
';
@endphp

<x-app-layout>
  <div class="min-h-[calc(100vh-64px)] bg-slate-50">

    {{-- HERO: NO pegado arriba --}}
    <section class="pt-16 pb-10 md:pt-20 md:pb-12">
      <div class="mx-auto max-w-6xl px-4">

        <div class="rounded-[2rem] border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-slate-100 shadow-sm">
          <div class="px-6 py-10 md:px-12 md:py-14 text-center">

            <h1 class="hero-title">
              Bienvenido
            </h1>

            <div class="mx-auto mt-5 h-1.5 w-40 rounded-full" style="background:#FDB31B;"></div>

            <p class="hero-subtitle mx-auto mt-6 max-w-3xl text-slate-700 leading-relaxed">
              Disfruta de esta herramienta para gestionar disponibilidad y trabajar cómodamente con datos hoteleros.
            </p>

            {{-- Selector BD debajo del texto --}}
            <div class="mt-7 flex flex-col items-center gap-3">
              <div class="text-sm text-slate-700">
                <span class="font-medium">Base de datos seleccionada:</span>
                <span class="ml-2 inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold
                  @if($db==='mysql')
                    border-slate-900/10 bg-slate-900 text-white
                  @else
                    border-emerald-300 bg-emerald-50 text-emerald-700
                  @endif">
                  {{ $db === 'mysql' ? 'itravex (principal)' : 'itravex_cliente2' }}
                </span>
              </div>

              <form method="POST" action="{{ route('db.switch') }}" class="flex flex-wrap items-center justify-center gap-3 rounded-3xl border border-slate-200 bg-white/80 p-2 shadow-sm">
                @csrf
                <input type="hidden" name="db_connection" id="db_connection" value="{{ $db }}">

                <button type="submit"
                  onclick="document.getElementById('db_connection').value='mysql'"
                  class="inline-flex items-center gap-2 rounded-2xl px-5 py-3 text-sm font-semibold shadow-sm transition
                    {{ $db==='mysql'
                      ? 'bg-slate-900 text-white'
                      : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                  <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl {{ $db==='mysql' ? 'bg-white/10 text-white' : 'bg-slate-100 text-slate-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 7c0-1.657 3.582-3 8-3s8 1.343 8 3-3.582 3-8 3-8-1.343-8-3zm0 5c0 1.657 3.582 3 8 3s8-1.343 8-3m-16 5c0 1.657 3.582 3 8 3s8-1.343 8-3" />
                    </svg>
                  </span>
                  <span>itravex: Inventario Web</span>
                </button>

                <button type="submit"
                  onclick="document.getElementById('db_connection').value='mysql_cli2'"
                  class="inline-flex items-center gap-2 rounded-2xl px-5 py-3 text-sm font-semibold shadow-sm transition
                    {{ $db==='mysql_cli2'
                      ? 'bg-emerald-600 text-white'
                      : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                  <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl {{ $db==='mysql_cli2' ? 'bg-white/10 text-white' : 'bg-emerald-50 text-emerald-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 7c0-1.657 3.582-3 8-3s8 1.343 8 3-3.582 3-8 3-8-1.343-8-3zm0 5c0 1.657 3.582 3 8 3s8-1.343 8-3m-16 5c0 1.657 3.582 3 8 3s8-1.343 8-3" />
                    </svg>
                  </span>
                  <span>itravex_cliente2: Portafolio API</span>
                </button>
              </form>

              @if($isCli2)
                <div class="mt-2 inline-flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 text-left max-w-2xl">
                  <span class="mt-0.5">⚠️</span>
                  <div>
                    <div class="font-medium">Modo itravex_cliente2</div>
                    <div class="text-amber-700">
                      Algunas secciones GIATA están deshabilitadas para evitar errores con esta base de datos.
                    </div>
                  </div>
                </div>
              @endif
            </div>

          </div>
        </div>

      </div>
    </section>

    {{-- Panel cards (separado del hero, NO pegado) --}}
    <section class="pb-14">
      <div class="mx-auto w-full max-w-6xl px-4">

        <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
          <div class="px-6 py-8 md:px-10 md:py-10">

            {{-- Debug conexión BD --}}
            <div class="mb-6 text-sm text-slate-500">
              <span>session: {{ session('db_connection') }}</span> ·
              <span>default: {{ DB::getDefaultConnection() }}</span> ·
              <span>db: {{ DB::connection()->getConfig('database') }}</span>
            </div>

            <div class="mt-6 border-t border-slate-200"></div>

            <div class="mt-8 grid gap-6 sm:grid-cols-2 auto-rows-fr items-stretch">

              <a href="{{ route('availability.form') }}" class="{{ $cardClass }}">
                <div class="flex items-start gap-4">
                  <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 ring-1 ring-blue-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                        d="M8 7V4m8 3V4M5 11h14M5 6h14a2 2 0 011 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z" />
                    </svg>
                  </span>

                  <div class="flex-1 min-w-0 flex flex-col">
                    <div class="flex items-start justify-between gap-3">
                      <h2 class="text-lg font-semibold tracking-tight text-slate-900 leading-snug">Formulario de disponibilidad</h2>
                      {!! $arrow !!}
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">Busca hoteles por fechas, zona o códigos y compara tarifas.</p>
                  </div>
                </div>
                <div class="mt-auto pt-5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Abrir módulo</div>
              </a>

              <a href="{{ route('itravex.status') }}" class="{{ $cardClass }}">
                <div class="flex items-start gap-4">
                  <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 ring-1 ring-amber-100">
                    <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M5 13l4 4L19 7" />
                    </svg>
                  </span>

                  <div class="flex-1 min-w-0 flex flex-col">
                    <div class="flex items-start justify-between gap-3">
                      <h2 class="text-lg font-semibold tracking-tight text-slate-900 leading-snug">Estado de peticiones (Locata)</h2>
                      {!! $arrow !!}
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">Revisa el estado y trazas de reservas en curso.</p>
                  </div>
                </div>
                <div class="mt-auto pt-5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Abrir módulo</div>
              </a>

              <a href="{{ route('logs.itravex') }}" class="{{ $cardClass }}">
                <div class="flex items-start gap-4">
                  <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 ring-1 ring-slate-200">
                    <svg class="h-6 w-6 text-slate-700" fill="none" stroke="currentColor">
                      <path stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"
                        d="M4 6h16M4 10h16M4 14h10m-6 4h6" />
                    </svg>
                  </span>

                  <div class="flex-1 min-w-0 flex flex-col">
                    <div class="flex items-start justify-between gap-3">
                      <h2 class="text-lg font-semibold tracking-tight text-slate-900 leading-snug">Ver logs de Itravex</h2>
                      {!! $arrow !!}
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">Depura llamadas, tiempos, respuestas y errores.</p>
                  </div>
                </div>
                <div class="mt-auto pt-5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Abrir módulo</div>
              </a>

              <a href="{{ route('claim-confirmations.index') }}" class="{{ $cardClass }}">
                <div class="flex items-start gap-4">
                  <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 ring-1 ring-emerald-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                        d="M4 6h16M4 10h16M4 14h16M4 18h10" />
                    </svg>
                  </span>

                  <div class="flex-1 min-w-0 flex flex-col">
                    <div class="flex items-start justify-between gap-3">
                      <h2 class="text-lg font-semibold tracking-tight text-slate-900 leading-snug">Claim confirmations</h2>
                      {!! $arrow !!}
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">Consulta todas las confirmaciones SAMO y lanza una actualizacion desde el ultimo changestamp almacenado.</p>
                  </div>
                </div>
                <div class="mt-auto pt-5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Abrir módulo</div>
              </a>

              @if(!$isCli2)

                <a href="{{ route('giata.properties.raw.index') }}" class="{{ $cardClass }}">
                  <div class="flex items-start gap-4">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl"
                      style="background:#00466510;border:1px solid #00466525;">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" stroke="currentColor" style="color:#004665">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                          d="M4 5h16v4H4zM4 11h10v4H4zM4 17h7v2H4z" />
                      </svg>
                    </span>

                    <div class="flex-1 min-w-0 flex flex-col">
                      <div class="flex items-start justify-between gap-3">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 leading-snug">GIATA – Propiedades (CSV)</h2>
                        {!! $arrow !!}
                      </div>
                      <p class="mt-2 text-sm leading-relaxed text-slate-600">Consulta las propiedades GIATA importadas del CSV (coords, address, emails…).</p>
                    </div>
                  </div>
                  <div class="mt-auto pt-5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Abrir módulo</div>
                </a>

                <a href="{{ route('giata.providers.index') }}" class="{{ $cardClass }}">
                  <div class="flex items-start gap-4">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl"
                      style="background:#00466510;border:1px solid #00466525;">
                      <svg class="h-6 w-6" fill="none" stroke="currentColor" style="color:#004665">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 7h18M3 12h18M3 17h18M7 7v10" />
                      </svg>
                    </span>

                    <div class="flex-1 min-w-0 flex flex-col">
                      <div class="flex items-start justify-between gap-3">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 leading-snug">GIATA – Proveedores</h2>
                        {!! $arrow !!}
                      </div>
                      <p class="mt-2 text-sm leading-relaxed text-slate-600">Busca proveedores de GIATA por nombre y tipo (GDS / TTOO).</p>
                    </div>
                  </div>
                  <div class="mt-auto pt-5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Abrir módulo</div>
                </a>

                <a href="{{ route('giata.codes.browser') }}" class="{{ $cardClass }}">
                  <div class="flex items-start gap-4">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl"
                      style="background:#FDB31B15;border:1px solid #FDB31B33;">
                      <svg class="h-6 w-6" fill="none" stroke="currentColor" style="color:#FDB31B">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 5h18M3 10h18M3 15h18M3 20h18" />
                      </svg>
                    </span>

                    <div class="flex-1 min-w-0 flex flex-col">
                      <div class="flex items-start justify-between gap-3">
                        <h2 class="text-lg font-semibold tracking-tight text-slate-900 leading-snug">GIATA – Códigos por hotel</h2>
                        {!! $arrow !!}
                      </div>
                      <p class="mt-2 text-sm leading-relaxed text-slate-600">Mapa de códigos por proveedor para cada hotel GIATA.</p>
                    </div>
                  </div>
                  <div class="mt-auto pt-5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Abrir módulo</div>
                </a>

              @endif
            </div>

            <footer class="mt-10 pt-6 border-t border-slate-200 text-xs text-slate-500 text-center">
              © {{ date('Y') }} Plugandbeds Itravex Tools
            </footer>

          </div>
        </div>

      </div>
    </section>

  </div>
</x-app-layout>
