<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class CustomerRepo
{
    private static ?array $webUserColumns = null;

    private static function webUsersColumnas(): array
    {
        if (self::$webUserColumns !== null) return self::$webUserColumns;
        try {
            $rows = Db::pdo()->query('SHOW COLUMNS FROM web_users')->fetchAll();
            self::$webUserColumns = array_fill_keys(array_column($rows, 'Field'), true);
        } catch (\Throwable $e) {
            self::$webUserColumns = [];
        }
        return self::$webUserColumns;
    }

    /** @return array<int, array<string,mixed>> */
    public function search(string $q = '', int $limit = 60, int $offset = 0, string $city = '', string $category = ''): array
    {
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);
        $q = trim($q);

        $clientCols = self::clientesColumnas();
        $webCols = self::webUsersColumnas();
        $webCity = !empty($webCols['city']) ? "NULLIF(TRIM(u.city), '')" : 'NULL';
        $clientCity = !empty($clientCols['localidad']) ? "COALESCE({$webCity}, NULLIF(TRIM(c.Localidad), ''))" : $webCity;
        $clientCategory = !empty($clientCols['categoria']) ? 'c.categoria' : 'NULL';
        $webCategory = !empty($webCols['customer_category']) ? 'u.customer_category' : 'NULL';
        $select = '
            SELECT u.id, u.email, u.name, u.phone, u.role, u.wholesale_status,
                   u.disabled_at, u.created_at, u.last_login_at,
                   ' . $webCity . ' AS city,
                   ' . $webCategory . ' AS customer_category,
                   u.cliente_id,
                   c.razon AS cliente_razon,
                   c.cuit AS cliente_cuit,
                   ' . $clientCity . ' AS contact_city,
                   ' . $clientCategory . ' AS erp_categoria,
                   COALESCE(o_sum.order_count, 0) AS order_count,
                   COALESCE(o_sum.total_spent, 0) AS total_spent_cents,
                   o_sum.last_order_at
        ';
        $from = '
            FROM web_users u
            LEFT JOIN clientes c ON c.idclien = u.cliente_id
            LEFT JOIN (
                SELECT user_id,
                       COUNT(*) AS order_count,
                       MAX(created_at) AS last_order_at,
                       SUM(total_cents) AS total_spent
                FROM orders
                WHERE user_id IS NOT NULL
                GROUP BY user_id
            ) o_sum ON o_sum.user_id = u.id
        ';
        $params = [];
        $where = [];

        if ($q !== '') {
            $digits = preg_replace('/[^0-9]/', '', $q) ?? '';
            $where[] = '(u.name LIKE :like OR u.email LIKE :like OR u.phone LIKE :like OR u.phone_key LIKE :pk'
                . ' OR c.razon LIKE :like OR c.cuit LIKE :cuit_like)';
            $params[':like'] = '%' . $q . '%';
            $params[':pk'] = $digits;
            $params[':cuit_like'] = $digits !== '' ? '%' . $digits . '%' : '%' . $q . '%';
        }
        if (trim($city) !== '') {
            $where[] = $clientCity . ' = :city';
            $params[':city'] = trim($city);
        }
        if (trim($category) !== '') {
            $categoryParts = [];
            if (!empty($webCols['customer_category'])) {
                $categoryParts[] = 'u.customer_category = :web_category';
                $params[':web_category'] = trim($category);
            }
            if (!empty($clientCols['categoria'])) {
                $categoryParts[] = 'c.categoria = :erp_category';
                $params[':erp_category'] = trim($category);
            }
            if ($categoryParts) $where[] = '(' . implode(' OR ', $categoryParts) . ')';
        }

        $sql = $select . $from;
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY COALESCE(NULLIF(TRIM(u.name), \'\'), u.email) ASC, u.id ASC LIMIT ' . $limit . ' OFFSET ' . $offset;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function countSearch(string $q = '', string $city = '', string $category = ''): int
    {
        $q = trim($q);
        $clientCols = self::clientesColumnas();
        $webCols = self::webUsersColumnas();
        $webCity = !empty($webCols['city']) ? "NULLIF(TRIM(u.city), '')" : 'NULL';
        $clientCity = !empty($clientCols['localidad']) ? "COALESCE({$webCity}, NULLIF(TRIM(c.Localidad), ''))" : $webCity;
        $params = [];
        $where = [];
        if ($q !== '') {
            $digits = preg_replace('/[^0-9]/', '', $q) ?? '';
            $where[] = '(u.name LIKE :like OR u.email LIKE :like OR u.phone LIKE :like OR u.phone_key LIKE :pk'
                . ' OR c.razon LIKE :like OR c.cuit LIKE :cuit_like)';
            $params[':like'] = '%' . $q . '%';
            $params[':pk'] = $digits;
            $params[':cuit_like'] = $digits !== '' ? '%' . $digits . '%' : '%' . $q . '%';
        }
        if (trim($city) !== '') {
            $where[] = $clientCity . ' = :city';
            $params[':city'] = trim($city);
        }
        if (trim($category) !== '') {
            $categoryParts = [];
            if (!empty($webCols['customer_category'])) {
                $categoryParts[] = 'u.customer_category = :web_category';
                $params[':web_category'] = trim($category);
            }
            if (!empty($clientCols['categoria'])) {
                $categoryParts[] = 'c.categoria = :erp_category';
                $params[':erp_category'] = trim($category);
            }
            if ($categoryParts) $where[] = '(' . implode(' OR ', $categoryParts) . ')';
        }
        $sql = 'SELECT COUNT(*) FROM web_users u LEFT JOIN clientes c ON c.idclien = u.cliente_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /**
     * Clientes cargados en facturación (tabla clientes) que no tienen usuario web.
     * Se muestran con etiqueta "Presencial".
     * @return array<int, array<string,mixed>>
     */
    public function searchPresenciales(string $q = '', int $limit = 60, int $offset = 0, string $city = '', string $category = ''): array
    {
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);
        $q = trim($q);
        $params = [];
        $where = 'w.id IS NULL';
        if ($q !== '') {
            $where .= ' AND (c.razon LIKE :like OR c.cuit LIKE :like2 OR c.tele LIKE :like3 OR c.mail LIKE :like4)';
            $params[':like'] = '%' . $q . '%';
            $params[':like2'] = '%' . $q . '%';
            $params[':like3'] = '%' . $q . '%';
            $params[':like4'] = '%' . $q . '%';
        }
        if (trim($city) !== '') {
            $where .= ' AND c.Localidad = :city';
            $params[':city'] = trim($city);
        }
        $cols = self::clientesColumnas();
        if (trim($category) !== '' && !empty($cols['categoria'])) {
            $where .= ' AND c.categoria = :category';
            $params[':category'] = trim($category);
        }
        try {
            $selCond = !empty($cols['condicion_iva']) ? 'c.condicion_iva,' : 'NULL AS condicion_iva,';
            $selCat = !empty($cols['categoria']) ? 'c.categoria,' : 'NULL AS categoria,';
            $selMayorista = !empty($cols['precio_mayorista']) ? 'c.precio_mayorista,' : 'NULL AS precio_mayorista,';
            $selEspec = !empty($cols['especialidad']) ? 'c.especialidad,' : 'NULL AS especialidad,';
            $st = Db::pdo()->prepare("
                SELECT c.idclien, c.razon, c.cuit, c.direc, c.tele AS phone, c.mail AS email,
                       c.Localidad AS city,
                       {$selCond} {$selCat} {$selMayorista} {$selEspec}
                       COUNT(f.id) AS facturas,
                       COALESCE(SUM(f.total_cents), 0) AS total_cents,
                       MAX(f.fecha) AS ultima_factura,
                       (SELECT COUNT(*) FROM ctacte_movimientos cm WHERE cm.idclien = c.idclien) AS mov_ctacte,
                       (SELECT COUNT(*) FROM puntos_movimientos pm WHERE pm.idclien = c.idclien) AS mov_puntos,
                       (SELECT COUNT(*) FROM recibos r WHERE r.idclien = c.idclien) AS mov_recibos
                FROM clientes c
                LEFT JOIN web_users w ON w.cliente_id = c.idclien
                LEFT JOIN facturas f ON f.idclien = c.idclien AND f.estado = 'emitida'
                WHERE {$where}
                GROUP BY c.idclien
                ORDER BY c.razon ASC, c.idclien ASC
                LIMIT {$limit} OFFSET {$offset}
            ");
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('CustomerRepo::searchPresenciales error: ' . $e->getMessage());
            return [];
        }
    }

    public function countPresenciales(string $q = '', string $city = '', string $category = ''): int
    {
        $q = trim($q);
        $params = [];
        $where = 'w.id IS NULL';
        if ($q !== '') {
            $where .= ' AND (c.razon LIKE :like OR c.cuit LIKE :like2 OR c.tele LIKE :like3 OR c.mail LIKE :like4)';
            $params[':like'] = '%' . $q . '%';
            $params[':like2'] = '%' . $q . '%';
            $params[':like3'] = '%' . $q . '%';
            $params[':like4'] = '%' . $q . '%';
        }
        if (trim($city) !== '') {
            $where .= ' AND c.Localidad = :city';
            $params[':city'] = trim($city);
        }
        if (trim($category) !== '' && !empty(self::clientesColumnas()['categoria'])) {
            $where .= ' AND c.categoria = :category';
            $params[':category'] = trim($category);
        }
        try {
            $st = Db::pdo()->prepare("
                SELECT COUNT(*)
                FROM clientes c
                LEFT JOIN web_users w ON w.cliente_id = c.idclien
                WHERE {$where}
            ");
            $st->execute($params);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Filtros disponibles para listados y envíos publicitarios. */
    public function ciudadesFiltro(): array
    {
        $values = [];
        try {
            $cols = self::clientesColumnas();
            $webCols = self::webUsersColumnas();
            $queries = [];
            if (!empty($webCols['city'])) $queries[] = "SELECT city AS nombre FROM web_users WHERE city IS NOT NULL AND TRIM(city) <> ''";
            if (!empty($cols['localidad'])) $queries[] = "SELECT Localidad AS nombre FROM clientes WHERE Localidad IS NOT NULL AND TRIM(Localidad) <> ''";
            if (!$queries) return [];
            $st = Db::pdo()->query(implode(' UNION ', $queries) . ' ORDER BY nombre ASC');
            foreach ($st->fetchAll() as $row) $values[] = trim((string)($row['nombre'] ?? ''));
        } catch (\Throwable $e) {
            error_log('CustomerRepo::ciudadesFiltro error: ' . $e->getMessage());
        }
        return array_values(array_unique(array_filter($values, static fn(string $v): bool => $v !== '')));
    }

    /** @return array<int,string> valor => etiqueta */
    public function categoriasFiltro(): array
    {
        $values = [];
        try {
            $cols = self::clientesColumnas();
            $webCols = self::webUsersColumnas();
            $queries = [];
            if (!empty($webCols['customer_category'])) $queries[] = "SELECT customer_category AS categoria FROM web_users WHERE customer_category IS NOT NULL AND TRIM(customer_category) <> ''";
            if (!empty($cols['categoria'])) $queries[] = "SELECT categoria FROM clientes WHERE categoria IS NOT NULL AND TRIM(categoria) <> ''";
            if (!$queries) return [];
            $st = Db::pdo()->query(implode(' UNION ', $queries) . ' ORDER BY categoria ASC');
            foreach ($st->fetchAll() as $row) {
                $value = trim((string)($row['categoria'] ?? ''));
                if ($value !== '') $values[$value] = $value;
            }
        } catch (\Throwable $e) {
            error_log('CustomerRepo::categoriasFiltro error: ' . $e->getMessage());
        }
        return $values;
    }

    private function marketingContactsFrom(): string
    {
        $cols = self::clientesColumnas();
        $webCols = self::webUsersColumnas();
        $cityCol = !empty($cols['localidad']) ? "NULLIF(TRIM(c.Localidad), '')" : "''";
        $categoryCol = !empty($cols['categoria']) ? "NULLIF(TRIM(c.categoria), '')" : 'NULL';
        $webCityCol = !empty($webCols['city']) ? "NULLIF(TRIM(u.city), '')" : 'NULL';
        $webCategoryCol = !empty($webCols['customer_category']) ? "NULLIF(TRIM(u.customer_category), '')" : 'NULL';
        $erpCategory = !empty($cols['categoria']) ? "COALESCE({$categoryCol}, 'minorista')" : "'none'";
        return "(
            SELECT CONCAT('w:', u.id) AS contact_key, 'web' AS source, u.id AS source_id,
                   COALESCE(NULLIF(TRIM(c.razon), ''), NULLIF(TRIM(u.name), ''), 'Sin nombre') AS nombre,
                   TRIM(u.email) AS email,
                   COALESCE({$webCityCol}, {$cityCol}, '') AS ciudad,
                   COALESCE({$webCategoryCol}, {$categoryCol}, 'none') AS categoria,
                   COALESCE({$categoryCol}, '') AS categoria_erp
            FROM web_users u
            LEFT JOIN clientes c ON c.idclien = u.cliente_id
            WHERE u.disabled_at IS NULL AND u.email IS NOT NULL AND TRIM(u.email) <> ''
            UNION ALL
            SELECT CONCAT('e:', c.idclien) AS contact_key, 'erp' AS source, c.idclien AS source_id,
                   COALESCE(NULLIF(TRIM(c.razon), ''), 'Sin nombre') AS nombre,
                   TRIM(c.mail) AS email,
                   COALESCE({$cityCol}, '') AS ciudad,
                   {$erpCategory} AS categoria,
                   COALESCE({$categoryCol}, '') AS categoria_erp
            FROM clientes c
            WHERE c.mail IS NOT NULL AND TRIM(c.mail) <> ''
              AND NOT EXISTS (SELECT 1 FROM web_users wu WHERE wu.cliente_id = c.idclien)
              AND NOT EXISTS (SELECT 1 FROM web_users we WHERE LOWER(TRIM(we.email)) = LOWER(TRIM(c.mail)))
              AND c.idclien = (
                  SELECT MIN(c2.idclien) FROM clientes c2
                  WHERE LOWER(TRIM(c2.mail)) = LOWER(TRIM(c.mail))
                    AND c2.mail IS NOT NULL AND TRIM(c2.mail) <> ''
                    AND NOT EXISTS (SELECT 1 FROM web_users w2 WHERE w2.cliente_id = c2.idclien)
                    AND NOT EXISTS (SELECT 1 FROM web_users w3 WHERE LOWER(TRIM(w3.email)) = LOWER(TRIM(c2.mail)))
              )
        )";
    }

    private function marketingContactFilters(string $q, string $city, string $category, array &$params): string
    {
        $where = [];
        $q = trim($q);
        if ($q !== '') {
            $where[] = '(m.nombre LIKE :mq1 OR m.email LIKE :mq2 OR m.ciudad LIKE :mq3)';
            $params[':mq1'] = '%' . $q . '%';
            $params[':mq2'] = '%' . $q . '%';
            $params[':mq3'] = '%' . $q . '%';
        }
        if (trim($city) !== '') {
            $where[] = 'm.ciudad = :mcity';
            $params[':mcity'] = trim($city);
        }
        if (trim($category) !== '') {
            $where[] = '(m.categoria = :mcat OR (m.source = \'web\' AND m.categoria_erp = :merpcat))';
            $params[':mcat'] = trim($category);
            $params[':merpcat'] = trim($category);
        }
        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }

    public function countMarketingContacts(string $q = '', string $city = '', string $category = ''): int
    {
        $params = [];
        $filters = $this->marketingContactFilters($q, $city, $category, $params);
        $st = Db::pdo()->prepare('SELECT COUNT(*) FROM ' . $this->marketingContactsFrom() . ' m' . $filters);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function marketingContacts(string $q = '', string $city = '', string $category = '', int $limit = 200, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $params = [];
        $filters = $this->marketingContactFilters($q, $city, $category, $params);
        $st = Db::pdo()->prepare('SELECT m.* FROM ' . $this->marketingContactsFrom() . ' m' . $filters
            . ' ORDER BY m.nombre ASC, m.email ASC LIMIT ' . $limit . ' OFFSET ' . $offset);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @param array<int,string> $contactKeys @return array<int,array<string,mixed>> */
    public function marketingContactsByKeys(array $contactKeys): array
    {
        $keys = array_values(array_unique(array_filter(array_map(static function ($key): string {
            $key = trim((string)$key);
            return preg_match('/^(?:w|e):[1-9][0-9]*$/', $key) ? $key : '';
        }, $contactKeys))));
        if (!$keys) return [];
        $keys = array_slice($keys, 0, 500);
        $st = Db::pdo()->prepare('SELECT m.* FROM ' . $this->marketingContactsFrom() . ' m WHERE m.contact_key IN ('
            . implode(',', array_fill(0, count($keys), '?')) . ') ORDER BY m.nombre ASC, m.email ASC');
        $st->execute($keys);
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT u.*,
                   COALESCE(o_sum.order_count, 0) AS order_count,
                   COALESCE(o_sum.total_spent, 0) AS total_spent_cents,
                   o_sum.last_order_at
            FROM web_users u
            LEFT JOIN (
                SELECT user_id,
                       COUNT(*) AS order_count,
                       MAX(created_at) AS last_order_at,
                       SUM(total_cents) AS total_spent
                FROM orders
                WHERE user_id IS NOT NULL
                GROUP BY user_id
            ) o_sum ON o_sum.user_id = u.id
            WHERE u.id = :i
            LIMIT 1
        ');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /** @return array<int, array<string,mixed>> */
    public function orders(int $userId): array
    {
        $st = Db::pdo()->prepare('
            SELECT o.*, COUNT(oi.id) AS items_count
            FROM orders o
            LEFT JOIN order_items oi ON oi.order_id = o.id
            WHERE o.user_id = :u
            GROUP BY o.id
            ORDER BY o.created_at DESC, o.id DESC
            LIMIT 100
        ');
        $st->execute([':u' => $userId]);
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function clienteErp(int $clienteId): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM clientes WHERE idclien = :i LIMIT 1');
        $st->execute([':i' => $clienteId]);
        $r = $st->fetch();
        return $r ?: null;
    }

    /** @return array<int, array<string,mixed>> */
    public function orderItems(int $orderId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM order_items WHERE order_id = :o ORDER BY id ASC');
        $st->execute([':o' => $orderId]);
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function notas(int $userId): array
    {
        $st = Db::pdo()->prepare('
            SELECT n.*, a.nombre AS admin_nombre
            FROM cliente_notas n
            LEFT JOIN admin_users a ON a.id = n.admin_user_id
            WHERE n.user_id = :u
            ORDER BY n.created_at DESC
            LIMIT 200
        ');
        $st->execute([':u' => $userId]);
        return $st->fetchAll();
    }

    public function addNota(int $userId, int $adminUserId, string $texto): int
    {
        $st = Db::pdo()->prepare('INSERT INTO cliente_notas (user_id, admin_user_id, nota, created_at) VALUES (:u, :a, :n, NOW())');
        $st->execute([':u' => $userId, ':a' => $adminUserId, ':n' => $texto]);
        return (int)Db::pdo()->lastInsertId();
    }

    /** @return array{facturas:int,pedidos:int,ctacte:int,puntos:int,recibos:int,total:int} */
    public function movimientos(int $userId, int $clienteId = 0): array
    {
        $erp = max(0, $clienteId);
        $sql = 'SELECT
            (SELECT COUNT(*) FROM facturas WHERE cliente_id = :u1' . ($erp > 0 ? ' OR idclien = :erp1' : '') . ') AS facturas,
            (SELECT COUNT(*) FROM orders WHERE user_id = :u2) AS pedidos,
            (SELECT COUNT(*) FROM ctacte_movimientos WHERE cliente_id = :u3' . ($erp > 0 ? ' OR idclien = :erp3' : '') . ') AS ctacte,
            (SELECT COUNT(*) FROM recibos WHERE cliente_id = :u4' . ($erp > 0 ? ' OR idclien = :erp4' : '') . ') AS recibos'
            . ($erp > 0 ? ', (SELECT COUNT(*) FROM puntos_movimientos WHERE idclien = :erp2) AS puntos' : ', 0 AS puntos');
        $params = [':u1' => $userId, ':u2' => $userId, ':u3' => $userId, ':u4' => $userId];
        if ($erp > 0) {
            $params[':erp1'] = $erp;
            $params[':erp2'] = $erp;
            $params[':erp3'] = $erp;
            $params[':erp4'] = $erp;
        }
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        $r = $st->fetch() ?: [];
        $out = [
            'facturas' => (int)($r['facturas'] ?? 0),
            'pedidos' => (int)($r['pedidos'] ?? 0),
            'ctacte' => (int)($r['ctacte'] ?? 0),
            'puntos' => (int)($r['puntos'] ?? 0),
            'recibos' => (int)($r['recibos'] ?? 0),
        ];
        $out['total'] = $out['facturas'] + $out['pedidos'] + $out['ctacte'] + $out['puntos'] + $out['recibos'];
        return $out;
    }

    /**
     * Movimientos de un cliente ERP (tabla clientes, sin usuario web).
     * @return array{facturas:int,pedidos:int,ctacte:int,puntos:int,recibos:int,total:int}
     */
    public function movimientosErp(int $idclien): array
    {
        $st = Db::pdo()->prepare('SELECT
            (SELECT COUNT(*) FROM facturas WHERE idclien = :i1) AS facturas,
            (SELECT COUNT(*) FROM ctacte_movimientos WHERE idclien = :i2) AS ctacte,
            (SELECT COUNT(*) FROM puntos_movimientos WHERE idclien = :i3) AS puntos,
            (SELECT COUNT(*) FROM recibos WHERE idclien = :i4) AS recibos');
        $st->execute([':i1' => $idclien, ':i2' => $idclien, ':i3' => $idclien, ':i4' => $idclien]);
        $r = $st->fetch() ?: [];
        $out = [
            'facturas' => (int)($r['facturas'] ?? 0),
            'pedidos' => 0,
            'ctacte' => (int)($r['ctacte'] ?? 0),
            'puntos' => (int)($r['puntos'] ?? 0),
            'recibos' => (int)($r['recibos'] ?? 0),
        ];
        $out['total'] = $out['facturas'] + $out['pedidos'] + $out['ctacte'] + $out['puntos'] + $out['recibos'];
        return $out;
    }

    /** ¿El cliente ERP tiene un usuario web vinculado? */
    public function clienteErpTieneUsuarioWeb(int $idclien): bool
    {
        $st = Db::pdo()->prepare('SELECT COUNT(*) FROM web_users WHERE cliente_id = :i');
        $st->execute([':i' => $idclien]);
        return (int)$st->fetchColumn() > 0;
    }

    /** Elimina un cliente ERP (sin movimientos ni usuario web). */
    public function deleteClienteErp(int $idclien): void
    {
        try {
            Db::pdo()->prepare('DELETE FROM puntos_cuentas WHERE idclien = :i AND saldo_puntos = 0')
                ->execute([':i' => $idclien]);
        } catch (\Throwable $e) {
            error_log('CustomerRepo::deleteClienteErp puntos_cuentas: ' . $e->getMessage());
        }
        Db::pdo()->prepare('DELETE FROM clientes WHERE idclien = :i LIMIT 1')->execute([':i' => $idclien]);
    }

    /** @param array<string,mixed> $data */
    public function updateWebUser(int $id, array $data): void
    {
        $allowed = ['name', 'email', 'phone', 'address', 'city', 'postal_code', 'customer_category'];
        $set = [];
        $params = [':id' => $id];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $set[] = "{$f} = :{$f}";
                $params[":{$f}"] = $data[$f];
            }
        }
        if (!$set) {
            return;
        }
        Db::pdo()->prepare('UPDATE web_users SET ' . implode(', ', $set) . ' WHERE id = :id')->execute($params);
    }

    /** @return array<string,bool> */
    public static function clientesColumnas(): array
    {
        static $cols = null;
        if ($cols === null) {
            try {
                $pdo = Db::pdo();
                $rows = $pdo->query('SHOW COLUMNS FROM clientes')->fetchAll();
                $cols = array_fill_keys(array_column($rows, 'Field'), true);
                $tipos = [];
                foreach ($rows as $r) {
                    $tipos[(string)$r['Field']] = (string)($r['Type'] ?? '');
                }
                $asegurar = [
                    'condicion_iva' => "VARCHAR(30) DEFAULT 'consumidor_final'",
                    'localidad' => 'VARCHAR(60) DEFAULT NULL',
                ];
                foreach ($asegurar as $col => $ddl) {
                    $encontrada = null;
                    foreach (array_keys($cols) as $k) {
                        if (strtolower((string)$k) === $col) {
                            $encontrada = (string)$k;
                            break;
                        }
                    }
                    if ($encontrada === null) {
                        try {
                            $pdo->exec("ALTER TABLE clientes ADD COLUMN {$col} {$ddl}");
                            $cols[$col] = true;
                        } catch (\Throwable $e) {
                            error_log('CustomerRepo::clientesColumnas ensure ' . $col . ': ' . $e->getMessage());
                        }
                        continue;
                    }
                    if ($encontrada !== $col) {
                        $cols[$col] = true;
                    }
                    if ($col === 'condicion_iva'
                        && preg_match('/^varchar\((\d+)\)$/i', $tipos[$encontrada] ?? '', $m)
                        && (int)$m[1] < 30
                    ) {
                        try {
                            $pdo->exec("ALTER TABLE clientes MODIFY condicion_iva VARCHAR(30) DEFAULT 'consumidor_final'");
                            $pdo->exec("UPDATE clientes SET condicion_iva = 'responsable_inscripto' WHERE condicion_iva = 'responsable_inscript'");
                        } catch (\Throwable $e) {
                            error_log('CustomerRepo::clientesColumnas widen condicion_iva: ' . $e->getMessage());
                        }
                    }
                }
            } catch (\Throwable $e) {
                $cols = [];
            }
        }
        return $cols;
    }

    /** @param array<string,mixed> $data */
    public function updateClienteErp(int $idclien, array $data): void
    {
        $map = [
            'razon' => 'razon',
            'cuit' => 'cuit',
            'direc' => 'direc',
            'tele' => 'tele',
            'mail' => 'mail',
            'localidad' => 'localidad',
            'condicion_iva' => 'condicion_iva',
            'categoria' => 'categoria',
            'precio_mayorista' => 'precio_mayorista',
            'especialidad' => 'especialidad',
        ];
        $cols = self::clientesColumnas();
        $set = [];
        $params = [':id' => $idclien];
        foreach ($map as $input => $col) {
            if (array_key_exists($input, $data) && !empty($cols[$col])) {
                $set[] = "{$col} = :{$input}";
                $params[":{$input}"] = $data[$input];
            }
        }
        if (!$set) {
            return;
        }
        Db::pdo()->prepare('UPDATE clientes SET ' . implode(', ', $set) . ' WHERE idclien = :id')->execute($params);
    }

    public function deleteWebUser(int $id): void
    {
        Db::pdo()->prepare('DELETE FROM web_users WHERE id = :i')->execute([':i' => $id]);
    }
}
