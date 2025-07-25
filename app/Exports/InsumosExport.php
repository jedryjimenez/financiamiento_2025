<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InsumosExport implements FromQuery, WithHeadings, WithMapping
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Unidad',
            'Precio Compra',
            'Precio Venta',
            'Stock',
            'Stock Mínimo',
            'Creado',
            'Actualizado',
        ];
    }

    public function map($i): array
    {
        return [
            $i->id,
            $i->nombre,
            $i->unidad,
            $i->precio_compra,
            $i->precio_venta,
            $i->stock,
            $i->stock_minimo,
            $i->created_at,
            $i->updated_at,
        ];
    }
}
