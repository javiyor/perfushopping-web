<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class SyncRepo
{
    /**
     * @param array<int, array<string,mixed>> $products
     * @param array<int, array<string,mixed>> $gustos
     * @param array<int, array<string,mixed>> $images
     * @return array<string,int>
     */
    public function sync(array $products, array $gustos, array $images): array
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $pCount = 0;
            $gCount = 0;
            $iCount = 0;

            foreach ($products as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $idprodu = (int)($p['idprodu'] ?? 0);
                if ($idprodu <= 0) {
                    continue;
                }
                $this->upsertProducto($p);
                $pCount++;
            }
            foreach ($gustos as $g) {
                if (!is_array($g)) {
                    continue;
                }
                $idcodgusto = (int)($g['idcodgusto'] ?? 0);
                if ($idcodgusto <= 0) {
                    continue;
                }
                $this->upsertGustos($g);
                $gCount++;
            }
            foreach ($images as $im) {
                if (!is_array($im)) {
                    continue;
                }
                $this->upsertImagen($im);
                $iCount++;
            }

            $pdo->commit();
            $this->log('sync_ok products=' . $pCount . ' gustos=' . $gCount . ' images=' . $iCount);
            return ['products' => $pCount, 'gustos' => $gCount, 'images' => $iCount];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @param array<string,mixed> $p */
    private function upsertProducto(array $p): void
    {
        $cols = [
            'codprodu','produ','codrub','codsub','iva','fecompra','precomp','precio','precio1','observ','image','fecalta','codprodup','ganan1','ganan2','precio2','boni','cantxenv','ganan3','precio3','ganan4','imagen','enweb','testigo'
        ];
        $data = [];
        $data['idprodu'] = (int)$p['idprodu'];
        foreach ($cols as $c) {
            if (array_key_exists($c, $p)) {
                $data[$c] = $p[$c];
            }
        }

        // Normalize image filename fields to match /upload/<name>
        if (array_key_exists('imagen', $data)) {
            $data['imagen'] = $this->normalizeFilename((string)$data['imagen']);
        }
        if (array_key_exists('image', $data)) {
            $data['image'] = $this->normalizeFilename((string)$data['image']);
        }

        $set = [];
        $params = [':idprodu' => $data['idprodu']];
        foreach ($cols as $c) {
            if (!array_key_exists($c, $data)) {
                continue;
            }
            $set[] = "{$c} = :{$c}";
            $params[":" . $c] = $data[$c];
        }
        if (!$set) {
            return;
        }

        // Try UPDATE first
        $sqlU = 'UPDATE producto SET ' . implode(', ', $set) . ' WHERE idprodu=:idprodu';
        $st = Db::pdo()->prepare($sqlU);
        $st->execute($params);
        if ($st->rowCount() > 0) {
            return;
        }

        // If not exists, INSERT with provided columns
        // Build explicit from $data
        $insCols = ['idprodu'];
        $insParams = [':idprodu' => $data['idprodu']];
        foreach ($cols as $c) {
            if (!array_key_exists($c, $data)) {
                continue;
            }
            $insCols[] = $c;
            $insParams[":" . $c] = $data[$c];
        }
        $place = array_map(static fn($c) => ':' . $c, $insCols);
        $sqlI = 'INSERT INTO producto (' . implode(',', $insCols) . ') VALUES (' . implode(',', $place) . ')';
        Db::pdo()->prepare($sqlI)->execute($insParams);
    }

    /** @param array<string,mixed> $g */
    private function upsertGustos(array $g): void
    {
        $cols = ['codgusto','nomgusto','idprodu','codscan','stocmin','stocmax','cantped','stockact','stockdev','fecha','AD','codprodu','discont','rutaimg'];
        $idcodgusto = (int)$g['idcodgusto'];

        if (array_key_exists('rutaimg', $g)) {
            $g['rutaimg'] = $this->normalizeFilename((string)$g['rutaimg']);
        }

        $set = [];
        $params = [':idcodgusto' => $idcodgusto];
        foreach ($cols as $c) {
            if (!array_key_exists($c, $g)) {
                continue;
            }
            $set[] = "{$c} = :{$c}";
            $params[":" . $c] = $g[$c];
        }
        if (!$set) {
            return;
        }
        $sqlU = 'UPDATE gustos SET ' . implode(', ', $set) . ' WHERE idcodgusto=:idcodgusto';
        $st = Db::pdo()->prepare($sqlU);
        $st->execute($params);
        if ($st->rowCount() > 0) {
            return;
        }

        // Insert
        $insCols = ['idcodgusto'];
        $insParams = [':idcodgusto' => $idcodgusto];
        foreach ($cols as $c) {
            if (!array_key_exists($c, $g)) {
                continue;
            }
            $insCols[] = $c;
            $insParams[":" . $c] = $g[$c];
        }
        $place = array_map(static fn($c) => ':' . $c, $insCols);
        $sqlI = 'INSERT INTO gustos (' . implode(',', $insCols) . ') VALUES (' . implode(',', $place) . ')';
        Db::pdo()->prepare($sqlI)->execute($insParams);
    }

    /** @param array<string,mixed> $im */
    private function upsertImagen(array $im): void
    {
        $idimagen = (int)($im['idimagen'] ?? 0);
        $oldRuta = $this->normalizeFilename((string)($im['old_rutaimg'] ?? ''));
        $rutaimg = $this->normalizeFilename((string)($im['rutaimg'] ?? ''));
        $idprodu = (int)($im['idprodu'] ?? 0);
        $idcodgusto = (int)($im['idcodgusto'] ?? 0);
        if ($rutaimg === '' || $idprodu <= 0 || $idcodgusto <= 0) {
            return;
        }

        $pdo = Db::pdo();

        // 1) Update by idimagen if caller knows it
        if ($idimagen > 0) {
            $sqlU = 'UPDATE imagen SET rutaimg=:r, idprodu=:p, idcodgusto=:g WHERE idimagen=:i';
            $st = $pdo->prepare($sqlU);
            $st->execute([':r' => $rutaimg, ':p' => $idprodu, ':g' => $idcodgusto, ':i' => $idimagen]);
            if ($st->rowCount() > 0) {
                return;
            }
        }

        // 2) Update by old rutaimg (helps VFP that doesn't track idimagen)
        if ($oldRuta !== '') {
            $st = $pdo->prepare('UPDATE imagen SET rutaimg=:new WHERE idprodu=:p AND idcodgusto=:g AND rutaimg=:old');
            $st->execute([':new' => $rutaimg, ':p' => $idprodu, ':g' => $idcodgusto, ':old' => $oldRuta]);
            if ($st->rowCount() > 0) {
                return;
            }
        }

        // 3) Insert if there is room (max 6 per gusto)
        $stc = $pdo->prepare('SELECT COUNT(*) FROM imagen WHERE idcodgusto=:g');
        $stc->execute([':g' => $idcodgusto]);
        $cnt = (int)$stc->fetchColumn();
        if ($cnt >= 6) {
            // Skip silently; caller can send old_rutaimg or idimagen to replace.
            return;
        }

        // Avoid duplicates (same ruta for same gusto)
        $st = $pdo->prepare('SELECT idimagen FROM imagen WHERE idprodu=:p AND idcodgusto=:g AND rutaimg=:r LIMIT 1');
        $st->execute([':p' => $idprodu, ':g' => $idcodgusto, ':r' => $rutaimg]);
        $exists = (int)($st->fetchColumn() ?: 0);
        if ($exists > 0) {
            return;
        }

        $sqlI = 'INSERT INTO imagen (rutaimg, idprodu, idcodgusto) VALUES (:r,:p,:g)';
        $pdo->prepare($sqlI)->execute([':r' => $rutaimg, ':p' => $idprodu, ':g' => $idcodgusto]);
    }

    public function log(string $line): void
    {
        $dir = defined('APP_BASE_DIR') ? (string)APP_BASE_DIR : (string)realpath(__DIR__ . '/../..');
        $path = rtrim($dir, '/\\') . '/storage/sync.log';
        @file_put_contents($path, '[' . date('Y-m-d H:i:s') . '] ' . $line . "\n", FILE_APPEND);
    }

    private function normalizeFilename(string $v): string
    {
        $v = trim($v);
        if ($v === '' || $v === '*') {
            return '';
        }
        // If URL or path is sent, keep only basename
        $v = str_replace('\\', '/', $v);
        $v = basename($v);
        // Remove surrounding quotes
        $v = trim($v, "\"' ");
        // Replace spaces with dashes
        $v = preg_replace('/\s+/', '-', $v) ?? $v;
        // Lowercase (avoid locale issues by mapping common accents)
        $v = mb_strtolower($v);
        $v = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $v);
        return $v;
    }
}
