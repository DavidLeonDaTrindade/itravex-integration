{{-- resources/views/availability/form.blade.php --}}
<style>
  /* ====== Tokens suaves tipo dashboard ====== */
  :root {
    --pb-primary: #004665;
    --pb-accent: #FDB31B;
  }

  /* Card wrapper */
  .pb-card {
    border-radius: 1rem;
    /* rounded-2xl */
    border: 1px solid rgb(226 232 240);
    /* slate-200 */
    background: #fff;
    box-shadow: 0 1px 2px rgba(2, 6, 23, .06);
  }

  .pb-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgb(241 245 249);
    /* slate-100 */
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
  }

  .pb-card-body {
    padding: 1.5rem;
  }

  /* Inputs uniformes */
  .pb-input,
  .pb-select,
  .pb-textarea {
    width: 100%;
    border-radius: .75rem;
    /* rounded-xl */
    border: 1px solid rgb(203 213 225);
    /* slate-300 */
    background: #fff;
    padding: .625rem .875rem;
    /* h cómoda */
    box-shadow: 0 1px 1px rgba(2, 6, 23, .04);
    transition: box-shadow .15s ease, border-color .15s ease;
    outline: none;
  }

  .pb-input:focus,
  .pb-select:focus,
  .pb-textarea:focus {
    border-color: rgba(0, 70, 101, .55);
    box-shadow: 0 0 0 4px rgba(0, 70, 101, .14);
  }

  .pb-input::placeholder,
  .pb-textarea::placeholder {
    color: rgb(148 163 184);
    /* slate-400 */
  }

  /* Labels */
  .pb-label {
    font-size: .875rem;
    font-weight: 600;
    color: rgb(51 65 85);
    /* slate-700 */
  }

  /* Helper text */
  .pb-help {
    margin-top: .375rem;
    font-size: .75rem;
    color: rgb(100 116 139);
    /* slate-500 */
    line-height: 1.35;
  }

  /* Botones */
  .pb-btn {
    border-radius: .75rem;
    padding: .625rem 1rem;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(2, 6, 23, .08);
    transition: transform .05s ease, box-shadow .15s ease, background .15s ease;
  }

  .pb-btn:active {
    transform: translateY(1px);
  }

  .pb-btn-primary {
    background: var(--pb-primary);
    color: #fff;
  }

  .pb-btn-primary:hover {
    background: #003a54;
  }

  .pb-btn-primary:focus {
    outline: none;
    box-shadow: 0 0 0 4px rgba(0, 70, 101, .18);
  }

  .pb-btn-ghost {
    color: var(--pb-primary);
    background: rgba(0, 70, 101, .06);
  }

  .pb-btn-ghost:hover {
    background: rgba(0, 70, 101, .10);
  }

  /* Sugerencias (dropdown) */
  .pb-suggest {
    border-radius: .875rem;
    border: 1px solid rgb(226 232 240);
    box-shadow: 0 12px 30px rgba(2, 6, 23, .12);
    overflow: hidden;
  }

  .pb-suggest button {
    padding: .75rem .875rem;
  }

  .pb-suggest button+button {
    border-top: 1px solid rgb(241 245 249);
  }

  /* Secciones internas (habitaciones / credenciales) */
  .pb-section {
    border-radius: 1rem;
    border: 1px solid rgb(226 232 240);
    background: rgb(248 250 252);
    /* slate-50 */
  }

  .pb-section-title {
    font-size: .875rem;
    font-weight: 700;
    color: rgb(51 65 85);
  }

  /* Room row */
  .pb-room {
    border-radius: 1rem;
    border: 1px solid rgb(226 232 240);
    background: #fff;
    box-shadow: 0 1px 2px rgba(2, 6, 23, .05);
  }

  /* Autofill sin “naranja” */
  input:-webkit-autofill {
    -webkit-text-fill-color: #0f172a !important;
    transition: background-color 9999s ease-in-out 0s;
    box-shadow: 0 0 0px 1000px #fff inset !important;
  }
</style>
<style>
  html {
    scrollbar-gutter: stable;
  }
</style>
<style>
  /* En pantallas grandes, fuerza el mismo padding a izquierda y derecha */
  @media (min-width: 1024px) {
    .pb-force-symmetric {
      padding-left: 2rem !important;
      padding-right: 2rem !important;
    }
  }
</style>


<x-app-layout>
  <div class="min-h-screen bg-slate-50 py-10">
    <div class="mx-auto max-w-7xl pb-force-symmetric px-4 sm:px-6 lg:px-8">


      <div class="mx-auto rounded-2xl p-8 lg:p-8"
        style="background:#e2e8f0; border:1px solid #cbd5e1;">


        <header class="mb-6">
          <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Buscar Disponibilidad</h1>
          <p class="mt-1 text-sm text-slate-600">Consulta por zona o por códigos de hotel y filtra por fechas.</p>
        </header>

        <div class="mb-8 pb-card" style="background:#cbd5e1;">

          <div class="pb-card-body">

            <form
              method="POST"
              action="{{ route('availability.search') }}"
              autocomplete="off"
              autocapitalize="off"
              spellcheck="false"
              class="grid grid-cols-1 gap-6 md:grid-cols-2"
              id="availability-form">
              @csrf

              {{-- ======================== BÚSQUEDA POR ZONA ======================== --}}
              <div class="md:col-span-1 relative">
                <label for="area_name" class="pb-label">
                  Área (buscar por nombre)
                </label>

                <input id="area_name" type="text"
                  class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="Escribe el nombre del área (p. ej., Tenerife)"
                  autocomplete="off" autocapitalize="off" spellcheck="false" />

                {{-- Contenedor de sugerencias --}}
                <div id="area_suggestions"
                  class="pb-suggest absolute z-50 mt-2 w-full bg-white hidden max-h-72 overflow-auto">
                  {{-- se pintan aquí las opciones --}}
                </div>

                <p class="mt-1 text-xs text-slate-500">
                  Escribe el nombre; al elegir, se rellenará el código <strong>A-...</strong> automáticamente.
                </p>
              </div>

              <div class="md:col-span-1">
                <label for="codzge" class="pb-label">Código de Zona</label>
                <input id="codzge" type="text" name="codzge" value="{{ old('codzge') }}"
                  data-codzge
                  class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="A-39018" autocomplete="off" autocapitalize="off" spellcheck="false" />
                <p class="mt-1 text-xs text-slate-500">Si se rellena, se ignorarán los códigos manuales (salvo intersección).</p>
              </div>

              {{-- ======================== BÚSQUEDA POR HOTEL ======================== --}}
              <div class="md:col-span-2 relative">
                <label for="hotel_name" class="pb-label">
                  Hotel (buscar por nombre)
                </label>

                <input id="hotel_name" type="text"
                  class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="Escribe el nombre del hotel (p. ej., Iberostar...)" autocomplete="off" autocapitalize="off" spellcheck="false" />

                {{-- Contenedor de sugerencias --}}
                <div id="hotel_suggestions"
                  class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg hidden max-h-72 overflow-auto">
                  {{-- opciones via JS --}}
                </div>

                <p class="mt-1 text-xs text-slate-500">
                  Al seleccionar un hotel se añadirá su <strong>codser</strong> al campo de “Códigos de hotel”.
                  Si <strong>Código de Zona</strong> está vacío, se rellenará automáticamente con la zona del hotel.
                </p>
              </div>

              {{-- ======================== CÓDIGOS MANUALES ======================== --}}
              <div class="md:col-span-2">
                <div class="flex items-center justify-between">
                  <label for="hotel_codes" class="pb-label">
                    Códigos de hotel (codser)
                  </label>
                  <span id="hotel_count" class="text-xs text-slate-500">0 hoteles</span>
                </div>
                <textarea id="hotel_codes" name="hotel_codes" rows="3"
                  class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="105971, 12345&#10;67890" autocomplete="off" autocapitalize="off" spellcheck="false">{{ old('hotel_codes') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Separados por coma, espacios o saltos de línea.</p>
              </div>

              {{-- ======================== FECHAS / ADULTOS ======================== --}}
              @php
              $today = date('Y-m-d');
              @endphp

              <div>
                <label for="fecini" class="pb-label">Fecha Inicio</label>
                <input
                  id="fecini"
                  type="date"
                  name="fecini"
                  value="{{ old('fecini') }}"
                  required
                  min="{{ $today }}"
                  onclick="this.showPicker()"
                  class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 cursor-pointer"
                  autocomplete="off" />
              </div>

              <div>
                <label for="fecfin" class="pb-label">Fecha Fin</label>
                <input
                  id="fecfin"
                  type="date"
                  name="fecfin"
                  value="{{ old('fecfin') }}"
                  required
                  min="{{ $today }}"
                  onclick="this.showPicker()"
                  class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 cursor-pointer"
                  autocomplete="off" />
              </div>




              <!-- ===== Habitaciones (multihabitación con edades de niños) ===== -->
              <div class="md:col-span-2 pb-section p-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-medium text-slate-700">Habitaciones</h3>
                  <button type="button" id="add-room"
                    class="text-sm text-blue-600 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg px-2 py-1">
                    Añadir habitación
                  </button>
                </div>

                <!-- Template de una habitación -->
                <template id="room-template">
                  <div class="room-row pb-room mt-4 p-4">
                    <div class="flex items-center justify-between">
                      <h4 class="text-sm font-medium text-slate-700">Habitación <span class="room-index"></span></h4>
                      <button type="button" class="remove-room text-xs text-red-600 hover:text-red-700">Quitar</button>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-3">
                      <div>
                        <label class="pb-label">Adultos</label>
                        <input type="number" min="1" max="4" name="rooms[__i__][adl]" value="2"
                          class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 adl-input" required />
                      </div>

                      <div>
                        <label class="pb-label">Edades de los adultos</label>
                        <div class="adult-ages-wrap grid grid-cols-3 gap-2">
                          <!-- aquí se pintan los inputs según nº de adultos -->
                        </div>

                        <p class="mt-1 text-xs text-slate-500">Opcional. Si las indicas, se usarán para calcular FECNAC en el bloqueo.</p>
                      </div>
                      </br>
                      <div>
                        <label class="pb-label">Niños</label>
                        <input type="number" min="0" max="3" name="rooms[__i__][chd]" value="0"
                          class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 chd-input" />
                        <p class="mt-1 text-xs text-slate-500">Máx. personas/hab: 4</p>
                      </div>
                      <div>
                        <label class="pb-label">Edades de los niños</label>
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
                <label for="codnac" class="pb-label">Código de país (ISO 3166-1)</label>
                <input id="codnac" type="text" name="codnac" value="{{ old('codnac') }}" placeholder="ESP" maxlength="3"
                  class="mt-1 block w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase"
                  autocomplete="off" autocapitalize="off" spellcheck="false" />
                <p class="mt-1 text-xs text-slate-500">ISO 3166-1 (p. ej. <strong>ESP</strong>, <strong>FRA</strong>, <strong>USA</strong>).</p>
              </div>

              {{-- ======================== OPCIONES DE RENDIMIENTO ======================== --}}
              <div class="grid grid-cols-2 gap-4 md:col-span-2">
                <div>
                  <label for="timeout" class="pb-label">Timeout (ms)</label>
                  <input id="timeout" type="number" name="timeout" min="1000" max="60000" value="{{ old('timeout') }}" placeholder="8000"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    autocomplete="off" />
                </div>

                <div>
                  <label for="numrst" class="pb-label">
                    Resultados por página del proveedor (numrst)
                  </label>
                  <input id="numrst" type="number" name="numrst" min="1" max="500" value="{{ old('numrst', 20) }}" placeholder="20"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    autocomplete="off" />
                  <p class="mt-1 text-xs text-slate-500">Se aplica en <strong>source=provider</strong> (paginación nativa indpag/numrst)</p>
                </div>

                <div class="md:col-span-2 pb-card">
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

                    {{-- HONEYPOT (absorbe autocompletado del navegador) --}}
                    <div class="sr-only" aria-hidden="true" style="position:absolute; left:-9999px; top:-9999px; width:1px; height:1px; overflow:hidden;">
                      <input type="text" name="email" autocomplete="username">
                      <input type="password" name="password" autocomplete="current-password">
                    </div>

                    <div class="mt-2 grid grid-cols-1 gap-4 md:grid-cols-3">
                      {{-- VISIBLES (UI) - sin name "real" --}}
                      <div>
                        <label for="endpoint_ui" class="pb-label">Endpoint</label>
                        <input id="endpoint_ui" type="url" value="{{ old('endpoint') }}" placeholder="https://api.proveedor.com/endpoint"
                          class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          autocomplete="off" autocapitalize="off" spellcheck="false">
                      </div>

                      <div>
                        <label for="codsys_ui" class="pb-label">codsys</label>
                        <input id="codsys_ui" type="text" value="{{ old('codsys') }}"
                          class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          autocomplete="off" autocapitalize="off" spellcheck="false">
                      </div>

                      <div>
                        <label for="codage_ui" class="pb-label">codage</label>
                        <input id="codage_ui" type="text" value="{{ old('codage') }}"
                          class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          autocomplete="off" autocapitalize="off" spellcheck="false">
                      </div>

                      <div>
                        <label for="user_ui" class="pb-label">user</label>
                        <input id="user_ui" type="text" value="{{ old('user') }}"
                          class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          autocomplete="off" autocapitalize="off" spellcheck="false">
                      </div>

                      <div>
                        <label for="pass_ui" class="pb-label">pass</label>
                        <input id="pass_ui" type="password" placeholder="••••••••"
                          class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          autocomplete="new-password" autocapitalize="off" spellcheck="false">
                      </div>

                      <div>
                        <label for="codtou_ui" class="pb-label">codtou</label>
                        <input id="codtou_ui" type="text" value="{{ old('codtou','LIB') }}"
                          class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          autocomplete="off" autocapitalize="off" spellcheck="false">
                      </div>

                      {{-- REALES (ocultos) que viajan en el POST --}}
                      <input type="hidden" name="endpoint" id="endpoint_real" value="">
                      <input type="hidden" name="codsys" id="codsys_real" value="">
                      <input type="hidden" name="codage" id="codage_real" value="">
                      <input type="hidden" name="user" id="user_real" value="">
                      <input type="hidden" name="pass" id="pass_real" value="">
                      <input type="hidden" name="codtou" id="codtou_real" value="">
                    </div>
                  </div>
                </div>
              </div>

              <div class="md:col-span-2">
                <button type="submit" id="submit-btn"
                  class="pb-btn pb-btn-primary inline-flex items-center gap-2">
                  <svg id="submit-spinner" class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                  </svg>

                  <span id="submit-text">Consultar</span>
                </button>

              </div>


            </form>
            <div id="loadingBox" class="hidden md:col-span-2">
              <div class="mt-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-slate-700">Consultando disponibilidad…</span>
                  <span id="progressPercent" class="text-xs text-slate-500">Cargando 0%</span>
                </div>

                <div class="mt-3" style="width:100%; height:8px; background:#e2e8f0; border-radius:9999px; overflow:hidden;">
                  <div id="progressBarFill"
                    style="height:100%; width:0%; background:#004665; border-radius:9999px; transition: width .12s linear;">
                  </div>
                </div>

                <p class="mt-2 text-xs text-slate-500">Esto puede tardar unos segundos según el proveedor.</p>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const fecini = document.getElementById('fecini');
      const fecfin = document.getElementById('fecfin');

      fecini.addEventListener('change', function() {
        // Establece el mínimo de fecfin igual a fecini
        fecfin.min = fecini.value;

        // Si la fecha fin actual es anterior, la borra
        if (fecfin.value && fecfin.value < fecini.value) {
          fecfin.value = fecini.value;
        }
      });
    });
  </script>

  <script>
    (function() {
      const inputName = document.getElementById('area_name');
      const suggestions = document.getElementById('area_suggestions');
      const inputCodzge = document.querySelector('input[data-codzge]');
      const textAreaHotels = document.getElementById('hotel_codes');
      const countEl = document.getElementById('hotel_count');

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
          const opened = panel.classList.toggle('hidden') === false;
          btn.textContent = opened ? 'Ocultar' : 'Mostrar';
          btn.setAttribute('aria-expanded', String(opened));

          // Anti-autofill: al abrir, vaciamos inmediatamente cualquier relleno forzado del navegador
          if (opened) {
            setTimeout(() => {
              ['endpoint_ui', 'codsys_ui', 'codage_ui', 'user_ui', 'pass_ui', 'codtou_ui'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el.matches(':-webkit-autofill')) el.value = '';
              });
            }, 0);
          }
        });

        // Anti-autofill adicional: readonly breve al cargar
        ['endpoint_ui', 'codsys_ui', 'codage_ui', 'user_ui', 'pass_ui', 'codtou_ui'].forEach(id => {
          const el = document.getElementById(id);
          if (el) {
            el.readOnly = true;
            setTimeout(() => {
              el.readOnly = false;
            }, 400);
          }
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
    (function() {
      const MAX_ROOMS = 4;

      const container = document.getElementById('rooms-container');
      const tpl = document.getElementById('room-template');
      const addBtn = document.getElementById('add-room');
      if (!container || !tpl || !addBtn) return;

      let idx = 0;

      function currentRoomsCount() {
        return container.querySelectorAll('.room-row').length;
      }

      function enforceAddButtonState() {
        const n = currentRoomsCount();
        addBtn.disabled = n >= MAX_ROOMS;
        addBtn.classList.toggle('opacity-50', addBtn.disabled);
        addBtn.classList.toggle('cursor-not-allowed', addBtn.disabled);
        addBtn.setAttribute('aria-disabled', String(addBtn.disabled));
      }

      function renderIndexes() {
        [...container.querySelectorAll('.room-row')].forEach((row, i) => {
          const span = row.querySelector('.room-index');
          if (span) span.textContent = i + 1;
        });
      }

      function syncCapacity(row) {
        const adl = row.querySelector('.adl-input');
        const chd = row.querySelector('.chd-input');
        const agesWrap = row.querySelector('.ages-wrap'); // niños
        const adultAgesWrap = row.querySelector('.adult-ages-wrap'); // adultos

        const a = parseInt(adl.value || '0', 10);
        const c = parseInt(chd.value || '0', 10);

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

        // ===== Niños: generar inputs = nº de niños =====
        const currentKids = agesWrap.querySelectorAll('input[type="number"]').length;
        const targetKids = Math.max(0, Math.min(3, c));
        if (currentKids !== targetKids) {
          agesWrap.innerHTML = '';
          for (let i = 0; i < targetKids; i++) {
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

        // ===== Adultos: generar inputs = nº de adultos =====
        const currentAdults = adultAgesWrap.querySelectorAll('input[type="number"]').length;
        const targetAdults = Math.max(1, Math.min(4, a));
        if (currentAdults !== targetAdults) {
          adultAgesWrap.innerHTML = '';
          for (let i = 0; i < targetAdults; i++) {
            const input = document.createElement('input');
            input.type = 'number';
            input.min = '12';
            input.max = '120';
            input.placeholder = 'Edad';
            input.name = `rooms[${row.dataset.idx}][adult_ages][${i}]`;
            input.className = 'rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 w-full';
            adultAgesWrap.appendChild(input);
          }
        }
      }


      function addRoom(defaults = {
        adl: 2,
        chd: 0
      }) {
        if (currentRoomsCount() >= MAX_ROOMS) {
          // Doble seguridad
          enforceAddButtonState();
          return;
        }

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
          enforceAddButtonState();
        });

        // Inicializar
        syncCapacity(row);
        idx++;
        renderIndexes();
        enforceAddButtonState();
      }

      addBtn.addEventListener('click', () => addRoom());

      // Crea una habitación por defecto
      addRoom({
        adl: 2,
        chd: 0
      });

    })();
    (function() {
      // Elementos del DOM
      const hotelNameInput = document.getElementById('hotel_name');
      const hotelSuggestBox = document.getElementById('hotel_suggestions');
      const codzgeInput = document.querySelector('input[data-codzge]');
      const hotelCodesTA = document.getElementById('hotel_codes');
      const hotelCountLabel = document.getElementById('hotel_count');

      if (!hotelNameInput || !hotelSuggestBox || !hotelCodesTA) return;

      // Endpoint del controller nuevo
      const HOTEL_SEARCH_ENDPOINT = @json(route('search.hotels'));

      // Estado local
      let h_abortCtrl = null;
      let h_debounce = null;
      let h_lastItems = [];
      let h_activeIndex = -1;

      // === Helpers ===
      function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, s => ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        } [s]));
      }

      function uniqueTokensFromText(str) {
        if (!str) return [];
        return Array.from(new Set(
          String(str)
          .replace(/[\n\r;]/g, ' ')
          .split(/[,\s]+/g)
          .map(s => s.trim())
          .filter(Boolean)
        ));
      }

      function updateHotelCounter() {
        const n = uniqueTokensFromText(hotelCodesTA.value).length;
        if (hotelCountLabel) hotelCountLabel.textContent = `${n} ${n === 1 ? 'hotel' : 'hoteles'}`;
      }

      function showHotelSuggestions(items) {
        hotelSuggestBox.innerHTML = '';
        if (!items || !items.length) {
          hotelSuggestBox.classList.add('hidden');
          return;
        }

        const frag = document.createDocumentFragment();
        items.forEach((it, idx) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'w-full text-left px-3 py-2 hover:bg-slate-100 focus:bg-slate-100';

          // Nombre / codser / zona
          const name = escapeHtml(it.name ?? '');
          const cod = escapeHtml(it.codser ?? '');
          const zname = escapeHtml(it.zone_name ?? '');

          btn.dataset.index = idx;
          btn.innerHTML = `
  <div class="text-sm font-medium">${name}</div>
  <div class="text-xs text-slate-500">codser: <strong>${cod}</strong> · zona: <strong>${zname || '—'}</strong></div>
`;
          btn.addEventListener('click', () => selectHotelIndex(idx));
          frag.appendChild(btn);
        });

        hotelSuggestBox.appendChild(frag);
        hotelSuggestBox.classList.remove('hidden');
        h_activeIndex = -1;
        highlightHotelActive();
      }

      function highlightHotelActive() {
        const children = Array.from(hotelSuggestBox.children);
        children.forEach((el, i) => {
          el.classList.toggle('bg-slate-100', i === h_activeIndex);
        });
      }

      async function selectHotelIndex(idx) {
        const item = h_lastItems[idx];
        if (!item) return;

        // 1) Pinta el nombre en el input
        hotelNameInput.value = item.name || '';

        // 2) Mezcla el codser en el textarea (sin duplicados)
        const existing = uniqueTokensFromText(hotelCodesTA.value);
        if (item.codser) {
          if (!existing.includes(String(item.codser))) {
            existing.push(String(item.codser));
          }
        }

        // 20 por línea
        const chunkSize = 20;
        const lines = [];
        for (let i = 0; i < existing.length; i += chunkSize) {
          lines.push(existing.slice(i, i + chunkSize).join(', '));
        }
        hotelCodesTA.value = lines.join('\n');
        updateHotelCounter();

        // 3) Autorrellenar zona si está vacía
        if (codzgeInput && (!codzgeInput.value || !codzgeInput.value.trim())) {
          if (item.zone_code) {
            codzgeInput.value = item.zone_code;
          }
        }

        // 4) Oculta sugerencias
        hotelSuggestBox.classList.add('hidden');
      }

      async function queryHotels(q) {
        if (h_abortCtrl) h_abortCtrl.abort();
        h_abortCtrl = new AbortController();

        try {
          const url = new URL(HOTEL_SEARCH_ENDPOINT, window.location.origin);
          url.searchParams.set('q', q);
          url.searchParams.set('limit', '10');

          // Filtro opcional por zona si existe
          if (codzgeInput && codzgeInput.value && /^A-\d+$/i.test(codzgeInput.value.trim())) {
            url.searchParams.set('zoneCode', codzgeInput.value.trim());
          }

          const res = await fetch(url.toString(), {
            signal: h_abortCtrl.signal
          });
          if (!res.ok) throw new Error('HTTP ' + res.status);
          const data = await res.json();
          h_lastItems = data.items || [];
          showHotelSuggestions(h_lastItems);
        } catch (e) {
          if (e.name !== 'AbortError') {
            console.error(e);
            h_lastItems = [];
            showHotelSuggestions([]);
          }
        }
      }

      // === Eventos ===
      hotelNameInput.addEventListener('input', () => {
        const q = hotelNameInput.value.trim();
        if (h_debounce) clearTimeout(h_debounce);

        if (q.length < 2) {
          hotelSuggestBox.classList.add('hidden');
          h_lastItems = [];
          return;
        }

        h_debounce = setTimeout(() => queryHotels(q), 180);
      });

      hotelNameInput.addEventListener('focus', () => {
        if (h_lastItems.length) hotelSuggestBox.classList.remove('hidden');
      });

      document.addEventListener('click', (e) => {
        if (!hotelSuggestBox.contains(e.target) && e.target !== hotelNameInput) {
          hotelSuggestBox.classList.add('hidden');
        }
      });

      // Teclado
      hotelNameInput.addEventListener('keydown', (e) => {
        const hasList = !hotelSuggestBox.classList.contains('hidden') && h_lastItems.length > 0;
        if (!hasList) return;

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          h_activeIndex = (h_activeIndex + 1) % h_lastItems.length;
          highlightHotelActive();
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          h_activeIndex = (h_activeIndex - 1 + h_lastItems.length) % h_lastItems.length;
          highlightHotelActive();
        } else if (e.key === 'Enter' && h_activeIndex >= 0) {
          e.preventDefault();
          selectHotelIndex(h_activeIndex);
        } else if (e.key === 'Escape') {
          hotelSuggestBox.classList.add('hidden');
        }
      });

      // ====== Validación de tope 4 habitaciones en el submit ======
      const MAX_ROOMS = 4;
      const formEl = document.getElementById('availability-form');

      function countRooms() {
        const container = document.getElementById('rooms-container');
        if (!container) return 0;
        return container.querySelectorAll('.room-row').length;
      }

      if (formEl) {
        formEl.addEventListener('submit', (e) => {
          const n = countRooms();
          if (n > MAX_ROOMS) {
            e.preventDefault();
            alert(`Máximo ${MAX_ROOMS} habitaciones.\nAhora mismo tienes ${n}. Quita habitaciones hasta ${MAX_ROOMS}.`);
            try {
              document.getElementById('add-room')?.focus();
            } catch (_) {}
          }
        });
      }
      // ============================================================

      // Contador dinámico por si el usuario edita a mano
      hotelCodesTA.addEventListener('input', updateHotelCounter);

      // Estado inicial
      updateHotelCounter();
    })();
  </script>
  <script>
    (function() {
      const form = document.getElementById('availability-form');
      const btn = document.getElementById('submit-btn');
      const text = document.getElementById('submit-text');

      const loadingBox = document.getElementById('loadingBox');
      const fillEl = document.getElementById('progressBarFill');
      const percentEl = document.getElementById('progressPercent');

      if (!form || !btn || !text || !loadingBox || !fillEl || !percentEl) return;

      let locked = false;
      let loadingInterval = null;
      let loadingPercent = 0;

      function startLoading() {
        // Reset
        loadingPercent = 0;
        fillEl.style.width = '0%';
        percentEl.textContent = 'Cargando 0%';

        // Mostrar caja
        loadingBox.classList.remove('hidden');

        // Barra simulada: rápido al principio, lenta al final, se queda en 92%
        loadingInterval = setInterval(() => {
          const cap = 92;

          if (loadingPercent < 35) loadingPercent += 3; // arranque rápido
          else if (loadingPercent < 70) loadingPercent += 2; // medio
          else if (loadingPercent < cap) loadingPercent += 1; // final lento

          if (loadingPercent > cap) loadingPercent = cap;

          fillEl.style.width = loadingPercent + '%';
          percentEl.textContent = `Cargando ${loadingPercent}%`;
        }, 120);
      }

      async function finishLoading() {
        if (loadingInterval) {
          clearInterval(loadingInterval);
          loadingInterval = null;
        }
        loadingPercent = 100;
        fillEl.style.width = '100%';
        percentEl.textContent = 'Cargando 100%';
        await new Promise(res => setTimeout(res, 120));
      }

      form.addEventListener('submit', async (e) => {
        if (locked) {
          e.preventDefault();
          return;
        }
        if (!form.checkValidity()) return;

        locked = true;

        // UI botón
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
        text.textContent = 'Consultando…';

        // UI barra
        startLoading();

        // Nota: en submit normal la página se irá, pero por si el navegador tarda:
        // hacemos un "finish" justo antes de salir (mejora percepción)
        setTimeout(() => {
          finishLoading();
        }, 1200);
      });

      // Si el usuario vuelve atrás o el navegador restaura estado, desbloquea
      window.addEventListener('pageshow', () => {
        locked = false;
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
        text.textContent = 'Consultar';
        loadingBox.classList.add('hidden');
        if (loadingInterval) clearInterval(loadingInterval);
        loadingInterval = null;
      });
    })();
  </script>



  <style>
    input:-webkit-autofill {
      outline: 2px solid orange !important;
      -webkit-text-fill-color: #0f172a !important;
    }
  </style>




</x-app-layout>