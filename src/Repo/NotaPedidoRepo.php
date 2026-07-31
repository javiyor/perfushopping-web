<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class NotaPedidoRepo
{
    public function nextCodigo(): string
    {
        $year = date('Y');
        $st = Db::pdo()->prepare("SELECT COUNT(*) FROM notas_pedido WHERE codigo LIKE :y");
        $st->execute([':y' => "NP-{$year}-%"]);
        $count = (int)$st->fetchColumn() + 1;
        return "NP-{$year}-" . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $items): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('
                INSERT INTO notas_pedido (codigo, proveedor_id, proveedor_nombre, transporte,
                    envio_direccion, envio_ciudad, envio_telefono, notas, created_by, created_at, updated_at)
                VALUES (:codigo, :pid, :pnom, :trans, :dir, :ciu, :tel, :notas, :cb, CURDATE(), NOW())
            ');
            $st->execute([
                ':codigo' => $data['codigo'],
                ':pid' => $data['proveedor_id'] ?? null,
                ':pnom' => $data['proveedor_nombre'] ?? '',
                ':trans' => $data['transporte'] ?? '',
                ':dir' => $data['envio_direccion'] ?? '',
                ':ciu' => $data['envio_ciudad'] ?? '',
                ':tel' => $data['envio_telefono'] ?? '',
                ':notas' => $data['notas'] ?? '',
                ':cb' => $data['created_by'] ?? null,
            ]);
            $notaId = (int)$pdo->lastInsertId();

            $st2 = $pdo->prepare('
                INSERT INTO notas_pedido_items (nota_id, idprodu, idcodgusto, producto, variedad, codscan, codprodup, qty)
                VALUES (:nid, :p, :g, :prod, :var, :cs, :cpp, :q)
            ');
            foreach ($items as $it) {
                $st2->execute([
                    ':nid' => $notaId,
                    ':p' => $it['idprodu'] ?? null,
                    ':g' => $it['idcodgusto'] ?? null,
                    ':prod' => $it['producto'] ?? '',
                    ':var' => $it['variedad'] ?? '',
                    ':cs' => $it['codscan'] ?? '',
                    ':cpp' => $it['codprodup'] ?? '',
                    ':q' => (int)($it['qty'] ?? 0),
                ]);
            }

            $pdo->commit();
            return $notaId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT np.*, u.nombre AS created_by_nombre
            FROM notas_pedido np
            LEFT JOIN admin_users u ON u.id = np.created_by
            WHERE np.id = :id LIMIT 1
        ');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    public function items(int $notaId): array
    {
        $st = Db::pdo()->prepare('
            SELECT npi.*, g.nomgusto, g.codscan AS g_codscan, p.codprodup AS p_codprodup, p.produ, p.codprodu
            FROM notas_pedido_items npi
            LEFT JOIN gustos g ON g.idcodgusto = npi.idcodgusto
            LEFT JOIN producto p ON p.idprodu = npi.idprodu
            WHERE npi.nota_id = :id
            ORDER BY npi.id ASC
        ');
        $st->execute([':id' => $notaId]);
        return $st->fetchAll();
    }

    public function searchProveedores(string $q, int $limit = 10): array
    {
        $q = trim($q);
        if ($q === '') return [];
        $st = Db::pdo()->prepare('
            SELECT idprovee, codprove, razon, cuit
            FROM proveedo
            WHERE razon LIKE :q OR cuit LIKE :q OR codprove LIKE :q
            LIMIT ' . max(1, min(20, $limit))
        );
        $st->execute([':q' => '%' . $q . '%']);
        return $st->fetchAll();
    }
}
