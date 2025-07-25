<?php
// app/Models/CreditoAbono.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Credito;

class CreditoAbono extends Model
{
    /**
     * Tabla asociada
     */
    protected $table = 'credito_abonos';

    /**
     * Campos asignables
     */
    protected $fillable = [
        'credito_id',
        'monto',
        'fecha',
        'comentario',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Relación inversa al crédito
     */
    public function credito()
    {
        return $this->belongsTo(Credito::class);
    }
}
