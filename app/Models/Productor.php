<?php

namespace App\Models;

use App\Support\CreditoHelper;
use Illuminate\Database\Eloquent\Model;

class Productor extends Model
{
    protected $fillable = [
        'nombre',
        'cedula',
        'telefono',
        'direccion',
        'saldo',
    ];
    public function creditos()
    {
        return $this->hasMany(Credito::class);
    }

    // (Opcional) Accesor para saldo pendiente calculado on the fly
    public function getSaldoPendienteAttribute()
    {
        return $this->creditos->sum(fn($c) => CreditoHelper::saldoCreditoPorDias($c));
    }

    public function creditosActivos()
    {
        return $this->hasMany(Credito::class)->where('estado', 'activo');
    }
}
