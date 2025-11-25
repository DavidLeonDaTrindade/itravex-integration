<?php

namespace App\Http\Controllers;

use App\Models\GiataPropertyRaw;
use Illuminate\Http\Request;

class GiataPropertyRawController extends Controller
{
    public function index(Request $request)
    {
        $place = $request->get('city');   // puede ser ciudad o destino
        $hotel = $request->get('hotel');  // nombre de hotel

        $query = GiataPropertyRaw::query();

        if ($place) {
            $query->where(function ($q) use ($place) {
                $q->where('city', 'like', '%' . $place . '%')
                    ->orWhere('destination', 'like', '%' . $place . '%');
            });
        }

        if ($hotel) {
            $query->where('name', 'like', '%' . $hotel . '%');
        }

        $properties = $query
            ->orderBy('giata_id')
            ->paginate(50)
            ->withQueryString();

        return view('giata.properties-raw.index', [
            'properties' => $properties,
            'city'       => $place,
            'hotel'      => $hotel,
        ]);
    }


    public function citySuggestions(Request $request)
    {
        $term = $request->get('q', '');

        $rows = GiataPropertyRaw::query()
            ->when($term, function ($q) use ($term) {
                $q->where(function ($qq) use ($term) {
                    $qq->where('city', 'like', $term . '%')
                        ->orWhere('destination', 'like', $term . '%');
                });
            })
            ->select('city', 'destination')
            ->limit(100)
            ->get();

        // Combinar city + destination en una lista única
        $labels = $rows->flatMap(function ($row) {
            return collect([$row->city, $row->destination]);
        })
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->take(10);

        return response()->json($labels->values());
    }

    public function nameSuggestions(Request $request)
    {
        $term = $request->get('q', '');
        $place = $request->get('city'); // ciudad o destino

        $query = GiataPropertyRaw::query();

        if ($place) {
            $query->where(function ($q) use ($place) {
                $q->where('city', 'like', '%' . $place . '%')
                    ->orWhere('destination', 'like', '%' . $place . '%');
            });
        }

        if ($term) {
            $query->where('name', 'like', $term . '%');
        }

        $names = $query
            ->whereNotNull('name')
            ->select('name')
            ->groupBy('name')
            ->orderBy('name')
            ->limit(10)
            ->pluck('name');

        return response()->json($names);
    }
    public function export(Request $request)
{
    $place = $request->get('city');    // ciudad o destino
    $hotel = $request->get('hotel');   // nombre de hotel

    $query = GiataPropertyRaw::query();

    if ($place) {
        $query->where(function ($q) use ($place) {
            $q->where('city', 'like', '%' . $place . '%')
              ->orWhere('destination', 'like', '%' . $place . '%');
        });
    }

    if ($hotel) {
        $query->where('name', 'like', '%' . $hotel . '%');
    }

    $filename = 'giata_properties_' . now()->format('Ymd_His') . '.csv';

    $callback = function () use ($query) {
        $handle = fopen('php://output', 'w');

        // BOM para que Excel respete UTF-8
        fwrite($handle, "\xEF\xBB\xBF");

        // Cabecera CSV
        fputcsv($handle, [
            'giata_id',
            'name',
            'city',
            'destination',
            'country_code',
            'latitude',
            'longitude',
            'email',
            'website',
        ]);

        // Sacamos TODO el resultado filtrado en chunks para no petar memoria
        $query->orderBy('giata_id')->chunk(2000, function ($rows) use ($handle) {
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->giata_id,
                    $row->name,
                    $row->city,
                    $row->destination,
                    $row->country_code,
                    $row->latitude,
                    $row->longitude,
                    $row->email,
                    $row->website,
                ]);
            }
        });

        fclose($handle);
    };

    return response()->streamDownload($callback, $filename, [
        'Content-Type' => 'text/csv; charset=UTF-8',
    ]);
}

}
