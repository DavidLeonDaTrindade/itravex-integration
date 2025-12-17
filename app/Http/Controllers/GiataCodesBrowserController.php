<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GiataCodesBrowserController extends Controller
{
    /**
     * Vista principal: muestra el browser de códigos GIATA.
     * Recupera, si existe, la lista GIATA guardada en sesión y la pasa al textarea.
     */
    public function index(Request $request)
    {
        $giataIdsString = session('giata_ids_string', '');

        return view('giata.codes', [
            'giataIdsString' => $giataIdsString,
        ]);
    }

    /**
     * Procesa un Excel XLSX subido con códigos GIATA.
     * - Solo primera hoja
     * - Columna A con cabecera en fila 1 (ej: "GIATA")
     * - Códigos desde la fila 2 hacia abajo
     */
    public function uploadGiata(Request $request)
    {
        $request->validate([
            'giata_file' => 'required|file|mimes:xlsx',
        ], [
            'giata_file.required' => 'Debes seleccionar un archivo XLSX.',
            'giata_file.mimes'    => 'El archivo debe ser un Excel .xlsx.',
        ]);

        $path = $request->file('giata_file')->getRealPath();

        if (!is_readable($path)) {
            return back()
                ->withErrors(['giata_file' => 'No se ha podido leer el archivo subido.'])
                ->withInput();
        }

        // Leemos el XLSX a mano con ZipArchive
        $giataIds = $this->extractGiataFromXlsx($path);

        if (empty($giataIds)) {
            return back()
                ->withErrors(['giata_file' => 'No se han encontrado códigos GIATA válidos en la columna A.'])
                ->withInput();
        }

        $giataIdsString = implode(', ', $giataIds);

        // Guardamos en sesión para que index() lo pinte en el textarea
        session(['giata_ids_string' => $giataIdsString]);

        return redirect()
            ->route('giata.codes.browser')
            ->with('status', 'Lista GIATA cargada (' . count($giataIds) . ' códigos).');
    }

    /**
     * Lee un XLSX sencillo y devuelve los valores de la columna A (fila 2 en adelante).
     * Sin librerías externas: ZipArchive + SimpleXML.
     */
    protected function extractGiataFromXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        // 1) sharedStrings (para celdas de tipo string)
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sx = simplexml_load_string($sharedXml);
            if ($sx !== false) {
                foreach ($sx->si as $si) {
                    // Un si puede tener t o varios r/t; nos quedamos con el texto plano
                    $sharedStrings[] = trim((string) $si->t);
                }
            }
        }

        // 2) Primera hoja: xl/worksheets/sheet1.xml
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);
        if ($sheet === false) {
            return [];
        }

        $giataIds = [];

        if (!isset($sheet->sheetData->row)) {
            return [];
        }

        foreach ($sheet->sheetData->row as $row) {
            $rowIndex = (int) $row['r'];

            // Saltar cabecera (fila 1)
            if ($rowIndex === 1) {
                continue;
            }

            foreach ($row->c as $c) {
                $cellRef = (string) $c['r']; // ej: "A2", "B2"

                // Solo nos interesa la columna A
                if (strpos($cellRef, 'A') !== 0) {
                    continue;
                }

                $type = (string) $c['t'];   // puede ser 's' (shared string) o vacío/num
                $value = isset($c->v) ? (string) $c->v : '';

                if ($value === '') {
                    continue;
                }

                if ($type === 's') {
                    // Índice a sharedStrings
                    $idx = (int) $value;
                    $text = $sharedStrings[$idx] ?? '';
                } else {
                    // Número o texto en bruto
                    $text = $value;
                }

                $text = trim((string) $text);
                if ($text !== '') {
                    $giataIds[] = $text;
                }
            }
        }

        // Únicos y en orden
        return collect($giataIds)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
