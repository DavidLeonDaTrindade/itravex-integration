<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZoneController extends Controller
{
    public function autocomplete(Request $request)
{
    $term = $request->query('q');

    if (strlen($term) < 3) {
        return response()->json([]);
    }

    return DB::table('zones')
        ->where('name', 'like', '%' . $term . '%')
        ->whereNotNull('name')
        ->select('code', 'name')
        ->limit(15)
        ->get();
}

}
