<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\ArcaRepo;

final class AfipWsfe
{
    private string $url;
    private string $cuit;
    private string $token = '';
    private string $sign = '';
    private bool $homologacion;
    private string $lastRequest = '';
    private string $lastResponse = '';
    private ?string $debugTag = null;

    public function setDebugTag(string $tag): void
    {
        $this->debugTag = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tag);
    }

    private function writeDebugFiles(): void
    {
        if ($this->debugTag === null || $this->debugTag === '') {
            return;
        }
        if (!defined('APP_BASE_DIR')) {
            return;
        }
        $dir = rtrim((string)APP_BASE_DIR, '/\\') . '/storage/arca';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($dir . '/' . $this->debugTag . '-request.xml', $this->lastRequest);
        @file_put_contents($dir . '/' . $this->debugTag . '-response.xml', $this->lastResponse);
    }

    private function resolvePuntoVentaArca(array $factura): int
    {
        $sucursalId = (int)($factura['sucursal_id'] ?? 0);
        if ($sucursalId > 0) {
            $arcaPv = (new \Perfushopping\Web\Repo\SucursalRepo())->puntoVentaArca($sucursalId);
            if ($arcaPv !== null && $arcaPv > 0) {
                return $arcaPv;
            }
        }
        $internalPv = (int)($factura['punto_venta'] ?? 1);
        $arcaPv = (new \Perfushopping\Web\Repo\SucursalRepo())->puntoVentaArcaPorPuntoVenta($internalPv);
        if ($arcaPv !== null && $arcaPv > 0) {
            return $arcaPv;
        }
        return $internalPv;
    }

    private static array $tipoCbteMap = [
        'FACT-A' => 1,
        'FACT-B' => 6,
        'FACT-C' => 11,
        'NC' => 8,
        'ND' => 9,
    ];

    private static array $condIvaReceptorMap = [
        'responsable_inscripto' => 1,
        'responsable_no_inscripto' => 2,
        'no_responsable' => 3,
        'exento' => 4,
        'consumidor_final' => 5,
        'monotributo' => 6,
        'monotributista' => 6,
        'sujeto_no_categorizado' => 7,
    ];

    private static array $alicuotaIvaMap = [
        0 => 3,
        10.5 => 4,
        21 => 5,
        27 => 6,
        5 => 8,
        2.5 => 9,
    ];

    private const NS = 'http://ar.gov.afip.dif.FEV1/';

    public function __construct()
    {
        $repo = new ArcaRepo();
        $this->homologacion = $repo->esHomologacion();
        $this->cuit = preg_replace('/\D/', '', $repo->getConfig('cuit'));
        $this->url = $this->homologacion
            ? 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx'
            : 'https://servicios1.afip.gov.ar/wsfev1/service.asmx';
    }

    public function autenticar(): void
    {
        $wsaa = new AfipWsaa();
        $ta = $wsaa->login();
        $this->token = $ta['token'];
        $this->sign = $ta['sign'];
    }

    public function autenticarSiNecesario(): void
    {
        if ($this->token === '' || $this->sign === '') {
            $ta = (new ArcaRepo())->getTicketAccesoValido();
            if ($ta) {
                $this->token = $ta['token'];
                $this->sign = $ta['sign'];
            } else {
                $this->autenticar();
            }
        }
    }

    public function getUltimoComprobanteAutorizado(int $puntoVenta, int $tipoCbte): int
    {
        $this->autenticarSiNecesario();

        $body = '<FECompUltimoAutorizado xmlns="' . self::NS . '">';
        $body .= $this->buildAuthXml();
        $body .= '<PtoVta>' . $puntoVenta . '</PtoVta>';
        $body .= '<CbteTipo>' . $tipoCbte . '</CbteTipo>';
        $body .= '</FECompUltimoAutorizado>';

        $xml = $this->buildEnvelope($body);
        $response = $this->call($xml, 'FECompUltimoAutorizado');

        $dom = new \DOMDocument();
        $dom->loadXML($response);
        $nro = $dom->getElementsByTagName('cbte_nro')->item(0)?->textContent ?? '0';

        return (int)$nro;
    }

    public function solicitarCAE(array $factura, array $items): array
    {
        $tipoCbte = self::$tipoCbteMap[$factura['tipo_comprobante']] ?? 6;
        $puntoVenta = $this->resolvePuntoVentaArca($factura);
        $ultimo = $this->getUltimoComprobanteAutorizado($puntoVenta, $tipoCbte);
        $cbteNro = $ultimo + 1;

        $tipoDoc = $this->getTipoDoc($factura);
        $nroDoc = $this->getNroDoc($factura);

        $fecha = str_replace('-', '', $factura['fecha']);
        $fechaVencPago = $fecha;

        $descuento = (int)($factura['descuento_cents'] ?? 0);

        // Agrupar neto/iva por alícuota
        $grupos = [];
        foreach ($items as $it) {
            $rate = (float)($it['iva_rate'] ?? 0);
            $lineIva = (int)($it['iva_cents'] ?? 0);
            $lineTotal = (int)($it['total_cents'] ?? 0);
            $lineNet = $lineTotal - $lineIva;
            if ($lineNet < 0) {
                $lineNet = 0;
            }
            if (!isset($grupos[$rate])) {
                $grupos[$rate] = ['base' => 0, 'iva' => 0];
            }
            $grupos[$rate]['base'] += $lineNet;
            $grupos[$rate]['iva'] += $lineIva;
        }

        // Distribuir descuento proporcionalmente sobre las bases
        if ($descuento > 0) {
            $totalBase = array_sum(array_column($grupos, 'base'));
            if ($totalBase > 0) {
                foreach ($grupos as $rate => &$g) {
                    $g['base'] -= (int)round($descuento * $g['base'] / $totalBase);
                    if ($g['base'] < 0) {
                        $g['base'] = 0;
                    }
                }
                unset($g);
            }
        }

        $impNeto = array_sum(array_column($grupos, 'base'));
        $impIva = array_sum(array_column($grupos, 'iva'));
        $impTotal = $impNeto + $impIva;

        // Para Factura C no se informa IVA
        $esFacturaC = $tipoCbte === 11;
        if ($esFacturaC) {
            $impNeto = $impTotal;
            $impIva = 0;
            $grupos = [];
        }

        $condIvaReceptor = self::$condIvaReceptorMap[$factura['cliente_condicion_iva'] ?? ''] ?? 5;

        $detalle = '<FECAEDetRequest>';
        $detalle .= '<Concepto>1</Concepto>';
        $detalle .= '<DocTipo>' . $tipoDoc . '</DocTipo>';
        $detalle .= '<DocNro>' . $nroDoc . '</DocNro>';
        $detalle .= '<CbteDesde>' . $cbteNro . '</CbteDesde>';
        $detalle .= '<CbteHasta>' . $cbteNro . '</CbteHasta>';
        $detalle .= '<CbteFch>' . $fecha . '</CbteFch>';
        $detalle .= '<ImpTotal>' . $this->centsToDecimal($impTotal) . '</ImpTotal>';
        $detalle .= '<ImpTotConc>' . $this->centsToDecimal(0) . '</ImpTotConc>';
        $detalle .= '<ImpNeto>' . $this->centsToDecimal($impNeto) . '</ImpNeto>';
        $detalle .= '<ImpOpEx>' . $this->centsToDecimal(0) . '</ImpOpEx>';
        $detalle .= '<ImpTrib>' . $this->centsToDecimal(0) . '</ImpTrib>';
        $detalle .= '<ImpIVA>' . $this->centsToDecimal($impIva) . '</ImpIVA>';
        $detalle .= '<FchVtoPago>' . $fechaVencPago . '</FchVtoPago>';
        $detalle .= '<MonId>PES</MonId>';
        $detalle .= '<MonCotiz>1.000000</MonCotiz>';
        $detalle .= '<CondicionIVAReceptorId>' . $condIvaReceptor . '</CondicionIVAReceptorId>';

        $ivaXml = '';
        foreach ($grupos as $rate => $g) {
            if ($g['iva'] <= 0) {
                continue;
            }
            $id = self::$alicuotaIvaMap[$rate] ?? 5;
            $ivaXml .= '<AlicIva>';
            $ivaXml .= '<Id>' . $id . '</Id>';
            $ivaXml .= '<BaseImp>' . $this->centsToDecimal($g['base']) . '</BaseImp>';
            $ivaXml .= '<Importe>' . $this->centsToDecimal($g['iva']) . '</Importe>';
            $ivaXml .= '</AlicIva>';
        }
        if ($ivaXml !== '') {
            $detalle .= '<Iva>' . $ivaXml . '</Iva>';
        }

        $detalle .= '</FECAEDetRequest>';

        $body = '<FECAESolicitar xmlns="' . self::NS . '">';
        $body .= $this->buildAuthXml();
        $body .= '<FeCAEReq>';
        $body .= '<FeCabReq><CantReg>1</CantReg><PtoVta>' . $puntoVenta . '</PtoVta><CbteTipo>' . $tipoCbte . '</CbteTipo></FeCabReq>';
        $body .= '<FeDetReq>' . $detalle . '</FeDetReq>';
        $body .= '</FeCAEReq>';
        $body .= '</FECAESolicitar>';

        $xml = $this->buildEnvelope($body);
        $response = $this->call($xml, 'FECAESolicitar');

        return $this->parsearRespuesta($response, $xml, $cbteNro);
    }

    public static function getTipoCbteCode(string $tipoComprobante): int
    {
        return self::$tipoCbteMap[$tipoComprobante] ?? 6;
    }

    public function getUrlQr(array $factura, int $codigoEmision, string $cae): string
    {
        $tipoCbte = self::getTipoCbteCode($factura['tipo_comprobante'] ?? 'FACT-B');
        $puntoVenta = $this->resolvePuntoVentaArca($factura);

        $descuento = (int)($factura['descuento_cents'] ?? 0);
        $subtotal = (int)($factura['subtotal_cents'] ?? 0);
        $iva = (int)($factura['iva_cents'] ?? 0);
        $importeNeto = $subtotal - $descuento;
        if ($importeNeto < 0) {
            $importeNeto = 0;
        }
        $importeTotal = $importeNeto + $iva;

        $condIva = $factura['cliente_condicion_iva'] ?? 'consumidor_final';
        $tipoDocRec = $condIva === 'consumidor_final' ? 99 : 80;
        $nroDocRec = '0';
        $cuit = trim((string)($factura['cliente_cuit'] ?? ''));
        if ($cuit !== '') {
            $tipoDocRec = 80;
            $nroDocRec = preg_replace('/\D/', '', $cuit);
        }

        $data = [
            'ver' => 1,
            'fecha' => $factura['fecha'] ?? date('Y-m-d'),
            'cuit' => (int)$this->cuit,
            'ptoVta' => $puntoVenta,
            'tipoCbte' => $tipoCbte,
            'nroCbte' => $codigoEmision,
            'importe' => $importeTotal / 100,
            'moneda' => 'PES',
            'ctz' => 1,
            'tipoDocRec' => $tipoDocRec,
            'nroDocRec' => (int)$nroDocRec,
            'tipoCodAut' => 'E',
            'codAut' => (int)$cae,
        ];

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $p = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');

        return 'https://www.afip.gob.ar/fe/qr/?p=' . $p;
    }

    private function getTipoDoc(array $factura): int
    {
        $cuit = trim((string)($factura['cliente_cuit'] ?? ''));
        if ($cuit !== '') {
            return 80;
        }
        return 99;
    }

    private function getNroDoc(array $factura): string
    {
        $cuit = trim((string)($factura['cliente_cuit'] ?? ''));
        if ($cuit !== '') {
            return preg_replace('/\D/', '', $cuit);
        }
        return '0';
    }

    private function buildAuthXml(): string
    {
        return '<Auth>'
            . '<Token>' . htmlspecialchars($this->token) . '</Token>'
            . '<Sign>' . htmlspecialchars($this->sign) . '</Sign>'
            . '<Cuit>' . $this->cuit . '</Cuit>'
            . '</Auth>';
    }

    private function buildEnvelope(string $body): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <soap:Body>
        {$body}
    </soap:Body>
</soap:Envelope>
XML;
    }

    private function centsToDecimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function call(string $xml, string $method): string
    {
        $soapAction = self::NS . $method;
        $this->lastRequest = $xml;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml; charset=UTF-8', 'SOAPAction: "' . $soapAction . '"'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $this->lastResponse = is_string($response) ? $response : '';
        $this->writeDebugFiles();
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('AFIP WSFE: ' . $error);
        }
        if ($httpCode !== 200) {
            error_log('AFIP WSFE [' . $this->url . '] HTTP ' . $httpCode . ' REQUEST: ' . $xml);
            error_log('AFIP WSFE [' . $this->url . '] HTTP ' . $httpCode . ' RESPONSE: ' . $this->lastResponse);
            throw new \RuntimeException('AFIP WSFE: HTTP ' . $httpCode);
        }

        return $response;
    }

    private function parsearRespuesta(string $response, string $requestXml, int $cbteNro): array
    {
        $dom = new \DOMDocument();
        $dom->loadXML($response);
        $dom->preserveWhiteSpace = false;

        $resultNode = $dom->getElementsByTagName('FECAESolicitarResult')->item(0);
        if ($resultNode === null) {
            $fault = $dom->getElementsByTagName('faultstring')->item(0)?->textContent ?? '';
            throw new \RuntimeException('ARCA: respuesta inesperada. ' . $fault);
        }

        $errors = $resultNode->getElementsByTagName('Err');
        $errorMsgs = [];
        foreach ($errors as $err) {
            $code = $err->getElementsByTagName('Code')->item(0)?->textContent ?? '';
            $msg = $err->getElementsByTagName('Msg')->item(0)?->textContent ?? '';
            if ($code !== '') {
                $errorMsgs[] = $code . ' - ' . $msg;
            }
        }
        if ($errorMsgs) {
            throw new \RuntimeException('ARCA: ' . implode(' / ', $errorMsgs));
        }

        $cabResp = $resultNode->getElementsByTagName('FeCabResp')->item(0);
        $resultado = $cabResp?->getElementsByTagName('Resultado')->item(0)?->textContent ?? '';
        $reproceso = $cabResp?->getElementsByTagName('Reproceso')->item(0)?->textContent ?? '';

        $detResp = $resultNode->getElementsByTagName('FECAEDetResponse')->item(0);
        $cae = $detResp?->getElementsByTagName('CAE')->item(0)?->textContent ?? '';
        $caeVto = $detResp?->getElementsByTagName('CAEFchVto')->item(0)?->textContent ?? '';
        $obs = $detResp?->getElementsByTagName('Observaciones')->item(0)?->textContent ?? '';

        if ($cae === 'NULL' || $cae === '') {
            $cae = '';
        }

        if ($resultado === 'R') {
            throw new \RuntimeException('ARCA: comprobante rechazado. ' . ($obs ?: 'Sin detalle.'));
        }

        if (!$cae) {
            throw new \RuntimeException('ARCA: no se obtuvo CAE. ' . ($obs ?: 'Sin detalle.'));
        }

        if ($caeVto && strlen($caeVto) === 8) {
            $caeVto = substr($caeVto, 0, 4) . '-' . substr($caeVto, 4, 2) . '-' . substr($caeVto, 6, 2);
        }

        return [
            'resultado' => $resultado ?: 'A',
            'cae' => $cae,
            'cae_vto' => $caeVto ?: null,
            'codigo_emision' => $cbteNro,
            'observaciones' => $obs ?: null,
            'request_xml' => $requestXml,
            'response_xml' => $response,
        ];
    }

    public function lastRequest(): string
    {
        return $this->lastRequest;
    }

    public function lastResponse(): string
    {
        return $this->lastResponse;
    }
}
