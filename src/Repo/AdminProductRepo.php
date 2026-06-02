<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class AdminProductRepo
{
    /** @return array<int, array<string,mixed>> */
    public function search(string $q, int $limit = 40): array
    {
        $pdo = Db::pdo();
        $limit = max(1, min(100, $limit));
        $q = trim($q);

        $params = [];
        $where = [];
        if ($q !== '') {
            if (ctype_digit($q)) {
                $where[] = 'p.idprodu = :idprodu_exact';
                $params[':idprodu_exact'] = (int)$q;
            }
            $where[] = 'p.produ LIKE :like';
            $where[] = 'p.codprodu LIKE :like';
            $where[] = 'EXISTS (SELECT 1 FROM gustos g WHERE g.idprodu = p.idprodu AND (g.nomgusto LIKE :like OR g.codscan LIKE :like))';
            $params[':like'] = '%' . $q . '%';
        }

        $sql = '
            SELECT
              p.idprodu, p.codprodu, p.produ, p.precio, p.precio1, p.imagen, p.enweb, p.fecompra,
              r.nomrub,
              s.nomsub,
              i.tiva,
              (SELECT COUNT(*) FROM gustos g WHERE g.idprodu = p.idprodu AND g.discont = 0) AS variants_count
            FROM producto p
            LEFT JOIN rubros r ON r.codrub = p.codrub
            LEFT JOIN subrubro s ON s.codsub = p.codsub
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
        ';

        if ($where) {
            $sql .= ' WHERE (' . implode(' OR ', $where) . ')';
        }

        $sql .= ' ORDER BY p.idprodu DESC LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function find(int $idprodu): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT p.*, r.nomrub, s.nomsub, i.tiva
            FROM producto p
            LEFT JOIN rubros r ON r.codrub = p.codrub
            LEFT JOIN subrubro s ON s.codsub = p.codsub
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE p.idprodu = :id
            LIMIT 1
        ');
        $st->execute([':id' => $idprodu]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string,mixed>> */
    public function variants(int $idprodu): array
    {
        $st = Db::pdo()->prepare('
            SELECT idcodgusto, idprodu, nomgusto, codscan, stockact, rutaimg, discont
            FROM gustos
            WHERE idprodu = :id
            ORDER BY discont ASC, nomgusto ASC, idcodgusto ASC
        ');
        $st->execute([':id' => $idprodu]);
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function variantImages(int $idcodgusto): array
    {
        $st = Db::pdo()->prepare('SELECT idimagen, rutaimg FROM imagen WHERE idcodgusto = :g ORDER BY idimagen ASC LIMIT 6');
        $st->execute([':g' => $idcodgusto]);
        return $st->fetchAll();
    }

    public function updateProduct(int $idprodu, string $observ, float $precioNeto, float $precio1Neto, bool $enweb): void
    {
        $st = Db::pdo()->prepare('UPDATE producto SET observ = :observ, precio = :precio, precio1 = :precio1, enweb = :enweb WHERE idprodu = :id LIMIT 1');
        $st->execute([
            ':observ' => $observ,
            ':precio' => $precioNeto,
            ':precio1' => $precio1Neto,
            ':enweb' => $enweb ? 1 : 0,
            ':id' => $idprodu,
        ]);
    }

    public function updateMainImage(int $idprodu, string $filename): void
    {
        $st = Db::pdo()->prepare('UPDATE producto SET imagen = :img WHERE idprodu = :id LIMIT 1');
        $st->execute([':img' => $filename, ':id' => $idprodu]);
    }

    public function deleteVariantImage(int $idimagen, int $idprodu, int $idcodgusto): void
    {
        $st = Db::pdo()->prepare('DELETE FROM imagen WHERE idimagen = :i AND idprodu = :p AND idcodgusto = :g LIMIT 1');
        $st->execute([':i' => $idimagen, ':p' => $idprodu, ':g' => $idcodgusto]);
    }

    public function countVariantImages(int $idcodgusto): int
    {
        $st = Db::pdo()->prepare('SELECT COUNT(*) FROM imagen WHERE idcodgusto = :g');
        $st->execute([':g' => $idcodgusto]);
        return (int)$st->fetchColumn();
    }

    public function insertVariantImage(int $idprodu, int $idcodgusto, string $filename): int
    {
        $st = Db::pdo()->prepare('INSERT INTO imagen (rutaimg, idprodu, idcodgusto) VALUES (:r, :p, :g)');
        $st->execute([':r' => $filename, ':p' => $idprodu, ':g' => $idcodgusto]);
        return (int)Db::pdo()->lastInsertId();
    }
}
