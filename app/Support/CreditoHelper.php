<?php

namespace App\Support;

use App\Models\Credito;
use Carbon\Carbon;

class CreditoHelper
{
    /**
     * Calcula saldo real (capital + interés) prorrateando pagos.
     * Si el crédito está pagado o el principal quedó en 0, retorna 0.
     */
    public static function saldoCreditoPorDias(Credito $c): float
    {
        // Si ya marcaste pagado, no generes más.
        if ($c->estado === 'pagado') {
            return 0.0;
        }

        // Fecha tope: si ya se liquidó, usa esa fecha; si no, hoy.
        $endDate = $c->liquidado_at
            ? Carbon::parse($c->liquidado_at)->startOfDay()
            : now()->startOfDay();

        // 1) Inicializar por detalle
        $balances = [];
        foreach ($c->detalles as $d) {
            $balances[$d->id] = [
                'principal' => (float) $d->subtotal,
                'rate'      => (($d->interes ?? 0) / 30) / 100, // diario
                'interest'  => 0.0,
            ];
        }

        // 2) Ordenar pagos
        $events = $c->abonos
            ->map(fn($a) => [
                'date'   => Carbon::parse($a->fecha)->startOfDay(),
                'amount' => (float) $a->monto,
            ])
            ->sortBy('date')
            ->values()
            ->all();

        // 3) Simulación por tramos
        $lastDate = Carbon::parse($c->fecha_entrega)->startOfDay();

        foreach ($events as $e) {
            // Si ya liquidaste el capital, termina.
            if (self::principalCero($balances)) {
                return 0.0;
            }

            // No pases del endDate
            if ($e['date']->greaterThan($endDate)) {
                break;
            }

            $days = $lastDate->diffInDays($e['date']);
            if ($days > 0) {
                foreach ($balances as &$b) {
                    $b['interest'] += round($b['principal'] * $b['rate'] * $days, 2);
                }
                unset($b);
            }

            // Aplicar pago
            $payment = $e['amount'];
            $totalI  = array_sum(array_column($balances, 'interest'));

            if ($payment <= $totalI) {
                // Paga sólo interés
                foreach ($balances as &$b) {
                    $share = $totalI > 0 ? $b['interest'] / $totalI : 0;
                    $b['interest'] = round($b['interest'] - $payment * $share, 2);
                }
                unset($b);
            } else {
                // Paga interés + capital
                $toPrincipal = $payment - $totalI;
                foreach ($balances as &$b) {
                    $b['interest'] = 0.0;
                }
                unset($b);

                $sumP = array_sum(array_column($balances, 'principal'));
                foreach ($balances as &$b) {
                    $share = $sumP > 0 ? $b['principal'] / $sumP : 0;
                    $b['principal'] = max(0, round($b['principal'] - $toPrincipal * $share, 2));
                }
                unset($b);
            }

            $lastDate = $e['date'];
        }

        // 4) Interés desde último pago hasta endDate
        if (!self::principalCero($balances)) {
            $days = $lastDate->diffInDays($endDate);
            if ($days > 0) {
                foreach ($balances as &$b) {
                    $b['interest'] += round($b['principal'] * $b['rate'] * $days, 2);
                }
                unset($b);
            }
        } else {
            return 0.0;
        }

        $sumP = array_sum(array_column($balances, 'principal'));
        $sumI = array_sum(array_column($balances, 'interest'));

        return round($sumP + $sumI, 2);
    }

    public static function actualizarEstado(Credito $credito): void
    {
        $credito->loadMissing('detalles', 'abonos');

        // 1) Calcula y redondea el saldo real a 2 decimales
        $saldo = round(self::saldoCreditoPorDias($credito), 2);

        // 2) Si saldo EXACTO = 0.00 → pagado, sino → activo
        if ($saldo === 0.0) {
            $credito->forceFill([
                'estado'       => 'pagado',
                // sólo lo seteo la primera vez que pasa a 0
                'liquidado_at' => $credito->liquidado_at ?? now(),
            ])->saveQuietly();
        } else {
            $credito->forceFill([
                'estado'       => 'activo',
                'liquidado_at' => null,
            ])->saveQuietly();
        }
    }

    public static function recalcularSaldoProductor($productor): void
    {
        $productor->load('creditos.detalles', 'creditos.abonos');
        $nuevoSaldo = $productor->creditos->sum(fn($c) => self::saldoCreditoPorDias($c));
        $productor->update(['saldo' => $nuevoSaldo]);
    }

    private static function principalCero(array $balances): bool
    {
        return array_sum(array_column($balances, 'principal')) <= 0.01;
    }

    public static function simularDetalle(Credito $c): array
    {
        // idéntico algoritmo que saldoCreditoPorDias, pero guardando por detalle
        $endDate = $c->estado === 'pagado' && $c->liquidado_at
            ? Carbon::parse($c->liquidado_at)->startOfDay()
            : now()->startOfDay();

        $balances = [];
        foreach ($c->detalles as $d) {
            $balances[$d->id] = [
                'detalle'   => $d,
                'principal' => (float) $d->subtotal,
                'rate'      => (($d->interes ?? 0) / 30) / 100,
                'interest'  => 0.0,
            ];
        }

        $events = $c->abonos->map(fn($a) => [
            'date'   => Carbon::parse($a->fecha)->startOfDay(),
            'amount' => (float) $a->monto,
        ])->sortBy('date')->values()->all();

        $lastDate = Carbon::parse($c->fecha_entrega)->startOfDay();

        foreach ($events as $e) {
            if (self::principalCero($balances)) break;
            if ($e['date']->greaterThan($endDate)) break;

            $days = $lastDate->diffInDays($e['date']);
            if ($days > 0) {
                foreach ($balances as &$b) {
                    $b['interest'] += round($b['principal'] * $b['rate'] * $days, 2);
                }
                unset($b);
            }

            $payment = $e['amount'];
            $totalI  = array_sum(array_column($balances, 'interest'));

            if ($payment <= $totalI) {
                foreach ($balances as &$b) {
                    $share = $totalI > 0 ? $b['interest'] / $totalI : 0;
                    $b['interest'] = round($b['interest'] - $payment * $share, 2);
                }
                unset($b);
            } else {
                $toPrincipal = $payment - $totalI;
                foreach ($balances as &$b) {
                    $b['interest'] = 0.0;
                }
                unset($b);

                $sumP = array_sum(array_column($balances, 'principal'));
                foreach ($balances as &$b) {
                    $share = $sumP > 0 ? $b['principal'] / $sumP : 0;
                    $b['principal'] = max(0, round($b['principal'] - $toPrincipal * $share, 2));
                }
                unset($b);
            }

            $lastDate = $e['date'];
        }

        $days = $lastDate->diffInDays($endDate);
        if ($days > 0 && !self::principalCero($balances)) {
            foreach ($balances as &$b) {
                $b['interest'] += round($b['principal'] * $b['rate'] * $days, 2);
            }
            unset($b);
        }

        // Empaquetar
        $detalles = [];
        foreach ($balances as $id => $b) {
            $detalles[] = [
                'detalle'   => $b['detalle'],
                'dias'      => $days, // opcional, puedes guardar por tramo si quieres exacto
                'principal' => $b['principal'],
                'interest'  => $b['interest'],
                'total'     => round($b['principal'] + $b['interest'], 2),
            ];
        }

        $saldo = array_sum(array_column($detalles, 'total'));

        return ['saldo' => round($saldo, 2), 'detalles' => $detalles];
    }
}
