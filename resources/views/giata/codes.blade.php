{{-- resources/views/giata/codes.blade.php --}}
<x-app-layout>
  <div class="min-h-screen bg-slate-50 px-4 py-8">
    <div class="mx-auto max-w-7xl">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">GIATA – Códigos por hotel</h1>
        <p class="text-slate-600 text-sm mt-1">Busca por nombre de hotel, GIATA ID, código de proveedor o provider_code.</p>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3">
          <!-- Fila 1: búsqueda global + por página -->
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-2 w-full md:w-[420px]">
              <input id="q" type="text" placeholder="Buscar… (ej. Hurghada, 25, 35200, itravex)"
                     class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300" />
              <button id="btnSearch"
                      class="rounded-md border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Buscar</button>
            </div>
            <div class="flex items-center gap-2">
              <label class="text-sm text-slate-600">Por página</label>
              <select id="perPage" class="rounded-md border border-slate-300 px-2 py-2 text-sm">
                <option>25</option><option>50</option><option>100</option>
              </select>
            </div>
          </div>

          <!-- Fila 2: comparador de proveedores A/B -->
          <div class="flex flex-col md:flex-row items-start md:items-center gap-3">
            <div class="flex items-center gap-2">
              <label class="text-sm text-slate-600 w-32">Comparar A</label>
              <input id="provA" list="provList" placeholder="p. ej. itravex"
                     class="rounded-md border border-slate-300 px-3 py-2 text-sm w-56" />
              <button id="clearA" class="rounded-md border border-slate-300 px-2 py-2 text-xs hover:bg-slate-50">Limpiar</button>
            </div>
            <div class="flex items-center gap-2">
              <label class="text-sm text-slate-600 w-32 md:w-24">vs B</label>
              <input id="provB" list="provList" placeholder="p. ej. hotelbeds"
                     class="rounded-md border border-slate-300 px-3 py-2 text-sm w-56" />
              <button id="clearB" class="rounded-md border border-slate-300 px-2 py-2 text-xs hover:bg-slate-50">Limpiar</button>
            </div>
            <div class="text-xs text-slate-500">Si dejas ambos vacíos, se muestran solo proveedores con datos (itravex primero si aplica).</div>
          </div>
          <datalist id="provList"></datalist>
        </div>

        <div class="mt-4 overflow-auto">
          <table class="min-w-full text-sm border-separate border-spacing-0" id="codesTable">
            <thead>
              <tr class="bg-slate-50">
                <th class="sticky left-0 z-10 bg-slate-50 text-left p-3 border-b">Hotel (GIATA)</th>
                <th id="providersLoading" class="p-3 border-b text-left text-slate-400">Cargando proveedores…</th>
              </tr>
            </thead>
            <tbody id="rowsBody">
              <tr><td class="p-4 text-slate-500" colspan="99">Cargando…</td></tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm">
          <div id="metaText" class="text-slate-600">—</div>
          <div class="flex gap-2">
            <button id="prevBtn" class="px-3 py-1 border rounded disabled:opacity-50">Anterior</button>
            <button id="nextBtn" class="px-3 py-1 border rounded disabled:opacity-50">Siguiente</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const qs  = (s, el=document) => el.querySelector(s);
      const qsa = (s, el=document) => Array.from(el.querySelectorAll(s));
      const apiUrl  = "{{ url('/giata/codes') }}"; // JSON

      const q       = qs('#q');
      const btn     = qs('#btnSearch');
      const perSel  = qs('#perPage');
      const rowsEl  = qs('#rowsBody');
      const metaEl  = qs('#metaText');
      const prevBtn = qs('#prevBtn');
      const nextBtn = qs('#nextBtn');

      const provA   = qs('#provA');
      const provB   = qs('#provB');
      const clearA  = qs('#clearA');
      const clearB  = qs('#clearB');
      const provList= qs('#provList');

      let allProviders = []; // lista completa (cache)
      let providers    = []; // columnas activas (filtradas)
      let page = 1;

      // --- Helpers UI/parse ---
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
        if (codes.length <= 1) { td.textContent = codes[0]; return td; }

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
          if (open) { menu.classList.remove('hidden'); btn.setAttribute('aria-expanded','true'); }
          else { menu.classList.add('hidden'); btn.setAttribute('aria-expanded','false'); }
        });
        document.addEventListener('click', () => {
          if (!menu.classList.contains('hidden')) { menu.classList.add('hidden'); btn.setAttribute('aria-expanded','false'); }
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
            const td = createCodeCell(value);
            tr.appendChild(td);
          });

          rowsEl.appendChild(tr);
        });
      };

      const setLoading = () => {
        rowsEl.innerHTML = '<tr><td class="p-4 text-slate-500" colspan="99">Cargando…</td></tr>';
      };

      // Helpers para selección de proveedores por código (case-insensitive)
      const codeEq = (a,b) => String(a||'').toLowerCase() === String(b||'').toLowerCase();
      const byCode  = (code) => allProviders.find(p => codeEq(p.provider_code, code));

      const getSelectedProviders = () => {
        const sel = [];
        const a = provA.value.trim();
        const b = provB.value.trim();
        if (a) { const pa = byCode(a); if (pa) sel.push(pa); }
        if (b && !codeEq(a,b)) { const pb = byCode(b); if (pb) sel.push(pb); }
        return sel;
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
        // Si hay selección A/B, forzamos esas columnas (aunque no tengan datos)
        const selected = getSelectedProviders();
        if (selected.length) return selected;

        // Si no hay selección, mostramos solo proveedores con datos en la página
        const used = new Set();
        rows.forEach(r => {
          if (!r.codes) return;
          Object.entries(r.codes).forEach(([pid, val]) => {
            if (val && String(val).trim() !== '') used.add(Number(pid));
          });
        });
        let filtered = allProviders.filter(p => used.has(p.id));

        // Poner 'itravex' primero si está en la lista filtrada
        const itxIdx = filtered.findIndex(p => (p.provider_code || '').toLowerCase() === 'itravex');
        if (itxIdx > 0) { const [itx] = filtered.splice(itxIdx, 1); filtered.unshift(itx); }

        return filtered;
      };

      const fetchPage = async () => {
        setLoading();
        const params = new URLSearchParams();
        if (q.value.trim()) params.set('q', q.value.trim());
        params.set('per_page', perSel.value);
        params.set('page', page);

        const res  = await fetch(`${apiUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' }});
        const json = await res.json();

        if (!allProviders.length && Array.isArray(json.providers)) {
          allProviders = json.providers;
          fillProviderDatalist();
        }

        const rows = json.data || [];
        providers = filterProvidersWithData(rows);
        renderProvidersHeader();
        renderRows(rows);

        const m = json.meta || { current_page: 1, last_page: 1, total: 0, per_page: perSel.value };
        page = m.current_page;

        metaEl.textContent = `Página ${m.current_page} de ${m.last_page} · ${m.total} resultados`;
        prevBtn.disabled = (page <= 1);
        nextBtn.disabled = (page >= m.last_page);
      };

      // Eventos
      btn.addEventListener('click', () => { page = 1; fetchPage(); });
      perSel.addEventListener('change', () => { page = 1; fetchPage(); });
      q.addEventListener('keydown', (e) => { if (e.key === 'Enter') { page = 1; fetchPage(); }});
      prevBtn.addEventListener('click', () => { if (page > 1) { page--; fetchPage(); }});
      nextBtn.addEventListener('click', () => { page++; fetchPage(); });

      // Comparador A/B: al cambiar, recarga con mismas filas pero nuevas columnas
      const onCompareChange = () => { page = 1; fetchPage(); };
      provA.addEventListener('change', onCompareChange);
      provB.addEventListener('change', onCompareChange);
      provA.addEventListener('keydown', e => { if (e.key === 'Enter') onCompareChange(); });
      provB.addEventListener('keydown', e => { if (e.key === 'Enter') onCompareChange(); });
      clearA.addEventListener('click', () => { provA.value=''; onCompareChange(); });
      clearB.addEventListener('click', () => { provB.value=''; onCompareChange(); });

      // Init
      fetchPage();
    })();
  </script>
</x-app-layout>
