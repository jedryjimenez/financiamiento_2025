<?php

namespace App\Http\Controllers;

use App\Models\RecepcionProducto;
use App\Models\Productor;
use App\Models\Caja;
use App\Models\Insumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RecepcionesExport;
use Barryvdh\DomPDF\Facade\PDF;
use App\Models\Proveedor;
use App\Models\RecepcionInsumo;
use App\Support\CreditoHelper;


class RecepcionProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = RecepcionProducto::with('productor');

        if ($request->filled('productor_id')) {
            $query->where('productor_id', $request->productor_id);
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }

        $recepciones = $query->latest()->paginate(20);

        // Cargar y calcular saldo
        $productores = Productor::with('creditos.detalles', 'creditos.abonos')->get();
        foreach ($productores as $p) {
            $p->saldo_actual = $p->creditos->sum(
                fn($c) => CreditoHelper::saldoCreditoPorDias($c)
            );
        }

        $proveedores = Proveedor::all();

        //dd($productores->map(fn($p) => ['nombre' => $p->nombre, 'saldo' => $p->saldo_actual]));

        return view('recepciones.index', compact('recepciones', 'productores', 'proveedores'));
    }

    public function create()
    {
        // 1) Trae productores con sus créditos, detalles y abonos
        $productores = Productor::with('creditos.detalles', 'creditos.abonos')->get();

        // 2) Para cada productor, calcula el saldo exactamente igual que en ProductoresController
        foreach ($productores as $p) {
            $p->saldo = $p->creditos->sum(
                fn($c) => CreditoHelper::saldoCreditoPorDias($c)
            );
        }

        // 3) Pasa esa colección a la vista
        return view('recepciones.create', compact('productores'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'productor_id'    => 'required|exists:productors,id',
            'producto'        => 'required|string|max:255',
            'cantidad_bruta'  => 'required|numeric|min:0.01',
            'humedad'         => 'required|numeric|min:0|max:100',
            'precio_unitario' => 'required|numeric|min:0.01',
            'comentario'      => 'nullable|string|max:1000',
        ]);

        // ===== Cálculos iniciales =====
        $cantidad_neta = round($data['cantidad_bruta'] * (1 - ($data['humedad'] / 100)), 2);
        $total_valor   = round($cantidad_neta * $data['precio_unitario'], 2);

        // Cargamos productor con créditos y abonos
        $productor = Productor::with('creditos.detalles', 'creditos.abonos')
            ->findOrFail($data['productor_id']);

        // Preparamos variables para repartir el valor
        $valorRestante = $total_valor;
        $abonadoTotal  = 0.0;

        DB::beginTransaction();
        try {
            // ===== 1) Repartir abono entre TODOS los créditos activos, del más antiguo al más reciente =====
            $creditos = $productor->creditos
                ->filter(fn($c) => CreditoHelper::saldoCreditoPorDias($c) > 0)
                ->sortBy('fecha_entrega'); // orden ascendente

            foreach ($creditos as $credito) {
                if ($valorRestante <= 0) {
                    break;
                }

                $saldoCredito  = CreditoHelper::saldoCreditoPorDias($credito);
                $montoAbono    = min($saldoCredito, $valorRestante);
                $kilosAbonados = round($montoAbono / $data['precio_unitario'], 2);

                if ($montoAbono > 0) {
                    // 1.1) Crear abono en cada crédito
                    $credito->abonos()->create([
                        'monto'             => $montoAbono,
                        'fecha'             => now()->toDateString(),
                        'comentario'        => 'Pago con producto: ' . $data['producto'],
                        'tipo'              => 'producto',
                        'producto_nombre'   => $data['producto'],
                        'producto_cantidad' => $kilosAbonados,
                    ]);

                    // 1.2) Incrementar campo 'abonado'
                    $credito->increment('abonado', $montoAbono);

                    // 1.3) Refrescar modelo y relaciones
                    $credito->refresh();
                    $credito->load('detalles', 'abonos');

                    // 1.4) Marcar estado/pagado si toca
                    \App\Support\CreditoHelper::actualizarEstado($credito);

                    $valorRestante -= $montoAbono;
                    $abonadoTotal  += $montoAbono;
                }
            }

            // ===== 2) Registrar la recepción con totales distribuidos =====
            $recepcion = RecepcionProducto::create([
                'productor_id'    => $data['productor_id'],
                'producto'        => $data['producto'],
                'cantidad_bruta'  => $data['cantidad_bruta'],
                'humedad'         => $data['humedad'],
                'precio_unitario' => $data['precio_unitario'],
                'cantidad_neta'   => $cantidad_neta,
                'total_valor'     => $total_valor,
                'abonado_credito' => $abonadoTotal,
                'efectivo_pagado' => $valorRestante, // excedente en efectivo
                'comentario'      => $data['comentario'] ?? null,
            ]);

            // ===== 3) Inventario =====
            $insumo = Insumo::firstOrCreate(
                ['nombre' => $data['producto']],
                ['unidad' => 'LBRS', 'precio_compra' => 0, 'precio_venta' => 0, 'stock_minimo' => 0]
            );
            $insumo->increment('stock', $cantidad_neta);

            // ===== 4) Movimiento de caja si hay excedente =====
            if ($valorRestante > 0) {
                $caja = Caja::whereNull('cierre_at')->firstOrFail();
                $mov  = $caja->movimientos()->create([
                    'fecha'      => now()->toDateString(),
                    'tipo'       => 'egreso',
                    'concepto'   => 'Pago excedente recepción producto #' . $recepcion->id,
                    'monto'      => $valorRestante,
                    'automatico' => true,
                ]);
                session(['mov_caja' => $mov->id]);
            }

            // ===== 5) Recalcular saldo del productor =====
            \App\Support\CreditoHelper::recalcularSaldoProductor($productor->fresh());

            DB::commit();

            return redirect()
                ->route('recepciones.recibo', $recepcion->id);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->with('error', 'Error al registrar la recepción: ' . $e->getMessage())
                ->withInput();
        }
    }


    public function exportExcel(Request $request)
    {
        $query = RecepcionProducto::with('productor');
        if ($request->filled('productor_id')) $query->where('productor_id', $request->productor_id);
        if ($request->filled('fecha_inicio')) $query->whereDate('created_at', '>=', $request->fecha_inicio);
        if ($request->filled('fecha_fin')) $query->whereDate('created_at', '<=', $request->fecha_fin);

        $data = $query->get();
        return Excel::download(new RecepcionesExport($data), 'recepciones.xlsx');
    }

    public function exportPDF(Request $request)
    {
        $query = RecepcionProducto::with('productor');
        if ($request->filled('productor_id')) $query->where('productor_id', $request->productor_id);
        if ($request->filled('fecha_inicio')) $query->whereDate('created_at', '>=', $request->fecha_inicio);
        if ($request->filled('fecha_fin')) $query->whereDate('created_at', '<=', $request->fecha_fin);

        $data = $query->get();
        $pdf  = Pdf::loadView('recepcion.export', ['recepciones' => $data]);
        return $pdf->download('recepciones.pdf');
    }

    public function recibo(RecepcionProducto $recepcion)
    {
        // Si usas el movimiento:
        $movId     = session('mov_caja');
        $movimiento = $movId
            ? \App\Models\MovimientoCaja::find($movId)
            : null;

        // Genera el PDF con la vista que ya tienes para impresión
        $pdf = PDF::loadView(
            'recepciones.recibo',      // tu misma blade, que genera un voucher
            compact('recepcion', 'movimiento')
        );

        // Envía el PDF inline al navegador (no fuerza descarga)
        return $pdf->stream("recibo_{$recepcion->id}.pdf");
    }


    public function reciboPdf(RecepcionProducto $recepcion)
    {
        $pdf = PDF::loadView('recepciones.recibo_pdf', compact('recepcion'));
        return $pdf->download("recibo_excedente_{$recepcion->id}.pdf");
    }

    public function reciboPrint(RecepcionInsumo $recepcion)
    {
        // Cargar relaciones que necesites
        $recepcion->load('productor');

        // Usamos la vista que creaste: recibo_plain
        return view('recepciones.recibo', compact('recepcion'));
    }
}
