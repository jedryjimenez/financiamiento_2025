<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InsumosExport;
use Illuminate\Support\Facades\DB;

class InsumoController extends Controller
{
    public function index()
    {
        // colección vacía para que Blade no reviente en el primer render
        $insumos = collect();
        return view('insumos.index', compact('insumos'));
    }

    public function list(Request $request)
    {
        $search   = $request->get('search');
        $perPage  = $request->get('per_page', 10);
        $perPage  = $perPage === 'all' ? null : (int)$perPage;

        // 1) Base del query con filtro por nombre
        $query = Insumo::query()
            ->when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"));

        // 2) Totales sobre TODO el conjunto filtrado
        $totalCompra = (float) $query->sum(DB::raw('stock * precio_compra'));
        $totalVenta  = (float) $query->sum(DB::raw('stock * precio_venta'));

        // 3) Aplicar orden y paginación si aplica
        $insumos = $perPage
            ? $query->orderBy('nombre')->paginate($perPage)->withQueryString()
            : $query->orderBy('nombre')->get();

        // 4) Renderizar solo la sección 'table' de la vista
        $sections = view('insumos.index', compact('insumos', 'totalCompra', 'totalVenta'))
            ->renderSections();

        return response()->json([
            'html' => $sections['table'] ?? '',
        ]);
    }

    public function export(Request $request)
    {
        $search = $request->get('search');

        $query = Insumo::query()
            ->when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"));

        return Excel::download(new InsumosExport($query), 'insumos.xlsx');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        Insumo::create($request->all());

        return redirect()->route('insumos.index')->with('success', 'Insumo creado correctamente.');
    }

    public function update(Request $request, Insumo $insumo)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $insumo->update($request->all());

        return redirect()->route('insumos.index')->with('success', 'Insumo actualizado correctamente.');
    }

    public function destroy(Insumo $insumo)
    {
        $insumo->delete();

        return redirect()->route('insumos.index')->with('success', 'Insumo eliminado.');
    }

    public function stockMinimo()
    {
        $insumos = Insumo::whereColumn('stock', '<', 'stock_minimo')->get();

        return view('insumos.stock-minimo', compact('insumos'));
    }
}
