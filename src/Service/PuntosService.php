<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\PuntosRepo;

final class PuntosService
{
    private PuntosRepo $repo;

    public function __construct()
    {
        $this->repo = new PuntosRepo();
    }

    /**
     * Points for a single item line. General % + brand (marca/subrubro) bonus + product bonus.
     * Base is the item total (net+IVA), rounded down so points are always 1 punto = $1.
     */
    public function puntosParaLinea(int $idprodu, int $codsub, int $totalCents): int
    {
        if ($totalCents <= 0) {
            return 0;
        }
        $pct = $this->repo->pctGeneral() + $this->repo->pctMarca($codsub) + $this->repo->pctProducto($idprodu);
        if ($pct <= 0) {
            return 0;
        }
        // 1% of $1 = 1 punto. totalCents / 100 = dollars; dollars * pct / 100 = puntos.
        $puntos = (int)floor($totalCents * $pct / 10000);
        return $puntos > 0 ? $puntos : 0;
    }

    /**
     * Total points for a list of items that carry idprodu + total_cents.
     *
     * @param array<int, array<string,mixed>> $items
     */
    public function puntosParaItems(array $items): int
    {
        $total = 0;
        foreach ($items as $it) {
            $idprodu = (int)($it['idprodu'] ?? 0);
            $codsub = (int)($it['codsub'] ?? $this->codsubDeProducto($idprodu));
            $lineTotal = (int)($it['total_cents'] ?? $it['line_total_cents'] ?? 0);
            $total += $this->puntosParaLinea($idprodu, $codsub, $lineTotal);
        }
        return $total;
    }

    public function codsubDeProducto(int $idprodu): int
    {
        if ($idprodu <= 0) {
            return 0;
        }
        $st = Db::pdo()->prepare('SELECT codsub FROM producto WHERE idprodu = :id LIMIT 1');
        $st->execute([':id' => $idprodu]);
        $v = $st->fetchColumn();
        return $v === false ? 0 : (int)$v;
    }

    /** @return array<int, array<string,mixed>> */
    public function itemsConCodsub(array $items): array
    {
        foreach ($items as &$it) {
            $idprodu = (int)($it['idprodu'] ?? 0);
            if (!array_key_exists('codsub', $it)) {
                $it['codsub'] = $this->codsubDeProducto($idprodu);
            }
        }
        return $items;
    }

    /**
     * Accrue points on a local factura. Idempotent (unique key on factura_id+tipo).
     */
    public function acreditarFactura(array $factura, array $items): ?int
    {
        $idclien = (int)($factura['idclien'] ?? 0);
        if ($idclien <= 0) {
            return null;
        }
        $estado = (string)($factura['estado'] ?? 'emitida');
        if (in_array($estado, ['anulada', 'pendiente'], true)) {
            return null;
        }
        // Nota de crédito: no acumula puntos.
        if (in_array((string)($factura['tipo_comprobante'] ?? ''), ['NC', 'ND'], true)) {
            return null;
        }
        if ($this->repo->tieneAcumulacionFactura((int)$factura['id'])) {
            return null;
        }
        // Only positive items (exclude NC/ND negatives handled by sign of total).
        $items = $this->itemsConCodsub($items);
        $puntos = $this->puntosParaItems($items);
        // Scale by the actually paid amount (after descuento and points redeemed).
        $bruto = (int)($factura['subtotal_cents'] ?? 0) + (int)($factura['iva_cents'] ?? 0);
        $pagado = (int)($factura['total_cents'] ?? 0);
        if ($bruto > 0 && $pagado > 0 && $pagado < $bruto) {
            $puntos = (int)floor($puntos * $pagado / $bruto);
        }
        if ($puntos <= 0) {
            return null;
        }
        return $this->repo->registrar(
            'acumulacion',
            $idclien,
            $puntos,
            (int)$factura['id'],
            null,
            'Compra ' . ($factura['codigo'] ?? ('Factura ' . $factura['id'])),
            (int)($factura['created_by'] ?? 0) ?: null
        );
    }

    /**
     * Reverse accrued points when a factura is anulada.
     */
    public function revertirFactura(int $facturaId): void
    {
        $st = Db::pdo()->prepare("SELECT idclien, puntos FROM puntos_movimientos WHERE factura_id = :f AND tipo = 'acumulacion' LIMIT 1");
        $st->execute([':f' => $facturaId]);
        $row = $st->fetch();
        if (!$row || (int)$row['puntos'] <= 0) {
            return;
        }
        $this->repo->registrar('ajuste', (int)$row['idclien'], -((int)$row['puntos']), $facturaId, null, 'Reversión por anulación de factura', null);
    }

    /**
     * Accrue points on a web order marked as paid. Idempotent (unique key on order_id+tipo).
     */
    public function acreditarOrder(array $order): ?int
    {
        if (($order['status'] ?? '') !== 'paid') {
            return null;
        }
        $idclien = $this->idclienDeOrder($order);
        if ($idclien <= 0) {
            return null;
        }
        if ($this->repo->tieneAcumulacionOrder((int)$order['id'])) {
            return null;
        }
        $items = (new \Perfushopping\Web\Repo\OrderRepo())->itemsByOrderIds([(int)$order['id']]);
        $items = $this->itemsConCodsub($items);
        $puntos = $this->puntosParaItems($items);
        // Scale by the actually paid amount (products after promo/credit/points, excl. shipping).
        $bruto = (int)($order['subtotal_net_cents'] ?? 0) + (int)($order['iva_cents'] ?? 0);
        $pagado = (int)($order['total_cents'] ?? 0) - (int)($order['shipping_cost_cents'] ?? 0);
        if ($bruto > 0 && $pagado > 0 && $pagado < $bruto) {
            $puntos = (int)floor($puntos * $pagado / $bruto);
        }
        if ($puntos <= 0) {
            return null;
        }
        return $this->repo->registrar(
            'acumulacion',
            $idclien,
            $puntos,
            null,
            (int)$order['id'],
            'Compra web ' . ($order['order_code'] ?? ('Pedido ' . $order['id'])),
            null
        );
    }

    /**
     * Reverse accrued points when a paid web order is cancelled/refunded.
     */
    public function revertirOrder(int $orderId): void
    {
        $st = Db::pdo()->prepare("SELECT idclien, puntos FROM puntos_movimientos WHERE order_id = :o AND tipo = 'acumulacion' LIMIT 1");
        $st->execute([':o' => $orderId]);
        $row = $st->fetch();
        if (!$row || (int)$row['puntos'] <= 0) {
            return;
        }
        $this->repo->registrar('ajuste', (int)$row['idclien'], -((int)$row['puntos']), null, $orderId, 'Reversión por anulación de pedido', null);
    }

    /**
     * Redeem points on a local factura (POS). Returns new balance or null.
     */
    public function usarEnFactura(int $idclien, int $puntos, int $facturaId, ?int $createdBy = null): ?int
    {
        if ($idclien <= 0 || $puntos <= 0) {
            return null;
        }
        return $this->repo->registrar('uso', $idclien, $puntos, $facturaId, null, 'Canje de puntos en factura', $createdBy);
    }

    /**
     * Redeem points on a web order. Returns new balance or null.
     */
    public function usarEnOrder(int $idclien, int $puntos, int $orderId): ?int
    {
        if ($idclien <= 0 || $puntos <= 0) {
            return null;
        }
        return $this->repo->registrar('uso', $idclien, $puntos, null, $orderId, 'Canje de puntos en pedido web', null);
    }

    /** Restore points if a web order that redeemed points is cancelled before accrual. */
    public function revertirUsoOrder(int $orderId): void
    {
        $uso = $this->repo->usoEnOrder($orderId);
        if (!$uso || (int)$uso['puntos'] <= 0) {
            return;
        }
        $this->repo->registrar('ajuste', (int)$uso['idclien'], (int)$uso['puntos'], null, $orderId, 'Devolución de puntos por pedido cancelado', null);
    }

    /** Restore points if a factura that redeemed points is anulada. */
    public function revertirUsoFactura(int $facturaId): void
    {
        $uso = $this->repo->usoEnFactura($facturaId);
        if (!$uso || (int)$uso['puntos'] <= 0) {
            return;
        }
        $this->repo->registrar('ajuste', (int)$uso['idclien'], (int)$uso['puntos'], $facturaId, null, 'Devolución de puntos por factura anulada', null);
    }

    /**
     * Resolve local client id (clientes.idclien) from a web order.
     * Prefers the web user link (web_users.cliente_id), then email match, then CUIT match.
     */
    public function idclienDeOrder(array $order): int
    {
        $userId = (int)($order['user_id'] ?? 0);
        if ($userId > 0) {
            $erp = (new \Perfushopping\Web\Repo\FacturaRepo())->findClienteErpByWebId($userId);
            if ($erp && (int)($erp['idclien'] ?? 0) > 0) {
                return (int)$erp['idclien'];
            }
        }
        $email = strtolower(trim((string)($order['email'] ?? '')));
        if ($email !== '') {
            $st = Db::pdo()->prepare('SELECT idclien FROM clientes WHERE LOWER(mail) = :e AND idclien > 0 LIMIT 1');
            $st->execute([':e' => $email]);
            $v = $st->fetchColumn();
            if ($v !== false && (int)$v > 0) {
                return (int)$v;
            }
        }
        return 0;
    }
}
