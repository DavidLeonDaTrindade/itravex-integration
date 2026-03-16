<x-app-layout>
  <div class="min-h-[calc(100vh-64px)] bg-slate-50 py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-sm font-medium text-slate-500">Claim confirmations</p>
          <h1 class="mt-1 text-3xl font-bold tracking-tight text-[#004665]">Listado de confirmaciones SAMO</h1>
          <p class="mt-2 max-w-3xl text-sm text-slate-600">
            Consulta el estado actual de las confirmaciones y ejecuta una sincronizacion desde el ultimo changestamp guardado en la base de datos activa.
          </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 shadow-sm">
            Conexion activa:
            <span class="font-semibold text-slate-900">{{ $connection }}</span>
          </div>
        </div>
      </div>

      @if(session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
          {{ session('status') }}
        </div>
      @endif

      @if(session('error'))
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
          {{ session('error') }}
        </div>
      @endif

      <div class="mb-6 rounded-3xl border border-slate-200 bg-gradient-to-r from-white to-slate-50 px-6 py-6 shadow-sm">
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
              class="group inline-flex min-h-14 items-center gap-3 rounded-2xl border border-slate-950 bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow-[0_14px_30px_rgba(15,23,42,0.18)] transition hover:-translate-y-0.5 hover:bg-slate-800 hover:shadow-[0_18px_36px_rgba(15,23,42,0.22)] focus:outline-none focus:ring-2 focus:ring-slate-400/40">
              <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/12 ring-1 ring-white/15 transition group-hover:bg-white/18">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v6h6M20 20v-6h-6M19 9a7 7 0 0 0-12-2M5 15a7 7 0 0 0 12 2" />
                </svg>
              </span>
              <span class="flex flex-col items-start leading-none">
                <span>Actualizar ahora</span>
                <span class="mt-1 text-[11px] font-medium uppercase tracking-[0.16em] text-slate-300">Sincronizar con SAMO</span>
              </span>
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
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total registros</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($stats['total']) }}</p>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Ultimo changestamp</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">
              {{ $stats['last_changestamp'] ? number_format($stats['last_changestamp']) : '0' }}
            </p>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Ultima actualizacion</p>
            <p class="mt-2 text-base font-semibold text-slate-900">
              {{ $stats['last_updated_at'] ? \Illuminate\Support\Carbon::parse($stats['last_updated_at'])->format('d/m/Y H:i:s') : 'Sin sincronizar' }}
            </p>
          </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-100">
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
                  <tr class="align-top hover:bg-slate-50">
                    <td class="whitespace-nowrap px-4 py-3">{{ $confirmation->id }}</td>
                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">{{ $confirmation->claim }}</td>
                    <td class="whitespace-nowrap px-4 py-3">{{ $confirmation->changestamp }}</td>
                    <td class="whitespace-nowrap px-4 py-3">{{ $confirmation->status }}</td>
                    <td class="whitespace-nowrap px-4 py-3">{{ $confirmation->flag ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $confirmation->comment ?? '-' }}</td>
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
