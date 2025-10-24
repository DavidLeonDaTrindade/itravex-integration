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

          {{-- Barra de búsqueda y filtros --}}
          <form method="GET" action="{{ route('giata.providers.index') }}" class="mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-6 gap-3">
              <div class="sm:col-span-4">
                <label class="sr-only" for="q">Buscar</label>
                <input type="text" id="q" name="q" value="{{ $q }}"
                       placeholder="Escribe nombre o código…"
                       class="w-full rounded-md border-slate-300 focus:border-[#004665] focus:ring-[#004665]"/>
              </div>

              <div class="sm:col-span-1">
                <label class="sr-only" for="type">Tipo</label>
                <select id="type" name="type"
                        class="w-full rounded-md border-slate-300 focus:border-[#004665] focus:ring-[#004665]">
                  <option value="">Todos</option>
                  <option value="gds" {{ $type==='gds' ? 'selected' : '' }}>GDS</option>
                  <option value="tourOperator" {{ $type==='tourOperator' ? 'selected' : '' }}>Tour Operator</option>
                </select>
              </div>

              <div class="sm:col-span-1">
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 font-medium text-white shadow-sm"
                        style="background:#004665">
                  Buscar
                </button>
              </div>
            </div>

            {{-- Chips de totales rápidos --}}
            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
  <a id="chip-all"  href="{{ route('giata.providers.index') }}"   class="inline-flex ...">Todos
    <span id="count-all" class="ml-1 rounded bg-slate-100 px-1">{{ $totals['all'] }}</span>
  </a>
  <a id="chip-gds"  href="{{ route('giata.providers.index', ['type'=>'gds','q'=>$q]) }}" class="inline-flex ...">GDS
    <span id="count-gds" class="ml-1 rounded bg-slate-100 px-1">{{ $totals['gds'] }}</span>
  </a>
  <a id="chip-to"   href="{{ route('giata.providers.index', ['type'=>'tourOperator','q'=>$q]) }}" class="inline-flex ...">Tour Operator
    <span id="count-to" class="ml-1 rounded bg-slate-100 px-1">{{ $totals['tourOperator'] }}</span>
  </a>
</div>
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
            {{ $providers->links() }}
          </div>

          <footer class="mt-10 pt-6 border-t border-slate-200 text-xs text-slate-500">
            © {{ date('Y') }} Plugandbeds Itravex Tools
          </footer>
          <script>
(function(){
  const input    = document.getElementById('q');     // tu input de búsqueda
  const typeSel  = document.getElementById('type');  // select de tipo
  const tbody    = document.getElementById('providers-body');
  const loading  = document.getElementById('providers-loading');

  const countAll = document.getElementById('count-all');
  const countGds = document.getElementById('count-gds');
  const countTo  = document.getElementById('count-to');

  // Debounce genérico
  function debounce(fn, delay){
    let t; return (...args) => {
      clearTimeout(t); t = setTimeout(() => fn(...args), delay);
    };
  }

  function badgeHtml(provider_type){
    const isGds = provider_type === 'gds';
    const bg    = isGds ? '#00466515' : '#FDB31B15';
    const bd    = isGds ? '#00466530' : '#FDB31B30';
    const tx    = isGds ? '#004665'   : '#8a6100';
    return `<span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px]"
              style="background:${bg}; border:1px solid ${bd}; color:${tx};">
              ${provider_type.toUpperCase()}
            </span>`;
  }

  function rowHtml(item){
    const updated = item.updated_at ? new Date(item.updated_at).toLocaleString() : '—';
    return `<tr class="hover:bg-slate-50">
              <td class="px-4 py-3">${badgeHtml(item.provider_type)}</td>
              <td class="px-4 py-3 font-mono text-xs text-slate-700">${item.provider_code ?? '—'}</td>
              <td class="px-4 py-3 text-slate-900">${item.provider_code ?? '—'}</td>
              <td class="px-4 py-3 text-slate-500">${updated}</td>
            </tr>`;
  }

  async function searchNow(){
    const q    = input.value.trim();
    const type = typeSel.value;
    const qs   = new URLSearchParams({ q, type, limit: 20 }).toString();
    loading && (loading.classList.remove('hidden'));
    try {
      const resp = await fetch(`{{ route('giata.providers.search') }}?${qs}`, {
        headers: { 'Accept': 'application/json' }
      });
      const json = await resp.json();
      const rows = (json.data || []).map(rowHtml).join('');
      tbody.innerHTML = rows || `<tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                   No se encontraron proveedores con esos criterios.
                                 </td></tr>`;
      // Actualiza contadores (opcional)
      if (json.totals){
        if (countAll) countAll.textContent = json.totals.all ?? '0';
        if (countGds) countGds.textContent = json.totals.gds ?? '0';
        if (countTo)  countTo.textContent  = json.totals.tourOperator ?? '0';
      }
    } catch(e){
      console.error(e);
    } finally {
      loading && (loading.classList.add('hidden'));
    }
  }

  const debounced = debounce(searchNow, 250); // 250–400ms va bien
  input.addEventListener('input', debounced);
  typeSel.addEventListener('change', searchNow);

  // Opcional: buscar al cargar si ya hay q/type
  // searchNow();
})();
</script>

        </div>
      </div>
    </div>
  </div>
</x-app-layout>
