<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    protected $fillable = [
        'nombre',
        'unidad',
        'precio_compra',
        'precio_venta',
        'stock',
        'stock_minimo',
    ];

    public function getNeedsReorderAttribute()
    {
        return $this->stock <= $this->stock_minimo;
    }
}