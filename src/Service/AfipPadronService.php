<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\ArcaRepo;

final class AfipPadronService
{
    private string $url;
    private string $cuit;
    private string $token;
    private string $sign;
    private bool $homologacion;

    public function __construct()
    {
        $repo = new ArcaRepo();
        $this->homologacion = $repo->esHomologacion();
        $this->cuit = $repo->getConfig('cuit');
        $this->url = $this->homologacion
            ? 'https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA5'
            : 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA5';
    }

    /**
     * Convert a DNI (7-8 digits) to both possible CUITs (20 and 27 prefix)
     * using AFIP módulo 11 check digit algorithm.
     */
    public static function dniToCuits(string $dni): array
    {
        $dni = preg_replace('/\D/', '', $dni);
        if (strlen($dni) < 7 || strlen($dni) > 8) {
            return [];
        }
        $dni = str_pad($dni, 8, '0', STR_PAD_LEFT);
        $multipliers = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $cuits = [];
        foreach (['20', '27'] as $prefix) {
            $base = $prefix . $dni;
            $sum = 0;
            for ($i = 0; $i < 10; $i++) {
                $sum += (int)$base[$i] * $multipliers[$i];
            }
            $rem = $sum % 11;
            $check = 11 - $rem;
            if ($check === 11) $check = 0;
            if ($check === 10) $check = 9;
            $cuits[] = $base . $check;
        }
        return $cuits;
    }

    public function autenticar(): void
    {
        $wsaa = new AfipWsaa();
        $ta = $wsaa->login('ws_sr_padron_a5');
        $this->token = $ta['token'];
        $this->sign = $ta['sign'];
    }

    public function consultar(string $cuit): ?array
    {
        $this->autenticar();
        $xml = $this->buildSoapRequest($cuit);
        $response = $this->call($xml);
        return $this->parsearRespuesta($response, $cuit);
    }

    private function buildSoapRequest(string $cuit): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://a5.soap.ws.server.puc.sr/" xmlns:wsaa="http://wsaa.view.sua.dvadac.desein.afip.gov">
   <soapenv:Header>
      <wsaa:Auth>
         <wsaa:Token>{$this->token}</wsaa:Token>
         <wsaa:Sign>{$this->sign}</wsaa:Sign>
         <wsaa:Cuit>{$this->cuit}</wsaa:Cuit>
      </wsaa:Auth>
   </soapenv:Header>
   <soapenv:Body>
      <ser:getPersona>
         <ser:idPersona>{$cuit}</ser:idPersona>
      </ser:getPersona>
   </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    private function call(string $xml): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml; charset=UTF-8', 'SOAPAction: "getPersona"'],
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
            throw new \RuntimeException('ARCA Padron: ' . $error);
        }
        if ($httpCode !== 200) {
            error_log('ARCA Padron HTTP ' . $httpCode . ' response: ' . substr($response, 0, 2000));
            throw new \RuntimeException('ARCA Padron: HTTP ' . $httpCode);
        }

        return $response;
    }

    private function parsearRespuesta(string $response, string $cuit): ?array
    {
        $dom = new \DOMDocument();
        $dom->loadXML($response);

        // Check for SOAP fault
        $fault = $dom->getElementsByTagNameNS('*', 'faultstring')->item(0)?->textContent ?? '';
        if ($fault !== '') {
            throw new \RuntimeException('ARCA Padron: ' . $fault);
        }

        $returnNode = $dom->getElementsByTagNameNS('*', 'return')->item(0);
        if (!$returnNode) {
            error_log('ARCA Padron: no se encontro nodo return en respuesta. XML: ' . substr($response, 0, 2000));
            return null;
        }

        $razonSocial = $this->getChildText($returnNode, 'razonSocial');
        $apellido = $this->getChildText($returnNode, 'apellido');
        $nombre = $this->getChildText($returnNode, 'nombre');

        if (!$razonSocial) {
            $razonSocial = trim(($apellido ?? '') . ' ' . ($nombre ?? ''));
        }

        $domicilio = $this->getFirstChildByTagName($returnNode, 'domicilioFiscal');
        $direc = '';
        $localidad = '';
        $codPostal = '';
        $provincia = '';
        if ($domicilio) {
            $direc = $this->getChildText($domicilio, 'direccion') ?? '';
            $localidad = $this->getChildText($domicilio, 'localidad') ?? '';
            $codPostal = $this->getChildText($domicilio, 'codPostal') ?? '';
            $provincia = $this->getChildText($domicilio, 'provincia') ?? '';
        }

        // Extract IVA category
        $condicionIva = 'consumidor_final';
        $impuestos = $this->getFirstChildByTagName($returnNode, 'impuestos');
        if ($impuestos) {
            foreach ($impuestos->childNodes as $imp) {
                if ($imp->nodeType !== XML_ELEMENT_NODE) continue;
                $desc = $this->getChildText($imp, 'descripcion') ?? '';
                if (strtoupper($desc) === 'IVA') {
                    $cat = $this->getChildText($imp, 'categoria') ?? '';
                    $condicionIva = $this->mapCategoria($cat);
                    break;
                }
            }
        }

        return [
            'cuit' => $cuit,
            'razon' => $razonSocial ?: ($apellido ?? '') . ' ' . ($nombre ?? ''),
            'razonSocial' => $razonSocial,
            'apellido' => $apellido,
            'nombre' => $nombre,
            'direc' => $direc,
            'localidad' => $localidad,
            'codPostal' => $codPostal,
            'provincia' => $provincia,
            'condicion_iva' => $condicionIva,
        ];
    }

    private function mapCategoria(string $categoria): string
    {
        $map = [
            'Responsable Inscripto' => 'responsable_inscripto',
            'Responsable Monotributo' => 'monotributista',
            'Monotributista' => 'monotributista',
            'Exento' => 'exento',
            'Consumidor Final' => 'consumidor_final',
        ];
        $normalized = trim($categoria);
        return $map[$normalized] ?? 'consumidor_final';
    }

    private function getChildText(\DOMElement $parent, string $tag): ?string
    {
        foreach ($parent->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === $tag) {
                return trim($child->textContent);
            }
        }
        return null;
    }

    private function getFirstChildByTagName(\DOMElement $parent, string $tag): ?\DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === $tag) {
                return $child;
            }
        }
        return null;
    }
}
