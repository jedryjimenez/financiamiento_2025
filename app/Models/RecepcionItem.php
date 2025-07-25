<?php
// app/Models/RecepcionItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecepcionItem extends Model
{
    protected $fillable = [
        'recepcion_id',
        'insumo_id',
        'cantidad',
        'precio_compra',
        'precio_venta',
        'subtotal',
    ];

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }
}
