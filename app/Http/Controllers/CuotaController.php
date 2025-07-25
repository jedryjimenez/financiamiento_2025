<?php
// app/Http/Controllers/CuotaController.php

namespace App\Http\Controllers;

use App\Models\Cuota;
use App\Models\MovimientoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CuotaController extends Controller
{
    /**
     * Registrar pago de una cuota, historial y flujo de caja
     */
    public function pagar(Request $request, Cuota $cuota)
    {
        // 1. Validar monto ingresado
        $data = $request->validate([
            'monto_pagado' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($cuota, $data) {
            $monto = $data['monto_pagado'];
            $hoy = now()->toDateString();

            // 2. Crear registro de pago en historial de la cuota
            $cuota->pagos()->create([
                'monto_pagado' => $monto,
                'fecha_pago' => $hoy,
            ]);

            // 3. Registrar ingreso en flujo de caja
            MovimientoCaja::create([
                'recepcion_id' => null,
                'tipo' => 'ingreso',
                'monto' => $monto,
                'fecha' => $hoy,
                'concepto' => "Pago cuota #{$cuota->numero} credito #{$cuota->credito_id}",
            ]);

            // 4. Actualizar saldo del productor
            $productor = $cuota->credito->productor;
            $productor->decrement('saldo', $monto);

            // 5. Aplicar posible sobrepago a cuotas siguientes
            $resto = $monto;
            $pendientes = $cuota->credito->cuotas()
                ->where('estado', 'pendiente')
                ->orderBy('numero')
                ->get();

            foreach ($pendientes as $c) {
                // Monto ya abonado a esta cuota
                $pagosAbonos = $c->pagos->sum('monto_pagado');
                $deudaCuota = $c->monto - $pagosAbonos;
                if ($deudaCuota <= 0) {
                    continue;
                }

                if ($resto >= $deudaCuota) {
                    // Cubrir deuda de esta cuota
                    $c->update(['estado' => 'pagada']);
                    $resto -= $deudaCuota;
                } else {
                    // Pago parcial, no la marca como pagada
                    break;
                }
            }
        });

        return back()->with('success', 'Pago de cuota registrado correctamente.');
    }
}
