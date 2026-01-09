{{-- resources/views/giata/providers/index.blade.php --}}
{{-- NO uses @extends ni @section --}}
<x-app-layout>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-6xl">
      <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="px-6 py-8 md:px-10 md:py-12">
          <header class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight" style="color:#004665">GIATA – Proveedores</h1>
            <p class="mt-1 text-slate-600">Busca por nombre o código, y filtra por tipo.</p>
          </header>

          @php
          // Lista fija de "Activos"
          $activeProviders = [
          'abbey_lc',
          'abbey_tp',
          'allbeds',
          'alturadestinationservices',
          'ask2travel',
          'babylon_holiday',
          'barcelo',
          'cn_travel',
          'connectycs',
          'darinaholidays',
          'DOTW',
          'dts_dieuxtravelservices',
          'gekko_infinite',
          'gekko_teldar',
          'guestincoming',
          'hotelbook',
          'hyperguest',
          'iol_iwtx',
          'itravex',
          'logitravel_dr',
          'methabook2',
          'mikitravel',
          'opentours',
          'ors_beds',
          'paximum',
          'ratehawk2',
          'restel',
          'solole',
          'sunhotels',
          'travellanda',
          'veturis',
          'w2m',
          'wl2t',
          'yalago',
          'travco',
          'abreu_master_codes',
          ];
          @endphp

          {{-- Barra de búsqueda y filtros --}}
          <form method="GET" action="{{ route('giata.providers.index') }}" class="mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
              <div class="sm:col-span-4">
                <label class="sr-only" for="q">Buscar</label>
                <input type="text" id="q" name="q" value="{{ $q }}"
                  placeholder="Escribe nombre o código…"
                  class="w-full rounded-md border-slate-300 focus:border-[#004665] focus:ring-[#004665]" />
              </div>

              <div class="sm:col-span-1">
                <label class="sr-only" for="type">Tipo</label>
                <select id="type" name="type"
                  class="w-full rounded-md border-slate-300 focus:border-[#004665] focus:ring-[#004665]">
                  <option value="">Todos</option>
                  <option value="gds" {{ $type==='gds' ? 'selected' : '' }}>GDS</option>
                  <option value="tourOperator" {{ $type==='tourOperator' ? 'selected' : '' }}>Tour Operator</option>
                  <option value="activos" {{ $type==='activos' ? 'selected' : '' }}>Activos</option>
                </select>
              </div>

              <div class="sm:col-span-1">
                <button type="submit"
                  class="w-full inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 font-medium text-white shadow-sm"
                  style="background:#004665">
                  Buscar
                </button>
              </div>

              {{-- ✅ BOTÓN NUEVO: Actualizar provider (dispatch job) --}}
              <div class="sm:col-span-6">
                <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                  <button type="button" id="btn-sync-provider"
                    class="inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 font-medium text-white shadow-sm"
                    style="background:#FDB31B; color:#1f2937">
                    Actualizar proveedor
                  </button>
                  
                  <span id="sync-status" class="text-xs text-slate-500"></span>
                </div>
                <p class="mt-1 text-[11px] text-slate-500">
                  Usa el texto del campo “Buscar”. Ej: <span class="font-mono">barcelo</span> (solo 1 provider).
                </p>
              </div>
            </div>

            {{-- Chips de totales rápidos --}}
            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
              <a id="chip-all" href="{{ route('giata.providers.index') }}" class="inline-flex ...">
                Todos
                <span id="count-all" class="ml-1 rounded bg-slate-100 px-1">{{ $totals['all'] }}</span>
              </a>

              <a id="chip-gds" href="{{ route('giata.providers.index', ['type'=>'gds','q'=>$q]) }}" class="inline-flex ...">
                GDS
                <span id="count-gds" class="ml-1 rounded bg-slate-100 px-1">{{ $totals['gds'] }}</span>
              </a>

              <a id="chip-to" href="{{ route('giata.providers.index', ['type'=>'tourOperator','q'=>$q]) }}" class="inline-flex ...">
                Tour Operator
                <span id="count-to" class="ml-1 rounded bg-slate-100 px-1">{{ $totals['tourOperator'] }}</span>
              </a>

              <a id="chip-act" href="{{ route('giata.providers.index', ['type'=>'activos','q'=>$q]) }}" class="inline-flex ...">
                Activos
                <span class="ml-1 rounded bg-slate-100 px-1">{{ count($activeProviders) }}</span>
              </a>
            </div>
          </form>

          {{-- Tabla de resultados --}}
          <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 text-slate-600">
                <tr>
                  <th class="px-4 py-3 text-left font-medium">Tipo</th>
                  <th class="px-4 py-3 text-left font-medium">Código</th>
                  <th class="px-4 py-3 text-left font-medium">Nombre</th>
                  <th class="px-4 py-3 text-left font-medium">Actualizado</th>
                </tr>
              </thead>

              {{-- En Activos lo llenamos por JS para traer updated_at real --}}
              <tbody id="providers-body" class="divide-y divide-slate-200">
                @forelse($providers as $p)
                <tr class="hover:bg-slate-50">
                  <td class="px-4 py-3">
                    @php
                    $isGds = $p->type === 'gds';
                    $badgeBg = $isGds ? '#00466515' : '#FDB31B15';
                    $badgeBorder = $isGds ? '#00466530' : '#FDB31B30';
                    $badgeText = $isGds ? '#004665' : '#8a6100';
                    @endphp
                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px]"
                      style="background:{{ $badgeBg }}; border:1px solid {{ $badgeBorder }}; color:{{ $badgeText }};">
                      {{ strtoupper($p->type) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 font-mono text-xs text-slate-700">
                    {{ $p->code ?? '—' }}
                  </td>
                  <td class="px-4 py-3 text-slate-900">
                    {{ $p->name }}
                  </td>
                  <td class="px-4 py-3 text-slate-500">
                    {{ optional($p->updated_at)->format('Y-m-d H:i') ?? '—' }}
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                    No se encontraron proveedores con esos criterios.
                  </td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          {{-- Paginación --}}
          <div class="mt-4">
            {{-- En "Activos" NO mostramos paginación del backend, porque usamos lista fija --}}
            @if($type !== 'activos')
            {{ $providers->links() }}
            @else
            <div class="text-xs text-slate-500">
              Mostrando {{ count($activeProviders) }} activos (con fecha real si existe en BD).
            </div>
            @endif
          </div>

          <footer class="mt-10 pt-6 border-t border-slate-200 text-xs text-slate-500">
            © {{ date('Y') }} Plugandbeds Itravex Tools
          </footer>

          <script>
            (function() {
              const input = document.getElementById('q');
              const typeSel = document.getElementById('type');
              const tbody = document.getElementById('providers-body');
              const loading = document.getElementById('providers-loading');

              const countAll = document.getElementById('count-all');
              const countGds = document.getElementById('count-gds');
              const countTo = document.getElementById('count-to');

              const ACTIVE_PROVIDERS = @json($activeProviders);

              // ✅ Sync button
              const btnSync = document.getElementById('btn-sync-provider');
              const syncStatus = document.getElementById('sync-status');

              function debounce(fn, delay) {
                let t;
                return (...args) => {
                  clearTimeout(t);
                  t = setTimeout(() => fn(...args), delay);
                };
              }

              function escapeHtml(str) {
                return String(str ?? '')
                  .replaceAll('&', '&amp;')
                  .replaceAll('<', '&lt;')
                  .replaceAll('>', '&gt;')
                  .replaceAll('"', '&quot;')
                  .replaceAll("'", "&#039;");
              }

              function badgeHtml(provider_type) {
                const isGds = provider_type === 'gds';
                const bg = isGds ? '#00466515' : '#FDB31B15';
                const bd = isGds ? '#00466530' : '#FDB31B30';
                const tx = isGds ? '#004665' : '#8a6100';
                return `<span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px]"
              style="background:${bg}; border:1px solid ${bd}; color:${tx};">
              ${String(provider_type || '').toUpperCase()}
            </span>`;
              }

              function rowHtml(item) {
                const rawUpdated = item.last_codes_updated_at ?? item.updated_at ?? null;
                const updated = rawUpdated ? new Date(rawUpdated).toLocaleString() : '—';

                const code = item.provider_code ?? item.code ?? '—';
                const name = item.provider_name ?? item.name ?? '—';
                const type = item.provider_type ?? item.type ?? '';
                return `<tr class="hover:bg-slate-50">
              <td class="px-4 py-3">${badgeHtml(type)}</td>
              <td class="px-4 py-3 font-mono text-xs text-slate-700">${escapeHtml(code)}</td>
              <td class="px-4 py-3 text-slate-900">${escapeHtml(name)}</td>
              <td class="px-4 py-3 text-slate-500">${escapeHtml(updated)}</td>
            </tr>`;
              }

              function activosRowHtml(code, match) {
                const updated = match?.updated_at ? new Date(match.updated_at).toLocaleString() : '—';
                const name = match?.provider_name ?? match?.name ?? code;
                const realCode = match?.provider_code ?? match?.code ?? code;

                const badge = `<span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px]"
                      style="background:#FDB31B15; border:1px solid #FDB31B30; color:#8a6100;">
                    ACTIVOS
                   </span>`;

                return `<tr class="hover:bg-slate-50">
              <td class="px-4 py-3">${badge}</td>
              <td class="px-4 py-3 font-mono text-xs text-slate-700">${escapeHtml(realCode)}</td>
              <td class="px-4 py-3 text-slate-900">${escapeHtml(name)}</td>
              <td class="px-4 py-3 text-slate-500">${escapeHtml(updated)}</td>
            </tr>`;
              }

              async function searchNow() {
                const q = input.value.trim();
                const type = typeSel.value;

                // --- MODO ACTIVOS: traer updated_at real desde API y filtrar por lista fija ---
                if (type === 'activos') {
                  const needle = q.toLowerCase();
                  const filteredActives = needle ?
                    ACTIVE_PROVIDERS.filter(x => x.toLowerCase().includes(needle)) :
                    ACTIVE_PROVIDERS.slice();

                  const qs = new URLSearchParams({
                    q: '',
                    type: '',
                    limit: 500
                  }).toString();
                  loading && (loading.classList.remove('hidden'));

                  try {
                    const resp = await fetch(`{{ route('giata.providers.search') }}?${qs}`, {
                      headers: {
                        'Accept': 'application/json'
                      }
                    });
                    const json = await resp.json();
                    const data = (json.data || []);

                    const byCode = new Map();
                    data.forEach(item => {
                      const c = (item.provider_code ?? item.code ?? '').toString();
                      if (c) byCode.set(c, item);
                    });

                    const rows = filteredActives.map(code => activosRowHtml(code, byCode.get(code))).join('');
                    tbody.innerHTML = rows || `<tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                     No se encontraron activos con esos criterios.
                                   </td></tr>`;

                    if (json.totals) {
                      if (countAll) countAll.textContent = json.totals.all ?? countAll.textContent;
                      if (countGds) countGds.textContent = json.totals.gds ?? countGds.textContent;
                      if (countTo) countTo.textContent = json.totals.tourOperator ?? countTo.textContent;
                    }
                  } catch (e) {
                    console.error(e);
                    const rows = filteredActives.map(code => activosRowHtml(code, null)).join('');
                    tbody.innerHTML = rows || `<tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                     No se encontraron activos con esos criterios.
                                   </td></tr>`;
                  } finally {
                    loading && (loading.classList.add('hidden'));
                  }

                  return;
                }

                // --- MODO NORMAL (GDS/TO/Todos): usa API normal ---
                const qs = new URLSearchParams({
                  q,
                  type,
                  limit: 20
                }).toString();
                loading && (loading.classList.remove('hidden'));

                try {
                  const resp = await fetch(`{{ route('giata.providers.search') }}?${qs}`, {
                    headers: {
                      'Accept': 'application/json'
                    }
                  });
                  const json = await resp.json();

                  const rows = (json.data || []).map(rowHtml).join('');
                  tbody.innerHTML = rows || `<tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                   No se encontraron proveedores con esos criterios.
                                 </td></tr>`;

                  if (json.totals) {
                    if (countAll) countAll.textContent = json.totals.all ?? '0';
                    if (countGds) countGds.textContent = json.totals.gds ?? '0';
                    if (countTo) countTo.textContent = json.totals.tourOperator ?? '0';
                  }
                } catch (e) {
                  console.error(e);
                } finally {
                  loading && (loading.classList.add('hidden'));
                }
              }

              const debounced = debounce(searchNow, 250);
              input.addEventListener('input', debounced);
              typeSel.addEventListener('change', searchNow);

              if (typeSel.value === 'activos') {
                searchNow();
              }

              // ✅ BOTÓN: llama a un endpoint POST que debe despachar el job en backend
              // Necesitas crear una ruta: giata.providers.sync (POST) que reciba {provider_code}
              btnSync && btnSync.addEventListener('click', async () => {
                const provider = (input.value || '').trim();

                if (!provider) {
                  syncStatus.textContent = 'Escribe un provider_code en el buscador (ej: barcelo).';
                  return;
                }

                btnSync.disabled = true;
                syncStatus.textContent = `Encolando sync para "${provider}"...`;

                try {
                  const resp = await fetch(`{{ route('giata.providers.sync') }}`, {
                    method: 'POST',
                    headers: {
                      'Accept': 'application/json',
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': `{{ csrf_token() }}`
                    },
                    body: JSON.stringify({
                      provider_code: provider
                    })
                  });

                  const json = await resp.json().catch(() => ({}));

                  if (!resp.ok) {
                    syncStatus.textContent = json.message ? `Error: ${json.message}` : 'Error al encolar el job.';
                    return;
                  }

                  syncStatus.textContent = json.message ?? `Job encolado para "${provider}".`;

                } catch (e) {
                  console.error(e);
                  syncStatus.textContent = 'Error de red al encolar el job.';
                } finally {
                  btnSync.disabled = false;
                }
              });

            })();
          </script>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>