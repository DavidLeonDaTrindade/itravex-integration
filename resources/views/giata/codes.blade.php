{{-- resources/views/giata/codes.blade.php --}}
<style>
  .per-page-select {
    padding-right: 2.2rem !important;
    /* mueve SOLO la flecha hacia la derecha */
  }
</style>
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
    /* slate-300 */
    border-radius: 9999px;
    overflow: hidden;
  }

  .progress-bar-fill {
    height: 100%;
    width: 0%;
    background: #004665;
    /* color corporativo */
    border-radius: 9999px;
    transition: width 0.15s linear;
  }

  .progress-percent {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #64748b;
    /* slate-500 */
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
              <label class="text-sm text-slate-600 whitespace-nowrap">
                Por página
              </label>

              <select id="perPage"
                class="rounded-md border border-slate-300 px-3 py-2 text-sm per-page-select">
                <option>25</option>
                <option>50</option>
                <option>100</option>
              </select>
            </div>


          </div>
        </div>



        {{-- Fila 2: selección de proveedores (hasta 10) --}}
        <div class="flex flex-col md:flex-row items-start md:items-center gap-10">
          <div class="flex flex-col gap-1 w-full md:w-auto">
            <div class="flex items-center gap-2">
              <label class="text-sm text-slate-600 whitespace-nowrap">
                Proveedores (máx. 10)
              </label>
              <input id="provMulti"
                list="provList"
                placeholder="Ej: itravex, hotelbeds..."
                class="flex-1 min-w-[260px] rounded-md border border-slate-300 px-3 py-2 text-sm" />
              <button id="clearProv"
                class="rounded-md border border-slate-300 px-2 py-2 text-xs hover:bg-slate-50">
                Limpiar
              </button>
            </div>
            <div id="provSelected"
              class="text-xs text-slate-600">
              {{-- Aquí mostraremos los seleccionados --}}
            </div>
          </div>

          <div class="text-xs text-slate-500">
            Selecciona hasta 10 <code>provider_code</code>.<br>
            Si lo dejas vacío, se mostrarán solo proveedores con datos en la página
            (itravex primero si aplica).
          </div>
        </div>

        {{-- Fila 3: subir Excel con GIATA + campo de filtro GIATA --}}
        <div class="flex flex-col md:flex-row md:items-center gap-3">
          {{-- Form solo para subir el XLSX con GIATA --}}
          <form method="POST"
            action="{{ route('giata.codes.uploadGiata') }}"
            enctype="multipart/form-data"
            class="flex flex-col gap-2 md:flex-row md:items-end">
            @csrf
            <div class="flex flex-col gap-1 w-full md:w-[320px]">
              <label class="text-sm text-slate-600">
                Cargar GIATA desde Excel (.xlsx)
              </label>
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
              <label class="text-sm text-slate-600">
                Filtro de GIATA (lista de IDs)
              </label>
              <button id="clearGiata"
                type="button"
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

        <datalist id="provList"></datalist>
        <datalist id="hotelList"></datalist>

        <div class="mt-4 overflow-auto">
          <table class="min-w-full text-sm border-separate border-spacing-0" id="codesTable">
            <thead>
              <tr class="bg-slate-50">
                <th class="sticky left-0 z-10 bg-slate-50 text-left p-3 border-b">Hotel (GIATA)</th>
                <th id="providersLoading" class="p-3 border-b text-left text-slate-400">
                  Cargando proveedores…
                </th>
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
            {{-- Botón de exportación --}}
            <button id="btnExport"
              class="px-3 py-1 border rounded bg-slate-50 hover:bg-slate-100">
              Exportar Excel
            </button>

            <button id="prevBtn" class="px-3 py-1 border rounded disabled:opacity-50">Anterior</button>
            <button id="nextBtn" class="px-3 py-1 border rounded disabled:opacity-50">Siguiente</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>

  <script>
    (function() {
      const qs = (s, el = document) => el.querySelector(s);
      const qsa = (s, el = document) => Array.from(el.querySelectorAll(s));

      const apiUrl = "{{ url('/giata/codes') }}"; // JSON GIATA
      const hotelsApiUrl = "{{ url('/giata/hotels-suggest') }}"; // JSON hoteles (tabla hotels)

      const perSel = qs('#perPage');
      const rowsEl = qs('#rowsBody');
      const metaEl = qs('#metaText');
      const prevBtn = qs('#prevBtn');
      const nextBtn = qs('#nextBtn');
      const btnExport = qs('#btnExport');

      const provMulti = qs('#provMulti');
      const clearProv = qs('#clearProv');
      const provList = qs('#provList');
      const provSelected = qs('#provSelected');

      const hotelSearch = qs('#hotelSearch');
      const clearHotel = qs('#clearHotel');
      const hotelList = qs('#hotelList');

      const giataFilter = qs('#giataFilter');
      const clearGiata = qs('#clearGiata');

      let allProviders = []; // lista completa (cache)
      let providers = []; // columnas activas (filtradas)
      let page = 1;
      let loadingInterval = null;
      let loadingPercent = 0;

      // cache de filas de la página actual
      let lastRows = [];

      // lista real de provider_code seleccionados (estado)
      let selectedCodes = [];

      // ==========================
      // Helpers UI/parse
      // ==========================
      const splitCodes = (raw) =>
        String(raw).split('|').map(s => s.trim()).filter(Boolean);

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
        btn.className =
          'ml-2 inline-flex items-center rounded border border-slate-300 px-1.5 py-0.5 text-xs hover:bg-slate-50';
        btn.setAttribute('aria-expanded', 'false');
        btn.textContent = `+${codes.length - 1}`;

        const menu = document.createElement('div');
        menu.className =
          'hidden mt-1 absolute z-20 rounded-md border border-slate-200 bg-white shadow-sm';
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
          rowsEl.innerHTML =
            '<tr><td class="p-4 text-slate-500" colspan="99">Sin resultados</td></tr>';
          return;
        }

        rows.forEach(r => {
          const tr = document.createElement('tr');
          tr.className = 'odd:bg-white even:bg-slate-50';

          const tdHotel = document.createElement('td');
          tdHotel.className =
            'sticky left-0 z-10 bg-inherit p-3 border-b align-top';
          tdHotel.innerHTML = `
            <div class="font-medium">${r.hotel_name ?? '—'}</div>
            <div class="text-xs text-slate-500">GIATA #${r.giata_id}</div>
          `;
          tr.appendChild(tdHotel);

          providers.forEach(p => {
            const value = (r.codes && r.codes[p.id]) ? r.codes[p.id] : '—';
            const td = createCodeCell(value);
            tr.appendChild(td);
          });

          rowsEl.appendChild(tr);
        });
      };

            const setLoading = () => {
        // Pintamos la fila con la barra y el porcentaje
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

        // Reseteamos estado
        loadingPercent = 0;
        const fillEl = document.getElementById('progressBarFill');
        const textEl = document.getElementById('progressPercent');

        if (loadingInterval) {
          clearInterval(loadingInterval);
          loadingInterval = null;
        }

        // Animación "falsa" hasta ~90% mientras esperamos el fetch real
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

        // Pequeña pausa para que el usuario vea el 100%
        await new Promise(res => setTimeout(res, 150));
      };




      // Helpers selección proveedores
      const codeEq = (a, b) =>
        String(a || '').toLowerCase() === String(b || '').toLowerCase();
      const byCode = (code) =>
        allProviders.find(p => codeEq(p.provider_code, code));

      // Devuelve los objetos proveedor a partir de selectedCodes
      const getSelectedProviders = () => {
        const lower = selectedCodes.map(c => String(c).toLowerCase());
        return allProviders.filter(p =>
          lower.includes(String(p.provider_code || '').toLowerCase())
        );
      };

      // Pinta debajo del input los códigos seleccionados
      const renderSelectedCodes = () => {
        if (!selectedCodes.length) {
          provSelected.textContent = 'Sin proveedores seleccionados.';
          return;
        }
        provSelected.textContent = `Seleccionados: ${selectedCodes.join(', ')}`;
      };

      // Mete en selectedCodes (máx. 10) los códigos que haya en el input
      const commitInputProviders = () => {
        const raw = provMulti.value || '';
        const tokens = raw
          .split(/[,\s]+/)
          .map(c => c.trim())
          .filter(Boolean);

        let changed = false;

        for (const token of tokens) {
          const prov = byCode(token);
          if (!prov) continue;

          const code = prov.provider_code || '';
          const lc = code.toLowerCase();
          const already = selectedCodes.some(c => c.toLowerCase() === lc);
          if (!already && selectedCodes.length < 10) {
            selectedCodes.push(code);
            changed = true;
          }
        }

        // Dejamos el input vacío para que el datalist vuelva a funcionar perfecto
        provMulti.value = '';

        if (changed) {
          renderSelectedCodes();
        }
      };

      const fillProviderDatalist = () => {
        provList.innerHTML = '';
        allProviders.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.provider_code;
          opt.label = p.name || p.provider_code;
          provList.appendChild(opt);
        });
      };

      const filterProvidersWithData = (rows) => {
        // 1) Si el usuario ha seleccionado proveedores, respetamos su selección.
        const selected = getSelectedProviders();
        if (selected.length) return selected;

        // 2) Si no hay selección, mostramos solo proveedores con datos en la página.
        const used = new Set();
        rows.forEach(r => {
          if (!r.codes) return;
          Object.entries(r.codes).forEach(([pid, val]) => {
            if (val && String(val).trim() !== '') used.add(Number(pid));
          });
        });

        let filtered = allProviders.filter(p => used.has(p.id));

        // Poner 'itravex' primero si está
        const itxIdx = filtered.findIndex(
          p => (p.provider_code || '').toLowerCase() === 'itravex'
        );
        if (itxIdx > 0) {
          const [itx] = filtered.splice(itxIdx, 1);
          filtered.unshift(itx);
        }

        return filtered;
      };

      const fetchPage = async () => {
        setLoading();
        const params = new URLSearchParams();

        const term = (hotelSearch.value || '').trim();
        if (term) params.set('q', term);

        // Pasar lista de GIATA (si hay en el textarea) como giata_ids[]
        // Solo tokens numéricos => así si el usuario pone "123456, CODIGO"
        // se usa 123456 y se ignora "CODIGO".
        if (giataFilter) {
          const giataRaw = (giataFilter.value || '').trim();
          if (giataRaw) {
            giataRaw
              .split(/[\s,;]+/) // separa por espacios, comas, punto y coma
              .map(v => v.trim())
              .filter(v => v !== '' && /^\d+$/.test(v)) // solo números enteros
              .forEach(id => params.append('giata_ids[]', id));
          }
        }

        // Pasar lista de proveedores seleccionados (provider_code) como providers[]
        if (selectedCodes.length) {
          selectedCodes.forEach(code => {
            params.append('providers[]', code);
          });
        }

        params.set('per_page', perSel.value);
        params.set('page', page);

        const res = await fetch(`${apiUrl}?${params.toString()}`, {
          headers: {
            'Accept': 'application/json'
          }
        });
        const json = await res.json();

        if (!allProviders.length && Array.isArray(json.providers)) {
          allProviders = json.providers;
          fillProviderDatalist();
        }

        const rows = json.data || [];
        lastRows = rows;

        providers = filterProvidersWithData(rows);
        renderProvidersHeader();
        renderRows(rows);

        const m = json.meta || {
          current_page: 1,
          last_page: 1,
          total: 0,
          per_page: perSel.value
        };

        page = m.current_page;

        metaEl.textContent =
          `Página ${m.current_page} de ${m.last_page} · ${m.total} resultados`;
        prevBtn.disabled = (page <= 1);
        nextBtn.disabled = (page >= m.last_page);
      };

      // --- Exportar tabla actual a CSV (Excel) ---
      function exportCurrentTableToCsv() {
        if (!lastRows.length || !providers.length) {
          alert('No hay datos que exportar.');
          return;
        }

        // Cabecera
        const header = [
          'Hotel',
          'GIATA ID',
          ...providers.map(p => p.name || p.provider_code || '')
        ];

        const dataRows = lastRows.map(r => {
          const row = [
            r.hotel_name ?? '',
            r.giata_id ?? ''
          ];

          providers.forEach(p => {
            const value = (r.codes && r.codes[p.id]) ? String(r.codes[p.id]) : '';
            row.push(value.replace(/\s+/g, ' '));
          });

          return row;
        });

        const allRows = [header, ...dataRows];

        const csv = allRows
          .map(cols =>
            cols
            .map(v => `"${String(v).replace(/"/g, '""')}"`)
            .join(';')
          )
          .join('\r\n');

        const blob = new Blob([csv], {
          type: 'text/csv;charset=utf-8;'
        });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = `giata_codes_page_${page}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      }

      // ======================
      // Autocomplete hoteles
      // ======================
      let hotelAbortController = null;

      async function fetchHotelSuggestions(term) {
        if (term.length < 3) {
          hotelList.innerHTML = '';
          return;
        }

        // Cancelar petición anterior
        if (hotelAbortController) {
          hotelAbortController.abort();
        }
        hotelAbortController = new AbortController();

        try {
          const params = new URLSearchParams({
            q: term
          });
          const res = await fetch(`${hotelsApiUrl}?${params.toString()}`, {
            headers: {
              'Accept': 'application/json'
            },
            signal: hotelAbortController.signal,
          });
          if (!res.ok) return;

          const data = await res.json();
          hotelList.innerHTML = '';

          data.forEach(h => {
            const opt = document.createElement('option');
            opt.value = h.name || '';
            opt.label = `${h.name || ''} (GIATA: ${h.giata_id ?? '—'})`;
            hotelList.appendChild(opt);
          });
        } catch (e) {
          // ignorar aborts
        }
      }

      // Cuando el usuario escribe en el buscador de hoteles
      hotelSearch.addEventListener('input', (e) => {
        const term = e.target.value || '';
        fetchHotelSuggestions(term);
      });

      // Cuando selecciona un hotel del datalist o pulsa Enter
      function applyHotelSelection() {
        const name = (hotelSearch.value || '').trim();
        if (!name) return;
        page = 1;
        fetchPage();
      }

      hotelSearch.addEventListener('change', () => {
        applyHotelSelection();
      });

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
      // Eventos básicos paginación
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

      const onCompareChange = () => {
        page = 1;
        fetchPage();
      };

      // Selección de proveedores:
      provMulti.addEventListener('change', () => {
        commitInputProviders();
        onCompareChange();
      });

      provMulti.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
          e.preventDefault();
          commitInputProviders();
          onCompareChange();
        }
      });

      clearProv.addEventListener('click', () => {
        selectedCodes = [];
        provMulti.value = '';
        renderSelectedCodes();
        onCompareChange();
      });

      // Limpiar filtro GIATA
      if (clearGiata && giataFilter) {
        clearGiata.addEventListener('click', () => {
          giataFilter.value = '';
          page = 1;
          fetchPage();
        });
      }

      // Exportar
      btnExport.addEventListener('click', exportCurrentTableToCsv);

      // Init
      renderSelectedCodes();
      fetchPage();
    })();
  </script>
</x-app-layout>