{{-- resources/views/giata/codes.blade.php --}}

<style>
  .per-page-select {
    padding-right: 2.2rem !important;
  }

  /* Barra de progreso + porcentaje */
  .progress-wrapper {
    max-width: 420px;
    margin: 0 auto;
    text-align: center;
  }

  .progress-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 9999px;
    overflow: hidden;
  }

  .progress-bar-fill {
    height: 100%;
    width: 0%;
    background: #004665;
    border-radius: 9999px;
    transition: width 0.15s linear;
  }

  .progress-percent {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #64748b;
  }

  /* Dropdown providers */
  #provOptions li {
    padding: 0.5rem 0.75rem;
    cursor: pointer;
  }

  #provOptions li:hover {
    background: #f8fafc;
  }

  /* slate-50 */
  #provOptions li.active {
    background: #e2e8f0;
  }

  /* slate-200 */

  .prov-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    border: 1px solid #cbd5e1;
    border-radius: 9999px;
    padding: .25rem .5rem;
    font-size: 12px;
    background: #fff;
  }

  .prov-chip button {
    border: 0;
    background: transparent;
    cursor: pointer;
    color: #64748b;
  }

  .prov-chip button:hover {
    color: #0f172a;
  }
</style>

<x-app-layout>
  <div class="min-h-screen bg-slate-50 px-4 py-8">
    <div class="mx-auto max-w-7xl">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">GIATA – Códigos por hotel</h1>
        <p class="text-slate-600 text-sm mt-1">
          Busca por nombre de hotel, GIATA ID, código de proveedor o provider_code.
        </p>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

        {{-- Mensaje de éxito al cargar GIATA --}}
        @if (session('status'))
        <div class="mb-3 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-xs text-green-700">
          {{ session('status') }}
        </div>
        @endif

        {{-- Errores (por ejemplo del XLSX) --}}
        @if ($errors->any())
        <div class="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
          <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white px-6 py-4">
          {{-- Fila 1: búsqueda por hotel + por página --}}
          <div class="flex flex-col md:flex-row md:items-center gap-3">
            <div class="flex flex-col gap-2 w-full md:w-[420px]">
              <div class="flex items-center gap-2">
                <input id="hotelSearch"
                  list="hotelList"
                  placeholder="Buscar hotel (nombre, etc.)…"
                  class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" />
                <button id="clearHotel"
                  class="rounded-md border border-slate-300 px-2 py-1 text-xs hover:bg-slate-50">
                  Limpiar
                </button>
              </div>
              <small class="text-[11px] text-slate-500">
                Escribe al menos 3 letras para ver sugerencias desde la tabla <code>hotels</code>.
                Puedes pulsar Enter o elegir una sugerencia para lanzar la búsqueda GIATA.
              </small>
            </div>

            <div class="flex items-center">
              <label class="text-sm text-slate-600 whitespace-nowrap">Por página</label>
              <select id="perPage" class="rounded-md border border-slate-300 px-3 py-2 text-sm per-page-select">
                <option>25</option>
                <option>50</option>
                <option>100</option>
              </select>
            </div>
          </div>
        </div>

        {{-- Fila 2: selección de proveedores (hasta 10) --}}
        <div class="flex flex-col md:flex-row items-start md:items-center gap-10 mt-4">
          <div class="flex flex-col gap-2 w-full md:w-auto">
            <label class="text-sm text-slate-600 whitespace-nowrap">Proveedores (máx. 10)</label>

            <div class="relative w-full min-w-[320px]">
              <div class="flex items-center gap-2">
                <input id="provMulti"
                  type="text"
                  autocomplete="off"
                  placeholder="Escribe para buscar… (Enter para añadir)"
                  class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" />

                <button id="provToggle"
                  type="button"
                  class="rounded-md border border-slate-300 px-2 py-2 text-xs hover:bg-slate-50"
                  aria-label="Abrir lista">
                  ▾
                </button>

                <button id="clearProv"
                  type="button"
                  class="rounded-md border border-slate-300 px-2 py-2 text-xs hover:bg-slate-50">
                  Limpiar
                </button>
              </div>

              <div id="provDropdown"
                class="hidden absolute z-30 mt-1 w-full rounded-md border border-slate-200 bg-white shadow-lg overflow-hidden">
                <ul id="provOptions" class="max-h-60 overflow-auto text-sm"></ul>
              </div>
            </div>

            <div id="provChips" class="flex flex-wrap gap-2"></div>
            <div id="provSelected" class="text-xs text-slate-600"></div>
          </div>

          <div class="text-xs text-slate-500">
            Selecciona hasta 10 <code>provider_code</code>.<br>
            Si lo dejas vacío, se mostrarán solo proveedores con datos en la página
            (itravex primero si aplica).
          </div>
        </div>

        {{-- Fila 3: subir Excel con GIATA + campo de filtro GIATA --}}
        <div class="flex flex-col md:flex-row md:items-center gap-3 mt-4">
          {{-- Form solo para subir el XLSX con GIATA --}}
          <form method="POST"
            action="{{ route('giata.codes.uploadGiata') }}"
            enctype="multipart/form-data"
            class="flex flex-col gap-2 md:flex-row md:items-end">
            @csrf
            <div class="flex flex-col gap-1 w-full md:w-[320px]">
              <label class="text-sm text-slate-600">Cargar GIATA desde Excel (.xlsx)</label>
              <input type="file" name="giata_file"
                class="block w-full rounded-md border border-slate-300 px-3 py-2 text-xs shadow-sm
                            focus:border-blue-500 focus:ring-blue-500">
              <small class="text-[11px] text-slate-500">
                Excel con una sola columna <strong>GIATA</strong>
                (cabecera en fila 1, códigos desde la fila 2).
              </small>
            </div>

            <button type="submit"
              class="inline-flex items-center rounded-md px-3 py-2 text-xs font-medium text-white shadow-sm mt-2 md:mt-0"
              style="background:#004665; border:1px solid #00354b;"
              onmouseover="this.style.background='#00354b'"
              onmouseout="this.style.background='#004665'">
              Cargar lista GIATA
            </button>
          </form>

          {{-- Campo de filtro GIATA donde se volcarán los códigos del Excel --}}
          <div class="flex flex-col gap-1 w-full md:w-[340px]">
            <div class="flex items-center justify-between gap-2">
              <label class="text-sm text-slate-600">Filtro de GIATA (lista de IDs)</label>
              <button id="clearGiata" type="button"
                class="rounded-md border border-slate-300 px-2 py-1 text-[11px] hover:bg-slate-50">
                Limpiar GIATA
              </button>
            </div>
            <textarea id="giataFilter"
              rows="3"
              placeholder="Ej: 123456, 789012, 345678..."
              class="w-full rounded-md border border-slate-300 px-3 py-2 text-[11px] shadow-sm
                             focus:border-blue-500 focus:ring-blue-500">@if(!empty($giataIdsString)){{ $giataIdsString }}@endif</textarea>
            <small class="text-[11px] text-slate-500">
              Aquí se rellenan los códigos desde el Excel. También puedes pegarlos/editar a mano
              (separados por espacios, comas, punto y coma o saltos de línea).
            </small>
          </div>
        </div>

        {{-- datalist hoteles (se mantiene) --}}
        <datalist id="hotelList"></datalist>

        <div class="mt-4 overflow-auto">
          <table class="min-w-full text-sm border-separate border-spacing-0" id="codesTable">
            <thead>
              <tr class="bg-slate-50">
                <th class="sticky left-0 z-10 bg-slate-50 text-left p-3 border-b">Hotel (GIATA)</th>
                <th class="p-3 border-b text-left text-slate-400">Cargando proveedores…</th>
              </tr>
            </thead>
            <tbody id="rowsBody">
              <tr>
                <td class="p-4 text-slate-500" colspan="99">Cargando…</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm">
          <div id="metaText" class="text-slate-600">—</div>

          <div class="flex gap-2">
            <button id="btnExport" type="button" class="px-3 py-1 border rounded bg-slate-50 hover:bg-slate-100">
              Exportar Excel
            </button>

            <button id="prevBtn" class="px-3 py-1 border rounded disabled:opacity-50">Anterior</button>
            <button id="nextBtn" class="px-3 py-1 border rounded disabled:opacity-50">Siguiente</button>
          </div>
        </div>

      </div>
    </div>
  </div>
  <script>
    (function() {
      const qs = (s, el = document) => el.querySelector(s);
      const qsa = (s, el = document) => Array.from(el.querySelectorAll(s));

      console.log('GIATA JS CARGADO ✅');

      const perf = {
        last: null,
        fmt(ms) {
          return `${ms.toFixed(0)} ms`;
        },
        now() {
          return performance.now();
        }
      };

      function logPerf(label, t0) {
        const ms = perf.now() - t0;
        console.log(`⏱️ ${label}: ${perf.fmt(ms)}`);
        return ms;
      }


      const apiUrl = "{{ url('/giata/codes') }}";
      const hotelsApiUrl = "{{ url('/giata/hotels-suggest') }}";
      const exportUrl = "{{ route('giata.codes.export') }}";

      const perSel = qs('#perPage');
      const rowsEl = qs('#rowsBody');
      const metaEl = qs('#metaText');
      const prevBtn = qs('#prevBtn');
      const nextBtn = qs('#nextBtn');
      const btnExport = qs('#btnExport');

      const provMulti = qs('#provMulti');
      const clearProv = qs('#clearProv');
      const provSelected = qs('#provSelected');

      const provToggle = qs('#provToggle');
      const provDropdown = qs('#provDropdown');
      const provOptions = qs('#provOptions');
      const provChips = qs('#provChips');

      const hotelSearch = qs('#hotelSearch');
      const clearHotel = qs('#clearHotel');
      const hotelList = qs('#hotelList');

      const giataFilter = qs('#giataFilter');
      const clearGiata = qs('#clearGiata');

      let allProviders = [];
      let providers = [];
      let page = 1;
      let hotelSuggestMap = {}; // name -> giata_id

      let lastRows = [];
      let selectedCodes = [];

      // loading fake bar
      let loadingInterval = null;
      let loadingPercent = 0;
      let lastPickWasSuggestion = false;

      // ==========================
      // Helpers UI / parse
      // ==========================
      const splitCodes = (raw) => String(raw).split('|').map(s => s.trim()).filter(Boolean);

      function createCodeCell(rawValue) {
        const td = document.createElement('td');
        td.className = 'p-3 border-b align-top whitespace-nowrap';

        if (!rawValue || String(rawValue).trim() === '—') {
          td.innerHTML = '<span class="text-slate-400">—</span>';
          return td;
        }

        const codes = splitCodes(rawValue);
        if (codes.length <= 1) {
          td.textContent = codes[0];
          return td;
        }

        const main = document.createElement('span');
        main.textContent = codes[0];

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ml-2 inline-flex items-center rounded border border-slate-300 px-1.5 py-0.5 text-xs hover:bg-slate-50';
        btn.setAttribute('aria-expanded', 'false');
        btn.textContent = `+${codes.length - 1}`;

        const menu = document.createElement('div');
        menu.className = 'hidden mt-1 absolute z-20 rounded-md border border-slate-200 bg-white shadow-sm';
        menu.style.minWidth = '14rem';
        menu.setAttribute('data-gcodes-menu', '1');

        const ul = document.createElement('ul');
        ul.className = 'max-h-56 overflow-auto text-sm';
        codes.slice(1).forEach(code => {
          const li = document.createElement('li');
          li.className = 'px-3 py-2 hover:bg-slate-50';
          li.textContent = code;
          ul.appendChild(li);
        });
        menu.appendChild(ul);

        const wrap = document.createElement('div');
        wrap.className = 'relative inline-block align-top';
        wrap.appendChild(main);
        wrap.appendChild(btn);
        wrap.appendChild(menu);

        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          document.querySelectorAll('[data-gcodes-menu]').forEach(m => m.classList.add('hidden'));
          const open = menu.classList.contains('hidden');
          if (open) {
            menu.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
          } else {
            menu.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
          }
        });

        document.addEventListener('click', () => {
          if (!menu.classList.contains('hidden')) {
            menu.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
          }
        });

        td.appendChild(wrap);
        return td;
      }

      const renderProvidersHeader = () => {
        const thead = qs('#codesTable thead tr');
        qsa('th', thead).slice(1).forEach(th => th.remove());

        providers.forEach(p => {
          const th = document.createElement('th');
          th.className = 'p-3 border-b text-left';
          th.textContent = p.name ?? p.provider_code;
          thead.appendChild(th);
        });
      };

      const renderRows = (rows) => {
        rowsEl.innerHTML = '';
        if (!rows.length) {
          rowsEl.innerHTML = '<tr><td class="p-4 text-slate-500" colspan="99">Sin resultados</td></tr>';
          return;
        }

        rows.forEach(r => {
          const tr = document.createElement('tr');
          tr.className = 'odd:bg-white even:bg-slate-50';

          const tdHotel = document.createElement('td');
          tdHotel.className = 'sticky left-0 z-10 bg-inherit p-3 border-b align-top';
          tdHotel.innerHTML = `
          <div class="font-medium">${r.hotel_name ?? '—'}</div>
          <div class="text-xs text-slate-500">GIATA #${r.giata_id}</div>
        `;
          tr.appendChild(tdHotel);

          providers.forEach(p => {
            const value = (r.codes && r.codes[p.id]) ? r.codes[p.id] : '—';
            tr.appendChild(createCodeCell(value));
          });

          rowsEl.appendChild(tr);
        });
      };

      // ==========================
      // Loading bar (fake)
      // ==========================
      const setLoading = () => {
        rowsEl.innerHTML = `
        <tr>
          <td colspan="99" class="p-6">
            <div class="progress-wrapper">
              <div class="progress-bar">
                <div id="progressBarFill" class="progress-bar-fill"></div>
              </div>
              <div id="progressPercent" class="progress-percent">Cargando 0%</div>
            </div>
          </td>
        </tr>
      `;

        loadingPercent = 0;
        const fillEl = document.getElementById('progressBarFill');
        const textEl = document.getElementById('progressPercent');

        if (loadingInterval) clearInterval(loadingInterval);
        loadingInterval = setInterval(() => {
          if (loadingPercent < 90) {
            loadingPercent += 5;
            if (fillEl) fillEl.style.width = `${loadingPercent}%`;
            if (textEl) textEl.textContent = `Cargando ${loadingPercent}%`;
          }
        }, 120);
      };

      const finishLoading = async () => {
        const fillEl = document.getElementById('progressBarFill');
        const textEl = document.getElementById('progressPercent');

        if (loadingInterval) {
          clearInterval(loadingInterval);
          loadingInterval = null;
        }

        loadingPercent = 100;
        if (fillEl) fillEl.style.width = '100%';
        if (textEl) textEl.textContent = 'Cargando 100%';

        await new Promise(res => setTimeout(res, 120));
      };

      // ==========================
      // Providers multi-select
      // ==========================
      let dropdownOpen = false;
      let activeIdx = -1;
      let filteredProviders = [];

      const openDropdown = () => {
        if (!provDropdown) return;
        provDropdown.classList.remove('hidden');
        dropdownOpen = true;
      };

      const closeDropdown = () => {
        if (!provDropdown) return;
        provDropdown.classList.add('hidden');
        dropdownOpen = false;
        activeIdx = -1;
        paintOptions();
      };

      const toggleDropdown = () => dropdownOpen ? closeDropdown() : openDropdown();

      const isSelected = (code) =>
        selectedCodes.some(c => String(c).toLowerCase() === String(code).toLowerCase());

      const addProvider = (code) => {
        if (!code) return;
        if (selectedCodes.length >= 10) return;
        if (isSelected(code)) return;

        selectedCodes.push(code);
        renderChips();
        renderSelectedCodes();
      };

      const removeProvider = (code) => {
        const lc = String(code).toLowerCase();
        selectedCodes = selectedCodes.filter(c => String(c).toLowerCase() !== lc);
        renderChips();
        renderSelectedCodes();
      };

      const renderChips = () => {
        if (!provChips) return;
        provChips.innerHTML = '';
        selectedCodes.forEach(code => {
          const chip = document.createElement('span');
          chip.className = 'prov-chip';
          chip.innerHTML = `<span>${code}</span><button type="button" aria-label="Quitar">✕</button>`;
          chip.querySelector('button').addEventListener('click', () => {
            removeProvider(code);
            page = 1;
            fetchPage();
          });
          provChips.appendChild(chip);
        });
      };

      const renderSelectedCodes = () => {
        provSelected.textContent = selectedCodes.length ?
          `Seleccionados: ${selectedCodes.join(', ')}` :
          'Sin proveedores seleccionados.';
      };

      const paintOptions = () => {
        if (!provOptions) return;
        provOptions.innerHTML = '';

        if (!filteredProviders.length) {
          const li = document.createElement('li');
          li.className = 'text-slate-500';
          li.textContent = 'Sin resultados…';
          provOptions.appendChild(li);
          return;
        }

        filteredProviders.forEach((p, idx) => {
          const li = document.createElement('li');
          li.className = (idx === activeIdx) ? 'active' : '';

          const code = p.provider_code;
          const label = p.name ?? p.provider_code;
          const selectedMark = isSelected(code) ? ' ✓' : '';

          li.textContent = `${label} (${code})${selectedMark}`;

          li.addEventListener('mousedown', (e) => {
            e.preventDefault();
            if (!isSelected(code) && selectedCodes.length < 10) {
              addProvider(code);
              page = 1;
              fetchPage();
            }
            provMulti.focus();
            openDropdown();
            filterOptions(provMulti.value);
          });

          provOptions.appendChild(li);
        });

        if (activeIdx >= 0) {
          const activeEl = provOptions.children[activeIdx];
          activeEl?.scrollIntoView({
            block: 'nearest'
          });
        }
      };

      const filterOptions = (term) => {
        const t = String(term || '').trim().toLowerCase();

        if (!allProviders.length) {
          filteredProviders = [];
          paintOptions();
          return;
        }

        filteredProviders = allProviders
          .filter(p => {
            const code = String(p.provider_code || '').toLowerCase();
            const name = String(p.name || '').toLowerCase();
            if (!t) return true;
            return code.includes(t) || name.includes(t);
          })
          .slice(0, 50);

        activeIdx = filteredProviders.length ? 0 : -1;
        paintOptions();
      };

      const getSelectedProviders = () => {
        const lower = selectedCodes.map(c => String(c).toLowerCase());
        return allProviders.filter(p =>
          lower.includes(String(p.provider_code || '').toLowerCase())
        );
      };

      const filterProvidersWithData = (rows) => {
        const selected = getSelectedProviders();
        if (selected.length) return selected;

        const used = new Set();
        rows.forEach(r => {
          if (!r.codes) return;
          Object.entries(r.codes).forEach(([pid, val]) => {
            if (val && String(val).trim() !== '') used.add(Number(pid));
          });
        });

        let filtered = allProviders.filter(p => used.has(p.id));

        const itxIdx = filtered.findIndex(p => (p.provider_code || '').toLowerCase() === 'itravex');
        if (itxIdx > 0) {
          const [itx] = filtered.splice(itxIdx, 1);
          filtered.unshift(itx);
        }

        return filtered;
      };

      // ==========================
      // Fetch main page (GET / POST)
      // ==========================
      const fetchPage = async () => {
        try {
          setLoading();

          let term = (hotelSearch.value || '').trim();
          const tAll = perf.now();
          const tReq = perf.now();

          let giataIds = [];
          const giataRaw = (giataFilter?.value || '').trim();
          if (giataRaw) {
            giataIds = giataRaw
              .split(/[\s,;]+/)
              .map(v => v.trim())
              .filter(v => v !== '' && /^\d+$/.test(v));
          }
          // no filtres por nombre (para no machacar la lista)
          if (lastPickWasSuggestion || giataIds.length) {
            term = '';
            lastPickWasSuggestion = false;
          }

          const USE_POST_THRESHOLD = 500;
          const usePost = giataIds.length > USE_POST_THRESHOLD;

          let res;

          if (!usePost) {
            const params = new URLSearchParams();
            if (term) params.set('q', term);
            giataIds.forEach(id => params.append('giata_ids[]', id));
            selectedCodes.forEach(code => params.append('providers[]', code));
            params.set('per_page', perSel.value);
            params.set('page', String(page));

            res = await fetch(`${apiUrl}?${params.toString()}`, {
              headers: {
                'Accept': 'application/json'
              }
            });
          } else {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            res = await fetch(apiUrl, {
              method: 'POST',
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...(csrf ? {
                  'X-CSRF-TOKEN': csrf
                } : {})
              },
              body: JSON.stringify({
                q: term || null,
                per_page: Number(perSel.value),
                page: Number(page),
                giata_ids: giataIds,
                providers: selectedCodes
              })
            });
          }

          if (!res.ok) {
            const text = await res.text();
            if (loadingInterval) {
              clearInterval(loadingInterval);
              loadingInterval = null;
            }
            console.error('Error HTTP', res.status, text);
            rowsEl.innerHTML = `<tr><td class="p-4 text-red-600" colspan="99">Error ${res.status}. Mira consola.</td></tr>`;
            return;
          }

          const contentType = res.headers.get('content-type') || '';
          if (!contentType.includes('application/json')) {
            const text = await res.text();
            if (loadingInterval) {
              clearInterval(loadingInterval);
              loadingInterval = null;
            }
            console.error('Respuesta no JSON:', text);
            rowsEl.innerHTML = `<tr><td class="p-4 text-red-600" colspan="99">El servidor devolvió HTML en vez de JSON.</td></tr>`;
            return;
          }
          logPerf('HTTP (hasta headers OK)', tReq);
          const tJson = perf.now();
          const json = await res.json();
          logPerf('JSON parse', tJson);
          const tCompute = perf.now();

          await finishLoading();

          if (!allProviders.length && Array.isArray(json.providers)) {
            allProviders = json.providers;
            filterOptions('');
          }

          lastRows = json.data || [];
          providers = filterProvidersWithData(lastRows);
          logPerf('compute providers (filterProvidersWithData)', tCompute);
          const tRender = perf.now();


          renderProvidersHeader();
          renderRows(lastRows);
          logPerf('render DOM (header+rows)', tRender);
          logPerf('TOTAL fetchPage()', tAll);


          const m = json.meta || {
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: perSel.value
          };
          page = m.current_page;

          metaEl.textContent = `Página ${m.current_page} de ${m.last_page} · ${m.total} resultados`;
          prevBtn.disabled = (page <= 1);
          nextBtn.disabled = (page >= m.last_page);

        } catch (e) {
          if (loadingInterval) {
            clearInterval(loadingInterval);
            loadingInterval = null;
          }
          console.error(e);
          rowsEl.innerHTML = `<tr><td class="p-4 text-red-600" colspan="99">Error de red o parseo JSON.</td></tr>`;
        }
      };

      // ==========================
      // Export (POST server-side)
      // ==========================
      async function handleExport(e) {
        e.preventDefault();
        e.stopPropagation();

        console.log('EXPORT: click ✅');

        const term = (hotelSearch.value || '').trim();

        let giataIds = [];
        const giataRaw = (giataFilter?.value || '').trim();
        if (giataRaw) {
          giataIds = giataRaw
            .split(/[\s,;]+/)
            .map(v => v.trim())
            .filter(v => v !== '' && /^\d+$/.test(v));
        }

        const payload = {
          q: term || null,
          per_page: Number(perSel.value),
          page: Number(page),
          giata_ids: giataIds,
          providers: selectedCodes,
          export_all: true
        };

        console.log('EXPORT payload', {
          q: payload.q,
          per_page: payload.per_page,
          page: payload.page,
          giata_ids_len: payload.giata_ids.length,
          providers_len: payload.providers.length
        });

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        console.log('CSRF?', !!csrf);

        try {
          const res = await fetch(exportUrl, {
            method: 'POST',
            headers: {
              'Accept': 'text/csv',
              'Content-Type': 'application/json',
              ...(csrf ? {
                'X-CSRF-TOKEN': csrf
              } : {})
            },
            body: JSON.stringify(payload)
          });

          console.log('EXPORT status', res.status, res.headers.get('content-type'));

          if (!res.ok) {
            const text = await res.text();
            console.error('EXPORT FAIL', res.status, text.slice(0, 800));
            alert(`Error exportando (${res.status}). Mira consola.`);
            return;
          }

          const blob = await res.blob();
          console.log('EXPORT blob size', blob.size);

          const disposition = res.headers.get('content-disposition') || '';
          const match = disposition.match(/filename="?([^"]+)"?/i);
          const filename = match?.[1] || `giata_codes_page_${page}.csv`;

          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = filename;
          a.style.display = 'none';
          document.body.appendChild(a);
          a.click();

          setTimeout(() => {
            URL.revokeObjectURL(url);
            a.remove();
          }, 0);

          console.log('EXPORT OK ✅');
        } catch (err) {
          console.error('EXPORT exception', err);
          alert('Error exportando. Mira consola.');
        }
      }

      // Enganchar export SOLO una vez
      btnExport?.addEventListener('click', handleExport, true);

      // ==========================
      // Autocomplete hoteles
      // ==========================
      let hotelAbortController = null;

      async function fetchHotelSuggestions(term) {
        if (term.length < 3) {
          hotelList.innerHTML = '';
          return;
        }

        if (hotelAbortController) hotelAbortController.abort();
        hotelAbortController = new AbortController();

        try {
          const params = new URLSearchParams({
            q: term
          });
          const res = await fetch(`${hotelsApiUrl}?${params.toString()}`, {
            headers: {
              'Accept': 'application/json'
            },
            signal: hotelAbortController.signal
          });
          if (!res.ok) return;

          const data = await res.json();
          hotelList.innerHTML = '';
          hotelSuggestMap = {};

          data.forEach(h => {
            hotelSuggestMap[h.name] = h.giata_id;
            const opt = document.createElement('option');
            opt.value = h.name || '';
            opt.label = `${h.name || ''} (GIATA: ${h.giata_id ?? '—'})`;
            hotelList.appendChild(opt);
          });
        } catch (e) {
          /* ignore abort */
        }
      }

      hotelSearch.addEventListener('input', (e) => fetchHotelSuggestions(e.target.value || ''));

      function applyHotelSelection() {
        const name = (hotelSearch.value || '').trim();
        if (!name) return;

        const gid = hotelSuggestMap[name];

        // Si viene de sugerencia (tiene GIATA), lo añadimos a la lista
        if (gid) {
          const current = (giataFilter.value || '').trim();
          const parts = current ?
            current.split(/[\s,;]+/).map(x => x.trim()).filter(Boolean) : [];

          const set = new Set(parts);
          set.add(String(gid));
          giataFilter.value = Array.from(set).join(', ');

          lastPickWasSuggestion = true;

          // opcional pero recomendado: deja el campo listo para buscar otro
          hotelSearch.value = '';
          hotelList.innerHTML = '';
        }

        page = 1;
        fetchPage();
      }

      hotelSearch.addEventListener('change', applyHotelSelection);
      hotelSearch.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
          e.preventDefault();
          applyHotelSelection();
        }
      });

      clearHotel.addEventListener('click', () => {
        hotelSearch.value = '';
        hotelList.innerHTML = '';
        page = 1;
        fetchPage();
      });

      // ======================
      // Paginación / filtros
      // ======================
      perSel.addEventListener('change', () => {
        page = 1;
        fetchPage();
      });

      prevBtn.addEventListener('click', () => {
        if (page > 1) {
          page--;
          fetchPage();
        }
      });

      nextBtn.addEventListener('click', () => {
        page++;
        fetchPage();
      });

      clearGiata?.addEventListener('click', () => {
        giataFilter.value = '';
        page = 1;
        fetchPage();
      });

      // ======================
      // Dropdown providers events
      // ======================
      provToggle?.addEventListener('click', () => {
        toggleDropdown();
        filterOptions(provMulti.value || '');
        provMulti.focus();
      });

      provMulti.addEventListener('input', () => {
        filterOptions(provMulti.value || '');
        openDropdown();
      });

      provMulti.addEventListener('keydown', (e) => {
        if (!dropdownOpen && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
          openDropdown();
          filterOptions(provMulti.value || '');
        }

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          if (!filteredProviders.length) return;
          activeIdx = Math.min(activeIdx + 1, filteredProviders.length - 1);
          paintOptions();
        }

        if (e.key === 'ArrowUp') {
          e.preventDefault();
          if (!filteredProviders.length) return;
          activeIdx = Math.max(activeIdx - 1, 0);
          paintOptions();
        }

        if (e.key === 'Enter') {
          e.preventDefault();
          if (!filteredProviders.length || activeIdx < 0) return;
          const p = filteredProviders[activeIdx];
          const code = p.provider_code;

          if (!isSelected(code) && selectedCodes.length < 10) {
            addProvider(code);
            page = 1;
            fetchPage();
          }

          openDropdown();
          filterOptions(provMulti.value || '');
        }

        if (e.key === 'Escape') {
          e.preventDefault();
          closeDropdown();
        }
      });

      document.addEventListener('click', (e) => {
        const wrap = provDropdown?.parentElement;
        if (!wrap) return;
        const clickedInside = wrap.contains(e.target);
        if (!clickedInside) closeDropdown();
      });

      clearProv.addEventListener('click', () => {
        selectedCodes = [];
        provMulti.value = '';
        renderChips();
        renderSelectedCodes();
        page = 1;
        fetchPage();
      });

      // Init
      renderChips();
      renderSelectedCodes();
      fetchPage();

    })();

    window.addEventListener('pageshow', (e) => {
      if (e.persisted) window.location.reload();
    });
  </script>


</x-app-layout>