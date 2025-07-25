<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $fillable = [
        'user_id',
        'apertura_at',
        'monto_inicial',
        'cierre_at',
        'monto_final_sistema',
        'monto_final_real',
        'diferencia',
        'observacion'
    ];

    protected $casts = [
        'apertura_at' => 'datetime',
        'cierre_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class);
    }

    public function getEstaAbiertaAttribute(): bool
    {
        return is_null($this->cierre_at);
    }
}
