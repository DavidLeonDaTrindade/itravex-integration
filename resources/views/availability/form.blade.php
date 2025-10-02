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

          <form method="POST" action="{{ route('availability.search') }}" autocomplete="off" class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @csrf

            {{-- ======================== BÚSQUEDA POR ZONA ======================== --}}
            <div class="md:col-span-1 relative">
              <label for="area_name" class="block text-sm font-medium text-slate-700">
                Área (buscar por nombre)
              </label>

              <input id="area_name" type="text"
                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Escribe el nombre del área (p. ej., Tenerife)"
                autocomplete="off" />

              {{-- Contenedor de sugerencias --}}
              <div id="area_suggestions"
                class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg hidden max-h-72 overflow-auto">
                {{-- se pintan aquí las opciones --}}
              </div>

              <p class="mt-1 text-xs text-slate-500">
                Escribe el nombre; al elegir, se rellenará el código <strong>A-...</strong> automáticamente.
              </p>
            </div>

            <div class="md:col-span-1">
              <label for="codzge" class="block text-sm font-medium text-slate-700">Código de Zona</label>
              <input id="codzge" type="text" name="codzge" value="{{ old('codzge') }}"
                data-codzge
                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="A-39018" />
              <p class="mt-1 text-xs text-slate-500">Si se rellena, se ignorarán los códigos manuales (salvo intersección).</p>
            </div>

            {{-- ======================== CÓDIGOS MANUALES ======================== --}}
            <div class="md:col-span-2">
              <div class="flex items-center justify-between">
                <label for="hotel_codes" class="block text-sm font-medium text-slate-700">
                  Códigos de hotel (codser)
                </label>
                <span id="hotel_count" class="text-xs text-slate-500">0 hoteles</span>
              </div>
              <textarea id="hotel_codes" name="hotel_codes" rows="3"
                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="105971, 12345&#10;67890">{{ old('hotel_codes') }}</textarea>
              <p class="mt-1 text-xs text-slate-500">Separados por coma, espacios o saltos de línea.</p>
            </div>

            {{-- ======================== FECHAS / ADULTOS ======================== --}}
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

            <!-- ===== Habitaciones (multihabitación con edades de niños) ===== -->
            <div class="md:col-span-2 rounded-xl border border-slate-200 p-4">
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium text-slate-700">Habitaciones</h3>
                <button type="button" id="add-room"
                  class="text-sm text-blue-600 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg px-2 py-1">
                  Añadir habitación
                </button>
              </div>

              <!-- Template de una habitación -->
              <template id="room-template">
                <div class="room-row mt-4 rounded-lg border border-slate-200 p-4">
                  <div class="flex items-center justify-between">
                    <h4 class="text-sm font-medium text-slate-700">Habitación <span class="room-index"></span></h4>
                    <button type="button" class="remove-room text-xs text-red-600 hover:text-red-700">Quitar</button>
                  </div>

                  <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Adultos</label>
                      <input type="number" min="1" max="4" name="rooms[__i__][adl]" value="2"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 adl-input" required />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Niños</label>
                      <input type="number" min="0" max="3" name="rooms[__i__][chd]" value="0"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 chd-input" />
                      <p class="mt-1 text-xs text-slate-500">Máx. personas/hab: 4</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Edades de los niños</label>
                      <div class="ages-wrap grid grid-cols-3 gap-2">
                        <!-- aquí se pintan los inputs de edades -->
                      </div>
                      <p class="mt-1 text-xs text-slate-500">Se generarán tantos campos como niños (0–17 años).</p>
                    </div>
                  </div>
                </div>
              </template>

              <div id="rooms-container" class="mt-2"></div>
            </div>


            <div>
              <label for="codnac" class="block text-sm font-medium text-slate-700">Código de país (ISO 3166-1)</label>
              <input id="codnac" type="text" name="codnac" value="{{ old('codnac') }}" placeholder="ESP" maxlength="3"
                class="mt-1 block w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase" />
              <p class="mt-1 text-xs text-slate-500">ISO 3166-1 (p. ej. <strong>ESP</strong>, <strong>FRA</strong>, <strong>USA</strong>).</p>
            </div>

            {{-- ======================== OPCIONES DE RENDIMIENTO ======================== --}}
            <div class="grid grid-cols-2 gap-4 md:col-span-2">
              <div>
                <label for="timeout" class="block text-sm font-medium text-slate-700">Timeout (ms)</label>
                <input id="timeout" type="number" name="timeout" min="1000" max="60000" value="{{ old('timeout') }}" placeholder="8000"
                  class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
              </div>

              <div>
                <label for="numrst" class="block text-sm font-medium text-slate-700">
                  Resultados por página del proveedor (numrst)
                </label>
                <input id="numrst" type="number" name="numrst" min="1" max="500" value="{{ old('numrst', 20) }}" placeholder="20"
                  class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                <p class="mt-1 text-xs text-slate-500">Se aplica en <strong>source=provider</strong> (paginación nativa indpag/numrst)</p>
              </div>


              <div class="md:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between p-4 md:p-5">
                  <div>
                    <h3 class="text-sm font-medium text-slate-700">Credenciales del proveedor (opcional)</h3>
                    <span class="text-xs text-slate-500">Si no rellenas nada, se usan las de config()</span>
                  </div>
                  <button type="button" id="toggle-cred"
                    class="text-sm text-blue-600 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg px-2 py-1">
                    Mostrar
                  </button>
                </div>

                <div id="cred-panel" class="hidden border-t border-slate-200 p-4 md:p-6">
                  <div class="mt-2 grid grid-cols-1 gap-4 md:grid-cols-3">
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

            </div>



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

  <script>
    (function() {
      const inputName = document.getElementById('area_name');
      const suggestions = document.getElementById('area_suggestions');
      const inputCodzge = document.querySelector('input[data-codzge]');
      const textAreaHotels = document.getElementById('hotel_codes');
      const countEl = document.getElementById('hotel_count');

      // Endpoints (ajusta si tu ruta difiere)
      const ENDPOINT = @json(url('areas')); // /areas?q=...
      const HOTELS_ENDPOINT_BASE = @json(url('areas')); // /areas/{code}/hotels

      let abortCtrl = null;
      let debounceTimer = null;
      let lastItems = [];
      let activeIndex = -1;

      function countCodes(str) {
        if (!str) return 0;
        const tokens = String(str)
          .replace(/[\n\r;]/g, ' ')
          .split(/[,\s]+/g)
          .map(s => s.trim())
          .filter(Boolean);
        return new Set(tokens).size;
      }

      function updateCounter() {
        const n = countCodes(textAreaHotels.value);
        if (countEl) countEl.textContent = `${n} ${n === 1 ? 'hotel' : 'hoteles'}`;
      }

      function showSuggestions(items) {
        suggestions.innerHTML = '';
        if (!items || !items.length) {
          suggestions.classList.add('hidden');
          return;
        }
        const frag = document.createDocumentFragment();

        items.forEach((it, idx) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'w-full text-left px-3 py-2 hover:bg-slate-100 focus:bg-slate-100';
          btn.dataset.index = idx;
          btn.innerHTML = `
            <div class="text-sm font-medium">${escapeHtml(it.name ?? '')}</div>
            <div class="text-xs text-slate-500">${escapeHtml(it.code ?? '')}</div>
          `;
          btn.addEventListener('click', () => selectIndex(idx));
          frag.appendChild(btn);
        });

        suggestions.appendChild(frag);
        suggestions.classList.remove('hidden');
        activeIndex = -1;
        highlightActive();
      }

      (function() {
        const btn = document.getElementById('toggle-cred');
        const panel = document.getElementById('cred-panel');
        if (!btn || !panel) return;
        btn.addEventListener('click', () => {
          const open = panel.classList.toggle('hidden') === false;
          btn.textContent = open ? 'Ocultar' : 'Mostrar';
          btn.setAttribute('aria-expanded', String(open));
        });

      })();

      function highlightActive() {
        const children = Array.from(suggestions.children);
        children.forEach((el, i) => el.classList.toggle('bg-slate-100', i === activeIndex));
      }

      async function selectIndex(idx) {
        const item = lastItems[idx];
        if (!item) return;
        inputName.value = item.name || '';
        inputCodzge.value = item.code || '';
        suggestions.classList.add('hidden');

        // Cargar hoteles de la zona seleccionada
        await fetchHotelsForArea(item.code);
      }

      function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, s => ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        } [s]));
      }

      async function queryServer(q) {
        if (abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();
        try {
          const url = new URL(ENDPOINT, window.location.origin);
          url.searchParams.set('q', q);
          url.searchParams.set('limit', '10');
          const res = await fetch(url.toString(), {
            signal: abortCtrl.signal
          });
          if (!res.ok) throw new Error('HTTP ' + res.status);
          const data = await res.json();
          lastItems = data.items || [];
          showSuggestions(lastItems);
        } catch (e) {
          if (e.name !== 'AbortError') {
            console.error(e);
            lastItems = [];
            showSuggestions([]);
          }
        }
      }

      async function fetchHotelsForArea(areaCode) {
        try {
          const url = `${HOTELS_ENDPOINT_BASE}/${encodeURIComponent(areaCode)}/hotels`;
          const res = await fetch(url);
          if (!res.ok) throw new Error('HTTP ' + res.status);
          const data = await res.json();

          const codes = (data.items || []).map(it => it.codser).filter(Boolean);

          // Agrupar 20 por línea para lectura; puedes cambiar a join('\n') si prefieres 1/linea
          const chunkSize = 20;
          const lines = [];
          for (let i = 0; i < codes.length; i += chunkSize) {
            lines.push(codes.slice(i, i + chunkSize).join(', '));
          }
          textAreaHotels.value = lines.join('\n');
          updateCounter();
        } catch (e) {
          console.error(e);
          textAreaHotels.value = '';
          updateCounter();
        }
      }

      // Al escribir, limpiar selección anterior y (si hay 2+ chars) pedir sugerencias
      inputName.addEventListener('input', () => {
        const q = inputName.value.trim();

        // limpiar selección previa
        inputCodzge.value = '';
        textAreaHotels.value = '';
        updateCounter();

        if (debounceTimer) clearTimeout(debounceTimer);
        if (q.length < 2) {
          suggestions.classList.add('hidden');
          lastItems = [];
          return;
        }
        debounceTimer = setTimeout(() => queryServer(q), 180);
      });

      inputName.addEventListener('focus', () => {
        if (lastItems.length) suggestions.classList.remove('hidden');
      });

      document.addEventListener('click', (e) => {
        if (!suggestions.contains(e.target) && e.target !== inputName) {
          suggestions.classList.add('hidden');
        }
      });

      // Navegación con teclado
      inputName.addEventListener('keydown', (e) => {
        const hasList = !suggestions.classList.contains('hidden') && lastItems.length > 0;
        if (!hasList) return;

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          activeIndex = (activeIndex + 1) % lastItems.length;
          highlightActive();
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          activeIndex = (activeIndex - 1 + lastItems.length) % lastItems.length;
          highlightActive();
        } else if (e.key === 'Enter' && activeIndex >= 0) {
          e.preventDefault();
          selectIndex(activeIndex);
        } else if (e.key === 'Escape') {
          suggestions.classList.add('hidden');
        }
      });

      // Contador dinámico al editar manualmente
      textAreaHotels.addEventListener('input', updateCounter);

      // Estado inicial
      updateCounter();
    })();
  </script>
  <script>
    (function() {
      const container = document.getElementById('rooms-container');
      const tpl = document.getElementById('room-template');
      const addBtn = document.getElementById('add-room');
      if (!container || !tpl || !addBtn) return;

      let idx = 0;

      function renderIndexes() {
        [...container.querySelectorAll('.room-row')].forEach((row, i) => {
          const span = row.querySelector('.room-index');
          if (span) span.textContent = i + 1;
        });
      }

      function syncCapacity(row) {
        const adl = row.querySelector('.adl-input');
        const chd = row.querySelector('.chd-input');
        const agesWrap = row.querySelector('.ages-wrap');

        const a = parseInt(adl.value || '0', 10);
        const c = parseInt(chd.value || '0', 10);

        // Regla: >=1 adulto y máx. 4 personas/hab
        if (a < 1) {
          adl.setCustomValidity('Debe haber al menos 1 adulto');
        } else {
          adl.setCustomValidity('');
        }
        if (a + c > 4) {
          chd.setCustomValidity('Máximo 4 personas por habitación');
        } else {
          chd.setCustomValidity('');
        }

        // Generar inputs de edades = nº de niños
        const current = agesWrap.querySelectorAll('input[type="number"]').length;
        const target = Math.max(0, Math.min(3, c));
        if (current !== target) {
          agesWrap.innerHTML = '';
          for (let i = 0; i < target; i++) {
            const input = document.createElement('input');
            input.type = 'number';
            input.min = '0';
            input.max = '17';
            input.required = true;
            input.placeholder = 'Edad';
            input.name = `rooms[${row.dataset.idx}][ages][${i}]`;
            input.className = 'rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full';
            agesWrap.appendChild(input);
          }
        }
      }

      function addRoom(defaults = {
        adl: 2,
        chd: 0
      }) {
        const node = document.importNode(tpl.content, true);
        const row = node.firstElementChild;
        row.dataset.idx = idx;

        // Reemplaza __i__ por índice real en los names
        row.innerHTML = row.innerHTML
          .replaceAll('rooms[__i__][adl]', `rooms[${idx}][adl]`)
          .replaceAll('rooms[__i__][chd]', `rooms[${idx}][chd]`);

        container.appendChild(row);

        // Set defaults
        const adl = row.querySelector('.adl-input');
        const chd = row.querySelector('.chd-input');
        if (adl) adl.value = defaults.adl ?? 2;
        if (chd) chd.value = defaults.chd ?? 0;

        // Eventos
        adl.addEventListener('input', () => syncCapacity(row));
        chd.addEventListener('input', () => syncCapacity(row));
        row.querySelector('.remove-room')?.addEventListener('click', () => {
          row.remove();
          renderIndexes();
        });

        // Inicializar
        syncCapacity(row);
        idx++;
        renderIndexes();
      }

      addBtn.addEventListener('click', () => addRoom());
      // Crea una habitación por defecto
      addRoom({
        adl: 2,
        chd: 0
      });
    })();
  </script>


</x-app-layout>