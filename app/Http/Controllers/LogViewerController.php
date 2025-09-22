<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LogViewerController extends Controller
{
    /**
     * Muestra las últimas N líneas del log de Itravex,
     * con filtro por texto o por locata.
     */
    public function itravey(Request $request)
    {
        $q       = trim((string) $request->input('q', ''));         // búsqueda libre
        $locata  = trim((string) $request->input('locata', ''));    // filtro locata exacto
        $lines   = (int) $request->input('lines', 400);             // cuántas líneas leer
        $channel = 'itravex';

        // Encuentra el último archivo del canal (si es daily = itrave x-YYYY-MM-DD.log)
        $latestPath = $this->latestChannelPath($channel);
        if (!$latestPath || !is_readable($latestPath)) {
            return view('logs.itravex', [
                'hasFile' => false,
                'file' => $latestPath,
                'entries' => [],
                'q' => $q,
                'locata' => $locata,
                'lines' => $lines,
            ]);
        }

        // Tail eficiente de las últimas N líneas
        $raw = $this->tailFile($latestPath, $lines);

        // Filtrado (si hay búsqueda o locata)
        $entries = array_values(array_filter(explode("\n", $raw), function ($ln) use ($q, $locata) {
            if ($locata !== '') {
                // Buscar locata como palabra completa o en JSON de contexto
                if (!Str::contains($ln, $locata)) return false;
            }
            if ($q !== '') {
                return Str::contains(Str::lower($ln), Str::lower($q));
            }
            return true;
        }));

        return view('logs.itravex', [
            'hasFile'  => true,
            'file'     => $latestPath,
            'entries'  => $entries,
            'q'        => $q,
            'locata'   => $locata,
            'lines'    => $lines,
        ]);
    }

    /**
     * Descarga del archivo de log actual.
     */
    public function download()
    {
        $path = $this->latestChannelPath('itravex');
        abort_unless($path && is_readable($path), 404, 'Archivo de log no disponible');
        return response()->download($path, basename($path));
    }

    /**
     * Busca el último archivo del canal (daily o single).
     */
    private function latestChannelPath(string $channel): ?string
    {
        $single = storage_path("logs/{$channel}.log");
        $dailyGlob = storage_path("logs/{$channel}-*.log");

        // Prioriza daily si existe alguno
        $dailyFiles = glob($dailyGlob) ?: [];
        if (!empty($dailyFiles)) {
            rsort($dailyFiles, SORT_STRING); // el más reciente primero por nombre
            return $dailyFiles[0];
        }
        if (is_file($single)) return $single;
        return null;
    }

    /**
     * Lee eficientemente las últimas $lines líneas de un archivo (tail).
     */
    private function tailFile(string $filepath, int $lines = 400, int $buffer = 4096): string
    {
        $f = fopen($filepath, 'rb');
        if ($f === false) return '';

        $output = '';
        $chunk = '';
        $pos = -1;
        $lineCount = 0;

        fseek($f, 0, SEEK_END);
        $filesize = ftell($f);

        while ($filesize > 0) {
            $seek = max($filesize - $buffer, 0);
            $read = $filesize - $seek;
            fseek($f, $seek);
            $chunk = fread($f, $read) . $chunk;
            $filesize = $seek;

            $linesFound = substr_count($chunk, "\n");
            if ($linesFound >= $lines + 1) { // +1 para evitar cortar la primera línea
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
