<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ItravexReservation;


class ItravexReservationController extends Controller
{
    public function index()
    {
        $peticiones = ItravexReservation::orderBy('created_at', 'desc')->paginate(10);
        return view('availability.status', compact('peticiones'));
    }
    public function destroy($id)
{
    $reserva = \App\Models\ItravexReservation::findOrFail($id);
    $reserva->delete();

    return redirect()->route('itravex.status')->with('success', 'Reserva eliminada correctamente.');
}

}
