<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollectionPoint;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CollectionPointController extends Controller
{
    /**
     * Lista TODOS os pontos (aprovados e pendentes) para gerenciamento.
     */
    public function index()
    {
        return CollectionPoint::query()
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Lista apenas os pontos pendentes de aprovação.
     */
    public function pending()
    {
        return CollectionPoint::query()
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Aprova um ponto pendente, deixando-o visível no mapa público.
     */
    public function approve(CollectionPoint $point)
    {
        $point->update(['status' => 'approved']);

        return response()->json($point);
    }

    /**
     * Edita um ponto existente (qualquer campo, inclusive o status).
     */
    public function update(Request $request, CollectionPoint $point)
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
            'status' => ['required', Rule::in(['pending', 'approved'])],
        ]);

        $point->update($dados);

        return response()->json($point);
    }

    /**
     * Exclui um ponto.
     */
    public function destroy(CollectionPoint $point)
    {
        $point->delete();

        return response()->noContent();
    }
}
