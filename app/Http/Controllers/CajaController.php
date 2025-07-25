<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;


class CajaController extends Controller
{
    public function index(Request $request)
    {
        $caja = Caja::whereNull('cierre_at')->latest()->first();

        $perPage = (int) $request->input('per_page', 10);   // default 10
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $movimientosQuery = $caja
            ? $caja->movimientos()->orderByDesc('fecha')->orderByDesc('id')
            : MovimientoCaja::query()->whereRaw('1=0'); // colección vacía si no hay caja

        $movimientos = $movimientosQuery
            ->paginate($perPage)
            ->appends(['per_page' => $perPage]); // para mantener el valor en los links

        $ingresos = $caja?->movimientos()->where('tipo', 'ingreso')->sum('monto') ?? 0;
        $egresos = $caja?->movimientos()->where('tipo', 'egreso')->sum('monto') ?? 0;
        $teorico = $caja ? $caja->monto_inicial + $ingresos - $egresos : 0;

        return view('caja.index', compact(
            'caja',
            'movimientos',
            'ingresos',
            'egresos',
            'teorico',
            'perPage'
        ));
    }


    public function abrir(Request $request)
    {
        $data = $request->validate([
            'monto_inicial' => 'required|numeric|min:0'
        ]);

        if (Caja::whereNull('cierre_at')->exists()) {
            return back()->with('error', 'Ya existe una caja abierta.');
        }

        Caja::create([
            'user_id' => auth()->id(),
            'apertura_at' => now(),
            'monto_inicial' => $data['monto_inicial']
        ]);

        return back()->with('success', 'Caja abierta correctamente.');
    }

    public function movimientosStore(Request $request)
    {
        $caja = Caja::whereNull('cierre_at')->firstOrFail();

        $data = $request->validate([
            'fecha' => 'required|date',
            'tipo' => 'required|in:ingreso,egreso',
            'concepto' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
        ]);

        $caja->movimientos()->create($data + ['automatico' => false]);

        return back()->with('success', 'Movimiento registrado.');
    }

    public function cierreForm(Caja $caja)
    {
        abort_if(!$caja->esta_abierta, 404);

        $ingresos = $caja->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $egresos = $caja->movimientos()->where('tipo', 'egreso')->sum('monto');
        $teorico = $caja->monto_inicial + $ingresos - $egresos;

        return view('caja.cierre', compact('caja', 'ingresos', 'egresos', 'teorico'));
    }

    public function cerrar(Request $request, Caja $caja)
    {
        abort_if(!$caja->esta_abierta, 404);

        $data = $request->validate([
            'monto_final_real' => 'required|numeric|min:0',
            'observacion' => 'nullable|string|max:500'
        ]);

        $ingresos = $caja->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $egresos = $caja->movimientos()->where('tipo', 'egreso')->sum('monto');
        $teorico = $caja->monto_inicial + $ingresos - $egresos;

        $caja->update([
            'cierre_at' => now(),
            'monto_final_sistema' => $teorico,
            'monto_final_real' => $data['monto_final_real'],
            'diferencia' => $data['monto_final_real'] - $teorico,
            'observacion' => $data['observacion'] ?? null,
        ]);

        return redirect()->route('caja.historial')->with('success', 'Caja cerrada.');
    }

    public function historial(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');

        $q = Caja::whereNotNull('cierre_at');
        if ($start && $end) {
            $q->whereBetween('cierre_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
        }

        $cajas = $q->orderByDesc('cierre_at')->paginate(15)->appends(compact('start', 'end'));

        return view('caja.historial', compact('cajas', 'start', 'end'));
    }
}
