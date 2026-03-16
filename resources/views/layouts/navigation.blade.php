<nav x-data="{ open: false }"
    class="bg-slate-200/80 backdrop-blur border-b border-slate-300 shadow-sm">

    @php
        $db = session('db_connection', 'mysql');
        $isCli2 = $db === 'mysql_cli2';
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <!-- LEFT -->
            <div class="flex items-center">
                <div class="hidden sm:flex items-center gap-6 sm:ms-6">
                    <x-nav-link
                        :href="route('dashboard')"
                        :active="request()->routeIs('dashboard')"
                        class="text-base font-semibold tracking-wide">
                        {{ __('Inicio') }}
                    </x-nav-link>

                    <x-nav-link
                        :href="route('claim-confirmations.index')"
                        :active="request()->routeIs('claim-confirmations.*')"
                        class="text-base font-semibold tracking-wide">
                        {{ __('Claim Confirmations') }}
                    </x-nav-link>

                    <x-nav-link
                        :href="route('availability.form')"
                        :active="request()->routeIs('availability.*')"
                        class="text-base font-semibold tracking-wide">
                        {{ __('Disponibilidad') }}
                    </x-nav-link>

                    <x-nav-link
                        :href="route('itravex.status')"
                        :active="request()->routeIs('itravex.status')"
                        class="text-base font-semibold tracking-wide">
                        {{ __('Estado Locata') }}
                    </x-nav-link>

                    <x-nav-link
                        :href="route('logs.itravex')"
                        :active="request()->routeIs('logs.itravex*')"
                        class="text-base font-semibold tracking-wide">
                        {{ __('Logs') }}
                    </x-nav-link>

                    @if(! $isCli2)
                        <x-nav-link
                            :href="route('giata.properties.raw.index')"
                            :active="request()->routeIs('giata.properties.raw.*')"
                            class="text-base font-semibold tracking-wide">
                            {{ __('GIATA Propiedades') }}
                        </x-nav-link>

                        <x-nav-link
                            :href="route('giata.providers.index')"
                            :active="request()->routeIs('giata.providers.*')"
                            class="text-base font-semibold tracking-wide">
                            {{ __('GIATA Proveedores') }}
                        </x-nav-link>

                        <x-nav-link
                            :href="route('giata.codes.browser')"
                            :active="request()->routeIs('giata.codes.browser')"
                            class="text-base font-semibold tracking-wide">
                            {{ __('GIATA Codigos') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- RIGHT -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-md
                                   text-sm font-medium text-slate-700
                                   bg-transparent hover:bg-slate-300/70
                                   focus:outline-none transition">

                            <span>{{ Auth::user()->name }}</span>

                            <svg class="h-4 w-4 text-slate-500"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- HAMBURGER -->
            <div class="flex items-center sm:hidden">
                <button @click="open = !open"
                    class="inline-flex items-center justify-center p-2 rounded-md
                               text-slate-500 hover:bg-slate-300/60
                               focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }"
                            class="inline-flex" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }"
                            class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    <!-- RESPONSIVE MENU -->
    <div x-show="open" class="sm:hidden border-t border-slate-300 bg-slate-200/90">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Inicio') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('claim-confirmations.index')" :active="request()->routeIs('claim-confirmations.*')">
                {{ __('Claim Confirmations') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('availability.form')" :active="request()->routeIs('availability.*')">
                {{ __('Disponibilidad') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('itravex.status')" :active="request()->routeIs('itravex.status')">
                {{ __('Estado Locata') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('logs.itravex')" :active="request()->routeIs('logs.itravex*')">
                {{ __('Logs') }}
            </x-responsive-nav-link>

            @if(! $isCli2)
                <x-responsive-nav-link :href="route('giata.properties.raw.index')" :active="request()->routeIs('giata.properties.raw.*')">
                    {{ __('GIATA Propiedades') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('giata.providers.index')" :active="request()->routeIs('giata.providers.*')">
                    {{ __('GIATA Proveedores') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('giata.codes.browser')" :active="request()->routeIs('giata.codes.browser')">
                    {{ __('GIATA Codigos') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-slate-300">
            <div class="px-4">
                <div class="font-medium text-base text-slate-800">
                    {{ Auth::user()->name }}
                </div>
                <div class="font-medium text-sm text-slate-500">
                    {{ Auth::user()->email }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link
                        :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
