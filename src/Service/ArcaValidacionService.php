<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\ArcaRepo;
use Perfushopping\Web\Repo\EmpresaRepo;
use Perfushopping\Web\Repo\FacturaRepo;

/**
 * Valida que cliente, condición de IVA y tipo de comprobante sean
 * compatibles con lo que requiere ARCA antes de emitir el CAE.
 */
final class ArcaValidacionService
{
    /** Devuelve null si todo es válido, o el mensaje de error a mostrar. */
    public static function validarFactura(array $factura): ?string
    {
        if (!(new ArcaRepo())->isHabilitado()) {
            return null;
        }

        $tipo = (string)($factura['tipo_comprobante'] ?? '');
        $nombre = trim((string)($factura['cliente_nombre'] ?? ''));
        $cuit = preg_replace('/\D/', '', (string)($factura['cliente_cuit'] ?? '')) ?? '';
        $cond = FacturaRepo::normalizeCondIva((string)($factura['cliente_condicion_iva'] ?? 'consumidor_final'));

        $errores = [];

        if ($nombre === '') {
            $errores[] = 'Falta el nombre o razón social del cliente.';
        } elseif ($cond !== 'consumidor_final' && strcasecmp($nombre, 'Consumidor Final') === 0) {
            $errores[] = 'Identificá al cliente con su nombre o razón social (no puede quedar como Consumidor Final).';
        }

        $exigeCuit = in_array($cond, ['responsable_inscripto', 'monotributista', 'exento'], true);
        if ($exigeCuit) {
            if (strlen($cuit) !== 11 || !AfipWsfe::cuitValido($cuit)) {
                $errores[] = 'El cliente es ' . self::labelCond($cond) . ' y ARCA exige un CUIT válido de 11 dígitos: corregí el CUIT del cliente o su condición de IVA.';
            }
        } elseif ($cuit !== '' && !((strlen($cuit) === 11 && AfipWsfe::cuitValido($cuit)) || (strlen($cuit) >= 7 && strlen($cuit) <= 8 && ctype_digit($cuit)))) {
            $errores[] = 'El documento del cliente no es válido: debe ser un CUIT de 11 dígitos o un DNI de 7 u 8 números.';
        }

        $emisor = self::condicionEmisor();
        $emisorPermiteAB = $emisor === null || $emisor === 'responsable_inscripto';

        if ($emisorPermiteAB) {
            if ($tipo === 'FACT-A' && $cond !== 'responsable_inscripto') {
                $errores[] = 'La Factura A solo puede emitirse a un Responsable Inscripto: corregí la condición de IVA del cliente o cambiá el tipo de comprobante.';
            }
            if ($tipo === 'FACT-B' && $cond === 'responsable_inscripto') {
                $errores[] = 'Para un cliente Responsable Inscripto corresponde Factura A: cambiá el tipo de comprobante.';
            }
        }

        if ($emisor !== null) {
            if ($emisor === 'responsable_inscripto' && $tipo === 'FACT-C') {
                $errores[] = 'La empresa es Responsable Inscripto y ARCA no autoriza Factura C: cambiá a Factura B (o Factura A si el cliente es Responsable Inscripto).';
            } elseif ($emisor !== 'responsable_inscripto' && in_array($tipo, ['FACT-A', 'FACT-B'], true)) {
                $errores[] = 'La empresa es ' . ($emisor === 'monotributo' ? 'Monotributo' : 'Exenta') . ' y ARCA solo autoriza Factura C para ese tipo de contribuyente.';
            }
        }

        if (!$errores) {
            return null;
        }
        return "No se puede enviar a ARCA todavía. Corregí antes de facturar:\n- " . implode("\n- ", $errores);
    }

    /** Condición de IVA de la empresa emisora, o null si no se pudo determinar. */
    public static function condicionEmisor(): ?string
    {
        static $cache = false;
        if ($cache !== false) {
            return $cache;
        }
        $cache = null;
        try {
            $empre = (new EmpresaRepo())->getDefault();
            $label = strtolower(trim((string)($empre['tipiva_label'] ?? '')));
            if (str_contains($label, 'monotribut')) {
                $cache = 'monotributo';
            } elseif (str_contains($label, 'exent')) {
                $cache = 'exento';
            } elseif (str_contains($label, 'inscripto') && !str_contains($label, 'no inscripto')) {
                $cache = 'responsable_inscripto';
            }
        } catch (\Throwable $e) {
            $cache = null;
        }
        return $cache;
    }

    private static function labelCond(string $cond): string
    {
        $map = [
            'responsable_inscripto' => 'Responsable Inscripto',
            'monotributista' => 'Monotributo',
            'exento' => 'Exento',
        ];
        return $map[$cond] ?? 'Responsable Inscripto';
    }
}
