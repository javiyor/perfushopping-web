<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\AffiliateLedgerRepo;
use Perfushopping\Web\Repo\OrderRepo;
use Perfushopping\Web\Repo\UserRepo;

final class AffiliateService
{
    public function maybeCreateCommissionForPaidOrder(array $order): void
    {
        // Order must be retail and paid.
        if (($order['customer_type'] ?? '') !== 'retail' || ($order['status'] ?? '') !== 'paid') {
            return;
        }
        $buyerUserId = (int)($order['user_id'] ?? 0);
        if ($buyerUserId <= 0) {
            // guest checkout: no commission in MVP (can be extended by linking email to user)
            return;
        }

        // Load buyer to get fixed referrer.
        $buyer = (new UserRepo())->findById($buyerUserId);
        if (!$buyer) {
            return;
        }
        $referrerId = (int)($buyer['affiliate_referrer_user_id'] ?? 0);
        if ($referrerId <= 0 || $referrerId === $buyerUserId) {
            return;
        }

        // Anti-fraud: block if phone matches OR (address+city) matches.
        $note = $this->fraudReason($referrerId, $order);
        $ledger = new AffiliateLedgerRepo();
        $orderId = (int)$order['id'];
        if ($note !== '') {
            $ledger->addBlocked($referrerId, $orderId, $note);
            return;
        }

        // Commission base: products total with IVA+discount, without shipping.
        $productsTotalCents = (int)$order['total_cents'] - (int)$order['shipping_cost_cents'];
        if ($productsTotalCents <= 0) {
            return;
        }
        $commission = (int)round($productsTotalCents * 0.10);
        if ($commission <= 0) {
            return;
        }

        // Available in 7 days
        $availableAt = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 7);
        $ledger->addPendingCommission($referrerId, $orderId, $commission, $availableAt, '10% referido (libera en 7 dias)');
    }

    private function fraudReason(int $referrerUserId, array $order): string
    {
        $ref = (new UserRepo())->findById($referrerUserId);
        if (!$ref) {
            return '';
        }
        $refPhoneKey = (string)($ref['phone_key'] ?? '');
        $refAddrKey = (string)($ref['addr_key'] ?? '');
        $refCityKey = (string)($ref['city_key'] ?? '');

        $orderPhoneKey = preg_replace('/[^0-9]/', '', (string)($order['phone'] ?? '')) ?? '';
        $orderAddrKey = $this->normKey((string)($order['ship_address'] ?? ''));
        $orderCityKey = $this->normKey((string)($order['ship_city'] ?? ''));

        if ($refPhoneKey !== '' && $orderPhoneKey !== '' && $refPhoneKey === $orderPhoneKey) {
            return 'Comision bloqueada (autopromo): mismo telefono.';
        }
        if ($refAddrKey !== '' && $refCityKey !== '' && $refAddrKey === $orderAddrKey && $refCityKey === $orderCityKey) {
            return 'Comision bloqueada (autopromo): misma direccion y localidad.';
        }
        return '';
    }

    private function normKey(string $s): string
    {
        $s = trim(mb_strtolower($s));
        $s = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $s);
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s) ?? '';
        $s = preg_replace('/\s+/', ' ', $s) ?? '';
        return trim($s);
    }
}
