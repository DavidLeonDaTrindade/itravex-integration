<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-8">

        {{-- Flash de éxito --}}
        @if (session('success'))
            <div class="bg-green-100 text-green-800 border border-green-300 rounded-md p-4 mb-6">
                ✅ {{ e(session('success')) }}
            </div>
        @endif

        {{-- Errores de validación --}}
        @if ($errors->any())
            <div class="bg-red-100 text-red-800 border border-red-300 rounded-md p-4 mb-6">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>❌ {{ e($error) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            // 1) Cargar el payload de la habitación/selección. Prioriza el que flasheamos en el controller.
            $incomingData = session('form_data');
            if (!$incomingData && isset($data) && is_iterable($data)) {
                $incomingData = collect($data)->toArray();
            }
            $incomingData = is_array($incomingData) ? $incomingData : [];

            // 2) Localizador flasheado por el controller después del bloqueo
            $locata = session('locata');

            // Pequeño helper para imprimir seguros
            $s = fn($v) => e((string)($v ?? ''));
        @endphp

        <div class="container mx-auto px-4 py-6 bg-white rounded-lg shadow">

            {{-- 🔒 PASO 1: BLOQUEAR --}}
            <div class="flex items-center gap-3 mb-4">
                <h1 class="text-2xl font-bold text-gray-800">Paso 1: Bloquear Reserva</h1>
                @if($locata)
                    <span class="text-sm px-2 py-1 rounded bg-green-50 text-green-700 border border-green-300">
                        Bloqueo ya generado (locata: {{ $s($locata) }})
                    </span>
                @endif
            </div>

            <form method="POST" action="{{ route('availability.lock.submit') }}">
                @csrf

                {{-- 🏨 Datos de la habitación --}}
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700">Datos de la Habitación</h3>

                    @if (!empty($incomingData))
                        <p><strong>Hotel:</strong>
                            {{ $s($incomingData['hotel_name'] ?? 'N/A') }}
                            ({{ $s($incomingData['hotel_code'] ?? 'N/A') }})
                        </p>
                        <p><strong>Habitación:</strong> {{ $s($incomingData['room_type'] ?? 'Sin especificar') }}</p>
                        <p><strong>Régimen:</strong> {{ $s($incomingData['board'] ?? 'N/A') }}</p>
                        <p>
                            <strong>Precio por noche:</strong>
                            {{ $s($incomingData['price_per_night'] ?? 'N/A') }}
                            {{ $s($incomingData['currency'] ?? '') }}
                        </p>
                        <p>
                            <strong>Fechas:</strong>
                            {{ $s($incomingData['start_date'] ?? '') }} - {{ $s($incomingData['end_date'] ?? '') }}
                        </p>

                        {{-- Campos ocultos: replica TODO el payload recibido de forma segura (filtrando claves no deseadas) --}}
                        @php
                            $deny = ['_token', '_method']; // por si acaso
                        @endphp
                        @foreach ($incomingData as $key => $value)
                            @continue(in_array($key, $deny, true))
                            @if (is_array($value))
                                @foreach ($value as $i => $item)
                                    <input type="hidden" name="{{ e($key) }}[{{ (int) $i }}]" value="{{ e((string) $item) }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ e($key) }}" value="{{ e((string) $value) }}">
                            @endif
                        @endforeach
                    @else
                        <p class="text-red-600">
                            ⚠️ No hay datos cargados de la reserva. Por favor, realiza una búsqueda nuevamente.
                        </p>
                    @endif
                </div>

                {{-- 👤 Datos del Cliente --}}
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700">Datos del Cliente</h3>

                    @for ($i = 0; $i < 2; $i++)
                        <div class="mb-4 border rounded p-4 bg-gray-50">
                            <label class="block mb-1 font-medium">Adulto {{ $i + 1 }}</label>

                            <label class="block mt-2 text-sm">Nombre:</label>
                            <input
                                type="text"
                                name="client_names[]"
                                required
                                class="w-full border rounded px-3 py-2"
                                value="{{ old('client_names.' . $i) }}"
                                placeholder="Nombre">

                            <label class="block mt-2 text-sm">Apellido:</label>
                            <input
                                type="text"
                                name="client_lastnames[]"
                                required
                                class="w-full border rounded px-3 py-2"
                                value="{{ old('client_lastnames.' . $i) }}"
                                placeholder="Apellido">

                            <label class="block mt-2 text-sm">Sexo:</label>
                            <select name="client_genders[]" required class="w-full border rounded px-3 py-2">
                                <option value="M" @selected(old('client_genders.' . $i) === 'M')>Masculino</option>
                                <option value="F" @selected(old('client_genders.' . $i) === 'F')>Femenino</option>
                            </select>

                            <label class="block mt-2 text-sm">Fecha de nacimiento:</label>
                            <input
                                type="date"
                                name="birthdates[]"
                                required
                                class="w-full border rounded px-3 py-2"
                                value="{{ old('birthdates.' . $i) }}">
                        </div>
                    @endfor
                </div>

                <button
                    type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 transition disabled:opacity-50"
                    @if(empty($incomingData)) disabled @endif
                >
                    🔒 Bloquear
                </button>
            </form>

            {{-- ✅ PASO 2: CIERRE DE RESERVA --}}
            @if ($locata)
                <div class="mt-10 p-6 bg-yellow-100 border border-yellow-400 rounded-md shadow-sm">
                    <h2 class="text-xl font-bold text-yellow-800 mb-2">Paso 2: Confirmar Reserva Final</h2>
                    <p class="text-yellow-800">
                        Bloqueo generado exitosamente. Localizador:
                        <strong>{{ $s($locata) }}</strong>
                    </p>
                    <p class="text-yellow-700 text-sm mt-1">
                        Recuerda que el bloqueo caduca en 30 minutos si no se confirma.
                    </p>

                    <form method="POST" action="{{ route('availability.close') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700">
                            ✅ Cerrar Reserva
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
