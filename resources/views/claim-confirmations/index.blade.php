<x-app-layout>
  <div class="min-h-[calc(100vh-64px)] bg-slate-50 py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <style>
        .cc-hero {
          position: relative;
          overflow: hidden;
          border: 1px solid #dbe5f0;
          border-radius: 1.75rem;
          background: linear-gradient(135deg, #ffffff 0%, #f7fbff 58%, #eef6fb 100%);
          box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }

        .cc-hero::after {
          content: "";
          position: absolute;
          right: -70px;
          top: -70px;
          width: 220px;
          height: 220px;
          background: radial-gradient(circle, rgba(0, 70, 101, 0.12) 0%, rgba(0, 70, 101, 0) 72%);
          pointer-events: none;
        }

        .cc-panel {
          border: 1px solid #dbe5f0;
          border-radius: 1.5rem;
          background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
          box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        }

        .cc-stat-card {
          position: relative;
          border: 1px solid #dbe5f0;
          border-radius: 1.35rem;
          background: linear-gradient(180deg, #ffffff 0%, #f9fbfd 100%);
          box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .cc-stat-card::before {
          content: "";
          position: absolute;
          left: 1.25rem;
          top: 1rem;
          width: 2.5rem;
          height: 0.3rem;
          border-radius: 999px;
          background: linear-gradient(90deg, #004665 0%, #0ea5e9 100%);
        }

        .cc-chip {
          display: inline-flex;
          align-items: center;
          gap: 0.45rem;
          border: 1px solid #dbe5f0;
          border-radius: 999px;
          background: rgba(255, 255, 255, 0.82);
          padding: 0.65rem 0.9rem;
          color: #475569;
          font-size: 0.84rem;
          box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        }

        .cc-toolbar-grid {
          display: grid;
          gap: 1.5rem;
        }

        @media (min-width: 1100px) {
          .cc-toolbar-grid {
            grid-template-columns: minmax(0, 1fr);
          }
        }

        .cc-table-wrap {
          overflow: hidden;
          border: 1px solid #dbe5f0;
          border-radius: 1.5rem;
          background: #ffffff;
          box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
        }

        .cc-table thead th {
          background: linear-gradient(180deg, #f8fbff 0%, #edf3f8 100%);
          color: #52637a;
          border-bottom: 1px solid #dbe5f0;
        }

        .cc-table tbody tr:hover {
          background: #f8fbff;
        }

        .cc-status-pill,
        .cc-flag-pill,
        .cc-filter-pill {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          border-radius: 999px;
          font-size: 0.72rem;
          font-weight: 700;
          letter-spacing: 0.02em;
          padding: 0.35rem 0.7rem;
          border: 1px solid transparent;
        }

        .cc-status-confirmed {
          color: #047857;
          background: #ecfdf5;
          border-color: #a7f3d0;
        }

        .cc-status-warning {
          color: #b45309;
          background: #fff7ed;
          border-color: #fdba74;
        }

        .cc-status-neutral {
          color: #334155;
          background: #f8fafc;
          border-color: #cbd5e1;
        }

        .cc-flag-pill {
          color: #0f172a;
          background: #f8fafc;
          border-color: #cbd5e1;
        }

        .cc-comment-error {
          border-left: 4px solid #fb7185;
          background: linear-gradient(90deg, #fff1f2 0%, #ffffff 22%);
        }

        .cc-filter-pill {
          color: #004665;
          background: #eef9ff;
          border-color: #bfdbfe;
        }

        .cc-toolbar-form {
          display: flex;
          flex-wrap: wrap;
          gap: 0.85rem;
          align-items: end;
        }

        .cc-filter-form {
          display: grid;
          gap: 0.85rem;
          width: 100%;
        }

        @media (min-width: 768px) {
          .cc-filter-form {
            grid-template-columns: minmax(220px, 320px) minmax(260px, 1fr);
            align-items: end;
          }
        }

        .cc-field label {
          display: block;
          margin-bottom: 0.4rem;
          font-size: 0.72rem;
          font-weight: 700;
          letter-spacing: 0.08em;
          text-transform: uppercase;
          color: #64748b;
        }

        .cc-input,
        .cc-select {
          width: 100%;
          min-width: 0;
          border: 1px solid #cbd5e1;
          border-radius: 1rem;
          background: #fff;
          padding: 0.9rem 1rem;
          color: #0f172a;
          font-size: 0.95rem;
          line-height: 1.2;
          box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        }

        .cc-select {
          min-width: 200px;
        }

        .cc-input:focus,
        .cc-select:focus {
          outline: none;
          border-color: #94a3b8;
          box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
        }

        .cc-check-card {
          display: inline-flex;
          align-items: center;
          gap: 0.75rem;
          min-height: 3.2rem;
          border: 1px solid #dbe5f0;
          border-radius: 1rem;
          background: #f8fbff;
          padding: 0.85rem 1rem;
          color: #334155;
          box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        }

        .cc-filter-actions {
          display: flex;
          flex-wrap: wrap;
          gap: 0.85rem;
        }

        @media (min-width: 768px) {
          .cc-filter-actions {
            grid-column: 1 / -1;
          }
        }

        .cc-primary-btn,
        .cc-secondary-btn {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          gap: 0.65rem;
          min-height: 3.2rem;
          border-radius: 1rem;
          padding: 0.85rem 1.15rem;
          font-size: 0.92rem;
          font-weight: 700;
          line-height: 1;
          white-space: nowrap;
          transition: 0.2s ease;
        }

        .cc-primary-btn {
          border: 1px solid #0f172a;
          background: #0f172a;
          color: #fff;
          box-shadow: 0 10px 22px rgba(15, 23, 42, 0.14);
        }

        .cc-primary-btn:hover {
          transform: translateY(-1px);
          background: #004665;
          border-color: #004665;
          box-shadow: 0 14px 28px rgba(0, 70, 101, 0.18);
        }

        .cc-secondary-btn {
          border: 1px solid #cbd5e1;
          background: #fff;
          color: #334155;
          box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
        }

        .cc-secondary-btn:hover {
          background: #f8fafc;
          border-color: #94a3b8;
        }

        .cc-btn-icon {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 2rem;
          height: 2rem;
          border-radius: 0.75rem;
          background: rgba(255, 255, 255, 0.1);
          border: 1px solid rgba(255, 255, 255, 0.14);
          flex: 0 0 auto;
        }
      </style>

      <div class="cc-hero mb-6 px-6 py-7 sm:px-8">
        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-sm font-medium uppercase tracking-[0.18em] text-slate-500">Claim confirmations</p>
          <h1 class="mt-2 text-3xl font-bold tracking-tight text-[#004665] sm:text-4xl">Listado de confirmaciones SAMO</h1>
          <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600">
            Consulta el estado actual de las confirmaciones y ejecuta una sincronizacion desde el ultimo changestamp guardado en la base de datos activa.
          </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div class="cc-chip">
            <span class="text-base">DB</span>
            <span>Conexion activa:</span>
            <span class="font-semibold text-slate-900">{{ $connection }}</span>
          </div>
        </div>
      </div>
      </div>

      @if(session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50/90 px-5 py-4 text-sm text-emerald-800 shadow-sm">
          {{ session('status') }}
        </div>
      @endif

      @if(session('error'))
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50/90 px-5 py-4 text-sm text-rose-800 shadow-sm">
          {{ session('error') }}
        </div>
      @endif

      <div class="cc-panel mb-6 rounded-3xl px-6 py-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Sincronizacion manual</p>
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Actualizar confirmaciones de claim</h2>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600">
              Lanza una sincronizacion inmediata contra SAMO y recupera solo los cambios nuevos a partir del ultimo `changestamp` guardado.
            </p>
          </div>

          <form method="POST" action="{{ route('claim-confirmations.sync') }}" class="shrink-0">
            @csrf
            <button type="submit"
              class="cc-primary-btn focus:outline-none focus:ring-2 focus:ring-slate-400/40">
              <span class="cc-btn-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v6h6M20 20v-6h-6M19 9a7 7 0 0 0-12-2M5 15a7 7 0 0 0 12 2" />
                </svg>
              </span>
              <span>Actualizar ahora</span>
            </button>
          </form>
        </div>
      </div>

      @if(! $tableReady)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 text-amber-900 shadow-sm">
          <h2 class="text-lg font-semibold">La tabla aun no existe en esta conexion</h2>
          <p class="mt-2 text-sm text-amber-800">
            El boton de actualizar ya esta disponible, pero esta conexion necesita primero la migracion de `claim_confirmations` para poder guardar y listar resultados.
          </p>
        </div>
      @else
        <div class="mb-6 grid gap-4 md:grid-cols-3">
          <div class="cc-stat-card p-5 pt-7">
            <p class="text-sm text-slate-500">Total registros</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['total']) }}</p>
          </div>

          <div class="cc-stat-card p-5 pt-7">
            <p class="text-sm text-slate-500">Ultimo changestamp</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">
              {{ $stats['last_changestamp'] ? number_format($stats['last_changestamp']) : '0' }}
            </p>
          </div>

          <div class="cc-stat-card p-5 pt-7">
            <p class="text-sm text-slate-500">Ultima actualizacion</p>
            <p class="mt-3 text-base font-semibold leading-relaxed text-slate-900">
              {{ $stats['last_updated_at'] ? \Illuminate\Support\Carbon::parse($stats['last_updated_at'])->format('d/m/Y H:i:s') : 'Sin sincronizar' }}
            </p>
          </div>
        </div>

        <div class="mb-6">
          <div class="cc-panel rounded-2xl p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
              <div>
                <p class="text-sm font-semibold text-slate-900">Exportar a CSV</p>
                <p class="mt-1 text-sm text-slate-600">
                  Descarga la lista actual respetando los filtros activos. Si dejas el limite vacio, se exporta todo el listado filtrado.
                </p>
              </div>

              <form method="GET" action="{{ route('claim-confirmations.export') }}" class="cc-toolbar-form">
                @if(($filters['status'] ?? '') !== '')
                  <input type="hidden" name="status" value="{{ $filters['status'] }}">
                @endif
                @if($filters['comment_error'] ?? false)
                  <input type="hidden" name="comment_error" value="1">
                @endif

                <div class="cc-field">
                  <label for="export_limit">
                    Maximo de filas
                  </label>
                  <input
                    id="export_limit"
                    name="limit"
                    type="number"
                    min="1"
                    max="1000"
                    value="{{ old('limit') }}"
                    placeholder="Todo"
                    class="cc-input sm:w-40"
                  >
                  @error('limit')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                  @enderror
                </div>

                <button
                  type="submit"
                  class="cc-primary-btn focus:outline-none focus:ring-2 focus:ring-slate-300"
                >
                  <span class="cc-btn-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v1a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-1" />
                    </svg>
                  </span>
                  <span>Exportar CSV</span>
                </button>
              </form>
            </div>
          </div>
        </div>

        <div class="mb-6">
          <div class="cc-panel rounded-2xl p-5">
            <div class="flex flex-col gap-4">
              <div>
                <p class="text-sm font-semibold text-slate-900">Filtrar resultados</p>
                <p class="mt-1 text-sm text-slate-600">
                  Acota el listado por estado o mostrando solo comentarios que contengan errores.
                </p>
              </div>

              <form method="GET" action="{{ route('claim-confirmations.index') }}" class="cc-filter-form">
                <div class="cc-field">
                  <label for="status_filter">
                    Status
                  </label>
                  <select
                    id="status_filter"
                    name="status"
                    class="cc-select"
                  >
                    <option value="">Todos</option>
                    @foreach($statusOptions as $statusOption)
                      <option value="{{ $statusOption }}" @selected(($filters['status'] ?? '') === $statusOption)>
                        {{ $statusOption }}
                      </option>
                    @endforeach
                  </select>
                </div>

                <label class="cc-check-card">
                  <input
                    type="checkbox"
                    name="comment_error"
                    value="1"
                    class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                    @checked($filters['comment_error'] ?? false)
                  >
                  <span>Solo comentarios con `ERROR`</span>
                </label>

                <div class="cc-filter-actions">
                  <button
                    type="submit"
                    class="cc-primary-btn focus:outline-none focus:ring-2 focus:ring-slate-300"
                  >
                    <span class="cc-btn-icon">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5h18M6 12h12M10 19h4" />
                      </svg>
                    </span>
                    <span>Aplicar filtros</span>
                  </button>

                  <a
                    href="{{ route('claim-confirmations.index') }}"
                    class="cc-secondary-btn focus:outline-none focus:ring-2 focus:ring-slate-200"
                  >
                    Limpiar
                  </a>
                </div>
              </form>
            </div>
          </div>
        </div>

        @if(($filters['status'] ?? '') !== '' || ($filters['comment_error'] ?? false))
          <div class="mb-6 flex flex-wrap gap-2">
            @if(($filters['status'] ?? '') !== '')
              <span class="cc-filter-pill">Status: {{ $filters['status'] }}</span>
            @endif
            @if($filters['comment_error'] ?? false)
              <span class="cc-filter-pill">Comentario con ERROR</span>
            @endif
          </div>
        @endif

        <div class="cc-table-wrap">
          <div class="overflow-x-auto">
            <table class="cc-table min-w-full divide-y divide-slate-200">
              <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                  <th class="px-4 py-3">ID</th>
                  <th class="px-4 py-3">Claim</th>
                  <th class="px-4 py-3">Changestamp</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3">Flag</th>
                  <th class="px-4 py-3">Comment</th>
                  <th class="px-4 py-3">Cost</th>
                  <th class="px-4 py-3">Created</th>
                  <th class="px-4 py-3">Updated</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white text-sm text-slate-700">
                @forelse($confirmations as $confirmation)
                  @php
                    $statusValue = strtoupper(trim((string) $confirmation->status));
                    $statusClass = str_contains($statusValue, 'NOT')
                      ? 'cc-status-warning'
                      : (str_contains($statusValue, 'CONFIRMED') ? 'cc-status-confirmed' : 'cc-status-neutral');
                    $hasErrorComment = str_contains(strtoupper((string) $confirmation->comment), 'ERROR');
                  @endphp
                  <tr class="align-top {{ $hasErrorComment ? 'cc-comment-error' : '' }}">
                    <td class="whitespace-nowrap px-4 py-4 text-slate-500">{{ $confirmation->id }}</td>
                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ $confirmation->claim }}</td>
                    <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700">{{ $confirmation->changestamp }}</td>
                    <td class="whitespace-nowrap px-4 py-3">
                      <span class="cc-status-pill {{ $statusClass }}">{{ $confirmation->status }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                      <span class="cc-flag-pill">{{ $confirmation->flag ?? '-' }}</span>
                    </td>
                    <td class="px-4 py-3 leading-relaxed {{ $hasErrorComment ? 'font-medium text-rose-700' : 'text-slate-700' }}">{{ $confirmation->comment ?? '-' }}</td>
                    <td class="whitespace-nowrap px-4 py-3">{{ number_format((float) $confirmation->cost, 4, '.', '') }}</td>
                    <td class="whitespace-nowrap px-4 py-3">{{ optional($confirmation->created_at)->format('d/m/Y H:i:s') }}</td>
                    <td class="whitespace-nowrap px-4 py-3">{{ optional($confirmation->updated_at)->format('d/m/Y H:i:s') }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">
                      No hay confirmaciones guardadas todavia en esta base de datos.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="border-t border-slate-200 px-4 py-4">
            {{ $confirmations->links() }}
          </div>
        </div>
      @endif
    </div>
  </div>
</x-app-layout>
