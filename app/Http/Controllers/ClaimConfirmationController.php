<?php

namespace App\Http\Controllers;

use App\Models\ClaimConfirmation;
use App\Services\ClaimConfirmationSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClaimConfirmationController extends Controller
{
    private const EXPORT_LIMIT_DEFAULT = 100;
    private const EXPORT_LIMIT_MAX = 1000;

    public function index(Request $request)
    {
        $connection = DB::getDefaultConnection();

        if (! Schema::connection($connection)->hasTable('claim_confirmations')) {
            return view('claim-confirmations.index', [
                'connection' => $connection,
                'tableReady' => false,
                'confirmations' => collect(),
                'stats' => [
                    'total' => 0,
                    'last_changestamp' => null,
                    'last_updated_at' => null,
                ],
            ]);
        }

        $query = ClaimConfirmation::query()->orderByDesc('changestamp')->orderByDesc('claim');

        $confirmations = $query->paginate(25)->withQueryString();

        return view('claim-confirmations.index', [
            'connection' => $connection,
            'tableReady' => true,
            'confirmations' => $confirmations,
            'stats' => [
                'total' => ClaimConfirmation::count(),
                'last_changestamp' => ClaimConfirmation::max('changestamp'),
                'last_updated_at' => ClaimConfirmation::max('updated_at'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $connection = DB::getDefaultConnection();

        if (! Schema::connection($connection)->hasTable('claim_confirmations')) {
            return back()->with('error', "La tabla claim_confirmations no existe todavia en la conexion {$connection}.");
        }

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:' . self::EXPORT_LIMIT_MAX],
        ]);

        $limit = (int) ($validated['limit'] ?? self::EXPORT_LIMIT_DEFAULT);

        $rows = ClaimConfirmation::query()
            ->orderByDesc('changestamp')
            ->orderByDesc('claim')
            ->limit($limit)
            ->get([
                'id',
                'claim',
                'changestamp',
                'status',
                'flag',
                'comment',
                'cost',
                'created_at',
                'updated_at',
            ]);

        $filename = sprintf(
            'claim-confirmations-%s-%s.csv',
            $connection,
            now()->format('Ymd-His')
        );

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'id',
                'claim',
                'changestamp',
                'status',
                'flag',
                'comment',
                'cost',
                'created_at',
                'updated_at',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->claim,
                    $row->changestamp,
                    $row->status,
                    $row->flag,
                    $row->comment,
                    $row->cost !== null ? number_format((float) $row->cost, 4, '.', '') : null,
                    optional($row->created_at)->format('Y-m-d H:i:s'),
                    optional($row->updated_at)->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function sync(
        ClaimConfirmationSyncService $syncService
    ): RedirectResponse {
        $connection = DB::getDefaultConnection();

        if (! Schema::connection($connection)->hasTable('claim_confirmations')) {
            return back()->with('error', "La tabla claim_confirmations no existe todavia en la conexion {$connection}.");
        }

        try {
            $result = $syncService->sync($connection);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = $result['rows_upserted'] > 0
            ? "Sincronizacion completada. {$result['rows_upserted']} confirmaciones actualizadas hasta changestamp {$result['last_changestamp']}."
            : "No habia cambios nuevos. Ultimo changestamp: {$result['last_changestamp']}.";

        return back()->with('status', $message);
    }
}
