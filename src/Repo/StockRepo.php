<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class StockRepo
{
    private static bool $ajustesAuthTableReady = false;

    private function ensureAjustesAuthTable(): void
    {
        if (self::$ajustesAuthTableReady) {
            return;
        }
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS stock_ajuste_autorizaciones (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            idprodu INT UNSIGNED NOT NULL,
            idcodgusto INT UNSIGNED DEFAULT NULL,
            iddepodesde INT UNSIGNED DEFAULT NULL,
            iddepohasta INT UNSIGNED DEFAULT NULL,
            cantidad INT UNSIGNED NOT NULL,
            motivo TEXT NOT NULL,
            requested_by INT UNSIGNED NOT NULL,
            requested_by_nombre VARCHAR(120) DEFAULT NULL,
            status ENUM('pendiente','procesando','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
            decided_by INT UNSIGNED DEFAULT NULL,
            decided_by_nombre VARCHAR(120) DEFAULT NULL,
            decided_at DATETIME DEFAULT NULL,
            rejection_note VARCHAR(255) DEFAULT NULL,
            stockcab_id INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            KEY idx_stock_aj_aut_status (status),
            KEY idx_stock_aj_aut_created (created_at),
            KEY idx_stock_aj_aut_requested_by (requested_by),
            KEY idx_stock_aj_aut_producto (idprodu)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$ajustesAuthTableReady = true;
    }

    public function listarStock(string $q = '', int $codepar = 0, string $stockFilter = '', int $codrub = 0, int $codsub = 0, string $codprove = '', int $limit = 80, ?int $iddepo = null, string $desde = '', string $hasta = '', int $page = 1, ?string $enweb = null): array
    {
        $limit = max(1, min(200, $limit));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        [$where, $stockWhere, $depDepo, $params] = $this->stockFilterParts($q, $codepar, $stockFilter, $codrub, $codsub, $codprove, $iddepo, $desde, $hasta, $enweb);

        if ($desde === '') $desde = date('Y-m-01');
        if ($hasta === '') $hasta = date('Y-m-d');
        $params[':desde'] = $desde;
        $params[':hasta'] = $hasta;
        $params[':desde2'] = $desde;
        $params[':hasta2'] = $hasta;
        if ($iddepo) {
            $params[':vd'] = $iddepo;
        }

        $sql = "
            SELECT p.idprodu, p.codprodu, p.produ, p.codprodup, p.precio, p.precomp, p.stocact, p.stocdep, p.codepar, p.enweb, p.observ, p.imagen,
                   d.nomdepar, g.idcodgusto, g.nomgusto, g.codscan, g.discont,
                   r.nomrub, s.nomsub, pv.razon AS nomprovee,
                   dep.iddepo, dep.nomdepo,
                   COALESCE(dep.stock, 0) AS stock_deposito,
                   CASE
                       WHEN dep.iddepo IS NOT NULL THEN COALESCE(cv.total_vendido, 0)
                       ELSE COALESCE(cvt.total_vendido, 0)
                   END AS total_vendido
            FROM producto p
            INNER JOIN gustos g ON g.idprodu = p.idprodu
            LEFT JOIN departa d ON d.codepar = p.codepar
            LEFT JOIN rubros r ON r.codrub = p.codrub
            LEFT JOIN subrubro s ON s.codsub = p.codsub
            LEFT JOIN proveedo pv ON pv.idprovee = p.codprove
            " . $this->depSubquery($depDepo) . "
            LEFT JOIN (
                SELECT sd.idcodgusto,
                    CASE
                        WHEN sc.tipo_movimiento = 'venta' THEN sc.iddepod
                        WHEN sc.tipo_movimiento = 'devolucion_venta' THEN sc.iddepoh
                    END AS iddepo,
                    SUM(
                        CASE
                            WHEN sc.tipo_movimiento = 'venta' THEN sd.canti
                            WHEN sc.tipo_movimiento = 'devolucion_venta' THEN -sd.canti
                            ELSE 0
                        END
                    ) AS total_vendido
                FROM stockdet sd
                INNER JOIN stockcab sc ON sc.idcabstock = sd.idstockcab
                WHERE sd.idcodgusto > 0
                  AND sc.fecha >= :desde
                  AND sc.fecha < DATE_ADD(:hasta, INTERVAL 1 DAY)
                  AND sc.tipo_movimiento IN ('venta', 'devolucion_venta')
                GROUP BY sd.idcodgusto, iddepo
            ) cv ON cv.idcodgusto = g.idcodgusto AND cv.iddepo = dep.iddepo
            LEFT JOIN (
                SELECT sd.idcodgusto,
                    SUM(
                        CASE
                            WHEN sc.tipo_movimiento = 'venta' THEN sd.canti
                            WHEN sc.tipo_movimiento = 'devolucion_venta' THEN -sd.canti
                            ELSE 0
                        END
                    ) AS total_vendido
                FROM stockdet sd
                INNER JOIN stockcab sc ON sc.idcabstock = sd.idstockcab
                WHERE sd.idcodgusto > 0
                  AND sc.fecha >= :desde2
                  AND sc.fecha < DATE_ADD(:hasta2, INTERVAL 1 DAY)
                  AND sc.tipo_movimiento IN ('venta', 'devolucion_venta')
                  " . ($iddepo ? "AND ((sc.tipo_movimiento = 'venta' AND sc.iddepod = :vd) OR (sc.tipo_movimiento = 'devolucion_venta' AND sc.iddepoh = :vd))" : '') . "
                GROUP BY sd.idcodgusto
            ) cvt ON cvt.idcodgusto = g.idcodgusto
            " . self::whereSql($where, $stockWhere) . "
            ORDER BY p.produ ASC, g.nomgusto ASC, dep.nomdepo ASC
            LIMIT {$offset}, {$limit}
        ";
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function contarStock(string $q = '', int $codepar = 0, string $stockFilter = '', int $codrub = 0, int $codsub = 0, string $codprove = '', ?int $iddepo = null, string $desde = '', string $hasta = '', ?string $enweb = null): int
    {
        [$where, $stockWhere, $depDepo, $params] = $this->stockFilterParts($q, $codepar, $stockFilter, $codrub, $codsub, $codprove, $iddepo, $desde, $hasta, $enweb);

        $sql = "
            SELECT COUNT(*)
            FROM producto p
            INNER JOIN gustos g ON g.idprodu = p.idprodu
            " . $this->depSubquery($depDepo) . "
            " . self::whereSql($where, $stockWhere) . "
        ";
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    private static function whereSql(array $where, string $stockWhere): string
    {
        $conds = $where;
        $extra = trim($stockWhere);
        if ($extra !== '') {
            $conds[] = (string)preg_replace('/^\s*AND\s+/i', '', $extra);
        }
        return $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
    }

    private function stockFilterParts(string $q, int $codepar, string $stockFilter, int $codrub, int $codsub, string $codprove, ?int $iddepo, string $desde, string $hasta, ?string $enweb = null): array
    {
        $params = [];
        $where = [];
        if ($enweb === '1') {
            $where[] = 'p.enweb = 1';
        } elseif ($enweb === '0') {
            $where[] = '(p.enweb = 0 OR p.enweb IS NULL)';
        }

        if ($q !== '') {
            $where[] = '(p.produ LIKE :like OR p.codprodu LIKE :like OR p.codprodup LIKE :like OR g.nomgusto LIKE :like2 OR g.codscan LIKE :like3)';
            $params[':like'] = '%' . $q . '%';
            $params[':like2'] = '%' . $q . '%';
            $params[':like3'] = '%' . $q . '%';
        }
        if ($codepar > 0) {
            $where[] = 'p.codepar = :codepar';
            $params[':codepar'] = $codepar;
        }
        if ($codrub > 0) {
            $where[] = 'p.codrub = :codrub';
            $params[':codrub'] = $codrub;
        }
        if ($codsub > 0) {
            $where[] = 'p.codsub = :codsub';
            $params[':codsub'] = $codsub;
        }
        if ($codprove !== '') {
            $where[] = 'p.codprove = :codprove';
            $params[':codprove'] = $codprove;
        }

        $stockWhere = '';
        if ($stockFilter === 'sin_stock') {
            $stockWhere = 'AND dep.idcodgusto IS NULL';
        } elseif ($stockFilter === 'bajo_stock') {
            $stockWhere = 'AND dep.stock > 0 AND dep.stock <= 5';
        } elseif ($stockFilter === 'con_stock') {
            $stockWhere = 'AND dep.stock > 0';
        }

        $depDepo = '';
        if ($iddepo) {
            $depDepo = 'AND ds.iddepo = :iddepo';
            $params[':iddepo'] = $iddepo;
        }

        return [$where, $stockWhere, $depDepo, $params];
    }

    private function depSubquery(string $depDepo): string
    {
        return "
            LEFT JOIN (
                SELECT ds.idcodgusto, ds.iddepo, d.nomdepo, ds.neto AS stock
                FROM (
                    SELECT t.idcodgusto, t.iddepo, SUM(t.neto) AS neto
                    FROM (
                        SELECT sd.idcodgusto, sc.iddepoh AS iddepo, SUM(sd.canti) AS neto
                        FROM stockdet sd
                        INNER JOIN stockcab sc ON sc.idcabstock = sd.idstockcab
                        WHERE sc.iddepoh IS NOT NULL AND sd.idcodgusto > 0
                        GROUP BY sd.idcodgusto, sc.iddepoh
                        UNION ALL
                        SELECT sd.idcodgusto, sc.iddepod AS iddepo, -SUM(sd.canti) AS neto
                        FROM stockdet sd
                        INNER JOIN stockcab sc ON sc.idcabstock = sd.idstockcab
                        WHERE sc.iddepod IS NOT NULL AND sd.idcodgusto > 0
                        GROUP BY sd.idcodgusto, sc.iddepod
                    ) t
                    GROUP BY t.idcodgusto, t.iddepo
                ) ds
                INNER JOIN deposito d ON d.iddepo = ds.iddepo AND d.marca = 2
                WHERE ds.neto > 0 {$depDepo}
            ) dep ON dep.idcodgusto = g.idcodgusto
        ";
    }

    public function productoDetalle(int $idprodu): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT p.*, d.nomdepar
            FROM producto p
            LEFT JOIN departa d ON d.codepar = p.codepar
            WHERE p.idprodu = :id LIMIT 1
        ');
        $st->execute([':id' => $idprodu]);
        return $st->fetch() ?: null;
    }

    public function variantesConStock(int $idprodu): array
    {
        $st = Db::pdo()->prepare('
            SELECT MIN(g.idcodgusto) AS idcodgusto, g.nomgusto, g.codscan, g.stockact
            FROM gustos g
            WHERE g.idprodu = :id AND g.discont = 0
            GROUP BY g.nomgusto, g.codscan, g.stockact
            ORDER BY g.nomgusto ASC
        ');
        $st->execute([':id' => $idprodu]);
        return $st->fetchAll();
    }

    public function stockPorDeposito(?int $idprodu = null, ?int $idcodgusto = null): array
    {
        $params = [];
        $where = [];
        if ($idprodu) {
            $where[] = 's.idprodu = :idp';
            $params[':idp'] = $idprodu;
        }
        if ($idcodgusto) {
            $where[] = 's.idcodgusto = :idg';
            $params[':idg'] = $idcodgusto;
        }
        $sql = '
            SELECT s.*, d.nomdepo, p.produ, g.nomgusto
            FROM stock s
            INNER JOIN deposito d ON d.iddepo = s.iddepo AND d.marca = 2
            LEFT JOIN producto p ON p.idprodu = s.idprodu
            LEFT JOIN gustos g ON g.idcodgusto = s.idcodgusto
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY d.nomdepo ASC';
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function depositos(): array
    {
        $st = Db::pdo()->query('SELECT iddepo, nomdepo, marca FROM deposito ORDER BY nomdepo ASC');
        return $st->fetchAll();
    }

    public function depositoById(int $iddepo): ?array
    {
        $st = Db::pdo()->prepare('SELECT iddepo, nomdepo, marca FROM deposito WHERE iddepo = :id LIMIT 1');
        $st->execute([':id' => $iddepo]);
        $row = $st->fetch();
        return is_array($row) ? $row : null;
    }

    public function requiereAutorizacionAjuste(int $iddepodesde, int $iddepohasta, string $rolUsuario): bool
    {
        if ($rolUsuario === 'superadmin' || $iddepodesde <= 0) {
            return false;
        }

        $depoDesde = $this->depositoById($iddepodesde);
        if (!$depoDesde) {
            return false;
        }

        $desdeMarca = (int)($depoDesde['marca'] ?? 0);
        $depoHasta = $iddepohasta > 0 ? $this->depositoById($iddepohasta) : null;
        $hastaMarca = $depoHasta ? (int)($depoHasta['marca'] ?? 0) : null;

        // Regla operativa:
        // - De marca 2 a marca 2: no pide autorizacion.
        // - De cualquier marca a marca 2: no pide autorizacion.
        // - De marca 2 a cualquier marca distinta de 2 (o egreso sin destino): pide autorizacion.
        return $desdeMarca === 2 && $hastaMarca !== 2;
    }

    public function crearSolicitudAjuste(
        int $idprodu,
        ?int $idcodgusto,
        int $iddepodesde,
        int $iddepohasta,
        int $cantidad,
        string $motivo,
        int $requestedBy,
        string $requestedByNombre
    ): int {
        $this->ensureAjustesAuthTable();
        $st = Db::pdo()->prepare('INSERT INTO stock_ajuste_autorizaciones
            (idprodu, idcodgusto, iddepodesde, iddepohasta, cantidad, motivo, requested_by, requested_by_nombre, status, created_at, updated_at)
            VALUES (:p, :g, :dd, :dh, :c, :m, :rb, :rn, :s, NOW(), NOW())');
        $st->execute([
            ':p' => $idprodu,
            ':g' => $idcodgusto ?: null,
            ':dd' => $iddepodesde > 0 ? $iddepodesde : null,
            ':dh' => $iddepohasta > 0 ? $iddepohasta : null,
            ':c' => max(1, $cantidad),
            ':m' => $motivo,
            ':rb' => $requestedBy,
            ':rn' => mb_substr($requestedByNombre, 0, 120),
            ':s' => 'pendiente',
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function countSolicitudesAjustePendientes(): int
    {
        $this->ensureAjustesAuthTable();
        $st = Db::pdo()->query("SELECT COUNT(*) FROM stock_ajuste_autorizaciones WHERE status = 'pendiente'");
        return (int)$st->fetchColumn();
    }

    public function solicitudesAjustePendientes(int $limit = 50): array
    {
        $this->ensureAjustesAuthTable();
        $limit = max(1, min(200, $limit));
        $sql = "
            SELECT a.*,
                   p.produ,
                   g.nomgusto,
                   dd.marca AS depo_desde_marca,
                   dd.nomdepo AS depo_desde_nombre,
                   dh.marca AS depo_hasta_marca,
                   dh.nomdepo AS depo_hasta_nombre
            FROM stock_ajuste_autorizaciones a
            INNER JOIN producto p ON p.idprodu = a.idprodu
            LEFT JOIN gustos g ON g.idcodgusto = a.idcodgusto
            LEFT JOIN deposito dd ON dd.iddepo = a.iddepodesde
            LEFT JOIN deposito dh ON dh.iddepo = a.iddepohasta
            WHERE a.status = 'pendiente'
            ORDER BY a.created_at ASC
            LIMIT {$limit}
        ";
        $st = Db::pdo()->query($sql);
        return $st->fetchAll();
    }

    public function solicitudesAjustePorSolicitante(int $adminUserId, int $limit = 50): array
    {
        $this->ensureAjustesAuthTable();
        $limit = max(1, min(200, $limit));
        $sql = "
            SELECT a.*,
                   p.produ,
                   g.nomgusto,
                   dd.marca AS depo_desde_marca,
                   dd.nomdepo AS depo_desde_nombre,
                   dh.marca AS depo_hasta_marca,
                   dh.nomdepo AS depo_hasta_nombre
            FROM stock_ajuste_autorizaciones a
            INNER JOIN producto p ON p.idprodu = a.idprodu
            LEFT JOIN gustos g ON g.idcodgusto = a.idcodgusto
            LEFT JOIN deposito dd ON dd.iddepo = a.iddepodesde
            LEFT JOIN deposito dh ON dh.iddepo = a.iddepohasta
            WHERE a.requested_by = :uid
            ORDER BY a.id DESC
            LIMIT {$limit}
        ";
        $st = Db::pdo()->prepare($sql);
        $st->execute([':uid' => $adminUserId]);
        return $st->fetchAll();
    }

    public function aprobarSolicitudAjuste(int $solicitudId, int $adminId, string $adminNombre): int
    {
        $this->ensureAjustesAuthTable();

        $claim = Db::pdo()->prepare("UPDATE stock_ajuste_autorizaciones
            SET status = 'procesando', decided_by = :ab, decided_by_nombre = :an, decided_at = NOW(), updated_at = NOW()
            WHERE id = :id AND status = 'pendiente' AND requested_by <> :ab2 LIMIT 1");
        $claim->execute([
            ':ab' => $adminId,
            ':an' => mb_substr($adminNombre, 0, 120),
            ':ab2' => $adminId,
            ':id' => $solicitudId,
        ]);
        if ($claim->rowCount() <= 0) {
            throw new \RuntimeException('La solicitud ya fue procesada por otro usuario.');
        }

        $st = Db::pdo()->prepare('SELECT * FROM stock_ajuste_autorizaciones WHERE id = :id LIMIT 1');
        $st->execute([':id' => $solicitudId]);
        $row = $st->fetch();
        if (!$row) {
            throw new \RuntimeException('Solicitud no encontrada.');
        }

        try {
            $cabId = $this->registrarAjuste(
                (int)$row['idprodu'],
                !empty($row['idcodgusto']) ? (int)$row['idcodgusto'] : null,
                (int)($row['iddepodesde'] ?? 0),
                (int)($row['iddepohasta'] ?? 0),
                (int)$row['cantidad'],
                (string)$row['motivo'],
                (int)$row['requested_by'],
                'ajuste_autorizado'
            );
            $ok = Db::pdo()->prepare("UPDATE stock_ajuste_autorizaciones
                SET status = 'aprobada', stockcab_id = :cab, updated_at = NOW()
                WHERE id = :id AND status = 'procesando' LIMIT 1");
            $ok->execute([':cab' => $cabId, ':id' => $solicitudId]);
            return $cabId;
        } catch (\Throwable $e) {
            $rollback = Db::pdo()->prepare("UPDATE stock_ajuste_autorizaciones
                SET status = 'pendiente', decided_by = NULL, decided_by_nombre = NULL, decided_at = NULL, updated_at = NOW()
                WHERE id = :id AND status = 'procesando' LIMIT 1");
            $rollback->execute([':id' => $solicitudId]);
            throw $e;
        }
    }

    public function rechazarSolicitudAjuste(int $solicitudId, int $adminId, string $adminNombre, string $nota): void
    {
        $this->ensureAjustesAuthTable();
        $st = Db::pdo()->prepare("UPDATE stock_ajuste_autorizaciones
            SET status = 'rechazada', decided_by = :ab, decided_by_nombre = :an, decided_at = NOW(), rejection_note = :n, updated_at = NOW()
            WHERE id = :id AND status = 'pendiente' AND requested_by <> :ab2 LIMIT 1");
        $st->execute([
            ':ab' => $adminId,
            ':an' => mb_substr($adminNombre, 0, 120),
            ':n' => mb_substr($nota, 0, 255),
            ':ab2' => $adminId,
            ':id' => $solicitudId,
        ]);
        if ($st->rowCount() <= 0) {
            throw new \RuntimeException('La solicitud ya no está pendiente.');
        }
    }

    public function grillaDepositos(): array
    {
        $st = Db::pdo()->query('SELECT iddepo, nomdepo FROM deposito WHERE marca = 2 ORDER BY nomdepo ASC');
        return $st->fetchAll();
    }

    public function departamentos(): array
    {
        $st = Db::pdo()->query('SELECT codepar, nomdepar FROM departa ORDER BY nomdepar ASC');
        return $st->fetchAll();
    }

    public function movimientos(int $idprodu, ?int $idcodgusto = null, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $params = [':idp' => $idprodu];
        $where = 'sd.idprodu = :idp';

        if ($idcodgusto) {
            $where .= ' AND sd.idcodgusto = :idg';
            $params[':idg'] = $idcodgusto;
        }
        $where .= " AND (sc.tipo_movimiento IS NULL OR sc.tipo_movimiento NOT IN ('compra', 'devolucion_compra', 'venta', 'devolucion_venta'))";

        $sql = "
            SELECT sd.*, sc.fecha AS mov_fecha, sc.iddepoh, sc.iddepod,
                   dh.nomdepo AS nom_depoh, dd.nomdepo AS nom_depod,
                   g.nomgusto, g.codscan
            FROM stockdet sd
            INNER JOIN stockcab sc ON sd.idstockcab = sc.idcabstock
            LEFT JOIN deposito dh ON dh.iddepo = sc.iddepoh
            LEFT JOIN deposito dd ON dd.iddepo = sc.iddepod
            LEFT JOIN gustos g ON g.idcodgusto = sd.idcodgusto
            WHERE {$where}
            ORDER BY sc.fecha DESC, sd.idstockcab DESC
            LIMIT {$limit}
        ";
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function comprasVentasPorDeposito(int $idprodu, ?int $idcodgusto = null): array
    {
        $params = [':idp' => $idprodu];
        $gustoWhere = '';
        if ($idcodgusto) {
            $gustoWhere = 'AND sd.idcodgusto = :idg';
            $params[':idg'] = $idcodgusto;
        }

        $sql = "
            SELECT
                d.iddepo,
                d.nomdepo,
                sd.idprodu,
                sd.idcodgusto,
                g.nomgusto,
                COALESCE(SUM(
                    CASE
                        WHEN sc.iddepod = 7 AND sc.iddepoh = d.iddepo THEN sd.canti
                        WHEN sc.tipo_movimiento = 'compra' AND sc.iddepoh IS NOT NULL AND sc.iddepod IS NULL AND d.iddepo = sc.iddepoh THEN sd.canti
                        WHEN sc.iddepoh = 7 AND sc.iddepod = d.iddepo THEN -sd.canti
                        WHEN sc.tipo_movimiento = 'devolucion_compra' AND sc.iddepoh IS NULL AND sc.iddepod IS NOT NULL AND d.iddepo = sc.iddepod THEN -sd.canti
                        ELSE 0
                    END
                ), 0) AS unidades_compradas,
                COALESCE(SUM(
                    CASE
                        WHEN sc.iddepod = d.iddepo AND sc.iddepoh = 6 THEN sd.canti
                        WHEN sc.tipo_movimiento = 'venta' AND sc.iddepoh IS NULL AND sc.iddepod IS NOT NULL AND d.iddepo = sc.iddepod THEN sd.canti
                        WHEN sc.iddepod = 6 AND sc.iddepoh = d.iddepo THEN -sd.canti
                        WHEN sc.tipo_movimiento = 'devolucion_venta' AND sc.iddepoh IS NOT NULL AND sc.iddepod IS NULL AND d.iddepo = sc.iddepoh THEN -sd.canti
                        ELSE 0
                    END
                ), 0) AS unidades_vendidas,
                COALESCE(SUM(
                    CASE
                        WHEN sc.iddepoh = d.iddepo THEN sd.canti
                        WHEN sc.iddepod = d.iddepo THEN -sd.canti
                        ELSE 0
                    END
                ), 0) AS stock_ledger
            FROM stockdet sd
            INNER JOIN stockcab sc ON sc.idcabstock = sd.idstockcab
            INNER JOIN deposito d ON d.iddepo IN (sc.iddepoh, sc.iddepod)
            LEFT JOIN gustos g ON g.idcodgusto = sd.idcodgusto
            WHERE d.marca = 2
              AND sd.idprodu = :idp
              {$gustoWhere}
            GROUP BY d.iddepo, d.nomdepo, sd.idprodu, sd.idcodgusto, g.nomgusto
            ORDER BY d.nomdepo
        ";
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function movimientosPorTipo(int $idprodu, ?int $idcodgusto = null, string $tipo = '', int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $params = [':idp' => $idprodu];
        $where = 'sd.idprodu = :idp';

        if ($idcodgusto) {
            $where .= ' AND sd.idcodgusto = :idg';
            $params[':idg'] = $idcodgusto;
        }

        if ($tipo === 'transferencia') {
            $where .= ' AND sc.iddepoh IS NOT NULL AND sc.iddepod IS NOT NULL';
        } elseif ($tipo === 'compra') {
            $where .= " AND sc.tipo_movimiento IN ('compra', 'devolucion_compra')";
        } elseif ($tipo === 'venta') {
            $where .= " AND sc.tipo_movimiento IN ('venta', 'devolucion_venta')";
        } elseif ($tipo === '') {
            $where .= ' AND sc.tipo_movimiento IS NULL';
        } else {
            $where .= ' AND sc.tipo_movimiento = :tipo';
            $params[':tipo'] = $tipo;
        }

        $sql = "
            SELECT sd.*, sc.fecha AS mov_fecha, sc.iddepoh, sc.iddepod, sc.notas, sc.tipo_movimiento,
                   dh.nomdepo AS nom_depoh, dd.nomdepo AS nom_depod,
                   g.nomgusto, g.codscan
            FROM stockdet sd
            INNER JOIN stockcab sc ON sd.idstockcab = sc.idcabstock
            LEFT JOIN deposito dh ON dh.iddepo = sc.iddepoh
            LEFT JOIN deposito dd ON dd.iddepo = sc.iddepod
            LEFT JOIN gustos g ON g.idcodgusto = sd.idcodgusto
            WHERE {$where}
            ORDER BY sc.fecha DESC, sd.idstockcab DESC
            LIMIT {$limit}
        ";
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    private static ?array $productoColumns = null;

    private function productoColumns(): array
    {
        if (self::$productoColumns !== null) {
            return self::$productoColumns;
        }
        try {
            $rows = Db::pdo()->query('SHOW COLUMNS FROM producto')->fetchAll();
            self::$productoColumns = array_column($rows, 'Field');
        } catch (\Throwable $e) {
            self::$productoColumns = [];
        }
        return self::$productoColumns;
    }

    public function searchProducts(string $q, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $q = trim($q);
        if ($q === '') return [];

        $hasCodbarra = in_array('codbarra', $this->productoColumns(), true);
        $like = '%' . $q . '%';
        $params = [
            ':likeProdu' => $like,
            ':likeCod' => $like,
            ':likeProv' => $like,
            ':likeBarcode' => $like,
            ':exactCod' => $q,
            ':exactProv' => $q,
            ':exactBarcode' => $q,
            ':exactVariant' => $q,
            ':exactNombre' => $q,
        ];
        if ($hasCodbarra) {
            $params[':exactCodbarra'] = $q;
        }
        $where = ['p.produ LIKE :likeProdu', 'p.codprodu LIKE :likeCod', 'p.codprodup LIKE :likeProv'];
        $exactRank = ['p.codprodu = :exactCod', 'p.codprodup = :exactProv'];
        if (ctype_digit($q)) {
            $params[':exactId'] = (int)$q;
            array_unshift($where, 'p.idprodu = :exactId');
            array_unshift($exactRank, 'p.idprodu = :exactId');
        }
        if ($hasCodbarra) {
            $params[':likeCodbarra'] = $like;
            $params[':exactCodbarra'] = $q;
            $where[] = 'p.codbarra LIKE :likeCodbarra';
            $exactRank[] = 'p.codbarra = :exactCodbarra';
        }

        $st = Db::pdo()->prepare('
            SELECT DISTINCT p.idprodu, p.codprodu, p.codbarra, p.produ, p.codprodup, p.stocact, p.precomp, p.precio,
                (SELECT g2.idcodgusto
                 FROM gustos g2
                 WHERE g2.idprodu = p.idprodu AND g2.codscan = :exactVariant AND g2.discont = 0
                 ORDER BY g2.idcodgusto ASC LIMIT 1) AS matched_variant_id,
                (SELECT g3.idcodgusto
                 FROM gustos g3
                 WHERE g3.idprodu = p.idprodu AND g3.discont = 0
                 ORDER BY g3.idcodgusto ASC LIMIT 1) AS first_variant_id,
                (SELECT g4.idcodgusto
                 FROM gustos g4
                 WHERE g4.idprodu = p.idprodu AND g4.codscan = :exactBarcode AND g4.discont = 0
                 ORDER BY g4.idcodgusto ASC LIMIT 1) AS barcode_variant_id
            FROM producto p
            WHERE ' . implode(' OR ', $where) . '
                OR EXISTS (SELECT 1 FROM gustos g WHERE g.idprodu = p.idprodu AND g.codscan LIKE :likeBarcode)
            ORDER BY CASE WHEN ' . implode(' OR ', $exactRank) . ' THEN 0
                WHEN EXISTS (SELECT 1 FROM gustos g3 WHERE g3.idprodu = p.idprodu AND g3.codscan = :exactBarcode) THEN 1
                WHEN p.produ = :exactNombre THEN 2 ELSE 3 END,
                p.produ ASC LIMIT ' . $limit
        );
        try {
            $st->execute($params);
        } catch (\Throwable $e) {
            error_log('StockRepo::searchProducts main query error: ' . $e->getMessage() . ' | Query: ' . $st->queryString . ' | Params: ' . json_encode($params));
            throw $e;
        }
        $products = $st->fetchAll();

        // Buscar variante exacta por codscan (idéntico a FacturaRepo)
        if (ctype_digit($q) || preg_match('/^\d{8,13}$/', $q)) {
            try {
                $st2 = Db::pdo()->prepare('
                    SELECT g.idcodgusto, g.idprodu, g.nomgusto, g.codscan,
                           p.idprodu, p.codprodu, p.produ, p.precio, p.precio1, p.precomp, p.codprodup, p.enweb, p.stocact,
                           i.codivaprodu, i.tiva
                    FROM gustos g
                    INNER JOIN producto p ON p.idprodu = g.idprodu
                    LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
                    WHERE g.codscan = :c
                    GROUP BY p.idprodu
                    LIMIT 1
                ');
                $st2->execute([':c' => $q]);
                $byCode = $st2->fetch();
                if ($byCode) {
                    $matchedVariant = [
                        'idcodgusto' => (int)$byCode['idcodgusto'],
                        'nomgusto' => $byCode['nomgusto'],
                        'codscan' => $q,
                    ];
                    $exists = false;
                    foreach ($products as $pr) {
                        if ((int)$pr['idprodu'] === (int)$byCode['idprodu']) { $exists = true; break; }
                    }
                    if (!$exists) array_unshift($products, $byCode);
                }
            } catch (\Throwable $e) {
                error_log('StockRepo searchProducts exact barcode error: ' . $e->getMessage());
            }
        }

        // Agregar variantes a cada producto
        foreach ($products as $idx => $pr) {
            $idprodu = (int)$pr['idprodu'];
            $st3 = Db::pdo()->prepare('
                SELECT idcodgusto, nomgusto, codscan, stockact
                FROM gustos
                WHERE idprodu = :id AND discont = 0
                GROUP BY idcodgusto, nomgusto, codscan, stockact
                ORDER BY nomgusto ASC
            ');
            $st3->execute([':id' => $idprodu]);
            $products[$idx]['variants'] = $st3->fetchAll();

            $products[$idx]['stock_total'] = (int)($pr['stocact'] ?? 0);
        }

        return $products;
    }

    public function variantesPorProducto(int $idprodu): array
    {
        $st = Db::pdo()->prepare('
            SELECT MIN(idcodgusto) AS idcodgusto, nomgusto, codscan, stockact
            FROM gustos
            WHERE idprodu = :id AND discont = 0
            GROUP BY nomgusto, codscan, stockact
            ORDER BY nomgusto ASC
        ');
        $st->execute([':id' => $idprodu]);
        return $st->fetchAll();
    }

    /**
     * Obtiene el stock actual de un producto/variante en un depósito específico.
     * Si idcodgusto es 0 o null, suma el stock de todas las variantes del producto en ese depósito.
     */
    public function stockEnDeposito(int $idprodu, int $iddepo, int $idcodgusto = 0): int
    {
        if ($idcodgusto > 0) {
            $st = Db::pdo()->prepare('
                SELECT COALESCE(SUM(stock), 0) FROM stock
                WHERE idprodu = :idprodu AND iddepo = :iddepo AND idcodgusto = :idcodgusto
            ');
            $st->execute([':idprodu' => $idprodu, ':iddepo' => $iddepo, ':idcodgusto' => $idcodgusto]);
        } else {
            $st = Db::pdo()->prepare('
                SELECT COALESCE(SUM(stock), 0) FROM stock
                WHERE idprodu = :idprodu AND iddepo = :iddepo
            ');
            $st->execute([':idprodu' => $idprodu, ':iddepo' => $iddepo]);
        }
        return (int)$st->fetchColumn();
    }

    public function registrarAjuste(
        int $idprodu,
        ?int $idcodgusto,
        int $iddepodesde,
        int $iddepohasta,
        int $cantidad,
        string $motivo,
        int $adminUserId,
        string $tipoMovimiento = 'ajuste',
        ?int $grupoId = null
    ): int {
        $pdo = Db::pdo();
        $hasGrupo = $this->ensureGrupoColumn();
        $pdo->beginTransaction();
        try {
            // VFP convention:
            // iddepoh = deposit receiving goods (adds to stock)
            // iddepod = deposit sending goods (subtracts from stock)
            // canti is always positive
            $iddepoh = $iddepohasta > 0 ? $iddepohasta : null;
            $iddepod = $iddepodesde > 0 ? $iddepodesde : null;
            $canti = max(1, $cantidad);

            // 1. Insert stockcab
            if ($hasGrupo) {
                $st = $pdo->prepare('
                    INSERT INTO stockcab (iddepoh, iddepod, fecha, notas, tipo_movimiento, grupo_id)
                    VALUES (:depoh, :depod, CURDATE(), :notas, :tipo, :grupo)
                ');
                $st->execute([
                    ':depoh' => $iddepoh,
                    ':depod' => $iddepod,
                    ':notas' => $motivo,
                    ':tipo' => $tipoMovimiento,
                    ':grupo' => $grupoId,
                ]);
            } else {
                $st = $pdo->prepare('
                    INSERT INTO stockcab (iddepoh, iddepod, fecha, notas, tipo_movimiento)
                    VALUES (:depoh, :depod, CURDATE(), :notas, :tipo)
                ');
                $st->execute([
                    ':depoh' => $iddepoh,
                    ':depod' => $iddepod,
                    ':notas' => $motivo,
                    ':tipo' => $tipoMovimiento,
                ]);
            }
            $cabId = (int)$pdo->lastInsertId();

            // 2. Insert stockdet
            $st = $pdo->prepare('
                INSERT INTO stockdet (idstockcab, idprodu, idcodgusto, canti)
                VALUES (:cab, :prod, :gusto, :cant)
            ');
            $st->execute([
                ':cab' => $cabId,
                ':prod' => $idprodu,
                ':gusto' => $idcodgusto ?: null,
                ':cant' => $canti,
            ]);

            // 3. Update stock table — subtract from origin (desde)
            if ($iddepod) {
                $this->updateStockDeposit($idprodu, $idcodgusto, $iddepod, -$canti);
            }
            // 4. Update stock table — add to destination (hasta)
            if ($iddepoh) {
                $this->updateStockDeposit($idprodu, $idcodgusto, $iddepoh, $canti);
            }

            // 5. Recalculate producto.stocact
            $st = $pdo->prepare('SELECT COALESCE(SUM(stock), 0) FROM stock WHERE idprodu = :prod');
            $st->execute([':prod' => $idprodu]);
            $totalStock = (int)$st->fetchColumn();
            $pdo->prepare('UPDATE producto SET stocact = :s WHERE idprodu = :id LIMIT 1')
                ->execute([':s' => $totalStock, ':id' => $idprodu]);

            // 6. Update gustos.stockact if variant
            if ($idcodgusto) {
                $st = $pdo->prepare('SELECT COALESCE(SUM(stock), 0) FROM stock WHERE idcodgusto = :g');
                $st->execute([':g' => $idcodgusto]);
                $variantStock = (int)$st->fetchColumn();
                $pdo->prepare('UPDATE gustos SET stockact = :s WHERE idcodgusto = :id LIMIT 1')
                    ->execute([':s' => $variantStock, ':id' => $idcodgusto]);
            }

            $pdo->commit();
            return $cabId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static ?bool $grupoColReady = null;

    /** Crea grupo_id en stockcab si falta. Devuelve true si la columna existe. */
    public function ensureGrupoColumn(): bool
    {
        if (self::$grupoColReady !== null) {
            return self::$grupoColReady;
        }
        self::$grupoColReady = true;
        try {
            $cols = array_column(Db::pdo()->query('SHOW COLUMNS FROM stockcab')->fetchAll(), 'Field');
            if (!in_array('grupo_id', $cols, true)) {
                Db::pdo()->exec('ALTER TABLE stockcab ADD COLUMN grupo_id INT UNSIGNED NULL DEFAULT NULL, ADD KEY idx_grupo_id (grupo_id)');
            }
        } catch (\Throwable $e) {
            error_log('StockRepo::ensureGrupoColumn: ' . $e->getMessage());
            self::$grupoColReady = false;
        }
        return self::$grupoColReady;
    }

    /**
     * Registra varios productos como UN grupo de ajuste (una registración).
     * @param array<int, array{idprodu:int, idcodgusto:?int, cantidad:int}> $items
     * @return array<int> ids de cabecera generados
     */
    public function registrarAjusteLote(array $items, int $iddepodesde, int $iddepohasta, string $motivo, int $adminUserId, string $tipoMovimiento = 'ajuste'): array
    {
        $ids = [];
        $grupo = null;
        foreach ($items as $it) {
            $id = $this->registrarAjuste(
                (int)$it['idprodu'],
                $it['idcodgusto'] !== null ? (int)$it['idcodgusto'] : null,
                $iddepodesde,
                $iddepohasta,
                max(1, (int)$it['cantidad']),
                $motivo,
                $adminUserId,
                $tipoMovimiento,
                $grupo
            );
            if ($grupo === null) {
                $grupo = $id;
                try {
                    Db::pdo()->prepare('UPDATE stockcab SET grupo_id = :g WHERE idcabstock = :id LIMIT 1')
                        ->execute([':g' => $grupo, ':id' => $id]);
                } catch (\Throwable $e) {
                    error_log('StockRepo::registrarAjusteLote grupo: ' . $e->getMessage());
                }
            }
            $ids[] = $id;
        }
        return $ids;
    }

    /**
     * Agrupa cabeceras sueltas bajo un mismo grupo (usa el id menor).
     * Para movimientos creados sin lote (facturas de compra, remitos viejos).
     */
    public function asegurarGrupo(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return 0;
        }
        $grupo = min($ids);
        if (!$this->ensureGrupoColumn()) {
            return $grupo;
        }
        try {
            $in = implode(',', $ids);
            Db::pdo()->prepare("UPDATE stockcab SET grupo_id = :g WHERE idcabstock IN ($in) AND grupo_id IS NULL")
                ->execute([':g' => $grupo]);
        } catch (\Throwable $e) {
            error_log('StockRepo::asegurarGrupo: ' . $e->getMessage());
        }
        return $grupo;
    }

    /** Etiquetas cortas de tipo de movimiento. */
    public static function tiposMovimiento(): array
    {
        return [
            'venta' => 'Venta',
            'devolucion_venta' => 'N.C. ventas',
            'compra' => 'Compra',
            'devolucion_compra' => 'Dev. compra',
            'ajuste' => 'Ajuste',
        ];
    }

    public static function etiquetaTipo(string $tipo): string
    {
        $t = strtolower(trim($tipo));
        return self::tiposMovimiento()[$t] ?? ($t !== '' ? ucfirst($t) : 'Movimiento');
    }

    /**
     * Etiqueta de depósito: si no hay depósito muestra el origen/destino
     * comercial según el tipo (Ventas / Compras).
     */
    public static function etiquetaDeposito(?string $nombre, string $tipo, string $lado): string
    {
        $n = trim((string)$nombre);
        if ($n !== '') {
            return $n;
        }
        $t = strtolower(trim($tipo));
        if ($lado === 'hasta') {
            if ($t === 'venta') {
                return 'Ventas';
            }
            if ($t === 'devolucion_compra') {
                return 'Compras';
            }
        } else {
            if ($t === 'compra') {
                return 'Compras';
            }
            if ($t === 'devolucion_venta') {
                return 'Ventas';
            }
        }
        return '—';
    }

    /** Grupo efectivo de una cabecera (viejas sin grupo = su propio id). */
    public function grupoDe(int $idcabstock): int
    {
        try {
            $this->ensureGrupoColumn();
            $st = Db::pdo()->prepare('SELECT COALESCE(grupo_id, idcabstock) FROM stockcab WHERE idcabstock = :id LIMIT 1');
            $st->execute([':id' => $idcabstock]);
            $g = (int)$st->fetchColumn();
            return $g > 0 ? $g : $idcabstock;
        } catch (\Throwable $e) {
            return $idcabstock;
        }
    }

    /** @return array<int, array<string,mixed>> un renglón por grupo de ajuste */
    public function ajustesRecientes(int $limit = 30, string $desde = '', string $hasta = '', int $offset = 0, int $depDesde = 0, int $depHasta = 0): array
    {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $params = [];
        $where = $this->ajustesFiltrosWhere($desde, $hasta, $params);
        $having = $this->ajustesFiltrosHaving($depDesde, $depHasta, $params);
        $grupoExpr = $this->ensureGrupoColumn()
            ? 'COALESCE(sc.grupo_id, sc.idcabstock)'
            : 'sc.idcabstock';
        $st = Db::pdo()->prepare("
            SELECT {$grupoExpr} AS id,
                   GROUP_CONCAT(sc.idcabstock ORDER BY sc.idcabstock ASC) AS ids,
                   MAX(sc.fecha) AS fecha, MAX(sc.notas) AS motivo, MAX(sc.tipo_movimiento) AS tipo,
                   MAX(sc.iddepoh) AS iddepoh, MAX(sc.iddepod) AS iddepod,
                   MAX(dh.nomdepo) AS depo_hasta, MAX(dd.nomdepo) AS depo_desde,
                   COUNT(sd.idprodu) AS items, COALESCE(SUM(sd.canti), 0) AS unidades
            FROM stockcab sc
            LEFT JOIN stockdet sd ON sd.idstockcab = sc.idcabstock
            LEFT JOIN deposito dh ON dh.iddepo = sc.iddepoh
            LEFT JOIN deposito dd ON dd.iddepo = sc.iddepod
            WHERE 1 = 1{$where}
            GROUP BY {$grupoExpr}
            {$having}
            ORDER BY MAX(sc.idcabstock) DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $st->execute($params);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $r['tipo_label'] = self::etiquetaTipo((string)($r['tipo'] ?? ''));
            $r['depo_desde_label'] = self::etiquetaDeposito($r['depo_desde'] ?? null, (string)($r['tipo'] ?? ''), 'desde');
            $r['depo_hasta_label'] = self::etiquetaDeposito($r['depo_hasta'] ?? null, (string)($r['tipo'] ?? ''), 'hasta');
        }
        return $rows;
    }

    /** Cantidad total de grupos de ajuste para paginar el historial. */
    public function ajustesGruposCount(string $desde = '', string $hasta = '', int $depDesde = 0, int $depHasta = 0): int
    {
        $params = [];
        $where = $this->ajustesFiltrosWhere($desde, $hasta, $params);
        $having = $this->ajustesFiltrosHaving($depDesde, $depHasta, $params);
        $grupoExpr = $this->ensureGrupoColumn()
            ? 'COALESCE(sc.grupo_id, sc.idcabstock)'
            : 'sc.idcabstock';
        $st = Db::pdo()->prepare("
            SELECT COUNT(*) AS total
            FROM (
                SELECT {$grupoExpr} AS gid
                FROM stockcab sc
                WHERE 1 = 1{$where}
                GROUP BY {$grupoExpr}
                {$having}
            ) g
        ");
        $st->execute($params);
        $row = $st->fetch();
        return (int)($row['total'] ?? 0);
    }

    private function ajustesFiltrosWhere(string $desde, string $hasta, array &$params): string
    {
        $where = '';
        if ($desde !== '') {
            $where .= ' AND sc.fecha >= :desde';
            $params[':desde'] = $desde;
        }
        if ($hasta !== '') {
            $where .= ' AND sc.fecha < DATE_ADD(:hasta, INTERVAL 1 DAY)';
            $params[':hasta'] = $hasta;
        }
        return $where;
    }

    private function ajustesFiltrosHaving(int $depDesde, int $depHasta, array &$params): string
    {
        $having = '';
        if ($depDesde > 0) {
            $having .= ' HAVING SUM(sc.iddepod = :depd) > 0';
            $params[':depd'] = $depDesde;
        }
        if ($depHasta > 0) {
            $having .= ($having === '' ? ' HAVING ' : ' AND ') . 'SUM(sc.iddepoh = :deph) > 0';
            $params[':deph'] = $depHasta;
        }
        return $having;
    }

    /** Cabecera + detalle de un ajuste con nombres de producto/variante/depósito. */
    public function ajusteConDetalle(int $idcabstock): ?array
    {
        $pdo = Db::pdo();
        $cab = $pdo->prepare('
            SELECT sc.idcabstock AS id, sc.fecha, sc.notas AS motivo, sc.tipo_movimiento AS tipo,
                   sc.iddepoh, sc.iddepod,
                   dh.nomdepo AS depo_hasta, dd.nomdepo AS depo_desde
            FROM stockcab sc
            LEFT JOIN deposito dh ON dh.iddepo = sc.iddepoh
            LEFT JOIN deposito dd ON dd.iddepo = sc.iddepod
            WHERE sc.idcabstock = :id LIMIT 1
        ');
        $cab->execute([':id' => $idcabstock]);
        $cabRow = $cab->fetch();
        if (!$cabRow) {
            return null;
        }
        $det = $pdo->prepare('
            SELECT sd.idprodu, sd.idcodgusto, sd.canti,
                   p.produ, p.codprodu,
                   g.nomgusto, g.codscan
            FROM stockdet sd
            LEFT JOIN producto p ON p.idprodu = sd.idprodu
            LEFT JOIN gustos g ON g.idcodgusto = sd.idcodgusto
            WHERE sd.idstockcab = :id
            ORDER BY p.produ ASC
        ');
        $det->execute([':id' => $idcabstock]);
        $cabRow['items'] = $det->fetchAll();
        $cabRow['tipo_label'] = self::etiquetaTipo((string)($cabRow['tipo'] ?? ''));
        $cabRow['depo_desde_label'] = self::etiquetaDeposito($cabRow['depo_desde'] ?? null, (string)($cabRow['tipo'] ?? ''), 'desde');
        $cabRow['depo_hasta_label'] = self::etiquetaDeposito($cabRow['depo_hasta'] ?? null, (string)($cabRow['tipo'] ?? ''), 'hasta');
        return $cabRow;
    }

    /** Grupo completo (todas las cabeceras generadas en la misma registración) con ítems. */
    public function ajusteGrupoDetalle(int $grupoId): ?array
    {
        $pdo = Db::pdo();
        $hasGrupo = $this->ensureGrupoColumn();
        if ($hasGrupo) {
            $cab = $pdo->prepare('
                SELECT MIN(sc.idcabstock) AS id, MAX(sc.fecha) AS fecha, MAX(sc.notas) AS motivo,
                       MAX(sc.tipo_movimiento) AS tipo, MAX(sc.iddepoh) AS iddepoh, MAX(sc.iddepod) AS iddepod,
                       MAX(dh.nomdepo) AS depo_hasta, MAX(dd.nomdepo) AS depo_desde
                FROM stockcab sc
                LEFT JOIN deposito dh ON dh.iddepo = sc.iddepoh
                LEFT JOIN deposito dd ON dd.iddepo = sc.iddepod
                WHERE sc.idcabstock = :id OR sc.grupo_id = :id
            ');
            $cab->execute([':id' => $grupoId]);
            $det = $pdo->prepare('
                SELECT sd.idprodu, sd.idcodgusto, sd.canti,
                       p.produ, p.codprodu,
                       g.nomgusto, g.codscan
                FROM stockdet sd
                INNER JOIN stockcab sc ON sc.idcabstock = sd.idstockcab
                LEFT JOIN producto p ON p.idprodu = sd.idprodu
                LEFT JOIN gustos g ON g.idcodgusto = sd.idcodgusto
                WHERE sc.idcabstock = :id OR sc.grupo_id = :id
                ORDER BY p.produ ASC
            ');
            $det->execute([':id' => $grupoId]);
        } else {
            return $this->ajusteConDetalle($grupoId);
        }
        $cabRow = $cab->fetch();
        if (!$cabRow || (int)($cabRow['id'] ?? 0) <= 0) {
            return null;
        }
        $cabRow['id'] = $grupoId;
        $cabRow['items'] = $det->fetchAll();
        if (!$cabRow['items']) {
            return null;
        }
        $cabRow['tipo_label'] = self::etiquetaTipo((string)($cabRow['tipo'] ?? ''));
        $cabRow['depo_desde_label'] = self::etiquetaDeposito($cabRow['depo_desde'] ?? null, (string)($cabRow['tipo'] ?? ''), 'desde');
        $cabRow['depo_hasta_label'] = self::etiquetaDeposito($cabRow['depo_hasta'] ?? null, (string)($cabRow['tipo'] ?? ''), 'hasta');
        return $cabRow;
    }

    /**
     * Anula un grupo de ajuste generando el movimiento inverso (trazable).
     * Devuelve los ids de las cabeceras de reversión.
     * @return array<int>
     */
    public function anularAjuste(int $idcabstock, int $adminUserId): array
    {
        $grupo = $this->grupoDe($idcabstock);
        $aj = $this->ajusteGrupoDetalle($grupo);
        if (!$aj || empty($aj['items'])) {
            throw new \RuntimeException('Ajuste no encontrado.');
        }
        $desde = (int)($aj['iddepoh'] ?? 0);
        $hasta = (int)($aj['iddepod'] ?? 0);
        $motivo = 'Anulación ajuste #' . $grupo . ': ' . trim((string)($aj['motivo'] ?? ''));
        $lote = [];
        foreach ($aj['items'] as $it) {
            $lote[] = [
                'idprodu' => (int)$it['idprodu'],
                'idcodgusto' => $it['idcodgusto'] !== null ? (int)$it['idcodgusto'] : null,
                'cantidad' => max(1, (int)$it['canti']),
            ];
        }
        return $this->registrarAjusteLote($lote, $desde, $hasta, $motivo, $adminUserId, 'ajuste');
    }

    private function updateStockDeposit(int $idprodu, ?int $idcodgusto, int $iddepo, int $delta): void
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('
            SELECT idstock, stock FROM stock
            WHERE iddepo = :depo AND idprodu = :prod AND (idcodgusto = :gusto OR (idcodgusto IS NULL AND :gusto2 IS NULL))
            LIMIT 1
        ');
        $st->execute([
            ':depo' => $iddepo,
            ':prod' => $idprodu,
            ':gusto' => $idcodgusto ?: null,
            ':gusto2' => $idcodgusto ?: null,
        ]);
        $existing = $st->fetch();

        if ($existing) {
            $newStock = (int)$existing['stock'] + $delta;
            $pdo->prepare('UPDATE stock SET stock = :s WHERE idstock = :id LIMIT 1')
                ->execute([':s' => max(0, $newStock), ':id' => (int)$existing['idstock']]);
        } else {
            $newStock = max(0, $delta);
            $pdo->prepare('
                INSERT INTO stock (iddepo, idprodu, idcodgusto, stock)
                VALUES (:depo, :prod, :gusto, :s)
            ')->execute([
                ':depo' => $iddepo,
                ':prod' => $idprodu,
                ':gusto' => $idcodgusto ?: null,
                ':s' => $newStock,
            ]);
        }
    }

    public function setDiscont(int $idcodgusto, int $discont): void
    {
        $st = Db::pdo()->prepare('UPDATE gustos SET discont = :d WHERE idcodgusto = :g LIMIT 1');
        $st->execute([':d' => $discont ? 1 : 0, ':g' => $idcodgusto]);
    }

    public function eliminarDiscontinuadas(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $i): bool => $i > 0));
        if (empty($ids)) return 0;

        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $in = implode(',', $ids);
            $st = $pdo->query("SELECT idprodu FROM gustos WHERE idcodgusto IN ({$in}) GROUP BY idprodu");
            $productIds = array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));

            $pdo->exec("DELETE FROM stock WHERE idcodgusto IN ({$in})");
            $pdo->exec("DELETE FROM gustos WHERE idcodgusto IN ({$in})");
            $count = $pdo->exec("DELETE FROM stockdet WHERE idcodgusto IN ({$in})");

            foreach ($productIds as $idprodu) {
                $s = $pdo->prepare('SELECT COALESCE(SUM(stock), 0) FROM stock WHERE idprodu = :p');
                $s->execute([':p' => $idprodu]);
                $pdo->prepare('UPDATE producto SET stocact = :s WHERE idprodu = :id LIMIT 1')
                    ->execute([':s' => (int)$s->fetchColumn(), ':id' => $idprodu]);
            }

            $pdo->commit();
            return $count;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ── Recalcular stock desde movimientos ──
    // Todos los movimientos en stockcab son transferencias (ambos iddepoh e iddepod seteados).
    // iddepoh = origen (envía), iddepod = destino (recibe).
    // El stock calculado neto de transferencias siempre da 0, así que distribuimos
    // producto.stocact (valor real del VFP) proporcionalmente según el flujo de entrada
    // a cada depósito (iddepod). NO se modifica producto.stocact ni gustos.stockact
    // porque esos los actualiza la sincronización VFP.

    public function recalcular(): string
    {
        $pdo = Db::pdo();

        $pdo->beginTransaction();
        try {
            // Backup current stock for products without movements
            $pdo->exec('CREATE TEMPORARY TABLE stock_backup (iddepo INT, idprodu INT, idcodgusto INT NULL, stock INT) ENGINE=MEMORY');
            $pdo->exec('INSERT INTO stock_backup (iddepo, idprodu, idcodgusto, stock) SELECT iddepo, idprodu, idcodgusto, stock FROM stock');

            $pdo->exec('DELETE FROM stock');

            // Distribute producto.stocact across deposits by inflow (iddepod = destination)
            $pdo->exec("
                INSERT INTO stock (iddepo, idprodu, idcodgusto, stock)
                SELECT
                    inflow.iddepo,
                    inflow.idprodu,
                    NULLIF(inflow.idcodgusto, 0) AS idcodgusto,
                    GREATEST(1, ROUND(p.stocact * inflow.qty / prod.total_qty)) AS stock
                FROM (
                    SELECT sc.iddepod AS iddepo, sd.idprodu, COALESCE(sd.idcodgusto, 0) AS idcodgusto, SUM(sd.canti) AS qty
                    FROM stockcab sc
                    INNER JOIN stockdet sd ON sd.idstockcab = sc.idcabstock
                    GROUP BY sc.iddepod, sd.idprodu, sd.idcodgusto
                ) inflow
                INNER JOIN (
                    SELECT sd.idprodu, COALESCE(sd.idcodgusto, 0) AS idcodgusto, SUM(sd.canti) AS total_qty
                    FROM stockcab sc
                    INNER JOIN stockdet sd ON sd.idstockcab = sc.idcabstock
                    GROUP BY sd.idprodu, sd.idcodgusto
                ) prod ON prod.idprodu = inflow.idprodu AND prod.idcodgusto = inflow.idcodgusto
                INNER JOIN producto p ON p.idprodu = inflow.idprodu
                WHERE p.stocact > 0
                HAVING stock > 0
            ");

            // Restore stock for products with zero rows after recalculation (no movement data)
            $pdo->exec("
                INSERT INTO stock (iddepo, idprodu, idcodgusto, stock)
                SELECT b.iddepo, b.idprodu, b.idcodgusto, b.stock
                FROM stock_backup b
                WHERE NOT EXISTS (SELECT 1 FROM stock s WHERE s.idprodu = b.idprodu)
            ");

            $pdo->exec('DROP TEMPORARY TABLE IF EXISTS stock_backup');

            // NO se actualiza producto.stocact ni gustos.stockact porque esos
            // vienen de la sincronización VFP y son la fuente de verdad.

            $pdo->commit();

            $inserted = (int)$pdo->query('SELECT COUNT(*) FROM stock')->fetchColumn();
            $sumStock = (int)$pdo->query('SELECT COALESCE(SUM(stock), 0) FROM stock')->fetchColumn();
            $prodConStock = (int)$pdo->query('SELECT COUNT(*) FROM producto WHERE stocact > 0')->fetchColumn();
            return "insert={$inserted} sum={$sumStock} pcs={$prodConStock}";
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function recalcularProductos(array $ids): void
    {
        if (empty($ids)) return;
        $pdo = Db::pdo();
        $ids = array_map('intval', $ids);
        $in = implode(',', $ids);

        $pdo->beginTransaction();
        try {
            // Delete existing stock for these products
            $pdo->exec("DELETE FROM stock WHERE idprodu IN ({$in})");

            // Distribute stocact by inflow (iddepod = destination)
            $pdo->exec("
                INSERT INTO stock (iddepo, idprodu, idcodgusto, stock)
                SELECT
                    inflow.iddepo,
                    inflow.idprodu,
                    NULLIF(inflow.idcodgusto, 0) AS idcodgusto,
                    GREATEST(1, ROUND(p.stocact * inflow.qty / prod.total_qty)) AS stock
                FROM (
                    SELECT sc.iddepod AS iddepo, sd.idprodu, COALESCE(sd.idcodgusto, 0) AS idcodgusto, SUM(sd.canti) AS qty
                    FROM stockcab sc
                    INNER JOIN stockdet sd ON sd.idstockcab = sc.idcabstock
                    WHERE sd.idprodu IN ({$in})
                    GROUP BY sc.iddepod, sd.idprodu, sd.idcodgusto
                ) inflow
                INNER JOIN (
                    SELECT sd.idprodu, COALESCE(sd.idcodgusto, 0) AS idcodgusto, SUM(sd.canti) AS total_qty
                    FROM stockcab sc
                    INNER JOIN stockdet sd ON sd.idstockcab = sc.idcabstock
                    WHERE sd.idprodu IN ({$in})
                    GROUP BY sd.idprodu, sd.idcodgusto
                ) prod ON prod.idprodu = inflow.idprodu AND prod.idcodgusto = inflow.idcodgusto
                INNER JOIN producto p ON p.idprodu = inflow.idprodu
                WHERE p.stocact > 0
                HAVING stock > 0
            ");

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ── Datos para filtros del listado de stock ──

    public function grillaRubros(): array
    {
        $st = Db::pdo()->query('SELECT codrub, nomrub FROM rubros ORDER BY nomrub ASC');
        return $st->fetchAll();
    }

    public function grillaSubrubros(int $codrub = 0): array
    {
        if ($codrub > 0) {
            $st = Db::pdo()->prepare('SELECT codsub, nomsub FROM subrubro WHERE codrub = :cr ORDER BY nomsub ASC');
            $st->execute([':cr' => $codrub]);
        } else {
            $st = Db::pdo()->query('SELECT codsub, nomsub FROM subrubro ORDER BY nomsub ASC');
        }
        return $st->fetchAll();
    }

    public function grillaProveedores(): array
    {
        $st = Db::pdo()->query("
            SELECT DISTINCT pv.idprovee AS codprove, pv.razon AS nomprovee
            FROM producto p
            INNER JOIN proveedo pv ON pv.idprovee = p.codprove
            WHERE p.enweb = 1 AND p.codprove IS NOT NULL AND p.codprove != ''
            ORDER BY pv.razon ASC
        ");
        return $st->fetchAll();
    }
}
