<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PanelController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('panel/Index', [
            'usuario' => [
                'name' => $request->user()->name,
                'rol' => $request->user()->rol?->descripcion,
            ],
        ]);
    }
}