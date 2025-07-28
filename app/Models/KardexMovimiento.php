<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KardexMovimiento extends Model
{

    protected $table = 'kardex_movimientos';

    protected $fillable = [
        'insumo_id',
        'tipo',
        'cantidad',
        'precio_unitario',
        'total',
        'referencia',
        'observaciones',
        'fecha',
    ];

    public $timestamps = true;

    protected $casts = [
        'cantidad'        => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'total'           => 'decimal:2',
        'fecha'           => 'datetime',
    ];


    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }
}
