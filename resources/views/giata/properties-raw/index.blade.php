{{-- NO uses @extends ni @section --}}
<x-app-layout>
    <div class="min-h-screen bg-slate-50 px-4 py-8">
        <div class="mx-auto max-w-7xl space-y-6">

            <div class="space-y-3">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">GIATA – Propiedades (CSV)</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $properties->total() }} registros importados desde el fichero GIATA.
                    </p>
                </div>

                <form method="GET" class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                        {{-- Autocomplete de ciudad/destino --}}
                        <div class="relative">
                            <label for="city-input" class="block text-[11px] font-medium text-slate-500 mb-1">
                                Ciudad o destino
                            </label>
                            <input
                                type="text"
                                name="city"
                                id="city-input"
                                value="{{ request('city') }}"
                                placeholder="Ej: Adeje, Tenerife, Barcelona..."
                                autocomplete="off"
                                class="w-64 rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <ul id="city-suggestions"
                                class="absolute left-0 top-full z-50 mt-1 max-h-60 w-full overflow-auto 
           rounded-md border border-slate-200 bg-white text-xs shadow-lg hidden"></ul>
                        </div>

                        {{-- Autocomplete de hotel --}}
                        <div class="relative">
                            <label for="hotel-input" class="block text-[11px] font-medium text-slate-500 mb-1">
                                Nombre del hotel
                            </label>
                            <input
                                type="text"
                                name="hotel"
                                id="hotel-input"
                                value="{{ request('hotel') }}"
                                placeholder="Nombre del hotel..."
                                autocomplete="off"
                                class="w-64 rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <ul id="hotel-suggestions"
                                class="absolute left-0 top-full z-50 mt-1 max-h-60 w-full overflow-auto 
           rounded-md border border-slate-200 bg-white text-xs shadow-lg hidden"></ul>
                        </div>
                    </div>

                    <div class="flex gap-2 justify-end">
                        @if(request('city') || request('hotel'))
                        <a href="{{ route('giata.properties.raw.index') }}"
                            class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                            Limpiar
                        </a>
                        @endif

                        <a href="{{ route('giata.properties.raw.export', request()->only('city','hotel')) }}"
                            class="inline-flex items-center rounded-md px-3 py-2 text-xs font-medium text-white shadow-sm"
                            style="background:#FDB31B; border:1px solid #e6a217;"
                            onmouseover="this.style.background='#e6a217'"
                            onmouseout="this.style.background='#FDB31B'">
                            Exportar CSV
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-blue-700">
                            Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2">GIATA ID</th>
                        <th class="px-3 py-2">Nombre</th>
                        <th class="px-3 py-2">City / Destino</th>
                        <th class="px-3 py-2">País</th>
                        <th class="px-3 py-2">Lat / Lng</th>
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2">Web</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($properties as $prop)
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="px-3 py-2 font-mono text-[11px] text-slate-600">
                            {{ $prop->giata_id }}
                        </td>
                        <td class="px-3 py-2 text-[11px]">
                            <div class="font-medium text-slate-900">{{ $prop->name }}</div>
                            @if($prop->alternative_name)
                            <div class="mt-0.5 text-[10px] text-slate-500 line-clamp-1">
                                {{ $prop->alternative_name }}
                            </div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-[11px]">
                            {{ $prop->city }}<br>
                            <span class="text-slate-500">{{ $prop->destination }}</span>
                        </td>
                        <td class="px-3 py-2 text-[11px]">
                            {{ $prop->country_code }}
                        </td>
                        <td class="px-3 py-2 text-[11px]">
                            @if($prop->latitude && $prop->longitude)
                            {{ $prop->latitude }}, {{ $prop->longitude }}
                            @else
                            <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-[11px] max-w-[180px]">
                            @if($prop->email)
                            <div class="text-slate-700 line-clamp-2">
                                {{ $prop->email }}
                            </div>
                            @else
                            <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-[11px] max-w-[160px]">
                            @if($prop->website)
                            @php
                            $firstUrl = explode(';', $prop->website)[0];
                            @endphp
                            <a href="{{ trim($firstUrl) }}" target="_blank"
                                class="text-blue-600 hover:underline line-clamp-1">
                                {{ trim($firstUrl) }}
                            </a>
                            @else
                            <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-xs text-slate-500">
                            No hay propiedades importadas todavía.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $properties->links() }}
        </div>

    </div>
    </div>

    {{-- Autocompletado JS --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const citiesUrl = "{{ route('giata.properties.raw.cities') }}";
            const namesUrl = "{{ route('giata.properties.raw.names') }}";

            const cityInput = document.getElementById('city-input');
            const cityList = document.getElementById('city-suggestions');
            const hotelInput = document.getElementById('hotel-input');
            const hotelList = document.getElementById('hotel-suggestions');

            function attachAutocomplete(input, list, urlBuilder) {
                let currentRequest = null;

                input.addEventListener('input', () => {
                    const term = input.value.trim();

                    if (term.length < 2) {
                        list.classList.add('hidden');
                        list.innerHTML = '';
                        return;
                    }

                    const url = urlBuilder(term);

                    if (currentRequest) {
                        currentRequest.abort();
                    }

                    const controller = new AbortController();
                    currentRequest = controller;

                    fetch(url, {
                            signal: controller.signal
                        })
                        .then(res => res.json())
                        .then(items => {
                            list.innerHTML = '';
                            if (!items.length) {
                                list.classList.add('hidden');
                                return;
                            }
                            items.forEach(value => {
                                const li = document.createElement('li');
                                li.textContent = value;
                                li.className = 'cursor-pointer px-2 py-1 hover:bg-slate-100 transition';
                                li.addEventListener('click', () => {
                                    input.value = value;
                                    list.classList.add('hidden');
                                    list.innerHTML = '';
                                });
                                list.appendChild(li);
                            });
                            list.classList.remove('hidden');
                        })
                        .catch(err => {
                            if (err.name !== 'AbortError') {
                                console.error(err);
                            }
                        });
                });

                document.addEventListener('click', (e) => {
                    if (!list.contains(e.target) && e.target !== input) {
                        list.classList.add('hidden');
                    }
                });
            }

            // Autocomplete de ciudades
            attachAutocomplete(
                cityInput,
                cityList,
                (term) => citiesUrl + '?q=' + encodeURIComponent(term)
            );

            // Autocomplete de hoteles (teniendo en cuenta la ciudad seleccionada)
            attachAutocomplete(
                hotelInput,
                hotelList,
                (term) => {
                    const city = cityInput.value.trim();
                    const params = new URLSearchParams();
                    params.set('q', term);
                    if (city) {
                        params.set('city', city);
                    }
                    return namesUrl + '?' + params.toString();
                }
            );
        });
    </script>
</x-app-layout>