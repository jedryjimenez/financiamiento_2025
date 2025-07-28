<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecepcionProducto extends Model
{

    protected $fillable = [
        'productor_id',
        'insumo_id',
        'producto',
        'cantidad_bruta',
        'humedad',
        'precio_unitario',
        'cantidad_neta',
        'total_valor',
        'abonado_credito',
        'efectivo_pagado',
        'comentario',
    ];

    protected $casts = [
        'cantidad_bruta'   => 'decimal:2',
        'humedad'          => 'decimal:2',
        'cantidad_neta'    => 'decimal:2',
        'precio_unitario'  => 'decimal:2',
        'total_valor'      => 'decimal:2',
        'abonado_credito'  => 'decimal:2',
        'efectivo_pagado'  => 'decimal:2',
    ];

    public function productor()
    {
        return $this->belongsTo(Productor::class);
    }
}
