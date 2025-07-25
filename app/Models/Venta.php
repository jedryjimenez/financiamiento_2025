<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'productor_id',
        'fecha',
        'tipo_comprobante',
        'serie',
        'numero',
        'subtotal',
        'descuento_total',
        'total',
        'paga_con',
        'cambio',
        'anulada'
    ];

    protected $casts = [
        'fecha' => 'date',
        'anulada' => 'boolean',
    ];

    public function productor()
    {
        return $this->belongsTo(Productor::class);
    }
    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }
}
