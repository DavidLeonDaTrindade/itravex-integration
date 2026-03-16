<?php

namespace App\Http\Controllers;

use App\Models\ClaimConfirmation;
use App\Services\ClaimConfirmationSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ClaimConfirmationController extends Controller
{
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
