<?php
// app/Http/Controllers/ProductorController.php

namespace App\Http\Controllers;

use App\Models\Productor;
use App\Models\Credito;
use App\Support\CreditoHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductorController extends Controller
{
    /**
     * Mostrar lista de productores con su saldo real
     * (capital + interés acumulado – abonos) por cada crédito.
     */
    public function index()
    {
        $productores = Productor::all()->map(function ($p) {
            $saldoAcumulado = 0.00;
            $hoy = Carbon::today();

            // Traer todos los créditos del productor, con detalles y abonos
            $creditos = Credito::with(['detalles', 'abonos'])
                ->where('productor_id', $p->id)
                ->get();

            foreach ($creditos as $c) {
                // 1) Inicializar balances por cada insumo (detalle)
                $balances = [];
                foreach ($c->detalles as $d) {
                    $balances[$d->id] = [
                        'principal' => $d->subtotal,
                        'rate'      => ($d->interes / 30) / 100,
                        'interest'  => 0.00,
                    ];
                }

                // 2) Ordenar abonos cronológicamente
                $events = $c->abonos
                    ->map(fn($a) => [
                        'date'   => Carbon::parse($a->fecha)->startOfDay(),
                        'amount' => $a->monto,
                    ])
                    ->sortBy('date')
                    ->values()
                    ->all();

                // 3) Simular desde la fecha de entrega
                $lastDate = Carbon::parse($c->fecha_entrega)->startOfDay();
                foreach ($events as $e) {
                    $date    = $e['date'];
                    $payment = $e['amount'];
                    $days    = $lastDate->diffInDays($date);

                    // a) Acumular interés hasta la fecha de pago
                    if ($days > 0) {
                        foreach ($balances as &$b) {
                            $b['interest'] += round($b['principal'] * $b['rate'] * $days, 2);
                        }
                        unset($b);
                    }

                    // b) Aplicar pago: primero interés, luego capital
                    $totalInterest = array_sum(array_column($balances, 'interest'));
                    if ($payment <= $totalInterest) {
                        // cubre solo interés
                        foreach ($balances as &$b) {
                            $share        = $totalInterest ? $b['interest'] / $totalInterest : 0;
                            $b['interest'] = round($b['interest'] - $payment * $share, 2);
                        }
                        unset($b);
                    } else {
                        // cubre interés y parte de capital
                        $toPrincipal = $payment - $totalInterest;
                        // cero todos los intereses
                        foreach ($balances as &$b) {
                            $b['interest'] = 0.00;
                        }
                        unset($b);
                        // reducir principal proporcionalmente
                        $sumP = array_sum(array_column($balances, 'principal'));
                        foreach ($balances as &$b) {
                            $share         = $sumP ? $b['principal'] / $sumP : 0;
                            $b['principal'] = max(0, round($b['principal'] - $toPrincipal * $share, 2));
                        }
                        unset($b);
                    }

                    $lastDate = $date;
                }

                // 4) Interés desde último abono hasta hoy
                $days = $lastDate->diffInDays($hoy);
                if ($days > 0) {
                    foreach ($balances as &$b) {
                        $b['interest'] += round($b['principal'] * $b['rate'] * $days, 2);
                    }
                    unset($b);
                }

                // 5) Sumar principal + interés pendientes de este crédito
                $sumP = array_sum(array_column($balances, 'principal'));
                $sumI = array_sum(array_column($balances, 'interest'));
                $saldoAcumulado += round($sumP + $sumI, 2);
            }

            $p->saldoAcumulado = round($saldoAcumulado, 2);
            return $p;
        });

        return view('productores.index', compact('productores'));
    }

    public function create()
    {
        return view('productores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'    => 'required|string|max:255',
            'cedula'    => 'nullable|string|max:50|unique:productors,cedula',
            'telefono'  => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:500',
        ]);

        Productor::create([
            'nombre'    => $request->nombre,
            'cedula'    => $request->cedula,
            'telefono'  => $request->telefono,
            'direccion' => $request->direccion,
            'saldo'     => 0,
        ]);

        return redirect()
            ->route('productores.index')
            ->with('success', 'Productor registrado correctamente.');
    }

    public function edit(Productor $productore)
    {
        return view('productores.edit', compact('productore'));
    }

    public function update(Request $request, Productor $productore)
    {
        $request->validate([
            'nombre'    => 'required|string|max:255',
            'cedula'    => 'nullable|string|max:50|unique:productors,cedula,' . $productore->id,
            'telefono'  => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:500',
        ]);

        $productore->update($request->only('nombre', 'cedula', 'telefono', 'direccion'));

        return redirect()
            ->route('productores.index')
            ->with('success', 'Productor actualizado correctamente.');
    }

    public function destroy(Productor $productor)
    {
        // Recalcular deuda antes de eliminar
        $saldoAcumulado = 0.00;
        $hoy = Carbon::today();

        $creditos = $productor->creditos()->with(['detalles', 'abonos'])->get();
        foreach ($creditos as $c) {
            // misma simulación que en index...
            $balances = [];
            foreach ($c->detalles as $d) {
                $balances[$d->id] = [
                    'principal' => $d->subtotal,
                    'rate'      => ($d->interes / 30) / 100,
                    'interest'  => 0.00,
                ];
            }
            $events = $c->abonos
                ->map(fn($a) => [
                    'date'   => Carbon::parse($a->fecha)->startOfDay(),
                    'amount' => $a->monto,
                ])
                ->sortBy('date')
                ->values()
                ->all();
            $lastDate = Carbon::parse($c->fecha_entrega)->startOfDay();
            foreach ($events as $e) {
                $days = $lastDate->diffInDays($e['date']);
                if ($days > 0) {
                    foreach ($balances as &$b) {
                        $b['interest'] += round($b['principal'] * $b['rate'] * $days, 2);
                    }
                    unset($b);
                }
                $totalI  = array_sum(array_column($balances, 'interest'));
                $payment = $e['amount'];
                if ($payment <= $totalI) {
                    foreach ($balances as &$b) {
                        $share        = $totalI ? $b['interest'] / $totalI : 0;
                        $b['interest'] = round($b['interest'] - $payment * $share, 2);
                    }
                    unset($b);
                } else {
                    $toP = $payment - $totalI;
                    foreach ($balances as &$b) {
                        $b['interest'] = 0.00;
                    }
                    unset($b);
                    $sumP = array_sum(array_column($balances, 'principal'));
                    foreach ($balances as &$b) {
                        $share         = $sumP ? $b['principal'] / $sumP : 0;
                        $b['principal'] = max(0, round($b['principal'] - $toP * $share, 2));
                    }
                    unset($b);
                }
                $lastDate = $e['date'];
            }
            $days = $lastDate->diffInDays($hoy);
            if ($days > 0) {
                foreach ($balances as &$b) {
                    $b['interest'] += round($b['principal'] * $b['rate'] * $days, 2);
                }
                unset($b);
            }
            $sumP = array_sum(array_column($balances, 'principal'));
            $sumI = array_sum(array_column($balances, 'interest'));
            $saldoAcumulado += round($sumP + $sumI, 2);
        }

        if ($saldoAcumulado > 0) {
            return redirect()
                ->route('productores.index')
                ->with('error', 'No se puede eliminar: saldo pendiente C$ ' . number_format($saldoAcumulado, 2));
        }

        // borrar créditos pagados + productor
        DB::transaction(function () use ($creditos, $productor) {
            foreach ($creditos as $c) {
                $c->detalles()->delete();
                $c->abonos()->delete();
                $c->delete();
            }
            $productor->delete();
        });

        return redirect()
            ->route('productores.index')
            ->with('success', 'Productor y sus créditos (pagados) eliminados correctamente.');
    }

    public function saldo(Productor $productor)   // usa model binding
    {
        $productor->load('creditos.detalles', 'creditos.abonos');

        $saldo = $productor->creditos->sum(
            fn($c) => CreditoHelper::saldoCreditoPorDias($c)
        );

        return response()->json(['saldo' => $saldo]);
    }
}
