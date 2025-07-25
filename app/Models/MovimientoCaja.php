<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    protected $table = 'movimientos_caja';

    protected $fillable = [
        'caja_id',
        'recepcion_id',
        'credito_id',
        'tipo',
        'monto',
        'fecha',
        'concepto',
        'automatico'
    ];

    protected $casts = [
        'fecha' => 'date',
        'automatico' => 'boolean',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }
    public function recepcion()
    {
        return $this->belongsTo(RecepcionInsumo::class, 'recepcion_id');
    }
    public function credito()
    {
        return $this->belongsTo(Credito::class, 'credito_id');
    }
}
