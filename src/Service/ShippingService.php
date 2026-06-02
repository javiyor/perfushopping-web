<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\ShippingRepo;
use Perfushopping\Web\Support\Format;

final class ShippingService
{
    /** @var array<string,int> */
    private array $fallbackLocalPrices = [
        'reconquista' => 150000,
        'avellaneda' => 300000,
        'guadalupe-norte' => 300000,
        'fortin-olmos' => 300000,
        'malabrigo' => 300000,
        'vera' => 300000,
        'romang' => 300000,
        'villa-ocampo' => 300000,
        'florencia' => 300000,
        'calchaqui' => 300000,
        'margarita' => 300000,
        'alejandra' => 300000,
        'lanteri' => 300000,
        'villa-ana' => 300000,
        'tartagal' => 300000,
        'arroyo-ceibal' => 300000,
        'el-sombrerito' => 300000,
        'las-toscas' => 300000,
        'san-javier' => 300000,
        'los-laureles' => 300000,
        'intiyaco' => 300000,
        'golondrina' => 300000,
    ];

    public function deliveryLocalOptions(string $localityKey, int $provinceCodprov): array
    {
        // Province must be Santa Fe (3)
        if ($provinceCodprov !== 3) {
            return [];
        }

        $repo = new ShippingRepo();
        $allowed = false;
        try {
            $allowed = $repo->isLocalityAllowed($localityKey);
        } catch (\Throwable $e) {
            $allowed = false;
        }
        if (!$allowed && !array_key_exists($localityKey, $this->fallbackLocalPrices)) {
            return [];
        }

        // Reconquista has 2 options
        if ($localityKey === 'reconquista') {
            return [
                ['id' => 'local_reconquista', 'label' => 'Delivery local (Reconquista)', 'price_cents' => 150000],
                ['id' => 'local_alejado', 'label' => 'Delivery local (zona alejada / Avellaneda)', 'price_cents' => 300000],
            ];
        }

        $price = null;
        try {
            $price = $repo->localPriceCents($localityKey);
        } catch (\Throwable $e) {
            $price = null;
        }
        if ($price === null) {
            $price = $this->fallbackLocalPrices[$localityKey] ?? 300000;
        }
        return [
            ['id' => 'local_standard', 'label' => 'Delivery local', 'price_cents' => $price],
        ];
    }

    public function correoArgentinoCostCents(int $idzona): ?int
    {
        return (new ShippingRepo())->correoImporteCents($idzona);
    }
}
