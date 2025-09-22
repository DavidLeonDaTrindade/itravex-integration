{{-- resources/views/logs/itravex.blade.php --}}
<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-slate-800 mb-4">
            Visor de logs — Canal: <span class="text-blue-700">itravex</span>
        </h1>

        @php
            // Defaults por si acaso
            $hasFile = $hasFile ?? false;
            $file    = $file    ?? null;
            $entries = $entries ?? [];
            $q       = $q       ?? '';
            $locata  = $locata  ?? '';
            $lines   = $lines   ?? 400;
        @endphp

        @if(!$hasFile)
            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded">
                No se encontró archivo de log para este canal.
            </div>
        @else
            <form method="GET" action="{{ route('logs.itravex') }}" class="grid md:grid-cols-4 gap-3 mb-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Filtrar por locata</label>
                    <input type="text" name="locata" value="{{ $locata }}" class="mt-1 w-full rounded border-slate-300" placeholder="ej: ABC1234">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Últimas líneas</label>
                    <select name="lines" class="mt-1 w-full rounded border-slate-300">
                        @foreach([200,400,800,1200,2000] as $opt)
                            <option value="{{ $opt }}" @selected($lines==$opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button class="h-10 px-4 rounded bg-blue-600 text-white hover:bg-blue-700">Aplicar</button>
                    <a href="{{ route('logs.itravex.download') }}" class="h-10 px-4 rounded bg-slate-700 text-white hover:bg-slate-800 inline-flex items-center">Descargar</a>
                </div>
            </form>

            <div class="text-sm text-slate-500 mb-2">
                Archivo: <code class="text-slate-700">{{ $file }}</code>
            </div>

            <div class="rounded bg-black text-green-200 p-4 overflow-auto max-h-[70vh] text-sm leading-relaxed font-mono">
                @forelse($entries as $ln)
                    <div>{!! nl2br(e($ln)) !!}</div>
                @empty
                    <div class="text-yellow-300">No hay líneas para mostrar con los filtros aplicados.</div>
                @endforelse
            </div>
        @endif
    </div>
</x-app-layout>
