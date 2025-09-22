{{-- resources/views/availability/form.blade.php --}}
<x-app-layout>
  <div class="min-h-screen bg-slate-50 py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <header class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Buscar Disponibilidad</h1>
        <p class="mt-1 text-sm text-slate-600">Consulta por zona o por códigos de hotel y filtra por fechas.</p>
      </header>

      <div class="mb-8 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="p-6 md:p-8">

          <form method="GET" action="{{ route('availability.search') }}" class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @csrf

            <div class="md:col-span-1">
              <label for="codzge" class="block text-sm font-medium text-slate-700">Código de Zona</label>
              <input id="codzge" type="text" name="codzge" value="{{ old('codzge') }}"
                     class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                     placeholder="A-39018" />
              <p class="mt-1 text-xs text-slate-500">Si se rellena, se ignorarán los códigos manuales (salvo intersección).</p>
            </div>

            <div class="md:col-span-1">
              <label for="hotel_codes" class="block text-sm font-medium text-slate-700">Códigos de hotel (codser)</label>
              <textarea id="hotel_codes" name="hotel_codes" rows="3"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="105971, 12345&#10;67890">{{ old('hotel_codes') }}</textarea>
              <p class="mt-1 text-xs text-slate-500">Separados por coma, espacios o saltos de línea.</p>
            </div>

            <div>
              <label for="fecini" class="block text-sm font-medium text-slate-700">Fecha Inicio</label>
              <input id="fecini" type="date" name="fecini" value="{{ old('fecini') }}" required
                     class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            </div>

            <div>
              <label for="fecfin" class="block text-sm font-medium text-slate-700">Fecha Fin</label>
              <input id="fecfin" type="date" name="fecfin" value="{{ old('fecfin') }}" required
                     class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            </div>

            <div>
              <label for="numadl" class="block text-sm font-medium text-slate-700">Número de adultos</label>
              <input id="numadl" type="number" name="numadl" min="1" value="{{ old('numadl', 2) }}" required
                     class="mt-1 block w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            </div>

            <div>
              <label for="codnac" class="block text-sm font-medium text-slate-700">Código de país (ISO 3166-1)</label>
              <input id="codnac" type="text" name="codnac" value="{{ old('codnac') }}" placeholder="ESP" maxlength="3"
                     class="mt-1 block w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase" />
              <p class="mt-1 text-xs text-slate-500">ISO 3166-1 (p. ej. <strong>ESP</strong>, <strong>FRA</strong>, <strong>USA</strong>).</p>
            </div>

            <div class="grid grid-cols-2 gap-4 md:col-span-2">
              <div>
                <label for="timeout" class="block text-sm font-medium text-slate-700">Timeout (ms)</label>
                <input id="timeout" type="number" name="timeout" min="1000" max="60000" value="{{ old('timeout') }}" placeholder="8000"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
              </div>

              <div>
                <label for="numrst" class="block text-sm font-medium text-slate-700">Resultados por página (numrst)</label>
                <input id="numrst" type="number" name="numrst" min="1" max="200" value="{{ old('numrst', 20) }}" placeholder="20"
                       class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
              </div>

              <div class="md:col-span-2 rounded-xl border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-medium text-slate-700">Credenciales del proveedor (opcional)</h3>
                  <span class="text-xs text-slate-500">Si no rellenas nada, se usan las de config()</span>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                  <div>
                    <label for="endpoint" class="block text-sm font-medium text-slate-700">Endpoint</label>
                    <input id="endpoint" type="url" name="endpoint" value="{{ old('endpoint') }}" placeholder="https://api.proveedor.com/endpoint"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                  </div>
                  <div>
                    <label for="codsys" class="block text-sm font-medium text-slate-700">codsys</label>
                    <input id="codsys" type="text" name="codsys" value="{{ old('codsys') }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                  </div>
                  <div>
                    <label for="codage" class="block text-sm font-medium text-slate-700">codage</label>
                    <input id="codage" type="text" name="codage" value="{{ old('codage') }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                  </div>
                  <div>
                    <label for="user" class="block text-sm font-medium text-slate-700">user</label>
                    <input id="user" type="text" name="user" autocomplete="off" value="{{ old('user') }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                  </div>
                  <div>
                    <label for="pass" class="block text-sm font-medium text-slate-700">pass</label>
                    <input id="pass" type="password" name="pass" autocomplete="off" placeholder="••••••••"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                  </div>
                  <div>
                    <label for="codtou" class="block text-sm font-medium text-slate-700">codtou</label>
                    <input id="codtou" type="text" name="codtou" value="{{ old('codtou','LIB') }}"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                  </div>
                </div>
              </div>
            </div>

            <fieldset class="md:col-span-2">
              <legend class="block text-sm font-medium text-slate-700">Modo</legend>
              <div class="mt-2 flex items-center gap-6">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                  <input type="radio" name="mode" value="fast" class="h-4 w-4 text-blue-600 focus:ring-blue-500" {{ old('mode','fast') === 'fast' ? 'checked' : '' }}>
                  <span>Rápido (parcial)</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                  <input type="radio" name="mode" value="full" class="h-4 w-4 text-blue-600 focus:ring-blue-500" {{ old('mode') === 'full' ? 'checked' : '' }}>
                  <span>Completo (puede tardar más)</span>
                </label>
              </div>
            </fieldset>

            <div class="md:col-span-2">
              <button type="submit"
                      class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Consultar
              </button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
