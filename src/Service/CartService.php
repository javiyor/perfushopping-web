<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

final class CartService
{
    /** @return array<int,int> */
    public function items(): array
    {
        $c = $_SESSION['cart'] ?? [];
        if (!is_array($c)) {
            return [];
        }
        $out = [];
        foreach ($c as $k => $v) {
            $id = (int)$k;
            $qty = (int)$v;
            if ($id <= 0 || $qty <= 0) {
                continue;
            }
            $out[$id] = min(999, $qty);
        }
        return $out;
    }

    public function add(int $idcodgusto, int $qty): void
    {
        $qty = max(1, min(999, $qty));
        $c = $this->items();
        $c[$idcodgusto] = ($c[$idcodgusto] ?? 0) + $qty;
        $_SESSION['cart'] = $c;
    }

    public function update(int $idcodgusto, int $qty): void
    {
        $c = $this->items();
        if ($qty <= 0) {
            unset($c[$idcodgusto]);
        } else {
            $c[$idcodgusto] = min(999, $qty);
        }
        $_SESSION['cart'] = $c;
    }

    public function remove(int $idcodgusto): void
    {
        $c = $this->items();
        unset($c[$idcodgusto]);
        $_SESSION['cart'] = $c;
    }

    public function clear(): void
    {
        $_SESSION['cart'] = [];
    }
}
