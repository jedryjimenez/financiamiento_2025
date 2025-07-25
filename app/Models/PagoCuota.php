<?php
// app/Models/PagoCuota.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PagoCuota extends Model
{
    protected $table = 'pagos_cuotas';
    protected $fillable = ['cuota_id', 'monto_pagado', 'fecha_pago'];

    public function cuota()
    {
        return $this->belongsTo(Cuota::class);
    }
}
