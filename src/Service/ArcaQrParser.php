<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

final class ArcaQrParser
{
    private const TIPOS = [
        '1' => 'Factura A',
        '2' => 'Nota de Débito A',
        '3' => 'Nota de Crédito A',
        '4' => 'Recibo A',
        '5' => 'Nota de Venta al Contado A',
        '6' => 'Factura B',
        '7' => 'Nota de Débito B',
        '8' => 'Nota de Crédito B',
        '9' => 'Recibo B',
        '10' => 'Nota de Venta al Contado B',
        '11' => 'Factura C',
        '12' => 'Nota de Débito C',
        '13' => 'Nota de Crédito C',
        '14' => 'Recibo C',
        '15' => 'Factura M',
        '16' => 'Nota de Débito M',
        '17' => 'Nota de Crédito M',
        '51' => 'Factura C (Monotributo)',
        '52' => 'Nota de Débito C (Monotributo)',
        '53' => 'Nota de Crédito C (Monotributo)',
        '201' => 'Factura C (Monotributo)',
        '206' => 'Factura C (Monotributo)',
        '211' => 'Factura C (Monotributo)',
        '212' => 'Nota de Crédito C (Monotributo)',
    ];

    /**
     * Parsea el texto de un QR de ARCA (https://www.afip.gob.ar/fe/qr/?p=1&v=...&c=...&n=...&i=...&f=...&t=...)
     * @return array{cuit:string, razon:string, imp_total:float, cod_tipo:string, tipo:string, punto_venta:string, numero:string, fecha:?string}
     */
    public function parse(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new \RuntimeException('El texto del QR está vacío.');
        }
        // Si viene sin esquema, lo forzamos para poder usar parse_url/parse_str.
        $url = $text;
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://qr/?q=' . ltrim($url, '?');
        }
        $query = '';
        $parts = parse_url($url);
        if (is_array($parts)) {
            $query = (string)($parts['query'] ?? '');
        }
        if ($query === '' && str_contains($url, '?')) {
            $query = (string)substr($url, strpos($url, '?') + 1);
        }
        $params = [];
        parse_str($query, $params);
        if ($params === []) {
            throw new \RuntimeException('No se encontraron parámetros en el QR.');
        }

        $cuit = \Perfushopping\Web\Service\ExcelReader::digits((string)($params['c'] ?? ''));
        $codTipo = (string)($params['n'] ?? '');
        $puntoVenta = \Perfushopping\Web\Service\ExcelReader::digits((string)($params['i'] ?? ''));
        $numero = \Perfushopping\Web\Service\ExcelReader::digits((string)($params['f'] ?? ''));
        $fecha = isset($params['t']) && (string)$params['t'] !== ''
            ? \Perfushopping\Web\Service\ExcelReader::toDate((string)$params['t'])
            : null;
        $impTotal = (float)(\Perfushopping\Web\Service\ExcelReader::toFloat((string)($params['v'] ?? '0')));

        if ($cuit === '') {
            throw new \RuntimeException('El QR no contiene CUIT del emisor (parámetro c).');
        }

        return [
            'cuit' => $cuit,
            'razon' => '',
            'imp_total' => $impTotal,
            'cod_tipo' => $codTipo,
            'tipo' => self::TIPOS[$codTipo] ?? ('Comprobante ' . $codTipo),
            'punto_venta' => $puntoVenta,
            'numero' => $numero,
            'fecha' => $fecha,
        ];
    }
}
