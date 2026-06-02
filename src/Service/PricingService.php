<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

final class PricingService
{
    public function cents(float $amount): int
    {
        return (int)round($amount * 100);
    }

    public function applyDiscountNet(int $netCents, float $discountPercent): int
    {
        $d = max(0.0, min(100.0, $discountPercent));
        return (int)round($netCents * (100.0 - $d) / 100.0);
    }

    public function ivaCents(int $netCents, float $ivaRate): int
    {
        return (int)round($netCents * $ivaRate / 100.0);
    }
}
