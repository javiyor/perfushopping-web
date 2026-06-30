<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\ArcaRepo;

final class AfipWsfe
{
    private string $url;
    private string $cuit;
    private string $token;
    private string $sign;
    private bool $homologacion;

    private static array $tipoCbteMap = [
        'FACT-A' => 1,
        'FACT-B' => 6,
        'FACT-C' => 11,
        'NC' => 8,
    ];

    private static array $condIvaMap = [
        'consumidor_final' => 4,
        'responsable_inscripto' => 1,
        'exento' => 3,
        'monotributo' => 5,
    ];

    private static array $tipoDocMap = [
        'cuit' => 80,
        'dni' => 96,
        'consumidor_final' => 99,
    ];

    public function __construct()
    {
        $repo = new ArcaRepo();
        $this->homologacion = $repo->esHomologacion();
        $this->cuit = $repo->getConfig('cuit');
        $this->url = $this->homologacion
            ? 'https://wswhomo.afip.gov.ar/wsfe/service.asmx'
            : 'https://servicios1.afip.gov.ar/wsfe/service.asmx';
    }

    public function autenticar(): void
    {
        $wsaa = new AfipWsaa();
        $ta = $wsaa->login();
        $this->token = $ta['token'];
        $this->sign = $ta['sign'];
    }

    public function getUltimoComprobanteAutorizado(int $puntoVenta, int $tipoCbte): int
    {
        $xml = $this->buildSoapRequest('FECompUltimoAutorizado', [
            'PtoVta' => $puntoVenta,
            'CbteTipo' => $tipoCbte,
        ]);

        $response = $this->call($xml);

        $dom = new \DOMDocument();
        $dom->loadXML($response);
        $nro = $dom->getElementsByTagName('CbteNro')->item(0)?->textContent ?? '0';

        return (int)$nro;
    }

    public function solicitarCAE(array $factura, array $items): array
    {
        $tipoCbte = self::$tipoCbteMap[$factura['tipo_comprobante']] ?? 6;
        $puntoVenta = (int)($factura['punto_venta'] ?? 1);
        $ultimo = $this->getUltimoComprobanteAutorizado($puntoVenta, $tipoCbte);
        $cbteNro = $ultimo + 1;

        $condIva = self::$condIvaMap[$factura['cliente_condicion_iva'] ?? 'consumidor_final'] ?? 4;
        $tipoDoc = $this->getTipoDoc($factura);
        $nroDoc = $this->getNroDoc($factura);

        $fecha = str_replace('-', '', $factura['fecha']);

        $neto = (int)($factura['subtotal_cents'] ?? 0);
        $ivaTotal = (int)($factura['iva_cents'] ?? 0);
        $total = (int)($factura['total_cents'] ?? 0);

        // Group IVA by rate
        $ivaGroups = [];
        foreach ($items as $it) {
            $rate = (float)($it['iva_rate'] ?? 21);
            $lineTotal = (int)($it['total_cents'] ?? 0);
            $lineIva = (int)($it['iva_cents'] ?? 0);
            $lineNeto = $lineTotal - $lineIva;
            $rateKey = (string)$rate;
            if (!isset($ivaGroups[$rateKey])) {
                $ivaGroups[$rateKey] = ['rate' => $rate, 'neto' => 0, 'iva' => 0];
            }
            $ivaGroups[$rateKey]['neto'] += $lineNeto;
            $ivaGroups[$rateKey]['iva'] += $lineIva;
        }

        $detalle = $this->buildDetalle($tipoCbte, $puntoVenta, $cbteNro, $fecha, $condIva, $tipoDoc, $nroDoc, $factura['cliente_nombre'] ?? '', $total, $neto, $ivaTotal, $ivaGroups, $factura['cliente_direc'] ?? '');

        $xml = $this->buildSoapRequest('FECAESolicitar', [
            'FeCAEReq' => [
                'FeCabReq' => [
                    'CantReg' => 1,
                    'PtoVta' => $puntoVenta,
                    'CbteTipo' => $tipoCbte,
                ],
                'FeDetReq' => [$detalle],
            ],
        ]);

        $response = $this->call($xml);

        return $this->parsearRespuesta($response, $xml, $cbteNro);
    }

    private function getTipoDoc(array $factura): int
    {
        $cuit = trim((string)($factura['cliente_cuit'] ?? ''));
        if ($cuit !== '') return 80;
        return 99;
    }

    private function getNroDoc(array $factura): string
    {
        $cuit = trim((string)($factura['cliente_cuit'] ?? ''));
        if ($cuit !== '') return preg_replace('/\D/', '', $cuit);
        return '0';
    }

    private function buildDetalle(int $tipoCbte, int $ptoVta, int $cbteNro, string $fecha, int $condIva, int $tipoDoc, string $nroDoc, string $razonSocial, int $total, int $neto, int $ivaTotal, array $ivaGroups, string $direccion): string
    {
        $monedaId = 'PES';
        $monedaCotiz = 1;

        $xml = '<FECAEDetRequest>';
        $xml .= "<Concepto>1</Concepto>";
        $xml .= "<DocTipo>{$tipoDoc}</DocTipo>";
        $xml .= "<DocNro>{$nroDoc}</DocNro>";
        $xml .= "<CbteDesde>{$cbteNro}</CbteDesde>";
        $xml .= "<CbteHasta>{$cbteNro}</CbteHasta>";
        $xml .= "<CbteFch>{$fecha}</CbteFch>";
        $xml .= "<ImpTotal>" . $this->centsToDecimal($total) . "</ImpTotal>";
        $xml .= "<ImpTotConc>0.00</ImpTotConc>";
        $xml .= "<ImpNeto>" . $this->centsToDecimal($neto) . "</ImpNeto>";
        $xml .= "<ImpOpEx>0.00</ImpOpEx>";
        $xml .= "<ImpIVA>" . $this->centsToDecimal($ivaTotal) . "</ImpIVA>";
        $xml .= "<ImpTrib>0.00</ImpTrib>";
        $xml .= "<MonedaId>{$monedaId}</MonedaId>";
        $xml .= "<MonedaCotiz>{$monedaCotiz}</MonedaCotiz>";

        // IVA array
        if ($ivaGroups) {
            $xml .= '<Iva>';
            $afipIvaIds = [0 => 3, 10.5 => 4, 21 => 5, 27 => 6];
            foreach ($ivaGroups as $g) {
                $rate = (float)$g['rate'];
                $ivaId = $afipIvaIds[(string)$rate] ?? 5;
                $xml .= '<AlicIva>';
                $xml .= "<Id>{$ivaId}</Id>";
                $xml .= "<BaseImp>" . $this->centsToDecimal((int)$g['neto']) . "</BaseImp>";
                $xml .= "<Importe>" . $this->centsToDecimal((int)$g['iva']) . "</Importe>";
                $xml .= '</AlicIva>';
            }
            $xml .= '</Iva>';
        }

        // Optional: customer data for Factura A
        if ($tipoCbte === 1 || $tipoCbte === 3) {
            $xml .= "<FchServDesde>{$fecha}</FchServDesde>";
            $xml .= "<FchServHasta>{$fecha}</FchServHasta>";
            $xml .= "<FchVtoPago>{$fecha}</FchVtoPago>";
        }

        $xml .= '</FECAEDetRequest>';

        return $xml;
    }

    private function centsToDecimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function buildSoapRequest(string $method, array $params): string
    {
        $body = $this->buildXmlBody($method, $params);

        $ta = (new ArcaRepo())->getTicketAccesoValido();
        $token = $ta['token'] ?? ($this->token ?? '');
        $sign = $ta['sign'] ?? ($this->sign ?? '');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://ar.gov.afip.dif.FEV1/">
    <soapenv:Header>
        <ser:Auth>
            <ser:Token>{$token}</ser:Token>
            <ser:Sign>{$sign}</ser:Sign>
            <ser:Cuit>{$this->cuit}</ser:Cuit>
        </ser:Auth>
    </soapenv:Header>
    <soapenv:Body>
        <ser:{$method}>{$body}</ser:{$method}>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    private function buildXmlBody(string $method, array $params): string
    {
        if ($method === 'FECompUltimoAutorizado') {
            return "<PtoVta>{$params['PtoVta']}</PtoVta><CbteTipo>{$params['CbteTipo']}</CbteTipo>";
        }

        if ($method === 'FECAESolicitar') {
            $req = $params['FeCAEReq'];
            $xml = '<FeCAEReq>';
            $xml .= '<FeCabReq>';
            $xml .= "<CantReg>{$req['FeCabReq']['CantReg']}</CantReg>";
            $xml .= "<PtoVta>{$req['FeCabReq']['PtoVta']}</PtoVta>";
            $xml .= "<CbteTipo>{$req['FeCabReq']['CbteTipo']}</CbteTipo>";
            $xml .= '</FeCabReq>';
            $xml .= '<FeDetReq>';
            foreach ($req['FeDetReq'] as $det) {
                $xml .= $det;
            }
            $xml .= '</FeDetReq>';
            $xml .= '</FeCAEReq>';
            return $xml;
        }

        return '';
    }

    private function call(string $xml): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml; charset=UTF-8', 'SOAPAction: http://ar.gov.afip.dif.FEV1/FECAESolicitar'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('AFIP WSFE: ' . $error);
        }
        if ($httpCode !== 200) {
            throw new \RuntimeException('AFIP WSFE: HTTP ' . $httpCode);
        }

        return $response;
    }

    private function parsearRespuesta(string $response, string $requestXml, int $cbteNro): array
    {
        $dom = new \DOMDocument();
        $dom->loadXML($response);
        $dom->preserveWhiteSpace = false;

        $resultado = $dom->getElementsByTagName('Resultado')->item(0)?->textContent ?? '';
        $cae = $dom->getElementsByTagName('CAE')->item(0)?->textContent ?? '';
        $caeVto = $dom->getElementsByTagName('CAEFchVto')->item(0)?->textContent ?? '';
        $obs = '';

        // Get observations if any
        $obsNodes = $dom->getElementsByTagName('Obs');
        $obsList = [];
        foreach ($obsNodes as $obsNode) {
            $code = $obsNode->getElementsByTagName('Code')->item(0)?->textContent ?? '';
            $msg = $obsNode->getElementsByTagName('Msg')->item(0)?->textContent ?? '';
            if ($code || $msg) {
                $obsList[] = "{$code}: {$msg}";
            }
        }
        if ($obsList) {
            $obs = implode(' | ', $obsList);
            // If rejected, throw with details
            if ($resultado === 'R') {
                throw new \RuntimeException('AFIP: comprobante rechazado. ' . $obs);
            }
        }

        // If no CAE and no result, get error info
        if (!$cae && !$resultado) {
            $fault = $dom->getElementsByTagName('faultstring')->item(0)?->textContent ?? '';
            $faultCode = $dom->getElementsByTagName('faultcode')->item(0)?->textContent ?? '';
            $detail = $fault ? "{$faultCode}: {$fault}" : 'Error desconocido al comunicar con ARCA.';
            throw new \RuntimeException('ARCA: ' . $detail);
        }

        if ($caeVto && strlen($caeVto) === 8) {
            $caeVto = substr($caeVto, 0, 4) . '-' . substr($caeVto, 4, 2) . '-' . substr($caeVto, 6, 2);
        }

        return [
            'resultado' => $resultado ?: 'R',
            'cae' => $cae ?: null,
            'cae_vto' => $caeVto ?: null,
            'codigo_emision' => $cbteNro,
            'observaciones' => $obs ?: null,
            'request_xml' => $requestXml,
            'response_xml' => $response,
        ];
    }
}
