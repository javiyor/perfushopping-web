<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class PuntosRepo
{
    public function getCuenta(int $idclien): array
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('SELECT * FROM puntos_cuentas WHERE idclien = :c LIMIT 1');
        $st->execute([':c' => $idclien]);
        $row = $st->fetch();
        if ($row) {
            return $row;
        }
        $pdo->prepare('INSERT IGNORE INTO puntos_cuentas (idclien, saldo_puntos, total_acumulado, total_usados, created_at, updated_at) VALUES (:c, 0, 0, 0, NOW(), NOW())')->execute([':c' => $idclien]);
        $st = $pdo->prepare('SELECT * FROM puntos_cuentas WHERE idclien = :c LIMIT 1');
        $st->execute([':c' => $idclien]);
        return $st->fetch() ?: ['idclien' => $idclien, 'saldo_puntos' => 0, 'total_acumulado' => 0, 'total_usados' => 0];
    }

    public function saldo(int $idclien): int
    {
        if ($idclien <= 0) {
            return 0;
        }
        $st = Db::pdo()->prepare('SELECT saldo_puntos FROM puntos_cuentas WHERE idclien = :c LIMIT 1');
        $st->execute([':c' => $idclien]);
        $v = $st->fetchColumn();
        return $v === false ? 0 : (int)$v;
    }

    /** Returns new balance, or null if duplicate. $puntos may be negative for reversal/adjustment. */
    public function registrar(string $tipo, int $idclien, int $puntos, ?int $facturaId, ?int $orderId, string $descripcion, ?int $createdBy): ?int
    {
        if ($idclien <= 0 || $puntos === 0) {
            return null;
        }
        $pdo = Db::pdo();
        $cuenta = $this->getCuenta($idclien);
        $saldo = (int)$cuenta['saldo_puntos'];

        if ($tipo === 'uso') {
            $saldoNuevo = $saldo - $puntos;
            if ($saldoNuevo < 0) {
                $puntos = $saldo;
                $saldoNuevo = 0;
            }
            if ($puntos <= 0) {
                return null;
            }
        } else {
            $saldoNuevo = $saldo + $puntos;
        }

        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare('
                INSERT INTO puntos_movimientos (idclien, tipo, puntos, factura_id, order_id, descripcion, created_by, created_at)
                VALUES (:c, :tipo, :puntos, :f, :o, :d, :u, NOW())
            ');
            $st->execute([
                ':c' => $idclien,
                ':tipo' => $tipo,
                ':puntos' => $puntos,
                ':f' => $facturaId,
                ':o' => $orderId,
                ':d' => $descripcion,
                ':u' => $createdBy,
            ]);
            $movId = (int)$pdo->lastInsertId();

            $acumulado = (int)$cuenta['total_acumulado'];
            $usados = (int)$cuenta['total_usados'];
            if ($tipo === 'acumulacion') {
                $acumulado += $puntos;
            } elseif ($tipo === 'uso') {
                $usados += $puntos;
            }
            $st = $pdo->prepare('UPDATE puntos_cuentas SET saldo_puntos = :s, total_acumulado = :a, total_usados = :u, updated_at = NOW() WHERE idclien = :c');
            $st->execute([':s' => $saldoNuevo, ':a' => $acumulado, ':u' => $usados, ':c' => $idclien]);
            $pdo->commit();
            return $saldoNuevo;
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Unique key hit = already registered for this source.
            return null;
        }
    }

    /** @return array<int, array<string,mixed>> */
    public function movimientos(int $idclien, int $limit = 80): array
    {
        $limit = max(1, min(300, $limit));
        $st = Db::pdo()->prepare('SELECT * FROM puntos_movimientos WHERE idclien = :c ORDER BY created_at DESC, id DESC LIMIT ' . $limit);
        $st->execute([':c' => $idclien]);
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function listarCuentas(string $q = '', int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $q = trim($q);
        $params = [];
        $where = '';
        if ($q !== '') {
            $where = ' AND (c.razon LIKE :like OR c.cuit LIKE :like2)';
            $params[':like'] = '%' . $q . '%';
            $params[':like2'] = '%' . $q . '%';
        }
        $st = Db::pdo()->prepare('
            SELECT p.*, c.razon, c.cuit, c.tele AS phone, c.mail AS email, c.Localidad AS city,
                   (SELECT COUNT(*) FROM puntos_movimientos m WHERE m.idclien = p.idclien) AS mov_count
            FROM puntos_cuentas p
            INNER JOIN clientes c ON c.idclien = p.idclien
            WHERE 1=1 ' . $where . '
            ORDER BY p.saldo_puntos DESC, p.updated_at DESC
            LIMIT ' . $limit
        );
        $st->execute($params);
        return $st->fetchAll();
    }

    public function config(string $clave, string $default = ''): string
    {
        $st = Db::pdo()->prepare('SELECT valor FROM puntos_config WHERE clave = :c LIMIT 1');
        $st->execute([':c' => $clave]);
        $v = $st->fetchColumn();
        return $v === false ? $default : (string)$v;
    }

    public function setConfig(string $clave, string $valor): void
    {
        Db::pdo()->prepare('INSERT INTO puntos_config (clave, valor) VALUES (:c, :v) ON DUPLICATE KEY UPDATE valor = :v2')
            ->execute([':c' => $clave, ':v' => $valor, ':v2' => $valor]);
    }

    public function pctGeneral(): float
    {
        return max(0.0, (float)$this->config('general_pct', '1'));
    }

    public function pctMarca(int $codsub): float
    {
        if ($codsub <= 0) {
            return 0.0;
        }
        $st = Db::pdo()->prepare('SELECT porcentaje FROM puntos_marcas WHERE codsub = :c LIMIT 1');
        $st->execute([':c' => $codsub]);
        $v = $st->fetchColumn();
        return $v === false ? 0.0 : (float)$v;
    }

    public function pctProducto(int $idprodu): float
    {
        if ($idprodu <= 0) {
            return 0.0;
        }
        $st = Db::pdo()->prepare('SELECT porcentaje FROM puntos_productos WHERE idprodu = :c LIMIT 1');
        $st->execute([':c' => $idprodu]);
        $v = $st->fetchColumn();
        return $v === false ? 0.0 : (float)$v;
    }

    public function setPctMarca(int $codsub, float $pct): void
    {
        if ($codsub <= 0) {
            return;
        }
        Db::pdo()->prepare('INSERT INTO puntos_marcas (codsub, porcentaje) VALUES (:c, :p) ON DUPLICATE KEY UPDATE porcentaje = :p2')
            ->execute([':c' => $codsub, ':p' => $pct, ':p2' => $pct]);
    }

    public function deletePctMarca(int $codsub): void
    {
        Db::pdo()->prepare('DELETE FROM puntos_marcas WHERE codsub = :c LIMIT 1')->execute([':c' => $codsub]);
    }

    public function setPctProducto(int $idprodu, float $pct): void
    {
        if ($idprodu <= 0) {
            return;
        }
        Db::pdo()->prepare('INSERT INTO puntos_productos (idprodu, porcentaje) VALUES (:c, :p) ON DUPLICATE KEY UPDATE porcentaje = :p2')
            ->execute([':c' => $idprodu, ':p' => $pct, ':p2' => $pct]);
    }

    public function deletePctProducto(int $idprodu): void
    {
        Db::pdo()->prepare('DELETE FROM puntos_productos WHERE idprodu = :c LIMIT 1')->execute([':c' => $idprodu]);
    }

    /** @return array<int, array<string,mixed>> */
    public function listarMarcas(): array
    {
        return Db::pdo()->query('SELECT s.codsub, s.nomsub, COALESCE(p.porcentaje, 0) AS porcentaje FROM subrubro s LEFT JOIN puntos_marcas p ON p.codsub = s.codsub ORDER BY s.nomsub ASC')->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function listarProductos(string $q = '', int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $q = trim($q);
        $params = [];
        $where = '';
        if ($q !== '') {
            $where = ' AND (p2.produ LIKE :like OR p2.codprodu LIKE :like2)';
            $params[':like'] = '%' . $q . '%';
            $params[':like2'] = '%' . $q . '%';
        }
        $st = Db::pdo()->prepare('
            SELECT pp.*, p2.codprodu, p2.produ, s.nomsub
            FROM puntos_productos pp
            INNER JOIN producto p2 ON p2.idprodu = pp.idprodu
            LEFT JOIN subrubro s ON s.codsub = p2.codsub
            WHERE 1=1 ' . $where . '
            ORDER BY p2.produ ASC
            LIMIT ' . $limit
        );
        $st->execute($params);
        return $st->fetchAll();
    }

    public function tieneAcumulacionFactura(int $facturaId): bool
    {
        $st = Db::pdo()->prepare("SELECT COUNT(*) FROM puntos_movimientos WHERE factura_id = :f AND tipo = 'acumulacion'");
        $st->execute([':f' => $facturaId]);
        return (int)$st->fetchColumn() > 0;
    }

    public function tieneAcumulacionOrder(int $orderId): bool
    {
        $st = Db::pdo()->prepare("SELECT COUNT(*) FROM puntos_movimientos WHERE order_id = :o AND tipo = 'acumulacion'");
        $st->execute([':o' => $orderId]);
        return (int)$st->fetchColumn() > 0;
    }

    public function usoEnFactura(int $facturaId): ?array
    {
        $st = Db::pdo()->prepare("SELECT * FROM puntos_movimientos WHERE factura_id = :f AND tipo = 'uso' LIMIT 1");
        $st->execute([':f' => $facturaId]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function usoEnOrder(int $orderId): ?array
    {
        $st = Db::pdo()->prepare("SELECT * FROM puntos_movimientos WHERE order_id = :o AND tipo = 'uso' LIMIT 1");
        $st->execute([':o' => $orderId]);
        $r = $st->fetch();
        return $r ?: null;
    }
}
