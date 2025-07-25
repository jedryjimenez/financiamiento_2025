<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RecepcionAbono;

class RecepcionInsumo extends Model
{
    protected $fillable = [
        'numero_factura',
        'fecha_factura',
        'tipo_pago',
        'total_factura',
        'abonado',
        'proveedor_id',
        'comprobante',
    ];

    protected $casts = [
        'fecha_factura' => 'date',
    ];

    // RELACIONES

    // Items de la recepción
    public function items()
    {
        return $this->hasMany(RecepcionItem::class, 'recepcion_id');
    }

    // Historial de abonos
    public function abonos()
    {
        return $this->hasMany(RecepcionAbono::class, 'recepcion_id');
    }

    // Proveedor asociado
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // ACCESORES

    // Saldo pendiente
    public function getSaldoAttribute()
    {
        return $this->total_factura - $this->abonado;
    }
}
