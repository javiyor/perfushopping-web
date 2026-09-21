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

    private static array $condIvaMap = [
        'consumidor_final' => 4,
        'responsable_inscripto' => 1,
        'exento' => 3,
        'monotributo' => 5,
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

    private static array $tipoDocMap = [
        'cuit' => 80,
        'dni' => 96,
        'consumidor_final' => 99,
    ];

    private const NS = 'http://ar.gov.afip.dif.facturaelectronica/';

    public function __construct()
    {
        $repo = new ArcaRepo();
        $this->homologacion = $repo->esHomologacion();
        $this->cuit = preg_replace('/\D/', '', $repo->getConfig('cuit'));
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

        $body = '<FERecuperaLastCMPRequest xmlns="' . self::NS . '">';
        $body .= $this->buildAuthXml();
        $body .= '<argTCMP><PtoVta>' . $puntoVenta . '</PtoVta><TipoCbte>' . $tipoCbte . '</TipoCbte></argTCMP>';
        $body .= '</FERecuperaLastCMPRequest>';

        $xml = $this->buildEnvelope($body);
        $response = $this->call($xml, 'FERecuperaLastCMPRequest');

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
        $subtotalOriginal = (int)($factura['subtotal_cents'] ?? 0);
        $ivaTotal = (int)($factura['iva_cents'] ?? 0);

        $neto = $subtotalOriginal - $descuento;
        if ($neto < 0) {
            $neto = 0;
        }
        $total = $neto + $ivaTotal;

        $imptoLiq = $ivaTotal;
        $impTotConc = 0;
        $imptoLiqRni = 0;
        $impOpEx = 0;

        // Generamos un id de lote simple (dentro de 32 bits)
        $id = random_int(1, 2147483647);

        $puntoVenta = $this->resolvePuntoVentaArca($factura);

        $detalle = '<FEDetalleRequest>';
        $detalle .= '<tipo_doc>' . $tipoDoc . '</tipo_doc>';
        $detalle .= '<nro_doc>' . $nroDoc . '</nro_doc>';
        $detalle .= '<tipo_cbte>' . $tipoCbte . '</tipo_cbte>';
        $detalle .= '<punto_vta>' . $puntoVenta . '</punto_vta>';
        $detalle .= '<cbt_desde>' . $cbteNro . '</cbt_desde>';
        $detalle .= '<cbt_hasta>' . $cbteNro . '</cbt_hasta>';
        $detalle .= '<imp_total>' . $this->centsToDecimal($total) . '</imp_total>';
        $detalle .= '<imp_tot_conc>' . $this->centsToDecimal($impTotConc) . '</imp_tot_conc>';
        $detalle .= '<imp_neto>' . $this->centsToDecimal($neto) . '</imp_neto>';
        $detalle .= '<impto_liq>' . $this->centsToDecimal($imptoLiq) . '</impto_liq>';
        $detalle .= '<impto_liq_rni>' . $this->centsToDecimal($imptoLiqRni) . '</impto_liq_rni>';
        $detalle .= '<imp_op_ex>' . $this->centsToDecimal($impOpEx) . '</imp_op_ex>';
        $detalle .= '<fecha_cbte>' . $fecha . '</fecha_cbte>';
        $detalle .= '<fecha_venc_pago>' . $fechaVencPago . '</fecha_venc_pago>';
        $condIvaReceptor = self::$condIvaReceptorMap[$factura['cliente_condicion_iva'] ?? ''] ?? 5;
        $detalle .= '<Cond_IVA_Receptor_Id>' . $condIvaReceptor . '</Cond_IVA_Receptor_Id>';
        $detalle .= '</FEDetalleRequest>';

        $body = '<FEAutRequest xmlns="' . self::NS . '">';
        $body .= $this->buildAuthXml();
        $body .= '<Fer>';
        $body .= '<Fecr><id>' . $id . '</id><cantidadreg>1</cantidadreg><presta_serv>0</presta_serv></Fecr>';
        $body .= '<Fedr>' . $detalle . '</Fedr>';
        $body .= '</Fer>';
        $body .= '</FEAutRequest>';

        $xml = $this->buildEnvelope($body);
        $response = $this->call($xml, 'FEAutRequest');

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
        return '<argAuth>'
            . '<Token>' . htmlspecialchars($this->token) . '</Token>'
            . '<Sign>' . htmlspecialchars($this->sign) . '</Sign>'
            . '<cuit>' . $this->cuit . '</cuit>'
            . '</argAuth>';
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

        $resultNode = $dom->getElementsByTagName('FEAutRequestResult')->item(0);
        if ($resultNode === null) {
            $fault = $dom->getElementsByTagName('faultstring')->item(0)?->textContent ?? '';
            throw new \RuntimeException('ARCA: respuesta inesperada. ' . $fault);
        }

        $percode = $resultNode->getElementsByTagName('percode')->item(0)?->textContent ?? '';
        if ($percode !== '' && (int)$percode !== 0) {
            $perrmsg = $resultNode->getElementsByTagName('perrmsg')->item(0)?->textContent ?? '';
            throw new \RuntimeException('ARCA: error ' . $percode . ' - ' . $perrmsg);
        }

        $resultado = $resultNode->getElementsByTagName('resultado')->item(0)?->textContent ?? '';
        $motivoHeader = $resultNode->getElementsByTagName('motivo')->item(0)?->textContent ?? '';
        $reproceso = $resultNode->getElementsByTagName('reproceso')->item(0)?->textContent ?? '';

        $detResp = $resultNode->getElementsByTagName('FEDetalleResponse')->item(0);
        $cae = $detResp?->getElementsByTagName('cae')->item(0)?->textContent ?? '';
        $caeVto = $detResp?->getElementsByTagName('fecha_vto')->item(0)?->textContent ?? '';
        $motivoDet = $detResp?->getElementsByTagName('motivo')->item(0)?->textContent ?? '';

        $motivo = $motivoHeader;
        if ($motivo === '' || $motivo === 'NULL' || $motivo === '00') {
            $motivo = $motivoDet;
        }

        $obs = '';
        if ($motivo !== '' && $motivo !== 'NULL' && $motivo !== '00') {
            $obs = 'Motivo: ' . $motivo;
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
