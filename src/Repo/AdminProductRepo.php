<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class AdminProductRepo
{
    /** @return array<int, array<string,mixed>> */
    public function search(string $q, int $codsub = 0, int $codrub = 0, int $limit = 40): array
    {
        $pdo = Db::pdo();
        $limit = max(1, min(100, $limit));
        $q = trim($q);

        $params = [];
        $where = ['p.fecompra > DATE_SUB(CURDATE(), INTERVAL 6 MONTH)'];
        $searchJoin = '';
        if ($q !== '') {
            $searchWhere = [];
            if (ctype_digit($q)) {
                $searchWhere[] = 'p.idprodu = :idprodu_exact';
                $params[':idprodu_exact'] = (int)$q;
            }
            $searchWhere[] = 'p.codprodu LIKE :like_prefix';
            $searchWhere[] = 'p.produ LIKE :like_any';
            $params[':like_prefix'] = $q . '%';
            $params[':like_any'] = '%' . $q . '%';

            $gustosWhere = ['nomgusto LIKE :like_any', 'codscan LIKE :like_prefix'];
            if (ctype_digit($q)) {
                $gustosWhere[] = 'codscan = :q_exact';
                $params[':q_exact'] = $q;
            }
            $searchJoin = ' LEFT JOIN (SELECT DISTINCT idprodu FROM gustos WHERE ' . implode(' OR ', $gustosWhere) . ') gmatch ON gmatch.idprodu = p.idprodu ';
            $searchWhere[] = 'gmatch.idprodu IS NOT NULL';
            $where[] = '(' . implode(' OR ', $searchWhere) . ')';
        }
        if ($codsub > 0) {
            $where[] = 'p.codsub = :codsub';
            $params[':codsub'] = $codsub;
        }
        if ($codrub > 0) {
            $where[] = 'p.codrub = :codrub';
            $params[':codrub'] = $codrub;
        }

        $sql = '
            SELECT
              DISTINCT p.idprodu, p.codprodu, p.produ, p.precio, p.precio1, p.imagen, p.enweb, p.fecompra,
              r.nomrub,
              s.nomsub,
              i.tiva,
              COALESCE(vc.variants_count, 0) AS variants_count
            FROM producto p
            LEFT JOIN rubros r ON r.codrub = p.codrub
            LEFT JOIN subrubro s ON s.codsub = p.codsub
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            LEFT JOIN (
                SELECT idprodu, COUNT(*) AS variants_count
                FROM gustos
                WHERE discont = 0
                GROUP BY idprodu
            ) vc ON vc.idprodu = p.idprodu
        ';

        $sql .= $searchJoin;

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY p.idprodu DESC LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @return array<int, array<string,mixed>> */
    public function ivaOptions(): array
    {
        $st = Db::pdo()->query('SELECT codivaprodu, tiva FROM ivaprodu ORDER BY tiva ASC');
        $rows = $st ? $st->fetchAll() : [];
        return is_array($rows) ? $rows : [];
    }

    /** @return array<int, array<string,mixed>> */
    public function allRubros(): array
    {
        $st = Db::pdo()->query('SELECT codrub, nomrub FROM rubros ORDER BY nomrub ASC');
        $rows = $st ? $st->fetchAll() : [];
        return is_array($rows) ? $rows : [];
    }

    /** @return array<int, array<string,mixed>> */
    public function allSubrubros(): array
    {
        $st = Db::pdo()->query('SELECT codsub, nomsub FROM subrubro ORDER BY nomsub ASC');
        $rows = $st ? $st->fetchAll() : [];
        return is_array($rows) ? $rows : [];
    }

    /** @return array<int, array<string,mixed>> */
    public function brandOptions(): array
    {
        $st = Db::pdo()->query('
            SELECT DISTINCT s.codsub, s.nomsub
            FROM producto p
            INNER JOIN subrubro s ON s.codsub = p.codsub
            WHERE s.nomsub IS NOT NULL AND s.nomsub <> ""
            ORDER BY s.nomsub ASC
        ');
        $rows = $st ? $st->fetchAll() : [];
        return is_array($rows) ? $rows : [];
    }

    /** @return array<int, array<string,mixed>> */
    public function categoryOptions(): array
    {
        $st = Db::pdo()->query('
            SELECT DISTINCT r.codrub, r.nomrub
            FROM producto p
            INNER JOIN rubros r ON r.codrub = p.codrub
            WHERE r.nomrub IS NOT NULL AND r.nomrub <> ""
            ORDER BY r.nomrub ASC
        ');
        $rows = $st ? $st->fetchAll() : [];
        return is_array($rows) ? $rows : [];
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
            SELECT idcodgusto, idprodu, nomgusto, codscan, stockact, discont, weight_g, height_cm, width_cm, depth_cm, product_category
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

    /** @param array<int,int> $variantIds
     *  @return array<int, array<int, array<string,mixed>>>
     */
    public function variantImagesMap(array $variantIds): array
    {
        $variantIds = array_values(array_filter(array_map('intval', $variantIds), static fn (int $id): bool => $id > 0));
        if (!$variantIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $st = Db::pdo()->prepare('SELECT idimagen, idcodgusto, rutaimg FROM imagen WHERE idcodgusto IN (' . $placeholders . ') ORDER BY idcodgusto ASC, idimagen ASC');
        $st->execute($variantIds);
        $rows = $st->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $variantId = (int)($row['idcodgusto'] ?? 0);
            if ($variantId <= 0) {
                continue;
            }
            if (!isset($map[$variantId])) {
                $map[$variantId] = [];
            }
            if (count($map[$variantId]) < 6) {
                $map[$variantId][] = $row;
            }
        }
        return $map;
    }

    public function createProduct(string $produ, float $precio, float $precio1, int $iva, float $precomp = 0, float $ganan1 = 0, float $ganan2 = 0, string $codprodu = ''): int
    {
        $st = Db::pdo()->query('SELECT COALESCE(MAX(idprodu), 0) + 1 FROM producto');
        $idprodu = (int)$st->fetchColumn();

        if ($codprodu === '') {
            $codprodu = (string)$idprodu;
        }

        $st = Db::pdo()->prepare('
            INSERT INTO producto (idprodu, codprodu, produ, precio, precio1, precomp, iva, ganan1, ganan2, enweb, fecompra, fecalta)
            VALUES (:id, :cp, :p, :pr, :pr1, :pc, :iva, :g1, :g2, 0, CURDATE(), NOW())
        ');
        $st->execute([
            ':id' => $idprodu,
            ':cp' => $codprodu,
            ':p' => $produ,
            ':pr' => $precio,
            ':pr1' => $precio1,
            ':pc' => $precomp,
            ':iva' => $iva,
            ':g1' => $ganan1,
            ':g2' => $ganan2,
        ]);
        return $idprodu;
    }

    public function updateProduct(int $idprodu, string $observ, float $precioNeto, float $precio1Neto, bool $enweb, string $produ = '', int $codrub = 0, int $codsub = 0, int $codepar = 0, string $codprove = '', float $ganan1 = 0, float $ganan2 = 0, float $precomp = 0): void
    {
        $st = Db::pdo()->prepare('UPDATE producto SET observ = :observ, precio = :precio, precio1 = :precio1, precomp = :precomp, enweb = :enweb, produ = :produ, codrub = :codrub, codsub = :codsub, codepar = :codepar, codprove = :codprove, ganan1 = :ganan1, ganan2 = :ganan2 WHERE idprodu = :id LIMIT 1');
        $st->execute([
            ':observ' => $observ,
            ':precio' => $precioNeto,
            ':precio1' => $precio1Neto,
            ':precomp' => $precomp,
            ':enweb' => $enweb ? 1 : 0,
            ':produ' => $produ !== '' ? $produ : null,
            ':codrub' => $codrub > 0 ? $codrub : null,
            ':codsub' => $codsub > 0 ? $codsub : null,
            ':codepar' => $codepar > 0 ? $codepar : null,
            ':codprove' => $codprove !== '' ? $codprove : null,
            ':ganan1' => $ganan1,
            ':ganan2' => $ganan2,
            ':id' => $idprodu,
        ]);
    }

    public function deleteProduct(int $idprodu): void
    {
        $pdo = Db::pdo();
        $pdo->prepare('DELETE FROM imagen WHERE idprodu = :id')->execute([':id' => $idprodu]);
        $pdo->prepare('DELETE FROM gustos WHERE idprodu = :id')->execute([':id' => $idprodu]);
        try { $pdo->prepare('DELETE FROM producto_admin WHERE idprodu = :id')->execute([':id' => $idprodu]); } catch (\Throwable $e) {}
        $pdo->prepare('DELETE FROM producto WHERE idprodu = :id LIMIT 1')->execute([':id' => $idprodu]);
    }

    public function deleteVariant(int $idcodgusto): void
    {
        $pdo = Db::pdo();
        $pdo->prepare('DELETE FROM imagen WHERE idcodgusto = :id')->execute([':id' => $idcodgusto]);
        $pdo->prepare('DELETE FROM gustos WHERE idcodgusto = :id LIMIT 1')->execute([':id' => $idcodgusto]);
    }

    public function updateVariantLogistics(int $idcodgusto, int $weightG, int $heightCm, int $widthCm, int $depthCm, string $productCategory): void
    {
        $st = Db::pdo()->prepare('UPDATE gustos SET weight_g = :weight_g, height_cm = :height_cm, width_cm = :width_cm, depth_cm = :depth_cm, product_category = :product_category WHERE idcodgusto = :id LIMIT 1');
        $st->execute([
            ':weight_g' => $weightG,
            ':height_cm' => $heightCm,
            ':width_cm' => $widthCm,
            ':depth_cm' => $depthCm,
            ':product_category' => $productCategory,
            ':id' => $idcodgusto,
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
