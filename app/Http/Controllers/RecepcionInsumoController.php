<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\RecepcionInsumo;
use App\Models\RecepcionItem;
use App\Models\Insumo;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;

class RecepcionInsumoController extends Controller
{
    /**
     * Mostrar listado de recepciones con filtros.
     */
    public function index(Request $request)
    {
        $query = RecepcionInsumo::with('items.insumo', 'proveedor', 'abonos');

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }
        if ($request->filled('tipo_pago')) {
            $query->where('tipo_pago', $request->tipo_pago);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('fecha_factura', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('fecha_factura', '<=', $request->date_to);
        }

        $recepciones = $query->latest('fecha_factura')
            ->paginate(10)
            ->withQueryString();

        $insumos      = Insumo::all();
        $proveedores  = Proveedor::all();

        return view('recepcion.index', compact('recepciones', 'insumos', 'proveedores'));
    }

    /**
     * Almacenar una nueva recepción de insumo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id'       => 'required|exists:proveedores,id',
            'numero_factura'     => 'required|string',
            'fecha_factura'      => 'required|date',
            'tipo_pago'          => 'required|in:contado,credito',
            'comprobante'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'items'              => 'required|array|min:1',
            'items.*.insumo_id'      => 'required|exists:insumos,id',
            'items.*.cantidad'       => 'required|integer|min:1',
            'items.*.precio_compra'  => 'required|numeric|min:0',
            'items.*.precio_venta'   => 'required|numeric|min:0',
        ]);

        if (RecepcionInsumo::where('proveedor_id', $request->proveedor_id)
            ->where('numero_factura', $request->numero_factura)
            ->exists()
        ) {
            return back()
                ->withInput()
                ->withErrors(['numero_factura' => 'Ya existe esa factura para el proveedor.']);
        }

        DB::transaction(function () use ($request) {
            // 1) Obtener la caja activa del usuario
            $caja = Caja::where('user_id', auth()->id())
                ->whereNull('cierre_at')
                ->firstOrFail();

            // 2) Crear la recepción
            $data = $request->only('proveedor_id', 'numero_factura', 'fecha_factura', 'tipo_pago');
            if ($file = $request->file('comprobante')) {
                $data['comprobante'] = $file->store('comprobantes');
            }

            $recep = RecepcionInsumo::create(array_merge($data, [
                'total_factura' => 0,
                'abonado'       => 0,
            ]));

            // 3) Procesar ítems y actualizar stock/precios
            $total = 0;
            foreach ($request->items as $item) {
                $subtotal = $item['cantidad'] * $item['precio_compra'];
                $total += $subtotal;

                RecepcionItem::create([
                    'recepcion_id'   => $recep->id,
                    'insumo_id'      => $item['insumo_id'],
                    'cantidad'       => $item['cantidad'],
                    'precio_compra'  => $item['precio_compra'],
                    'precio_venta'   => $item['precio_venta'],
                    'subtotal'       => $subtotal,
                ]);

                $insumo = Insumo::findOrFail($item['insumo_id']);
                $insumo->increment('stock', $item['cantidad']);
                $insumo->update([
                    'precio_compra' => $item['precio_compra'],
                    'precio_venta'  => $item['precio_venta'],
                ]);
            }

            // 4) Actualizar totales en la recepción
            $recep->update([
                'total_factura' => $total,
                'abonado'       => $request->tipo_pago === 'contado' ? $total : 0,
            ]);

            // 5) Registrar movimiento en caja si es contado
            if ($request->tipo_pago === 'contado') {
                MovimientoCaja::create([
                    'caja_id'      => $caja->id,
                    'recepcion_id' => $recep->id,
                    'tipo'         => 'egreso',
                    'monto'        => $total,
                    'fecha'        => $recep->fecha_factura,
                    'concepto'     => "Pago contado factura {$recep->numero_factura}",
                    'automatico'   => true,
                ]);
            }
        });

        return back()->with('success', 'Recepción registrada correctamente.');
    }

    /**
     * Eliminar una recepción (y sus abonos, ítems y movimientos).
     */
    public function destroy(RecepcionInsumo $recepcion)
    {
        DB::transaction(function () use ($recepcion) {
            // Eliminar abonos asociados
            $recepcion->abonos()->delete();

            // Eliminar movimientos de caja vinculados a esta recepción
            MovimientoCaja::where('recepcion_id', $recepcion->id)->delete();

            // Eliminar ítems y la recepción
            $recepcion->items()->delete();
            $recepcion->delete();
        });

        return back()->with('success', 'Recepción eliminada.');
    }

    /**
     * Registrar un abono a una recepción y el egreso correspondiente.
     */
    public function abonar(Request $request, RecepcionInsumo $recepcion)
    {
        $pendiente = $recepcion->total_factura - $recepcion->abonado;

        $request->validate([
            'fecha_abono' => 'required|date',
            'comprobante' => 'nullable|string|max:100',
            'monto'       => "required|numeric|min:0.01|max:{$pendiente}",
        ]);

        DB::transaction(function () use ($request, $recepcion) {
            // 1) Obtener caja activa
            $caja = Caja::where('user_id', auth()->id())
                ->whereNull('cierre_at')
                ->firstOrFail();

            // 2) Crear el abono
            $recepcion->abonos()->create([
                'fecha_abono' => $request->fecha_abono,
                'comprobante' => $request->comprobante,
                'monto'       => $request->monto,
            ]);
            $recepcion->increment('abonado', $request->monto);

            // 3) Registrar egreso en caja
            MovimientoCaja::create([
                'caja_id'      => $caja->id,
                'recepcion_id' => $recepcion->id,
                'tipo'         => 'egreso',
                'monto'        => $request->monto,
                'fecha'        => $request->fecha_abono,
                'concepto'     => "Abono factura {$recepcion->numero_factura}",
                'automatico'   => false,
            ]);
        });

        return back()->with('success', 'Abono registrado correctamente.');
    }

    /**
     * Exportar recepciones a CSV.
     */
    public function export(Request $request)
    {
        $query = RecepcionInsumo::with('proveedor');

        foreach (['proveedor_id', 'tipo_pago', 'date_from', 'date_to'] as $f) {
            if ($request->filled($f)) {
                if (in_array($f, ['date_from', 'date_to'])) {
                    $op  = $f === 'date_from' ? '>=' : '<=';
                    $col = 'fecha_factura';
                    $query->whereDate($col, $op, $request->$f);
                } else {
                    $query->where($f, $request->$f);
                }
            }
        }

        $all = $query->latest('fecha_factura')->get();

        $filename = 'recepciones_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        $cols = ['Factura', 'Proveedor', 'Fecha', 'Tipo', 'Total', 'Abonado', 'Saldo'];

        $callback = function () use ($all, $cols) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $cols);
            foreach ($all as $r) {
                fputcsv($f, [
                    $r->numero_factura,
                    $r->proveedor->nombre ?? '-',
                    $r->fecha_factura->format('Y-m-d'),
                    strtoupper($r->tipo_pago),
                    $r->total_factura,
                    $r->abonado,
                    $r->total_factura - $r->abonado,
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generar y descargar PDF de una recepción.
     */
    public function pdf(RecepcionInsumo $recepcion)
    {
        $recepcion->load('items.insumo', 'abonos', 'proveedor');

        $pdf = PDF::loadView('recepcion.pdf', ['r' => $recepcion])
            ->setPaper('a4', 'portrait');

        return $pdf->download("recepcion_{$recepcion->numero_factura}.pdf");
    }
}
