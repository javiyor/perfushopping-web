<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\PromoRepo;

final class InstallmentsService
{
    /** @return array{promo: array<string,mixed>|null, cuotas:int|null, recargo_percent:float, total_with_recargo_cents:int, cuota_cents:int}|null */
    public function computeAllCardsPromo(int $baseTotalCents, int $weekday1to7): ?array
    {
        if ($baseTotalCents <= 0) {
            return null;
        }
        $promo = (new PromoRepo())->bestWebPromoForAllCards($weekday1to7);
        if (!$promo) {
            return null;
        }

        $descrip = (string)($promo['descrip'] ?? '');
        $cuotas = $this->extractCuotas($descrip);
        if ($cuotas === null || $cuotas <= 1) {
            return null;
        }
        $recargo = (float)($promo['recargo'] ?? 0.0);
        $recargo = max(0.0, $recargo);

        $totalWithRecargo = (int)round($baseTotalCents * (1.0 + $recargo / 100.0));
        $cuota = (int)round($totalWithRecargo / $cuotas);

        return [
            'promo' => $promo,
            'cuotas' => $cuotas,
            'recargo_percent' => $recargo,
            'total_with_recargo_cents' => $totalWithRecargo,
            'cuota_cents' => $cuota,
        ];
    }

    private function extractCuotas(string $descrip): ?int
    {
        if (preg_match('/(\d{1,2})/', $descrip, $m) !== 1) {
            return null;
        }
        return (int)$m[1];
    }
}
