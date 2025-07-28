<?php

namespace App\Http\Controllers;

use App\Models\KardexMovimiento;
use Illuminate\Http\Request;

class KardexController extends Controller
{
    public function index(Request $request)
    {
        $query = KardexMovimiento::with('insumo')
            ->orderBy('fecha', 'desc');

        // Filtros de movimientos
        if ($request->insumo_id) {
            $query->where('insumo_id', $request->insumo_id);
        }
        if ($request->desde && $request->hasta) {
            $query->whereBetween('fecha', [$request->desde, $request->hasta]);
        }

        $movimientos = $query->paginate(15);

        // Para el select de insumos
        $insumos = \App\Models\Insumo::pluck('nombre', 'id');

        // Stock actual si hay insumo filtrado
        $stockActual = null;
        if ($request->insumo_id) {
            $insumo = \App\Models\Insumo::find($request->insumo_id);
            $stockActual = $insumo ? $insumo->stock : null;
        }

        return view('kardex.index', compact('movimientos', 'insumos', 'stockActual'));
    }
}
