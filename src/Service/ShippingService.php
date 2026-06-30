<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\MetaRepo;
use Perfushopping\Web\Repo\ShippingRepo;
use Perfushopping\Web\Support\Format;

final class ShippingService
{
    public function deliveryLocalOptions(string $localityKey, int $provinceCodprov): array
    {
        // Province must be Santa Fe (3)
        if ($provinceCodprov !== 3) {
            return [];
        }

        if ($localityKey === 'reconquista') {
            return [
                ['id' => 'local_reconquista_gratis', 'label' => 'Delivery gratis (Reconquista)', 'price_cents' => 0, 'time_slots' => ['12:00', '19:30']],
                ['id' => 'local_reconquista_especial', 'label' => 'Delivery especial (Reconquista)', 'price_cents' => 200000, 'time_slots' => []],
            ];
        }
        if ($localityKey === 'avellaneda') {
            return [
                ['id' => 'local_avellaneda_gratis', 'label' => 'Delivery gratis (Avellaneda)', 'price_cents' => 0, 'time_slots' => ['12:00', '19:30']],
                ['id' => 'local_avellaneda_especial', 'label' => 'Delivery especial (Avellaneda)', 'price_cents' => 300000, 'time_slots' => []],
            ];
        }

        return [];
    }

    public function deliveryLocalOptionsForDestination(string $city, int $provinceCodprov): array
    {
        if ($provinceCodprov !== 3) {
            return [];
        }

        $lugaresMap = [
            1 => 'reconquista',
            6 => 'avellaneda',
        ];

        try {
            $lugar = (new MetaRepo())->findLugarByNameAndProvince($city, $provinceCodprov);
            $codLugar = (int)($lugar['cod_lugar'] ?? 0);
            if ($codLugar > 0 && isset($lugaresMap[$codLugar])) {
                return $this->deliveryLocalOptions($lugaresMap[$codLugar], $provinceCodprov);
            }
        } catch (\Throwable $e) {
            // Fallback below.
        }

        return $this->deliveryLocalOptions(Format::slugKey($city), $provinceCodprov);
    }

    /** @return array{base_cents:int, discount_percent:int, final_cents:int, free:bool}|null */
    public function correoCostForProvince(int $codprov, int $cartTotalCents): ?array
    {
        $base = self::correoBaseCentsByProvince($codprov);
        if ($base === null) {
            return null;
        }

        return self::applyCorreoDiscount($base, $cartTotalCents);
    }

    public static function correoBaseCentsByProvince(int $codprov): ?int
    {
        return match ($codprov) {
            3 => 900000,
            2, 4, 5, 6, 8, 9 => 1400000,
            1, 7, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24 => 2500000,
            default => null,
        };
    }

    /** @return array{base_cents:int, discount_percent:int, final_cents:int, free:bool} */
    public static function applyCorreoDiscount(int $baseCents, int $cartTotalCents): array
    {
        $discount = 0;
        $free = false;

        if ($cartTotalCents >= 25000000) {
            $free = true;
            $discount = 100;
        } elseif ($cartTotalCents >= 18000000) {
            $discount = 75;
        } elseif ($cartTotalCents >= 10000000) {
            $discount = 50;
        }

        $finalCents = $free ? 0 : (int)round($baseCents * (100 - $discount) / 100);

        return [
            'base_cents' => $baseCents,
            'discount_percent' => $discount,
            'final_cents' => $finalCents,
            'free' => $free,
        ];
    }
}
