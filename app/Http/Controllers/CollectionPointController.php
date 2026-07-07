<?php

namespace App\Http\Controllers;

use App\Models\CollectionPoint;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CollectionPointController extends Controller
{
    /**
     * Lista pública: retorna apenas os pontos aprovados.
     */
    public function index()
    {
        return CollectionPoint::query()
            ->where('status', 'approved')
            ->orderBy('name')
            ->get();
    }

    /**
     * Cria um novo ponto sugerido pelo público (sempre nasce como 'pending').
     */
    public function store(Request $request)
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'waste_types' => ['required', 'array', 'min:1'],
            'waste_types.*' => [Rule::in(CollectionPoint::WASTE_TYPES)],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        // Independente do que vier na requisição, o status é sempre pendente.
        $dados['status'] = 'pending';

        $ponto = CollectionPoint::create($dados);

        return response()->json($ponto, 201);
    }
}
