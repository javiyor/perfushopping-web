<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class CobroCuentaRepo
{
    public function getTransferenciaCuentaId(): ?int
    {
        $st = Db::pdo()->prepare("SELECT banco_cuenta_id FROM cobro_cuentas WHERE tipo='transferencia' AND idtarje=0 LIMIT 1");
        $st->execute();
        $v = $st->fetchColumn();
        return $v !== false && $v !== null ? (int)$v : null;
    }

    public function getTarjetaCuentaId(int $idtarje): ?int
    {
        $st = Db::pdo()->prepare("SELECT banco_cuenta_id FROM cobro_cuentas WHERE tipo='tarjeta' AND idtarje=:id LIMIT 1");
        $st->execute([':id' => $idtarje]);
        $v = $st->fetchColumn();
        return $v !== false && $v !== null ? (int)$v : null;
    }

    public function setTransferenciaCuenta(int $bancoCuentaId): void
    {
        Db::pdo()->prepare("INSERT INTO cobro_cuentas (tipo, idtarje, banco_cuenta_id, created_at, updated_at) VALUES ('transferencia', 0, :b, NOW(), NOW()) ON DUPLICATE KEY UPDATE banco_cuenta_id = :b2, updated_at = NOW()")->execute([':b' => $bancoCuentaId, ':b2' => $bancoCuentaId]);
    }

    public function setTarjetaCuenta(int $idtarje, int $bancoCuentaId): void
    {
        Db::pdo()->prepare("INSERT INTO cobro_cuentas (tipo, idtarje, banco_cuenta_id, created_at, updated_at) VALUES ('tarjeta', :t, :b, NOW(), NOW()) ON DUPLICATE KEY UPDATE banco_cuenta_id = :b2, updated_at = NOW()")->execute([':t' => $idtarje, ':b' => $bancoCuentaId, ':b2' => $bancoCuentaId]);
    }

    public function deleteTarjetaCuenta(int $idtarje): void
    {
        Db::pdo()->prepare("DELETE FROM cobro_cuentas WHERE tipo='tarjeta' AND idtarje=:id LIMIT 1")->execute([':id' => $idtarje]);
    }

    public function all(): array
    {
        $st = Db::pdo()->query("SELECT cc.*, bc.banco, bc.numero_cuenta, t.nomtar FROM cobro_cuentas cc LEFT JOIN banco_cuentas bc ON bc.id=cc.banco_cuenta_id LEFT JOIN tarjeta t ON t.idtarje=cc.idtarje ORDER BY cc.tipo ASC, t.nomtar ASC");
        return $st->fetchAll();
    }
}
