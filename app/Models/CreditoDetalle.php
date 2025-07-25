<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditoDetalle extends Model
{
    protected $fillable = [
        'credito_id',
        'insumo_id',
        'cantidad',
        'precio_unitario',
        'interes',
        'subtotal',
        'total',
    ];

    public function credito()
    {
        return $this->belongsTo(Credito::class);
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }

    public function totalConInteresLinea()
    {
        $subtotal = $this->cantidad * $this->precio_unitario;
        $interes  = ($this->interes ?? 0) / 100;
        return $subtotal + ($subtotal * $interes);
    }
}
