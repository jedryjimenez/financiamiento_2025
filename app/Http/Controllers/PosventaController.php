<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\Insumo;
use App\Models\Productor;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Credito;
use App\Models\CreditoDetalle;
use App\Support\CreditoHelper;
use App\Models\KardexMovimiento;

class PosventaController extends Controller
{
    public function create()
    {
        $clientes = Productor::orderBy('nombre')->get();
        return view('posventa.create', compact('clientes'));
    }

    public function buscarInsumos(Request $request)
    {
        $term = $request->get('q', '');
        $data = Insumo::where('nombre', 'like', "%$term%")
            ->limit(15)->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'text' => $i->nombre,
                'precio' => $i->precio_venta,
                'stock' => $i->stock
            ]);
        return response()->json(['results' => $data]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'productor_id'        => 'nullable|exists:productors,id',
            'fecha'               => 'required|date',
            'modo'                => 'required|in:contado,credito',
            'paga_con'            => 'nullable|numeric|min:0',
            'items'               => 'required|array|min:1',
            'items.*.insumo_id'   => 'required|exists:insumos,id',
            'items.*.cantidad'    => 'required|numeric|min:0.01',
            'items.*.precio'      => 'required|numeric|min:0',
            'items.*.descuento'   => 'nullable|numeric|min:0',
        ]);

        if ($data['modo'] === 'credito' && empty($data['productor_id'])) {
            return back()
                ->with('error', 'Para venta a crédito debe seleccionar un productor.')
                ->withInput();
        }

        $caja = null;
        if ($data['modo'] === 'contado') {
            $caja = Caja::whereNull('cierre_at')->first();
            if (!$caja) {
                return back()->with('error', 'No hay caja abierta.')->withInput();
            }
        }

        DB::transaction(function () use ($data, $caja) {
            $subtotal   = 0;
            $descuentos = 0;
            $detalles   = [];

            foreach ($data['items'] as $item) {
                $insumo = Insumo::findOrFail($item['insumo_id']);
                if ($insumo->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para {$insumo->nombre}");
                }

                $lineDiscount = $item['descuento'] ?? 0;
                $lineSub      = ($item['cantidad'] * $item['precio']) - $lineDiscount;

                $subtotal   += $item['cantidad'] * $item['precio'];
                $descuentos += $lineDiscount;

                $detalles[] = [
                    'insumo_id'        => $insumo->id,
                    'cantidad'         => $item['cantidad'],
                    'precio_unitario'  => $item['precio'],
                    'descuento'        => $lineDiscount,
                    'subtotal'         => $lineSub,
                ];

                // → Decremento de stock
                $insumo->decrement('stock', $item['cantidad']);

                // → kardex salida
                KardexMovimiento::create([
                    'insumo_id'       => $insumo->id,
                    'tipo'            => 'Salida',                       // marca salida
                    'cantidad'        => $item['cantidad'],              // unidades vendidas
                    'precio_unitario' => $item['precio'],
                    'total'           => $item['cantidad'] * $item['precio'],
                    'referencia'      => 'Venta #' . ($venta->id ?? 'XX'),
                    'observaciones'   => 'Venta ' . ucfirst($data['modo']),
                    'fecha'           => $data['fecha'],
                ]);
            }

            $total = $subtotal - $descuentos;

            // Registrar la venta principal
            $venta = Venta::create([
                'productor_id'     => $data['productor_id'] ?? null,
                'fecha'            => $data['fecha'],
                'subtotal'         => $subtotal,
                'descuento_total'  => $descuentos,
                'total'            => $total,
                'paga_con'         => $data['modo'] === 'contado' ? ($data['paga_con'] ?? $total) : 0,
                'cambio'           => $data['modo'] === 'contado'
                    ? max(0, ($data['paga_con'] ?? $total) - $total)
                    : 0,
                'es_credito'       => $data['modo'] === 'credito',
            ]);

            // ahora que $venta->id existe, ajustamos la referencia en kardex
            foreach (KardexMovimiento::where('referencia', 'Venta #XX')->get() as $mov) {
                $mov->update(['referencia' => 'Venta #' . $venta->id]);
            }

            // relacionamos detalles
            foreach ($detalles as $d) {
                $venta->detalles()->create($d);
            }

            if ($data['modo'] === 'contado') {
                MovimientoCaja::create([
                    'caja_id'    => $caja->id,
                    'tipo'       => 'ingreso',
                    'monto'      => $total,
                    'fecha'      => $data['fecha'],
                    'concepto'   => "Venta #{$venta->id}",
                    'automatico' => true,
                ]);
            } else {
                // ventas a crédito...
                $credito = Credito::create([
                    'productor_id'  => $data['productor_id'],
                    'fecha_entrega' => $data['fecha'],
                    'moneda'        => 'C$',
                    'total'         => $total,
                    'abonado'       => 0,
                ]);
                foreach ($detalles as $d) {
                    $insumo = Insumo::find($d['insumo_id']);
                    $tasa   = in_array(strtolower($insumo->nombre), ['frijoles', 'maiz']) ? 0 : 3;
                    $credito->detalles()->create([
                        'insumo_id'      => $d['insumo_id'],
                        'cantidad'       => $d['cantidad'],
                        'precio_unitario' => $d['precio_unitario'],
                        'subtotal'       => $d['subtotal'],
                        'interes'        => $tasa,
                    ]);
                }
                CreditoHelper::recalcularSaldoProductor($credito->productor);
            }
        });

        return redirect()
            ->route('posventa.create')
            ->with('success', $data['modo'] === 'credito'
                ? 'Venta registrada como crédito.'
                : 'Venta registrada.');
    }



    public function listar()
    {
        $ventas = Venta::with('productor')->latest()->paginate(20);
        return view('posventa.index', compact('ventas'));
    }

    public function anular(Venta $venta)
    {
        if ($venta->anulada) {
            return back();
        }

        DB::transaction(function () use ($venta) {
            foreach ($venta->detalles as $d) {
                $d->insumo->increment('stock', $d->cantidad);
            }
            $venta->update(['anulada' => true]);

            // registrar egreso para revertir ingreso
            $caja = Caja::whereNull('cierre_at')->first();
            if ($caja) {
                MovimientoCaja::create([
                    'caja_id' => $caja->id,
                    'tipo' => 'egreso',
                    'monto' => $venta->total,
                    'fecha' => now()->toDateString(),
                    'concepto' => "Anulación venta #{$venta->id}",
                    'automatico' => true,
                ]);
            }
        });

        return back()->with('success', 'Venta anulada.');
    }
}
