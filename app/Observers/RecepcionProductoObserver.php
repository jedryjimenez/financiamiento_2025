<?php

namespace App\Observers;

use App\Models\RecepcionProducto;
use App\Models\KardexMovimiento;

class RecepcionProductoObserver
{
    public function created(RecepcionProducto $rp): void {}
}
