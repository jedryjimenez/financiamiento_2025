<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Productor;
use App\Models\CreditoDetalle;
use App\Models\CreditoAbono;
use App\Support\CreditoHelper;

class Credito extends Model
{
    protected $fillable = [
        'productor_id',
        'fecha_entrega',
        'moneda',
        'total',
        'abonado',
        'estado',
        'liquidado_at',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'liquidado_at'  => 'datetime',
    ];

    public function productor()
    {
        return $this->belongsTo(Productor::class);
    }

    public function detalles()
    {
        return $this->hasMany(CreditoDetalle::class);
    }

    public function abonos()
    {
        return $this->hasMany(CreditoAbono::class);
    }

    // 💡 NUEVO MÉTODO
    public function totalConInteres()
    {
        return $this->detalles->sum(function ($d) {
            $subtotal = $d->subtotal; // cantidad * precio_unitario
            $interes  = ($d->interes ?? 0) / 100;
            return $subtotal + ($subtotal * $interes);
        });
    }

    public function scopeActivos($q)
    {
        return $q->where('estado', 'activo');
    }
    public function scopePagados($q)
    {
        return $q->where('estado', 'pagado');
    }

    // (Opcional) Estado calculado si no quieres usar DB:
    public function getEstadoCalculadoAttribute()
    {
        return CreditoHelper::saldoCreditoPorDias($this) <= 0 ? 'pagado' : 'activo';
    }
}
