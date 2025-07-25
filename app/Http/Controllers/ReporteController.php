<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Productor;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\EstadoCuentaGeneralExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Support\CreditoHelper;
use App\Models\Credito;

class ReporteController extends Controller
{

    public function estadoCuentaGeneral(Request $request)
    {
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $estado = $request->input('estado');
        $nombre = $request->input('nombre');

        try {
            $desde = $desde ? Carbon::createFromFormat('Y-m-d', $desde)->startOfDay() : Carbon::now()->startOfMonth();
            $hasta = $hasta ? Carbon::createFromFormat('Y-m-d', $hasta)->endOfDay() : Carbon::now()->endOfMonth();
        } catch (\Exception $e) {
            return back()->with('error', 'Formato de fecha inválido.')->withInput();
        }

        $query = Productor::with(['creditos.detalles', 'creditos.abonos']);

        if ($nombre) {
            $query->where('nombre', 'like', "%$nombre%");
        }

        $productores = $query->get()
            ->map(function ($p) use ($desde, $hasta, $estado, $request) {
                $hoy = Carbon::today();
                $creditos = $p->creditos->filter(function ($c) use ($desde, $hasta, $request) {
                    // Solo filtrar por fechas si el usuario especificó fechas en el formulario
                    if ($request->filled('desde') && $request->filled('hasta')) {
                        return $c->created_at >= $desde && $c->created_at <= $hasta;
                    }
                    return true; // incluir todos si no se filtró por fecha
                });

                if ($creditos->isEmpty()) return null;

                $saldo = 0.00;
                $total_creditos = 0.00;
                $total_efectivo = 0.00;
                $total_producto = 0.00;

                foreach ($creditos as $c) {
                    $balances = [];
                    foreach ($c->detalles as $d) {
                        $balances[$d->id] = [
                            'principal' => $d->subtotal,
                            'rate' => ($d->interes / 30) / 100,
                            'interest' => 0.00,
                        ];
                    }

                    $events = $c->abonos
                        ->map(fn($a) => [
                            'date' => Carbon::parse($a->fecha)->startOfDay(),
                            'amount' => $a->monto,
                            'tipo_pago' => $a->tipo_pago,
                        ])
                        ->sortBy('date')
                        ->values()
                        ->all();

                    $lastDate = Carbon::parse($c->fecha_entrega)->startOfDay();
                    foreach ($events as $e) {
                        $date = $e['date'];
                        $payment = $e['amount'];
                        $days = $lastDate->diffInDays($date);

                        if ($days > 0) {
                            foreach ($balances as &$b) {
                                $b['interest'] += round($b['principal'] * $b['rate'] * $days, 2);
                            }
                            unset($b);
                        }

                        $totalInterest = array_sum(array_column($balances, 'interest'));
                        if ($payment <= $totalInterest) {
                            foreach ($balances as &$b) {
                                $share = $totalInterest ? $b['interest'] / $totalInterest : 0;
                                $b['interest'] = round($b['interest'] - $payment * $share, 2);
                            }
                            unset($b);
                        } else {
                            $toPrincipal = $payment - $totalInterest;
                            foreach ($balances as &$b) {
                                $b['interest'] = 0.00;
                            }
                            unset($b);
                            $sumP = array_sum(array_column($balances, 'principal'));
                            foreach ($balances as &$b) {
                                $share = $sumP ? $b['principal'] / $sumP : 0;
                                $b['principal'] = max(0, round($b['principal'] - $toPrincipal * $share, 2));
                            }
                            unset($b);
                        }

                        $lastDate = $date;

                        // registrar tipo de abono
                        if ($e['tipo_pago'] == 'efectivo') {
                            $total_efectivo += $payment;
                        } elseif ($e['tipo_pago'] == 'producto') {
                            $total_producto += $payment;
                        }
                    }

                    // interés acumulado desde último abono hasta hoy
                    $days = $lastDate->diffInDays($hoy);
                    if ($days > 0) {
                        foreach ($balances as &$b) {
                            $b['interest'] += round($b['principal'] * $b['rate'] * $days, 2);
                        }
                        unset($b);
                    }

                    $sumP = array_sum(array_column($balances, 'principal'));
                    $sumI = array_sum(array_column($balances, 'interest'));
                    $saldo += round($sumP + $sumI, 2);

                    $total_creditos += $c->detalles->sum('subtotal');
                }

                $total_abonos = $total_efectivo + $total_producto;

                if ($estado === 'activo' && $saldo <= 0) return null;
                if ($estado === 'pagado' && $saldo > 0) return null;

                $p->creditos_activos = $creditos->count();
                $p->total_creditos = $total_creditos;
                $p->total_efectivo = $total_efectivo;
                $p->total_producto = $total_producto;
                $p->total_abonos = $total_abonos;
                $p->saldo = $saldo;

                $p->porcentaje_pagado = $total_creditos > 0
                    ? round(($total_abonos / $total_creditos) * 100, 2)
                    : 100;

                $p->ultimo_credito = optional($creditos->sortByDesc('created_at')->first())->created_at;
                $p->ultimo_abono = optional($creditos->flatMap->abonos->sortByDesc('fecha')->first())->fecha;

                return $p;
            })
            ->filter()
            ->values();

        return view('reportes.estado_cuenta_general', compact('productores', 'desde', 'hasta'));
    }



    public function estadoCuentaGeneralPDF(Request $request)
    {
        $productores = $this->estadoCuentaGeneral($request)->getData()['productores'];
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        $pdf = Pdf::loadView('reportes.estado_cuenta_general_pdf', compact('productores', 'desde', 'hasta'));
        return $pdf->download('estado_cuenta_general.pdf');
    }

    public function exportarEstadoCuentaGeneralExcel(Request $request)
    {
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');
        $estado = $request->input('estado');
        $nombre = $request->input('nombre');

        try {
            $desde = $desde ? Carbon::createFromFormat('Y-m-d', $desde)->startOfDay() : Carbon::now()->startOfMonth();
            $hasta = $hasta ? Carbon::createFromFormat('Y-m-d', $hasta)->endOfDay() : Carbon::now()->endOfMonth();
        } catch (\Exception $e) {
            return back()->with('error', 'Formato de fecha inválido.')->withInput();
        }

        $query = Productor::with(['creditos.detalles', 'creditos.abonos']);

        if ($nombre) {
            $query->where('nombre', 'like', "%$nombre%");
        }

        $productores = $query->get()
            ->map(function ($p) use ($desde, $hasta, $estado) {
                $creditos = $p->creditos;

                $total_creditos = 0;
                $total_abonos = 0;
                $total_efectivo = 0;
                $total_producto = 0;
                $creditos_activos = 0;
                $ultimo_credito = null;
                $ultimo_abono = null;

                foreach ($creditos as $credito) {
                    // Validar si está dentro del rango de fecha (créditos emitidos)
                    if ($credito->created_at < $desde || $credito->created_at > $hasta) continue;

                    $totalCredito = $credito->detalles->sum('subtotal') +
                        $credito->detalles->sum(fn($d) => $d->subtotal * $d->interes / 100);

                    $total_creditos += $totalCredito;
                    $abonos = $credito->abonos;

                    $efectivo = $abonos->where('tipo_pago', 'efectivo')->sum('monto');
                    $producto = $abonos->where('tipo_pago', 'producto')->sum('monto');
                    $total_abono = $efectivo + $producto;

                    $total_abonos += $total_abono;
                    $total_efectivo += $efectivo;
                    $total_producto += $producto;

                    if ($total_abono < $totalCredito) $creditos_activos++;

                    // Fechas
                    if (!$ultimo_credito || $credito->created_at > $ultimo_credito) {
                        $ultimo_credito = $credito->created_at;
                    }

                    $abonoFecha = $abonos->sortByDesc('fecha')->first()?->fecha;
                    if ($abonoFecha && (!$ultimo_abono || $abonoFecha > $ultimo_abono)) {
                        $ultimo_abono = $abonoFecha;
                    }
                }

                $saldo = $total_creditos - $total_abonos;

                if ($estado === 'activo' && $saldo <= 0) return null;
                if ($estado === 'pagado' && $saldo > 0) return null;

                $p->total_creditos = $total_creditos;
                $p->total_efectivo = $total_efectivo;
                $p->total_producto = $total_producto;
                $p->total_abonos = $total_abonos;
                $p->saldo = $saldo;
                $p->porcentaje_pagado = $total_creditos > 0 ? round(($total_abonos / $total_creditos) * 100, 2) : 100;
                $p->creditos_activos = $creditos_activos;
                $p->ultimo_credito = $ultimo_credito;
                $p->ultimo_abono = $ultimo_abono;

                return $p;
            })
            ->filter()
            ->values();

        return Excel::download(
            new EstadoCuentaGeneralExport($productores, $desde, $hasta),
            'estado_cuenta_general.xlsx'
        );
    }

    public function fichaProductor(Request $request)
    {
        $productores = Productor::orderBy('nombre')->get();
        $productor_id = $request->productor_id;
        $desde = $request->desde;
        $hasta = $request->hasta;
        $tipo = $request->tipo;

        $productor = null;
        $movimientos = [];

        if ($productor_id) {
            $productor = Productor::with(['creditos.abonos'])->findOrFail($productor_id);
            foreach ($productor->creditos as $credito) {
                if ($desde && $hasta && ($credito->created_at < $desde || $credito->created_at > $hasta)) continue;
                if (!$tipo || $tipo == 'credito') {
                    $movimientos[] = [
                        'fecha' => $credito->created_at,
                        'tipo' => 'Crédito',
                        'descripcion' => 'Crédito por C$ ' . number_format($credito->monto, 2),
                        'monto' => $credito->monto
                    ];
                }

                foreach ($credito->abonos as $abono) {
                    if ($desde && $hasta && ($abono->created_at < $desde || $abono->created_at > $hasta)) continue;
                    if (!$tipo || $tipo == $abono->tipo_pago) {
                        $movimientos[] = [
                            'fecha' => $abono->created_at,
                            'tipo' => ucfirst($abono->tipo_pago),
                            'descripcion' => 'Abono ' . $abono->tipo_pago . ' por C$ ' . number_format($abono->monto, 2),
                            'monto' => $abono->monto
                        ];
                    }
                }
            }

            usort($movimientos, fn($a, $b) => $a['fecha'] <=> $b['fecha']);
        }

        return view('reportes.ficha_productor', compact('productores', 'productor', 'movimientos', 'desde', 'hasta', 'tipo'));
    }

    // en ReporteController.php (o donde lo tengas)

    public function fichaProductorPdf(Request $request, $productor_id)
    {
        // 1) Carga el productor y todos sus créditos con abonos
        $productor = Productor::with(['creditos.detalles', 'creditos.abonos'])->findOrFail($productor_id);

        $historial     = [];
        $principal     = 0.0;
        $interes       = 0.0;
        $abono         = 0.0;

        // 2) Recorremos cada crédito para build historial y totales
        foreach ($productor->creditos as $credito) {
            // a) monto original de ese crédito (suma de subtotales de detalles)
            $montoCred = $credito->detalles->sum(fn($d) => $d->subtotal);
            $principal += $montoCred;

            $historial[] = [
                'fecha'       => Carbon::parse($credito->fecha_entrega)->format('d/m/Y'),
                'tipo'        => 'Crédito',
                'descripcion' => 'Crédito por C$ ' . number_format($montoCred, 2),
                'monto'       => $montoCred,
            ];

            // b) todos los abonos de este crédito
            foreach ($credito->abonos as $a) {
                $abono += $a->monto;
                $historial[] = [
                    'fecha'       => Carbon::parse($a->fecha)->format('d/m/Y'),
                    'tipo'        => 'Abono',
                    'descripcion' => 'Abono por C$ ' . number_format($a->monto, 2),
                    'monto'       => $a->monto,
                ];
            }

            // c) interés real acumulado hasta hoy para ese crédito
            //    lo extraemos del helper restando principal luego
            $saldoTotalConInteres = CreditoHelper::saldoCreditoPorDias($credito)   // capital + intereses – abonos
                + $credito->abonado;                           // + abonos = capital + intereses
            // entonces: intereses = (capital + intereses) − capital
            $interes += max(0, $saldoTotalConInteres - $montoCred);
        }

        // 3) Total de obligación original (capital + interés)
        $total = round($principal + $interes, 2);

        // 4) Pendiente = Total − Abono (clip a 0)
        $pendiente = round(max(0, $total - $abono), 2);

        // 5) Enviar todo a la vista
        $pdf = Pdf::loadView('reportes.ficha_productor_pdf', compact(
            'productor',
            'historial',
            'principal',
            'interes',
            'total',
            'abono',
            'pendiente'
        ));

        return $pdf->stream("ficha_productor_{$productor->id}.pdf");
    }


    public function productoresConCreditosActivos()
    {
        $query = Productor::with(['creditos' => function ($q) {
            $q->where('estado', 'activo');
        }, 'creditos.detalles', 'creditos.abonos']);

        if (request()->filled('nombre')) {
            $query->where('nombre', 'like', '%' . request('nombre') . '%');
        }

        $productores = $query->get()->map(function ($p) {
            $p->creditos_activos = $p->creditos->count();
            $p->total_deuda = $p->creditos->sum(fn($c) => CreditoHelper::saldoCreditoPorDias($c));
            return $p;
        })->filter(fn($p) => $p->creditos_activos > 1)->values();

        return view('reportes.productores_creditos_activos', compact('productores'));
    }
}
