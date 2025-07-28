<?php

namespace App\Observers;

use App\Models\RecepcionItem;
use App\Models\KardexMovimiento;

class RecepcionItemObserver
{
    /**
     * Handle the RecepcionItem "created" event.
     */
    public function created(RecepcionItem $item): void
    {
        // Crea un movimiento de Entrada por cada ítem registrado
        KardexMovimiento::create([
            'insumo_id'       => $item->insumo_id,
            'tipo'            => 'Entrada',
            'cantidad'        => $item->cantidad,
            'precio_unitario' => $item->precio_unitario,
            'total'           => $item->cantidad * $item->precio_unitario,
            'referencia'      => 'Recepción #' . $item->recepcion_id,
            'observaciones'   => 'Factura ' . $item->recepcion->numero_factura,
            'fecha'           => $item->created_at,
        ]);
    }
}
