<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class EstadoCuentaGeneralExport implements FromView
{
    public $productores;
    public $desde;
    public $hasta;

    public function __construct($productores, $desde, $hasta)
    {
        $this->productores = $productores;
        $this->desde = $desde;
        $this->hasta = $hasta;
    }

    public function view(): View
    {
        return view('reportes.estado_cuenta_general_excel', [
            'productores' => $this->productores,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
        ]);
    }
}
