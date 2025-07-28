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
use App\Models\KardexMovimiento;



class RecepcionProductoController extends Controller
{

    public function index(Request $request)
    {
        // 1) Consulta principal con filtros
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

        // 2) Calcular saldo actual de cada productor
        $productores = Productor::with('creditos.detalles', 'creditos.abonos')->get();
        foreach ($productores as $p) {
            $p->saldo_actual = $p->creditos->sum(
                fn($c) => CreditoHelper::saldoCreditoPorDias($c)
            );
        }

        // 3) Proveedores (si los usas en la vista)
        $proveedores = Proveedor::all();

        // 4) Sólo esos 4 insumos para el modal
        $insumos = Insumo::whereIn('nombre', ['Maíz', 'Frijoles', 'Café', 'Cacao'])
            ->pluck('nombre', 'id');

        // dd($insumos->all());

        // 5) Envío todo a la vista
        return view('recepciones.index', compact(
            'recepciones',
            'productores',
            'proveedores',
            'insumos'
        ));
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

        // 3) Trae sólo los 4 insumos que quieres permitir
        $insumos = Insumo::whereIn('nombre', ['Maíz', 'Frijoles', 'Café', 'Cacao'])
            ->pluck('nombre', 'id');

        // 4) Pásalos a la vista
        return view('recepciones.create', compact('productores', 'insumos'));
    }


    public function store(Request $request)
    {
        // 1) Validar formulario
        $data = $request->validate([
            'productor_id'    => 'required|exists:productors,id',
            'insumo_id'       => 'required|exists:insumos,id',
            'cantidad_bruta'  => 'required|numeric|min:0.01',
            'humedad'         => 'required|numeric|min:0|max:100', // [cite: 53]
            'precio_unitario' => 'required|numeric|min:0.01',
            'comentario'      => 'nullable|string|max:1000',
        ]);

        // 2) Calcular neto y total
        $cantidadNeta = round($data['cantidad_bruta'] * (1 - $data['humedad'] / 100), 2); // [cite: 54]
        $totalValor   = round($cantidadNeta * $data['precio_unitario'], 2); // [cite: 55]

        // 3) Cargar relaciones
        $productor = Productor::with('creditos.detalles', 'creditos.abonos')
            ->findOrFail($data['productor_id']); // [cite: 56]
        $insumo    = Insumo::findOrFail($data['insumo_id']);

        // Variables para distribuir crédito
        $valorRestante = $totalValor;
        $abonadoTotal  = 0;

        $recepcion = null; // Definir la variable aquí para que esté disponible fuera de la transacción

        DB::transaction(function () use (
            $data,
            $productor,
            $insumo,
            $cantidadNeta,
            $totalValor,
            &$valorRestante,
            &$abonadoTotal,
            &$recepcion // Pasar por referencia para asignarla dentro
        ) {
            // 4) Repartir abonos en créditos vigentes (más antiguos primero)
            $creditos = $productor->creditos
                ->filter(fn($c) => CreditoHelper::saldoCreditoPorDias($c) > 0)
                ->sortBy('fecha_entrega'); // [cite: 58]

            foreach ($creditos as $c) {
                if ($valorRestante <= 0) break;
                $saldo    = CreditoHelper::saldoCreditoPorDias($c);
                $abono    = min($saldo, $valorRestante); // [cite: 60]
                $kilos    = round($abono / $data['precio_unitario'], 2); // [cite: 61]

                if ($abono > 0) {
                    $c->abonos()->create([
                        'monto'             => $abono,
                        'fecha'             => now()->toDateString(), // [cite: 62]
                        'comentario'        => 'Pago con producto',
                        'tipo'              => 'producto',
                        'producto_nombre'   => $insumo->nombre, // [cite: 63]
                        'producto_cantidad' => $kilos,
                    ]);
                    $c->increment('abonado', $abono); // [cite: 64]
                    CreditoHelper::actualizarEstado($c->fresh());

                    $valorRestante -= $abono;
                    $abonadoTotal  += $abono; // [cite: 64]
                }
            }

            // 5) Crear la recepción
            $recepcion = RecepcionProducto::create([
                'productor_id'    => $productor->id,
                'insumo_id'       => $insumo->id,
                'producto'        => $insumo->nombre, // [cite: 66]
                'cantidad_bruta'  => $data['cantidad_bruta'],
                'humedad'         => $data['humedad'],
                'precio_unitario' => $data['precio_unitario'],
                'cantidad_neta'   => $cantidadNeta,
                'total_valor'     => $totalValor, // [cite: 67]
                'abonado_credito' => $abonadoTotal,
                'efectivo_pagado' => $valorRestante,
                'comentario'      => $data['comentario'] ?? null,
            ]);

            // 6) Actualizar stock
            $insumo->increment('stock', $cantidadNeta); // [cite: 68]

            // 7) Registrar entrada en Kardex
            KardexMovimiento::create([
                'insumo_id'       => $insumo->id,
                'tipo'            => 'Entrada',
                'cantidad'        => $cantidadNeta, // Variable que ya tienes
                'precio_unitario' => $data['precio_unitario'], // Variable que ya tienes
                'total'           => $totalValor, // Variable que ya tienes
                'referencia'      => "Recepción Prod #{$recepcion->id}",
                'observaciones'   => 'Recepción de producto del productor ' . $productor->nombre,
                'fecha'           => now(), // [cite: 71]
            ]);

            // 8) Si hay excedente, registrar egreso en caja
            if ($valorRestante > 0) {
                $caja = Caja::whereNull('cierre_at')->firstOrFail(); // [cite: 72]
                $mov  = $caja->movimientos()->create([
                    'fecha'      => now()->toDateString(),
                    'tipo'       => 'egreso',
                    'concepto'   => "Excedente recept. prod #{$recepcion->id}",
                    'monto'      => $valorRestante, // [cite: 74]
                    'automatico' => true,
                ]);
                session(['mov_caja' => $mov->id]); // [cite: 75]
            }

            // 9) Recalcular saldo en todos los créditos del productor
            // Asegúrate que esta función NO cree entradas en el Kardex.
            CreditoHelper::recalcularSaldoProductor($productor->fresh()); // [cite: 76]
        });

        // 10) Redirigir al PDF de recibo
        return redirect()->route('recepciones.recibo', $recepcion->id); // [cite: 77]
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
