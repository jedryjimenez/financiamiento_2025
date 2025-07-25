<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Credito;
use App\Models\Productor;
use App\Models\Insumo;
use App\Models\RecepcionInsumo;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();
        $year = $hoy->year;

        // --- KPIs ---

        // Traemos créditos con relaciones necesarias
        $creditos = Credito::with(['detalles', 'abonos'])->get();

        // 1) Créditos activos (saldo pendiente > 0.01)
        $totalCreditos = $creditos->filter(function ($c) use ($hoy) {
            $dias = Carbon::parse($c->fecha_entrega)->diffInDays($hoy);
            $totalReal = $c->detalles->sum(
                fn($d) => round($d->subtotal * (1 + (($d->interes / 30) / 100) * $dias), 2)
            );
            return ($totalReal - $c->abonado) > 0.01;
        })->count();

        // 2) Total pendiente por cobrar
        $montoTotal = $creditos->reduce(function ($sum, $c) use ($hoy) {
            $dias = Carbon::parse($c->fecha_entrega)->diffInDays($hoy);
            $totalReal = $c->detalles->sum(
                fn($d) => round($d->subtotal * (1 + (($d->interes / 30) / 100) * $dias), 2)
            );
            return $sum + max(0, round($totalReal - $c->abonado, 2));
        }, 0.00);

        // 3) Productores activos
        $productoresActivos = Productor::count();

        // 4) Insumos en stock crítico
        $insumosCriticos = Insumo::whereColumn('stock', '<', 'stock_minimo')->count();

        // 5) Cuentas por pagar a proveedores
        $cuentasPorPagar = RecepcionInsumo::whereColumn('total_factura', '>', 'abonado')
            ->get()
            ->sum(fn($r) => $r->total_factura - $r->abonado);

        // --- Gráfico: Créditos otorgados por mes del año actual ---
        $raw = Credito::selectRaw('MONTH(fecha_entrega) as mes, COUNT(*) as cnt')
            ->whereYear('fecha_entrega', $year)
            ->groupBy('mes')
            ->pluck('cnt', 'mes')
            ->toArray();

        $meses = range(1, 12);
        $datosCred = array_map(fn($m) => $raw[$m] ?? 0, $meses);

        return view('dashboard', compact(
            'totalCreditos',
            'montoTotal',
            'productoresActivos',
            'insumosCriticos',
            'cuentasPorPagar',
            'meses',
            'datosCred',
            'year'
        ));
    }
}
