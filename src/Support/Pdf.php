<?php
declare(strict_types=1);

namespace Perfushopping\Web\Support;

final class Pdf
{
    /**
     * Genera un PDF (A4) a partir de líneas de texto (Helvetica).
     * @param array<int, array{text:string, bold?:bool, size?:float, indent?:float}> $lines
     */
    public static function render(array $lines, float $lineHeight = 14, float $margin = 40, float $pageWidth = 595, float $pageHeight = 842): string
    {
        $pages = [];
        $stream = '';
        $y = $pageHeight - $margin;
        foreach ($lines as $l) {
            if ($y < $margin) {
                $pages[] = $stream;
                $stream = '';
                $y = $pageHeight - $margin;
            }
            $text = mb_substr(self::toAscii((string)($l['text'] ?? '')), 0, 95);
            $size = (float)($l['size'] ?? 10);
            $x = $margin + (float)($l['indent'] ?? 0);
            $font = !empty($l['bold']) ? 'F2' : 'F1';
            $stream .= sprintf("BT /%s %.1f Tf %.2f %.2f Td (%s) Tj ET\n", $font, $size, $x, $y, self::escape($text));
            $y -= $lineHeight;
        }
        if ($stream !== '') {
            $pages[] = $stream;
        }
        if (!$pages) {
            $pages = [''];
        }

        $objNum = 5;
        $pageObjs = [];
        for ($i = 0, $n = count($pages); $i < $n; $i++) {
            $pageObjs[] = $objNum;
            $objNum += 2;
        }

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(static fn (int $n): string => $n . ' 0 R', $pageObjs)) . '] /Count ' . count($pages) . ' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
        foreach ($pages as $i => $stream) {
            $pageObj = $pageObjs[$i];
            $contentObj = $pageObj + 1;
            $objects[$pageObj] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $pageWidth . ' ' . $pageHeight . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentObj . ' 0 R >>';
            $objects[$contentObj] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
        }

        $out = "%PDF-1.4\n";
        $offsets = [];
        for ($i = 1; $i < $objNum; $i++) {
            $offsets[$i] = strlen($out);
            $out .= $i . " 0 obj\n" . $objects[$i] . "\nendobj\n";
        }
        $xref = strlen($out);
        $out .= "xref\n0 " . $objNum . "\n";
        $out .= "0000000000 65535 f \n";
        for ($i = 1; $i < $objNum; $i++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $out .= "trailer\n<< /Size " . $objNum . " /Root 1 0 R >>\n";
        $out .= "startxref\n" . $xref . "\n%%EOF\n";
        return $out;
    }

    private static function escape(string $s): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }

    private static function toAscii(string $s): string
    {
        $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','ñ'=>'n','Ñ'=>'N','ü'=>'u','Ü'=>'U','¿'=>'?','¡'=>'!'];
        return (string)preg_replace('/[^\x20-\x7E]/', '', strtr($s, $map));
    }
}
