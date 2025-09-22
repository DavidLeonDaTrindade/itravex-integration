<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request) {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request) {
        // valida y guarda (puedes dejarlo básico por ahora)
        $request->validate(['name' => 'required|string|max:255']);
        $request->user()->update(['name' => $request->name]);
        return back()->with('status', 'Perfil actualizado.');
    }

    public function destroy(Request $request) {
        $request->validate(['password' => 'required']);
        // si no quieres soportar borrar cuenta, puedes retornar 404 o deshabilitar este botón en la vista
        abort(404);
    }
}
