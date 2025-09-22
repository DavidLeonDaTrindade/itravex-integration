<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Itravex · Plug&Beds</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700|instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- Tailwind fallback (del scaffold original) --}}
        <style>
            /*! tailwind fallback minified - omitido por brevedad en este snippet */
        </style>
    @endif

    <meta name="color-scheme" content="light dark">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 dark:bg-slate-900 dark:text-slate-100 antialiased">

    {{-- Topbar --}}
    <header class="border-b border-slate-200/70 dark:border-slate-700/60 bg-white/80 dark:bg-slate-900/70 backdrop-blur supports-backdrop-blur:backdrop-blur-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="h-16 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                    <div class="h-9 w-9 rounded-xl bg-blue-600 text-white grid place-items-center shadow-sm group-hover:scale-105 transition">
                        🛎️
                    </div>
                    <div class="leading-tight">
                        <p class="font-semibold text-slate-900 dark:text-white tracking-tight">Itravex</p>
                        <p class="text-xs text-slate-500">Plug&Beds · B2B</p>
                    </div>
                </a>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition text-sm">
                                <span>Ir al panel</span> <span>→</span>
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition text-sm">
                                Iniciar sesión
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition text-sm shadow">
                                    Crear cuenta
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-transparent to-cyan-50 dark:from-slate-800/50 dark:via-transparent dark:to-slate-800/10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-14 relative">
            <div class="grid lg:grid-cols-2 gap-10 items-center">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        Motor de disponibilidad & reservas Itravex
                    </h1>
                    <p class="mt-4 text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
                        Consulta disponibilidad, bloquea habitaciones y confirma reservas en tiempo real.
                        Accede también al estado de tus peticiones y al visor de logs técnico.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('availability.form') }}"
                               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition shadow">
                                🔍 Buscar disponibilidad
                            </a>
                            <a href="{{ route('itravex.status') }}"
                               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-slate-300/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                📊 Estado peticiones
                            </a>
                            <a href="{{ route('logs.itravex') }}"
                               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-slate-300/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                🧾 Ver logs
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition shadow">
                                Entrar para comenzar
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-slate-300/80 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                                    Crear cuenta
                                </a>
                            @endif
                        @endauth
                    </div>

                    @auth
                        <p class="mt-3 text-sm text-slate-500">
                            También puedes ir a tu <a href="{{ route('dashboard') }}" class="underline decoration-slate-300 hover:text-slate-700 dark:hover:text-slate-200">Inicio</a>.
                        </p>
                    @endauth
                </div>

                <div class="relative">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm p-6">
                        <div class="grid sm:grid-cols-2 gap-4">
                            {{-- Card 1 --}}
                            <a href="{{ route('availability.form') }}"
                               class="group rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md hover:border-blue-300/70 transition bg-slate-50/60 dark:bg-slate-900/40">
                                <div class="text-2xl">🧭</div>
                                <h3 class="mt-2 font-semibold">Disponibilidad</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    Formulario de búsqueda con zona/códigos y fechas.
                                </p>
                                <span class="mt-2 inline-flex items-center gap-1 text-blue-700 dark:text-blue-400 text-sm">
                                    Abrir <span class="group-hover:translate-x-0.5 transition">→</span>
                                </span>
                            </a>

                            {{-- Card 2 --}}
                            <a href="{{ route('itravex.status') }}"
                               class="group rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md hover:border-blue-300/70 transition bg-slate-50/60 dark:bg-slate-900/40">
                                <div class="text-2xl">📡</div>
                                <h3 class="mt-2 font-semibold">Estado de peticiones</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    Últimas búsquedas, locks y cierres guardados.
                                </p>
                                <span class="mt-2 inline-flex items-center gap-1 text-blue-700 dark:text-blue-400 text-sm">
                                    Ver estado <span class="group-hover:translate-x-0.5 transition">→</span>
                                </span>
                            </a>

                            {{-- Card 3 --}}
                            <a href="{{ route('logs.itravex') }}"
                               class="group rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md hover:border-blue-300/70 transition bg-slate-50/60 dark:bg-slate-900/40">
                                <div class="text-2xl">🧪</div>
                                <h3 class="mt-2 font-semibold">Logs Itravex</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    Visor rápido con filtro por locata y descarga.
                                </p>
                                <span class="mt-2 inline-flex items-center gap-1 text-blue-700 dark:text-blue-400 text-sm">
                                    Abrir visor <span class="group-hover:translate-x-0.5 transition">→</span>
                                </span>
                            </a>

                            {{-- Card 4 --}}
                            <a href="{{ route('dashboard') }}"
                               class="group rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:shadow-md hover:border-blue-300/70 transition bg-slate-50/60 dark:bg-slate-900/40">
                                <div class="text-2xl">🏠</div>
                                <h3 class="mt-2 font-semibold">Inicio</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    Tu panel con accesos y métricas.
                                </p>
                                <span class="mt-2 inline-flex items-center gap-1 text-blue-700 dark:text-blue-400 text-sm">
                                    Ir al panel <span class="group-hover:translate-x-0.5 transition">→</span>
                                </span>
                            </a>
                        </div>
                    </div>
                    <div class="absolute -inset-4 -z-10 bg-gradient-to-tr from-blue-200/20 via-cyan-200/10 to-transparent blur-2xl rounded-3xl"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-slate-200/70 dark:border-slate-700/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 text-sm text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p>© {{ date('Y') }} Plug&Beds · Itravex Integration</p>
            <div class="flex items-center gap-3">
                <a href="{{ route('availability.form') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Disponibilidad</a>
                <span class="opacity-40">·</span>
                <a href="{{ route('itravex.status') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Estado</a>
                <span class="opacity-40">·</span>
                <a href="{{ route('logs.itravex') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Logs</a>
            </div>
        </div>
    </footer>

</body>
</html>
