<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenciaController extends Controller
{
    /**
     * Guarda tema, sonido, volumen, presentacion y hojas (lo envia el navegador al cambiarlos).
     */
    public function guardar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'tema' => ['sometimes', 'in:light,dark'],
            'sonido' => ['sometimes', 'boolean'],
            'volumen' => ['sometimes', 'numeric', 'between:0,1'],
            'intro' => ['sometimes', 'boolean'],
            'hojas' => ['sometimes', 'boolean'],
        ]);

        $request->user()->guardarPreferencias($datos);

        return response()->json(['success' => true, 'preferencias' => $request->user()->preferenciasPublicas()]);
    }
}
