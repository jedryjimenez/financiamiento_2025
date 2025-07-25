<?php
// app/Console/Commands/RecalcularEstadosCreditos.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Credito;
use App\Support\CreditoHelper;

class RecalcularEstadosCreditos extends Command
{
    protected $signature = 'creditos:recalcular-estados';
    protected $description = 'Recalcula estado y liquidado_at de todos los créditos';

    public function handle()
    {
        Credito::with('detalles', 'abonos')
            ->chunk(100, fn($rows) => $rows->each(fn($c) => CreditoHelper::actualizarEstado($c)));
        $this->info('Estados de créditos recalculados correctamente.');
    }
}
