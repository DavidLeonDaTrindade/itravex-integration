<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LogViewerController extends Controller
{
    /**
     * Visor de logs (canal itravex) con selector de archivo.
     * GET /logs/itravex?file=itravex-2025-10-15.log&lines=400&locata=ABC123&q=...
     */
    public function itravey(Request $request)
    {
        $q       = trim((string) $request->input('q', ''));          // búsqueda libre (contiene)
        $locata  = trim((string) $request->input('locata', ''));     // filtro por locata
        $lines   = (int) $request->input('lines', 400);              // últimas N líneas
        $lines   = in_array($lines, [200,400,800,1200,2000]) ? $lines : 400;
        $channel = 'itravex';

        // 1) Listar archivos disponibles del canal
        [$allFiles, $pattern] = $this->listLogFiles($channel);       // nombres: itravex-YYYY-MM-DD.log (orden desc)

        // 2) Resolver archivo seleccionado (query ?file=)
        $requested = (string) $request->input('file', '');
        [$selected, $selectedPath] = $this->resolveSelectedFile($requested, $allFiles, $pattern, $channel);

        $hasFile = $selectedPath && is_readable($selectedPath);

        // 3) Tail y filtros
        $entries = [];
        if ($hasFile) {
            $rawTail = $this->tailFile($selectedPath, $lines); // string con \n
            $entries = array_values(array_filter(explode("\n", $rawTail), function ($ln) use ($q, $locata) {
                if ($locata !== '' && !Str::contains($ln, $locata)) {
                    return false;
                }
                if ($q !== '') {
                    return Str::contains(Str::lower($ln), Str::lower($q));
                }
                return true;
            }));
        }

        return view('logs.itravex', [
            'hasFile'  => $hasFile,
            'file'     => $selectedPath, // ruta completa mostrada bajo el cuadro
            'entries'  => $entries,
            'q'        => $q,
            'locata'   => $locata,
            'lines'    => $lines,
            'allFiles' => $allFiles,     // <- para el <select>
            'selected' => $selected,     // <- nombre seleccionado (no ruta)
        ]);
    }

    /**
     * Descarga del archivo de log elegido.
     * GET /logs/itravex/download?file=itravex-2025-10-15.log
     */
    public function download(Request $request)
    {
        $channel = 'itravex';
        [$allFiles, $pattern] = $this->listLogFiles($channel);

        $requested = (string) $request->input('file', '');
        [$selected, $selectedPath] = $this->resolveSelectedFile($requested, $allFiles, $pattern, $channel);
        abort_unless($selectedPath && is_readable($selectedPath), 404, 'Archivo de log no disponible');

        return response()->download($selectedPath, $selected);
    }

    /**
     * Devuelve [listaDeArchivosOrdenadaDesc, regexPattern].
     */
    private function listLogFiles(string $channel): array
    {
        $logDir  = storage_path('logs');
        $pattern = '/^' . preg_quote($channel, '/') . '-\d{4}-\d{2}-\d{2}\.log$/';

        $allFiles = collect(File::files($logDir))
            ->map(fn($f) => $f->getFilename())
            ->filter(fn($name) => preg_match($pattern, $name))
            ->sortDesc()
            ->values()
            ->all();

        // Si no hay daily, ofrecer el single (canal.log) si existe
        $single = "{$channel}.log";
        if (empty($allFiles) && File::exists($logDir . DIRECTORY_SEPARATOR . $single)) {
            $allFiles = [$single];
            // patrón que también acepte el single
            $pattern = '/^(' . preg_quote($channel, '/') . '\.log|' . preg_quote($channel, '/') . '-\d{4}-\d{2}-\d{2}\.log)$/';
        }

        return [$allFiles, $pattern];
    }

    /**
     * Valida y resuelve el archivo seleccionado. Devuelve [nombre, ruta].
     * Si no es válido o no existe, elige el más reciente disponible, o null.
     */
    private function resolveSelectedFile(string $requested, array $allFiles, string $pattern, string $channel): array
    {
        $logDir = storage_path('logs');

        if ($requested !== '' && preg_match($pattern, $requested) && in_array($requested, $allFiles, true)) {
            $path = $logDir . DIRECTORY_SEPARATOR . $requested;
            if (is_file($path)) {
                return [$requested, $path];
            }
        }

        // fallback: último disponible (si lo hay)
        if (!empty($allFiles)) {
            $fallback = $allFiles[0];
            return [$fallback, $logDir . DIRECTORY_SEPARATOR . $fallback];
        }

        // último recurso: intentar último por nombre (legacy)
        $latestPath = $this->latestChannelPath($channel);
        if ($latestPath && is_file($latestPath)) {
            return [basename($latestPath), $latestPath];
        }

        return [null, null];
    }

    /**
     * Busca el último archivo del canal (daily o single). Legacy/fallback.
     */
    private function latestChannelPath(string $channel): ?string
    {
        $single = storage_path("logs/{$channel}.log");
        $dailyGlob = storage_path("logs/{$channel}-*.log");

        $dailyFiles = glob($dailyGlob) ?: [];
        if (!empty($dailyFiles)) {
            rsort($dailyFiles, SORT_STRING);
            return $dailyFiles[0];
        }
        if (is_file($single)) return $single;
        return null;
    }

    /**
     * Lee eficientemente las últimas $lines líneas de un archivo (tail).
     * Retorna string con saltos de línea.
     */
    private function tailFile(string $filepath, int $lines = 400, int $buffer = 4096): string
    {
        $f = fopen($filepath, 'rb');
        if ($f === false) return '';

        $output = '';
        $chunk  = '';
        $filesize = filesize($filepath);

        while ($filesize > 0) {
            $seek = max($filesize - $buffer, 0);
            $read = $filesize - $seek;
            fseek($f, $seek);
            $chunk = fread($f, $read) . $chunk;
            $filesize = $seek;

            if (substr_count($chunk, "\n") >= $lines + 1) {
                $pos = $this->nthLastPos($chunk, "\n", $lines);
                $output = substr($chunk, $pos + 1);
                break;
            }
        }
        if ($output === '') $output = $chunk;
        fclose($f);
        return rtrim($output, "\r\n");
    }

    private function nthLastPos(string $haystack, string $needle, int $n): int
    {
        $pos = strlen($haystack);
        for ($i = 0; $i < $n; $i++) {
            $pos = strrpos(substr($haystack, 0, $pos), $needle);
            if ($pos === false) return -1;
        }
        return $pos;
    }
}
