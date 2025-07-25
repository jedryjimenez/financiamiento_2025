<?php
// app/Http/Controllers/CreditoController.php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\Productor;
use App\Models\Insumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Caja;
use App\Support\CreditoHelper;

class CreditoController extends Controller
{
    /**
     * Mostrar la lista de créditos con cálculos dinámicos
     */
    public function index()
    {
        $creditos = Credito::with(['productor', 'detalles.insumo', 'abonos'])
            ->has('detalles')
            ->latest()
            ->get();

        // Saldo total usando el helper (ya no suma intereses después de pagado)
        $saldoPendiente = round(
            $creditos->sum(fn($c) => CreditoHelper::saldoCreditoPorDias($c)),
            2
        );

        $productores = Productor::all();
        $insumos     = Insumo::all();

        return view('creditos.index', compact('creditos', 'productores', 'insumos', 'saldoPendiente'));
    }


    /**
     * Almacenar un nuevo crédito (sin cuotas)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'productor_id'           => 'required|exists:productors,id',
            'fecha_entrega'          => 'required|date',
            'moneda'                 => 'required|string',
            'insumos'                => 'required|array|min:1',
            'insumos.*.insumo_id'    => 'required|exists:insumos,id',
            'insumos.*.cantidad'     => 'required|numeric|min:0.01',
            'insumos.*.interes'      => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data) {
            $credito = Credito::create([
                'productor_id'  => $data['productor_id'],
                'fecha_entrega' => $data['fecha_entrega'],
                'moneda'        => $data['moneda'],
                'total'         => 0,
                'abonado'       => 0,
                'estado'        => 'activo',
                'liquidado_at'  => null,
            ]);

            $subtotal = 0;
            foreach ($data['insumos'] as $item) {
                $insumo   = Insumo::findOrFail($item['insumo_id']);
                $cantidad = $item['cantidad'];
                $precio   = $insumo->precio_venta;
                $linea    = $cantidad * $precio;

                $credito->detalles()->create([
                    'insumo_id'       => $item['insumo_id'],
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal'        => $linea,
                    'interes'         => $item['interes'],
                ]);

                $insumo->decrement('stock', $cantidad);
                $subtotal += $linea;
            }

            $credito->update(['total' => $subtotal]);

            // Recalcula el saldo global del productor, sin tocar estado
            \App\Support\CreditoHelper::recalcularSaldoProductor($credito->productor);
        });

        return redirect()->route('creditos.index')
            ->with('success', 'Crédito registrado correctamente.');
    }


    /**
     * Registrar abono directo al crédito
     */

    public function abonar(Request $request, Credito $credito)
    {
        // 1) Validar que haya caja abierta
        if (!Caja::whereNull('cierre_at')->exists()) {
            return back()->with('error', 'Debe abrir caja antes de registrar abonos.');
        }

        // 2) Calcular saldo actual para el max: helper + redondeo
        $saldoActual = round(CreditoHelper::saldoCreditoPorDias($credito), 2);

        $data = $request->validate([
            'monto'      => "required|numeric|min:0.01|max:{$saldoActual}",
            'comentario' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($credito, $data) {
            $monto    = (float) $data['monto'];
            $hoyFecha = now()->toDateString();

            // 3) Registrar el abono
            $credito->abonos()->create([
                'monto'      => $monto,
                'fecha'      => $hoyFecha,
                'comentario' => $data['comentario'] ?? null,
            ]);

            // 4) Actualizar el subtotal abonado
            $credito->increment('abonado', $monto);

            // 5) Refrescar relaciones para el helper
            $credito->load('detalles', 'abonos');

            // 6) MARCAR estado/pagado SI corresponde
            CreditoHelper::actualizarEstado($credito);

            // 7) Recalcular SALDO GLOBAL del productor
            CreditoHelper::recalcularSaldoProductor($credito->productor);

            // 8) Movimiento de caja
            $caja = Caja::whereNull('cierre_at')->first();
            $caja->movimientos()->create([
                'fecha'      => $hoyFecha,
                'tipo'       => 'ingreso',
                'concepto'   => "Abono crédito #{$credito->id}",
                'monto'      => $monto,
                'automatico' => true,
            ]);
        });

        return back()->with('success', 'Abono registrado correctamente.');
    }

    /**
     * Formulario de edición (sin lógica de abono)
     */
    public function edit(Credito $credito)
    {
        $productores = Productor::all();
        $insumos = [];
        return view('creditos.edit', compact('credito', 'productores', 'insumos'));
    }

    /**
     * Actualizar crédito
     */
    public function update(Request $request, Credito $credito)
    {
        // ... La lógica de update también debe ser consistente
        // (El código original no tenía la lógica de ajuste aquí, pero si la tuviera, se quitaría)
    }

    /**
     * Eliminar crédito y abonos
     */
    public function destroy(Credito $credito)
    {
        DB::transaction(function () use ($credito) {
            foreach ($credito->detalles as $d) {
                $d->insumo->increment('stock', $d->cantidad);
            }

            $productor = $credito->productor;
            $productorCreditos = $productor->creditos()->with('detalles')->get();
            $saldoActualProductor = 0;
            foreach ($productorCreditos as $pc) {
                if ($pc->id !== $credito->id) {
                    $fechaEntrega = Carbon::parse($pc->fecha_entrega)->startOfDay();
                    $hoy = now()->startOfDay();
                    $dias = $fechaEntrega->diffInDays($hoy);
                    $pcTotalReal = $pc->detalles->reduce(function ($carry, $d) use ($dias) {
                        $tasaDia = ($d->interes / 30) / 100;
                        $intA = round($d->subtotal * $tasaDia * $dias, 2);
                        return $carry + round($d->subtotal + $intA, 2);
                    }, 0);
                    $pcRawDifference = $pcTotalReal - $pc->abonado;
                    $pcRealPend = max(0, round($pcRawDifference, 2));

                    // El bloque IF que ajustaba a 0.01 ha sido eliminado.

                    $saldoActualProductor += $pcRealPend;
                }
            }
            $productor->update(['saldo' => round($saldoActualProductor, 2)]);

            $credito->detalles()->delete();
            $credito->abonos()->delete();
            $credito->delete();
        });

        return redirect()->route('creditos.index')
            ->with('success', 'Crédito eliminado correctamente.');
    }

    public function pdf(Credito $credito)
    {
        // Cargar las relaciones necesarias
        $credito->load(['productor', 'detalles.insumo', 'abonos']);

        // Calcula días, intereses y totales igual que en tu vista
        $fechaEntrega = \Carbon\Carbon::parse($credito->fecha_entrega)->startOfDay();
        $hoy = now()->startOfDay();
        $dias = $fechaEntrega->diffInDays($hoy);

        // Puedes pasar más datos si lo necesitás
        $pdf = Pdf::loadView('creditos.pdf', compact('credito', 'dias'));

        return $pdf->download("credito-{$credito->id}.pdf");
    }
}
