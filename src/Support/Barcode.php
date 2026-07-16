<?php
declare(strict_types=1);

namespace Perfushopping\Web\Support;

final class Barcode
{
    public static function ean13(int $idcodgusto): string
    {
        $digits = str_pad((string)$idcodgusto, 12, '9', STR_PAD_LEFT);
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$digits[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $check = (10 - ($sum % 10)) % 10;
        return $digits . $check;
    }

    public static function ean13Svg(string $ean): string
    {
        if (strlen($ean) !== 13 || !ctype_digit($ean)) {
            $ean = str_pad($ean, 13, '0');
        }

        $patterns = [
            '0' => ['LLLRRR', '0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'],
            '1' => ['LLRLRR', '0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'],
        ];

        $bars = [];
        $bars[] = ['w' => 1, 'c' => '#000']; // start guard
        $bars[] = ['w' => 1, 'c' => '#fff'];
        $bars[] = ['w' => 1, 'c' => '#000'];

        for ($i = 0; $i < 6; $i++) {
            $d = (int)$ean[$i + 1];
            $code = $patterns[0][1][$d];
            for ($j = 0; $j < 7; $j++) {
                $bars[] = ['w' => 1, 'c' => $code[$j] === '1' ? '#000' : '#fff'];
            }
        }

        $bars[] = ['w' => 1, 'c' => '#fff']; // middle guard
        $bars[] = ['w' => 1, 'c' => '#000'];
        $bars[] = ['w' => 1, 'c' => '#fff'];
        $bars[] = ['w' => 1, 'c' => '#000'];
        $bars[] = ['w' => 1, 'c' => '#fff'];

        for ($i = 7; $i < 13; $i++) {
            $d = (int)$ean[$i];
            $code = $patterns[0][1][$d];
            for ($j = 0; $j < 7; $j++) {
                $bars[] = ['w' => 1, 'c' => $code[$j] === '1' ? '#000' : '#fff'];
            }
        }

        $bars[] = ['w' => 1, 'c' => '#000']; // end guard
        $bars[] = ['w' => 1, 'c' => '#fff'];
        $bars[] = ['w' => 1, 'c' => '#000'];

        $totalWidth = 0;
        foreach ($bars as $b) $totalWidth += $b['w'];
        $scale = 2;
        $svgWidth = $totalWidth * $scale;
        $svgHeight = 60;

        $x = 0;
        $rects = '';
        foreach ($bars as $b) {
            if ($b['c'] === '#000') {
                $rects .= '<rect x="' . $x * $scale . '" y="0" width="' . ($b['w'] * $scale) . '" height="' . $svgHeight . '" fill="#000"/>';
            }
            $x += $b['w'];
        }

        $textY = $svgHeight + 12;
        $svgHeight += 16;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '" style="width:' . $svgWidth . 'px;height:' . $svgHeight . 'px">
            ' . $rects . '
            <text x="7" y="' . ($textY - 2) . '" font-family="monospace" font-size="10" fill="#000">' . $ean[0] . '</text>
            <text x="' . (7 * $scale + 5) . '" y="' . ($textY - 2) . '" font-family="monospace" font-size="10" fill="#000">' . substr($ean, 1, 6) . '</text>
            <text x="' . (43 * $scale + 5) . '" y="' . ($textY - 2) . '" font-family="monospace" font-size="10" fill="#000">' . substr($ean, 7) . '</text>
        </svg>';
    }
}
