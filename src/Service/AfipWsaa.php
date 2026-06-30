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

    public function login(): ?array
    {
        $repo = new ArcaRepo();

        // Check for valid cached TA
        $ta = $repo->getTicketAccesoValido();
        if ($ta) {
            return $ta;
        }

        if ($this->certPath === '' || $this->keyPath === '') {
            throw new \RuntimeException('AFIP: certificado no configurado.');
        }

        $ticketXml = $this->generarTicketXml();
        $cms = $this->firmarTicket($ticketXml);

        $response = $this->callWsaa($cms);
        $taData = $this->parsearRespuesta($response);

        // Cache the TA
        $repo->guardarTicketAcceso($taData['token'], $taData['sign'], $taData['expiration']);

        return [
            'token' => $taData['token'],
            'sign' => $taData['sign'],
            'expiration' => $taData['expiration'],
        ];
    }

    private function generarTicketXml(): string
    {
        $cuit = (new ArcaRepo())->getConfig('cuit');
        $service = $this->homologacion ? 'https://wswhomo.afip.gov.ar/wsfe/service.asmx' : 'https://servicios1.afip.gov.ar/wsfe/service.asmx';
        $genTime = gmdate('Y-m-d\TH:i:s.xxx\Z');
        $expTime = gmdate('Y-m-d\TH:i:s.xxx\Z', strtotime('+12 hours'));

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<loginTicketRequest version="1.0">
    <header>
        <uniqueId>{$this->uniqueId()}</uniqueId>
        <generationTime>{$genTime}</generationTime>
        <expirationTime>{$expTime}</expirationTime>
        <service>wsfe</service>
    </header>
</loginTicketRequest>
XML;
    }

    private function uniqueId(): string
    {
        return time() . rand(1000, 9999);
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

        $ok = openssl_pkcs7_sign(
            $xmlFile,
            $tmpSigned,
            'file://' . $cert,
            ['file://' . $key, ''],
            [],
            PKCS7_BINARY | PKCS7_DETACHED
        );

        unlink($xmlFile);

        if (!$ok) {
            throw new \RuntimeException('AFIP: error al firmar el ticket.');
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

        return $cms;
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
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('AFIP WSAA: ' . $error);
        }
        if ($httpCode !== 200) {
            throw new \RuntimeException('AFIP WSAA: HTTP ' . $httpCode);
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
