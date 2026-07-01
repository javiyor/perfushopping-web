<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class ImportRepo
{
    public function findByCodprodup(string $codprodup): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT p.*, r.nomrub, s.nomsub, i.tiva
            FROM producto p
            LEFT JOIN rubros r ON r.codrub = p.codrub
            LEFT JOIN subrubro s ON s.codsub = p.codsub
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE p.codprodup = :c
            LIMIT 1
        ');
        $st->execute([':c' => $codprodup]);
        $p = $st->fetch();
        if (!$p) {
            return null;
        }

        $st2 = Db::pdo()->prepare('
            SELECT idcodgusto, nomgusto, codscan, stockact
            FROM gustos
            WHERE idprodu = :id AND discont = 0
            ORDER BY idcodgusto ASC
            LIMIT 1
        ');
        $st2->execute([':id' => (int)$p['idprodu']]);
        $g = $st2->fetch();

        $p['_idcodgusto'] = $g ? (int)$g['idcodgusto'] : 0;
        $p['_nomgusto'] = $g ? (string)($g['nomgusto'] ?? '') : '';
        $p['_stockact'] = $g ? (int)($g['stockact'] ?? 0) : 0;
        return $p;
    }

    public function findByCodscan(string $codscan): ?array
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('
            SELECT g.idcodgusto, g.idprodu, g.nomgusto, g.codscan, g.stockact, g.discont
            FROM gustos g
            WHERE g.codscan = :c
            LIMIT 1
        ');
        $st->execute([':c' => $codscan]);
        $g = $st->fetch();
        if (!$g) {
            return null;
        }

        $st2 = $pdo->prepare('
            SELECT p.*, r.nomrub, s.nomsub, i.tiva
            FROM producto p
            LEFT JOIN rubros r ON r.codrub = p.codrub
            LEFT JOIN subrubro s ON s.codsub = p.codsub
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE p.idprodu = :id
            LIMIT 1
        ');
        $st2->execute([':id' => (int)$g['idprodu']]);
        $p = $st2->fetch();
        if (!$p) {
            return null;
        }

        $p['_match_type'] = 'codscan';
        $p['_idcodgusto'] = (int)$g['idcodgusto'];
        $p['_nomgusto'] = $g['nomgusto'];
        $p['_stockact'] = $g['stockact'];
        return $p;
    }

    public function findByCodprodupOrCodscan(string $codprodup, string $codscan): ?array
    {
        if ($codprodup !== '') {
            $p = $this->findByCodprodup($codprodup);
            if ($p) {
                $p['_match_type'] = 'codprodup';
                return $p;
            }
        }
        if ($codscan !== '') {
            $p = $this->findByCodscan($codscan);
            if ($p) {
                return $p;
            }
        }
        return null;
    }

    public function updatePrecios(int $idprodu, float $precioNet, float $precompNet, ?float $ganan1 = null, ?float $ganan2 = null, ?float $precio1Net = null): void
    {
        $set = ['precio = :p', 'precomp = :pc'];
        $params = [':p' => $precioNet, ':pc' => $precompNet, ':id' => $idprodu];
        if ($ganan1 !== null) { $set[] = 'ganan1 = :g1'; $params[':g1'] = $ganan1; }
        if ($ganan2 !== null) { $set[] = 'ganan2 = :g2'; $params[':g2'] = $ganan2; }
        if ($precio1Net !== null) { $set[] = 'precio1 = :p1'; $params[':p1'] = $precio1Net; }
        $sql = 'UPDATE producto SET ' . implode(', ', $set) . ' WHERE idprodu = :id LIMIT 1';
        Db::pdo()->prepare($sql)->execute($params);
    }

    public function updateStock(int $idcodgusto, int $stock): void
    {
        $st = Db::pdo()->prepare('UPDATE gustos SET stockact = :s WHERE idcodgusto = :id LIMIT 1');
        $st->execute([':s' => $stock, ':id' => $idcodgusto]);
    }

    public function getLastVariants(int $idprodu): array
    {
        $st = Db::pdo()->prepare('
            SELECT idcodgusto, nomgusto, codscan, stockact
            FROM gustos
            WHERE idprodu = :id AND discont = 0
            ORDER BY idcodgusto ASC
            LIMIT 50
        ');
        $st->execute([':id' => $idprodu]);
        return $st->fetchAll();
    }
}
