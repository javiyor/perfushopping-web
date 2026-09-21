<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\ArcaRepo;

final class AfipWsaa
{
    private string $certPath;
    private string $keyPath;
    private string $url;
    private bool $homologacion;
    private string $lastRequest = '';
    private string $lastResponse = '';

    private static array $serviceDestMap = [
        'wsfe' => [
            'homo' => 'https://wswhomo.afip.gov.ar/wsfe/service.asmx',
            'prod' => 'https://servicios1.afip.gov.ar/wsfe/service.asmx',
        ],
        'ws_sr_padron_a5' => [
            'homo' => 'https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA5?wsdl',
            'prod' => 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA5?wsdl',
        ],
    ];

    public function __construct()
    {
        $repo = new ArcaRepo();
        $this->homologacion = $repo->esHomologacion();
        $this->certPath = $repo->getConfig('cert_path');
        $this->keyPath = $repo->getConfig('key_path');
        $this->url = $this->homologacion
            ? 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms'
            : 'https://wsaa.afip.gov.ar/ws/services/LoginCms';
    }

    public function login(string $service = 'wsfe'): ?array
    {
        $repo = new ArcaRepo();

        // Check for valid cached TA for this service
        $ta = $repo->getTicketAccesoValido($service);
        if ($ta) {
            return $ta;
        }

        if ($this->certPath === '' || $this->keyPath === '') {
            throw new \RuntimeException('AFIP: certificado no configurado.');
        }

        $ticketXml = $this->generarTicketXml($service);
        $cms = $this->firmarTicket($ticketXml);
        if (trim($cms) === '') {
            throw new \RuntimeException('AFIP: no se pudo extraer el CMS firmado del ticket.');
        }

        $response = $this->callWsaa($cms);
        $taData = $this->parsearRespuesta($response);

        // Cache the TA
        $repo->guardarTicketAcceso($taData['token'], $taData['sign'], $taData['expiration'], $service);

        return [
            'token' => $taData['token'],
            'sign' => $taData['sign'],
            'expiration' => $taData['expiration'],
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

    private function generarTicketXml(string $service): string
    {
        $cuit = preg_replace('/\D/', '', (new ArcaRepo())->getConfig('cuit'));
        // Formato del manual de AFIP, sin milisegundos.
        $genTime = gmdate('Y-m-d\TH:i:s\Z');
        $expTime = gmdate('Y-m-d\TH:i:s\Z', strtotime('+12 hours'));

        // Según el schema de AFIP, <service> va fuera de <header>.
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<loginTicketRequest version="1.0">
<header>
<uniqueId>{$this->uniqueId()}</uniqueId>
<generationTime>{$genTime}</generationTime>
<expirationTime>{$expTime}</expirationTime>
</header>
<service>{$service}</service>
</loginTicketRequest>
XML;
    }

    private function uniqueId(): string
    {
        // AFIP schema define uniqueId como entero de 32 bits con signo.
        // time() actual (1.7G) entra; no concatenar rand porque supera 2^31.
        return (string)time();
    }

    private function firmarTicket(string $xml): string
    {
        $tempDir = sys_get_temp_dir();
        $xmlFile = $tempDir . '/afip_ta_' . getmypid() . '.xml';
        $tmpSigned = $tempDir . '/afip_ta_signed_' . getmypid() . '.tmp';

        file_put_contents($xmlFile, $xml);

        $cert = realpath($this->certPath);
        $key = realpath($this->keyPath);

        if (!$cert || !$key) {
            unlink($xmlFile);
            throw new \RuntimeException('AFIP: archivos de certificado no encontrados.');
        }

        if (!is_readable($cert) || !is_readable($key)) {
            unlink($xmlFile);
            throw new \RuntimeException('AFIP: certificado o clave sin permisos de lectura.');
        }

        $certContent = (string)@file_get_contents($cert);
        $certRes = $certContent !== '' ? @openssl_x509_read($certContent) : false;
        if ($certRes === false) {
            unlink($xmlFile);
            throw new \RuntimeException('AFIP: certificado invalido o en formato incorrecto. ' . $this->opensslErrors());
        }
        $certInfo = openssl_x509_parse($certRes);
        if (is_array($certInfo) && isset($certInfo['validTo_time_t']) && (int)$certInfo['validTo_time_t'] < time()) {
            unlink($xmlFile);
            throw new \RuntimeException('AFIP: el certificado venció el ' . date('d/m/Y H:i', (int)$certInfo['validTo_time_t']) . '. Generá uno nuevo y asocialo en AFIP.');
        }

        $keyRes = @openssl_pkey_get_private('file://' . $key);
        if ($keyRes === false) {
            unlink($xmlFile);
            throw new \RuntimeException('AFIP: clave privada invalida, protegida con passphrase o con formato incorrecto. ' . $this->opensslErrors());
        }
        // AFIP WSAA espera el CMS en modo adjunto (attached): el TRA va
        // dentro del PKCS#7. Con PKCS7_DETACHED el "CMS" extraído queda con
        // texto MIME y el XML en claro, y WSAA responde HTTP 500 (SAXParseException).
        $ok = openssl_pkcs7_sign(
            $xmlFile,
            $tmpSigned,
            'file://' . $cert,
            ['file://' . $key, ''],
            [],
            PKCS7_BINARY
        );

        unlink($xmlFile);

        if (!$ok) {
            throw new \RuntimeException('AFIP: error al firmar el ticket. ' . $this->opensslErrors());
        }

        $signed = file_get_contents($tmpSigned);
        unlink($tmpSigned);

        // Extract CMS between PKCS7 boundaries
        $parts = preg_split('/\n\n/', $signed, 2);
        $cms = '';
        if (isset($parts[1])) {
            $cms = $parts[1];
            // Remove trailing headers
            $cms = preg_replace('/\n-----END.*/', '', $cms);
            $cms = str_replace("\n", '', $cms);
        }

        error_log('AFIP WSAA debug firmarTicket: signed_len=' . strlen($signed) . ' cms_len=' . strlen($cms) . ' cms_first30=' . substr($cms, 0, 30));

        if (trim($cms) === '') {
            throw new \RuntimeException('AFIP: CMS extraído vacío tras firmar.');
        }

        return $cms;
    }

    private function opensslErrors(): string
    {
        $errors = [];
        while ($err = openssl_error_string()) {
            $errors[] = $err;
        }
        return $errors ? ('Detalle OpenSSL: ' . implode(' | ', $errors)) : 'Detalle OpenSSL no disponible.';
    }

    private function callWsaa(string $cms): string
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsdl="http://wsaa.view.sua.dvadac.desein.afip.gov">
<soap:Body>
<wsdl:loginCms>
<wsdl:in0>{$cms}</wsdl:in0>
</wsdl:loginCms>
</soap:Body>
</soap:Envelope>
XML;

        $ch = curl_init();
        $this->lastRequest = $xml;

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml; charset=UTF-8', 'SOAPAction: http://wsaa.view.sua.dvadac.desein.afip.gov/loginCms'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $this->lastResponse = is_string($response) ? $response : '';
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('AFIP WSAA: ' . $error);
        }
        if ($httpCode !== 200) {
            $body = is_string($response) ? $response : '';
            error_log('AFIP WSAA [' . $this->url . '] HTTP ' . $httpCode . ' REQUEST: ' . $xml);
            error_log('AFIP WSAA [' . $this->url . '] HTTP ' . $httpCode . ' RESPONSE: ' . $body);
            $fault = '';
            if ($body !== '' && ($dom = new \DOMDocument()) && @$dom->loadXML($body)) {
                $fault = $dom->getElementsByTagName('faultstring')->item(0)?->textContent ?? '';
            }
            $detail = $fault !== '' ? ' Motivo AFIP: ' . trim($fault) : ' (sin detalle de AFIP en el cuerpo)';
            throw new \RuntimeException('AFIP WSAA: HTTP ' . $httpCode . '.' . $detail);
        }

        return $response;
    }

    private function parsearRespuesta(string $response): array
    {
        $dom = new \DOMDocument();
        $dom->loadXML($response);

        $token = $dom->getElementsByTagName('token')->item(0)?->textContent ?? '';
        $sign = $dom->getElementsByTagName('sign')->item(0)?->textContent ?? '';
        $expiration = $dom->getElementsByTagName('expirationTime')->item(0)?->textContent ?? '';

        if (!$token || !$sign) {
            // Try to get fault info
            $fault = $dom->getElementsByTagName('faultstring')->item(0)?->textContent ?? '';
            throw new \RuntimeException('AFIP WSAA: error de autenticación. ' . $fault);
        }

        return [
            'token' => $token,
            'sign' => $sign,
            'expiration' => $expiration,
        ];
    }
}
