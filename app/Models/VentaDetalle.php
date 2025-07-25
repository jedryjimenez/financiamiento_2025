<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaDetalle extends Model
{
    protected $fillable = [
        'venta_id',
        'insumo_id',
        'cantidad',
        'precio_unitario',
        'descuento',
        'subtotal'
    ];

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
}
