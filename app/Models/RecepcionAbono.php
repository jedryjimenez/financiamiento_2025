<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecepcionAbono extends Model
{
    // Si tu tabla se llama 'recepcion_abonos', explícitalo:
    protected $table = 'recepcion_abonos';

    protected $fillable = [
        'recepcion_id',
        'fecha_abono',
        'comprobante',
        'monto',
    ];

    protected $casts = [
        'fecha_abono' => 'date',
    ];

    /**
     * Cada abono pertenece a una recepción.
     */
    public function recepcion()
    {
        return $this->belongsTo(RecepcionInsumo::class, 'recepcion_id');
    }
}
