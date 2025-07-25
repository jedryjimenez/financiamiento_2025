<?php

namespace App\Observers;

use App\Models\Credito;
use App\Support\CreditoHelper;

class CreditoObserver
{
    public function creating(Credito $credito): void
    {
        if (is_null($credito->estado)) {
            $credito->estado = 'activo';
        }
    }

    public function saved(Credito $credito): void
    {
        // Cargar relaciones mínimas
        $credito->loadMissing('detalles', 'abonos', 'productor.creditos.detalles', 'productor.creditos.abonos');

        /** 
         * 1) Si todavía no hay DETALLES, no intentes calcular nada.
         *    (Durante el create, primero se guarda el crédito y luego los detalles)
         */
        if ($credito->detalles->isEmpty()) {
            return;
        }

        // 2) Calcular saldo real con el helper
        $saldo = CreditoHelper::saldoCreditoPorDias($credito);

        /**
         * 3) Marcar pagado solo si:
         *    - saldo <= 0
         *    - hubo al menos un abono o el total era > 0
         *    - y el estado actual no es ya 'pagado'
         */
        if ($saldo <= 0 && $credito->estado !== 'pagado' && $credito->abonado > 0) {
            $credito->forceFill([
                'estado'       => 'pagado',
                'liquidado_at' => now(),
            ])->saveQuietly(); // evita loop
        }

        /**
         * 4) (Opcional) Si por alguna razón vuelve a quedar deuda y estaba en pagado,
         *    lo regresamos a activo.
         */
        if ($saldo > 0 && $credito->estado === 'pagado') {
            $credito->forceFill([
                'estado'       => 'activo',
                'liquidado_at' => null,
            ])->saveQuietly();
        }

        // 5) Recalcular saldo global del productor
        CreditoHelper::recalcularSaldoProductor($credito->productor);
    }

    public function deleted(Credito $credito): void
    {
        $credito->load('productor');
        CreditoHelper::recalcularSaldoProductor($credito->productor);
    }
}
