<?php
declare(strict_types=1);

namespace Perfushopping\Web\Support;

final class Plazo
{
    /**
     * Cronograma de vencimientos del plazo de pago.
     * @return array<int, array{cuota:int, dias:int, fecha:?string, monto:float}>
     */
    public static function cronograma(array $compra): array
    {
        $cuotas = max(1, (int)($compra['plazo_cuotas'] ?? 1));
        $dias = array_values(array_filter(
            array_map('intval', array_map('trim', explode(',', (string)($compra['plazo_dias'] ?? '')))),
            static fn (int $d): bool => $d >= 0
        ));
        $total = (float)($compra['imp_total'] ?? 0);
        $fecha = (string)($compra['fecha'] ?? '');

        if ($total <= 0 || $fecha === '') {
            return [];
        }

        $monto = round($total / $cuotas, 2);
        $acum = 0.0;
        $ts = strtotime($fecha);
        $out = [];
        for ($i = 0; $i < $cuotas; $i++) {
            $d = $dias !== [] ? ($dias[$i] ?? $dias[count($dias) - 1]) : 0;
            $esUltima = $i === $cuotas - 1;
            $importe = $esUltima ? round($total - $acum, 2) : $monto;
            $acum += $importe;
            $out[] = [
                'cuota' => $i + 1,
                'dias' => $d,
                'fecha' => $ts !== false ? date('Y-m-d', strtotime('+' . $d . ' days', $ts)) : null,
                'monto' => $importe,
            ];
        }
        return $out;
    }
}
