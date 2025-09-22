{{-- resources/views/availability/status.blade.php --}}
<x-app-layout>
  @php
      // Protecciones: valores por defecto si la ruta no inyecta datos
      /** @var \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection $peticiones */
      $peticiones = $peticiones ?? collect();
  @endphp

  <div class="min-h-screen bg-gray-100 py-10 px-4">
    <div class="max-w-6xl mx-auto bg-white shadow-lg rounded-lg p-6">
      <h2 class="text-2xl font-bold text-blue-700 mb-6">Estado de Peticiones Itravex</h2>

      

      <div class="overflow-x-auto">
        <table class="min-w-full table-auto border border-gray-300 text-sm text-center">
          <thead class="bg-gray-200 text-gray-700">
            <tr>
              <th class="py-2 px-3 border">Locata</th>
              <th class="py-2 px-3 border">Hotel</th>
              <th class="py-2 px-3 border">Habitación</th>
              <th class="py-2 px-3 border">Régimen</th>
              <th class="py-2 px-3 border">Fechas</th>
              <th class="py-2 px-3 border">Huéspedes</th>
              <th class="py-2 px-3 border">Precio</th>
              <th class="py-2 px-3 border">Estado</th>
              <th class="py-2 px-3 border">Acción</th>
            </tr>
          </thead>

          <tbody>
          @forelse ($peticiones as $p)
            <tr class="hover:bg-gray-50">
              <td class="py-2 px-3 border font-mono">{{ $p->locata }}</td>
              <td class="py-2 px-3 border">{{ $p->hotel_name ?? '—' }}</td>
              <td class="py-2 px-3 border">{{ $p->room_type ?? '—' }}</td>
              <td class="py-2 px-3 border">{{ $p->board ?? '—' }}</td>
              <td class="py-2 px-3 border">
                @php
                  $ini = $p->start_date ? \Carbon\Carbon::parse($p->start_date)->format('d/m/Y') : '—';
                  $fin = $p->end_date ? \Carbon\Carbon::parse($p->end_date)->format('d/m/Y') : '—';
                @endphp
                {{ $ini }} - {{ $fin }}
              </td>
              <td class="py-2 px-3 border">{{ $p->num_guests ?? 0 }}</td>
              <td class="py-2 px-3 border">
                {{ number_format((float)($p->total_price ?? 0), 2, ',', '.') }} {{ $p->currency ?? '' }}
              </td>
              <td class="py-2 px-3 border">
                @php
                  $statusClasses = [
                    'ND' => ['label' => 'No disponible', 'color' => 'bg-gray-200 text-gray-700'],
                    'CE' => ['label' => 'Cerrado',       'color' => 'bg-gray-400 text-white'],
                    'DS' => ['label' => 'Disponible',    'color' => 'bg-green-100 text-green-800'],
                    'OP' => ['label' => 'Opción',        'color' => 'bg-yellow-100 text-yellow-800'],
                    'PC' => ['label' => 'Bajo petición', 'color' => 'bg-purple-100 text-purple-800'],
                    'CM' => ['label' => 'Confirmado',    'color' => 'bg-blue-100 text-blue-800'],
                    'VL' => ['label' => 'Venta libre',   'color' => 'bg-teal-100 text-teal-800'],
                    'AN' => ['label' => 'Cancelado',     'color' => 'bg-red-100 text-red-800'],
                    'FA' => ['label' => 'Facturado',     'color' => 'bg-indigo-100 text-indigo-800'],
                    'S'  => ['label' => 'Sin estado',    'color' => 'bg-gray-100 text-gray-700'],
                  ];
                  $estado = $statusClasses[$p->status ?? 'S'] ?? ['label' => ($p->status ?? '—'), 'color' => 'bg-gray-300 text-gray-800'];
                @endphp
                <span class="{{ $estado['color'] }} px-2 py-1 text-xs rounded-full font-medium">
                  {{ $estado['label'] }}
                </span>
              </td>

              <td class="py-2 px-3 border">
                {{-- Botón cancelar: usa el nombre de ruta real. En tus rutas es "availability.cancel" --}}
                @if (Route::has('availability.cancel'))
                  <form action="{{ route('availability.cancel') }}" method="POST"
                        onsubmit="return confirm('¿Estás seguro de que deseas cancelar esta reserva?');"
                        class="inline-block mb-1">
                    @csrf
                    <input type="hidden" name="locata" value="{{ $p->locata }}">
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3 py-1 rounded-full transition">
                      Cancelar
                    </button>
                  </form>
                @endif

                {{-- Botón eliminar: solo si existe la ruta itravex.destroy --}}
                @if (Route::has('itravex.destroy'))
                  <form action="{{ route('itravex.destroy', $p->id) }}" method="POST"
                        class="inline-block"
                        onsubmit="return confirm('¿Eliminar esta reserva de la base de datos?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="bg-gray-500 hover:bg-gray-600 text-white text-xs font-semibold px-3 py-1 rounded-full">
                      Eliminar
                    </button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="py-4 text-gray-500">No hay peticiones registradas.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-6">
        @if (is_object($peticiones) && method_exists($peticiones, 'links'))
          {{ $peticiones->links() }}
        @endif
      </div>
    </div>
  </div>
</x-app-layout>
