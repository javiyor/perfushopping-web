<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

final class ExcelReader
{
    /**
     * Lee un archivo XLSX (o CSV) y devuelve filas asociativas clave = header normalizado.
     * @return array<int, array<string,string>>
     */
    public function readRows(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('El archivo no existe.');
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'xlsx') {
            $rows = $this->readXlsx($path);
        } elseif (in_array($ext, ['csv', 'txt'], true)) {
            $rows = $this->readCsv($path);
        } else {
            throw new \RuntimeException('Formato no soportado. Subí un archivo .xlsx o .csv (ARCA descarga .xlsx).');
        }
        return $this->toAssoc($rows);
    }

    /** @return array<int, array<int, string>> */
    private function readXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('No se pudo abrir el archivo .xlsx (zip inválido).');
        }

        // Shared strings
        $shared = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $ss = @simplexml_load_string($ssXml);
            if ($ss !== false) {
                foreach ($ss->si as $si) {
                    $txt = '';
                    foreach ($si->t as $t) {
                        $txt .= (string)$t;
                    }
                    if (isset($si->r)) {
                        $txt = '';
                        foreach ($si->r as $r) {
                            $txt .= (string)$r->t;
                        }
                    }
                    $shared[] = $txt;
                }
            }
        }

        // First worksheet
        $sheetName = 'xl/worksheets/sheet1.xml';
        if ($zip->locateName($sheetName) === false) {
            // pick first entry under xl/worksheets/
            $sheetName = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (is_string($name) && str_starts_with($name, 'xl/worksheets/') && str_ends_with($name, '.xml')) {
                    $sheetName = $name;
                    break;
                }
            }
        }
        $sheetXml = $sheetName !== null ? $zip->getFromName($sheetName) : false;
        $zip->close();

        if ($sheetXml === false) {
            throw new \RuntimeException('El .xlsx no contiene hojas de cálculo.');
        }

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false) {
            throw new \RuntimeException('No se pudo leer la hoja del .xlsx.');
        }

        $rows = [];
        $ns = $xml->getNamespaces(true);
        $mainNs = $ns[''] ?? null;
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string)($c['r'] ?? '');
                $col = preg_replace('/\d+$/', '', $ref);
                $type = (string)($c['t'] ?? '');
                $val = '';
                if ($type === 's') {
                    $idx = (int)trim((string)$c->v);
                    $val = $shared[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string)$c->is->t;
                } elseif ($type === 'b') {
                    $val = ((string)$c->v === '1') ? '1' : '0';
                } else {
                    $val = trim((string)$c->v);
                }
                if ($col !== '') {
                    $cells[$col] = $val;
                }
            }
            // rebuild in column order
            $sorted = [];
            $cols = array_keys($cells);
            usort($cols, static fn (string $a, string $b): int => strcmp($a, $b));
            foreach ($cols as $col) {
                $sorted[] = $cells[$col];
            }
            $rows[] = $sorted;
        }
        return $rows;
    }

    /** @return array<int, array<int, string>> */
    private function readCsv(string $path): array
    {
        $content = (string)file_get_contents($path);
        if ($content === '') {
            return [];
        }
        // Strip UTF-8 BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }
        $lines = preg_split('/\r\n|\n|\r/', $content);
        if (!is_array($lines)) {
            return [];
        }

        // Detect delimiter
        $first = $lines[0] ?? '';
        $semi = substr_count($first, ';');
        $comma = substr_count($first, ',');
        $delim = $semi > $comma ? ';' : ',';

        $rows = [];
        foreach ($lines as $line) {
            if (trim((string)$line) === '') {
                continue;
            }
            $row = str_getcsv($line, $delim);
            $rows[] = $row;
        }
        return $rows;
    }

    /** @param array<int, array<int, string>> $rows
     *  @return array<int, array<string, string>>
     */
    private function toAssoc(array $rows): array
    {
        if (!$rows) {
            return [];
        }
        $headers = [];
        $first = $rows[0];
        foreach ($first as $i => $h) {
            $norm = self::normHeader((string)$h);
            $headers[$i] = $norm !== '' ? $norm : ('col' . $i);
        }
        $out = [];
        for ($i = 1, $n = count($rows); $i < $n; $i++) {
            $row = $rows[$i];
            $assoc = [];
            foreach ($headers as $idx => $key) {
                $assoc[$key] = (string)($row[$idx] ?? '');
            }
            $out[] = $assoc;
        }
        return $out;
    }

    public static function normHeader(string $h): string
    {
        $h = mb_strtolower(trim($h), 'UTF-8');
        $h = strtr($h, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'ñ' => 'n', 'Ñ' => 'n',
        ]);
        return (string)preg_replace('/[^a-z0-9]/', '', $h);
    }

    public static function toFloat(string $s): float
    {
        $s = trim((string)$s);
        if ($s === '' || !is_numeric(str_replace([',', '.', ' ', '$'], '', $s))) {
            return 0.0;
        }
        $s = str_replace(['$', ' '], '', $s);
        if (str_contains($s, ',')) {
            // Estilo AR: puntos = miles, coma = decimal
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (substr_count($s, '.') > 1) {
            // Miles sin decimales
            $s = str_replace('.', '', $s);
        }
        return (float)$s;
    }

    public static function toDate(string $s): ?string
    {
        $s = trim((string)$s);
        if ($s === '') {
            return null;
        }
        if (is_numeric($s) && (float)$s > 1) {
            $days = (int)$s;
            $base = strtotime('1899-12-30');
            if ($base === false) {
                return null;
            }
            return date('Y-m-d', $base + $days * 86400);
        }
        $s = (string)preg_replace('/\s+\d{1,2}:\d{2}(:\d{2})?.*$/', '', $s);
        foreach (['d/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y', 'Y-m-d', 'Y/m/d', 'j/n/Y', 'j-n-Y'] as $f) {
            $d = \DateTime::createFromFormat($f, $s);
            if ($d !== false) {
                $check = $d->format($f);
                if ($check === $s) {
                    return $d->format('Y-m-d');
                }
            }
        }
        $t = strtotime($s);
        if ($t !== false) {
            return date('Y-m-d', $t);
        }
        return null;
    }

    public static function digits(string $s): string
    {
        return (string)preg_replace('/\D/', '', (string)$s);
    }
}
