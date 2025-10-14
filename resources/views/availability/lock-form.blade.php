{{-- DEBUG temporal: quitar luego --}}
{{-- @dump($incomingData['distri'] ?? null) --}}
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
            // 1) Cargar el payload de la habitación/selección.
            // ⚠️ Prioriza SIEMPRE el $data que manda el controlador (incluye 'distri'),
            // y usa session('form_data') solo para completar lo que falte.
            $incomingData = [];

            if (isset($data) && is_iterable($data)) {
                $incomingData = collect($data)->toArray();
            }

            // Mezcla no destructiva: lo que venga en $data pisa lo anterior de sesión.
            $incomingData = array_replace_recursive(
                (array) session('form_data', []),
                (array) $incomingData
            );

            // 2) Localizador flasheado por el controller después del bloqueo
            $locata = session('locata');

            // Helper para imprimir seguro
            $s = fn ($v) => e((string) ($v ?? ''));

            // Claves a excluir al reinyectar como hidden
            $deny = ['_token', '_method'];

            /**
             * Imprime inputs hidden soportando arrays anidados (pack, distri, etc.)
             */
            function renderHiddenInputs(string $name, $value): void {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $newName = is_int($k) ? "{$name}[{$k}]" : "{$name}[{$k}]";
                        renderHiddenInputs($newName, $v);
                    }
                } else {
                    echo '<input type="hidden" name="' . e($name) . '" value="' . e((string) $value) . '">';
                }
            }

            // --- Derivados que usas más abajo ---
            $pack   = $incomingData['pack'] ?? [];
            $distri = $incomingData['distri'] ?? [];

            // Normaliza las distribuciones por habitación en base a 'pack' y 'distri'
            // IMPORTANTE: los formularios envían distri con índice 1..N
            // Aquí lo normalizamos a 0..N-1 para pintar la UI.
            $roomDistributions = [];

            if (is_array($pack) && count($pack) > 0) {
                foreach ($pack as $idx => $_room) {
                    $k1 = $idx + 1; // distri viene 1-based
                    $roomDistributions[$idx] = [
                        'numadl' => (int) ($distri[$k1]['numadl'] ?? 2),
                        'numnin' => (int) ($distri[$k1]['numnin'] ?? 0),
                        'edanin' => isset($distri[$k1]['edanin']) && is_array($distri[$k1]['edanin'])
                            ? array_values($distri[$k1]['edanin'])
                            : [],
                    ];
                }
            } else {
                // Caso habitación simple: construimos desde distri directamente (1..N => 0..N-1)
                if (!empty($distri) && is_array($distri)) {
                    $i = 0;
                    foreach ($distri as $unused => $v) {
                        $roomDistributions[$i++] = [
                            'numadl' => (int) ($v['numadl'] ?? 2),
                            'numnin' => (int) ($v['numnin'] ?? 0),
                            'edanin' => isset($v['edanin']) && is_array($v['edanin']) ? array_values($v['edanin']) : [],
                        ];
                    }
                }
            }

            $hasPack   = isset($incomingData['pack']) && is_array($incomingData['pack']) && count($incomingData['pack']) > 0;
            $hasDistri = !empty($distri) && is_array($distri);
        @endphp

        <div class="container mx-auto px-4 py-6 bg-white rounded-lg shadow">

            {{-- 🔒 PASO 1: BLOQUEAR --}}
            <div class="flex items-center gap-3 mb-4">
                <h1 class="text-2xl font-bold text-gray-800">Paso 1: Bloquear Reserva</h1>
                @if ($locata)
                    <span class="text-sm px-2 py-1 rounded bg-green-50 text-green-700 border border-green-300">
                        Bloqueo ya generado (locata: {{ $s($locata) }})
                    </span>
                @endif
            </div>

            <form method="POST" action="{{ route('availability.lock.submit') }}">
                @csrf

                {{-- 🏨 Datos de la selección --}}
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-700">Datos de la Selección</h3>

                    @if (!empty($incomingData))
                        <p><strong>Hotel:</strong>
                            {{ $s($incomingData['hotel_name'] ?? 'N/A') }}
                            ({{ $s($incomingData['hotel_code'] ?? 'N/A') }})
                        </p>

                        <p><strong>Régimen:</strong> {{ $s($incomingData['board'] ?? 'N/A') }}</p>

                        <p>
                            <strong>Fechas:</strong>
                            {{ $s($incomingData['start_date'] ?? '') }} - {{ $s($incomingData['end_date'] ?? '') }}
                        </p>

                        @if ($hasPack)
                            <div class="mt-3 border rounded-md p-3 bg-gray-50">
                                <p class="font-medium text-gray-700 mb-2">Habitaciones incluidas (pack):</p>
                                <ul class="list-disc pl-5 space-y-1 text-sm text-gray-700">
                                    @foreach ($incomingData['pack'] as $idx => $room)
                                        @php
                                            $rid = $room['room_internal_id'] ?? '';
                                            $ref = $room['refdis'] ?? '';
                                            $ppn = $room['price_per_night'] ?? '';
                                        @endphp
                                        <li>
                                            <span class="font-semibold">Hab {{ $idx + 1 }}:</span>
                                            ID {{ $s($rid) }}
                                            @if ($ref !== '')
                                                — Ref: {{ $s($ref) }}
                                            @endif
                                            @if ($ppn !== '')
                                                — {{ $s($ppn) }} {{ $s($incomingData['currency'] ?? '') }} / noche
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <p><strong>Habitación:</strong> {{ $s($incomingData['room_type'] ?? 'Sin especificar') }}</p>
                            <p>
                                <strong>Precio por noche:</strong>
                                {{ $s($incomingData['price_per_night'] ?? 'N/A') }}
                                {{ $s($incomingData['currency'] ?? '') }}
                            </p>
                            @if (!empty($incomingData['room_internal_id']))
                                <p class="text-sm text-gray-600">
                                    <strong>ID Interno Habitación:</strong> {{ $s($incomingData['room_internal_id']) }}
                                </p>
                            @endif
                        @endif

                        {{-- Reinyecta TODO el payload original como hidden inputs --}}
                        @foreach ($incomingData as $key => $value)
                            @continue(in_array($key, $deny, true))
                            @php renderHiddenInputs($key, $value); @endphp
                        @endforeach
                    @else
                        <p class="text-red-600">
                            ⚠️ No hay datos cargados de la reserva. Por favor, realiza una búsqueda nuevamente.
                        </p>
                    @endif
                </div>

                {{-- 👶 Fechas de nacimiento por habitación (adultos/niños) --}}
                @if (!empty($incomingData) && ($hasPack || $hasDistri) && !empty($roomDistributions))
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700">Fechas de nacimiento por Habitación</h3>
                        <p class="text-sm text-gray-600 mb-3">
                            Introduce la <strong>fecha de nacimiento</strong> de cada ocupante (adultos y niños) por habitación.
                        </p>

                        @foreach ($roomDistributions as $idx => $dist)
                            @php
                                $numAdl = max(0, (int) ($dist['numadl'] ?? 2));
                                $numNin = max(0, (int) ($dist['numnin'] ?? 0));
                            @endphp

                            <div class="mb-4 border rounded p-4 bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium">Habitación {{ $idx + 1 }}</div>
                                    <div class="text-sm text-gray-600">
                                        Adultos: {{ $numAdl }} @if ($numNin > 0) · Niños: {{ $numNin }} @endif
                                    </div>
                                </div>

                                {{-- Adultos --}}
                                @if ($numAdl > 0)
                                    <div class="mt-3">
                                        <label class="block font-medium text-sm text-gray-700 mb-1">Fechas de nacimiento de adultos</label>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                            @for ($a = 0; $a < $numAdl; $a++)
                                                <input
                                                    type="date"
                                                    name="birthdates[{{ $idx }}][adults][]"
                                                    required
                                                    class="w-full border rounded px-3 py-2"
                                                    placeholder="dd/mm/aaaa">
                                            @endfor
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">Adultos ≥ 12 años.</p>
                                    </div>
                                @endif

                                {{-- Niños --}}
                                @if ($numNin > 0)
                                    <div class="mt-4">
                                        <label class="block font-medium text-sm text-gray-700 mb-1">Fechas de nacimiento de niños</label>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                            @for ($k = 0; $k < $numNin; $k++)
                                                <input
                                                    type="date"
                                                    name="birthdates[{{ $idx }}][children][]"
                                                    required
                                                    class="w-full border rounded px-3 py-2"
                                                    placeholder="dd/mm/aaaa">
                                            @endfor
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">La edad del niño se calculará automáticamente según la fecha de entrada.</p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <button
                    type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 transition disabled:opacity-50"
                    @if (empty($incomingData)) disabled @endif>
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
