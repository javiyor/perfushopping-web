<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\ArcaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Service\AfipWsaa;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ArcaController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new ArcaRepo();
        $config = $repo->getAllConfig();
        $comprobantes = $repo->listarComprobantes(20);

        $taValido = $repo->getTicketAccesoValido();

        echo View::adminPage('admin/arca/index.php', [
            'adminUser' => $adminUser,
            'config' => $config,
            'comprobantes' => $comprobantes,
            'taValido' => $taValido,
            'csrf' => Csrf::token(),
            'pageTitle' => 'ARCA — Factura electrónica',
        ]);
    }

    public function config(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new ArcaRepo();
        $config = $repo->getAllConfig();

        echo View::adminPage('admin/arca/config.php', [
            'adminUser' => $adminUser,
            'config' => $config,
            'csrf' => Csrf::token(),
            'pageTitle' => 'ARCA — Configuración',
        ]);
    }

    public function configSave(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $repo = new ArcaRepo();
        $repo->setConfig('ambiente', (string)($_POST['ambiente'] ?? 'homologacion'));
        $repo->setConfig('cuit', trim((string)($_POST['cuit'] ?? '')));
        $repo->setConfig('cert_path', trim((string)($_POST['cert_path'] ?? '')));
        $repo->setConfig('key_path', trim((string)($_POST['key_path'] ?? '')));
        $repo->setConfig('habilitado', (string)(int)(($_POST['habilitado'] ?? '0') === '1'));

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Configuración ARCA guardada.'];
        Response::redirect('/admin/arca');
    }

    public function testConnection(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        try {
            $wsaa = new AfipWsaa();
            $ta = $wsaa->login();
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Conexión ARCA exitosa. TA válido hasta: ' . ($ta['expiration'] ?? '?')];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error de conexión: ' . $e->getMessage()];
        }

        Response::redirect('/admin/arca');
    }

    public function reenviar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $facturaId = (int)($_POST['factura_id'] ?? 0);
        if ($facturaId <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'ID de factura inválido.'];
            Response::redirect('/admin/arca');
        }

        $repo = new ArcaRepo();
        $facturaRepo = new \Perfushopping\Web\Repo\FacturaRepo();
        $factura = $facturaRepo->findById($facturaId);

        if (!$factura) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Factura no encontrada.'];
            Response::redirect('/admin/arca');
        }

        $items = $facturaRepo->items($facturaId);

        try {
            $wsfe = new \Perfushopping\Web\Service\AfipWsfe();
            $wsfe->autenticar();
            $resultado = $wsfe->solicitarCAE($factura, $items);
            $repo->guardarComprobante($facturaId, $resultado);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Factura enviada a ARCA. CAE: ' . ($resultado['cae'] ?? '—')];
        } catch (\Throwable $e) {
            $repo->guardarComprobante($facturaId, [
                'resultado' => 'R',
                'observaciones' => $e->getMessage(),
                'cae' => null,
                'cae_vto' => null,
                'codigo_emision' => null,
                'request_xml' => null,
                'response_xml' => null,
            ]);
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error ARCA: ' . $e->getMessage()];
        }

        Response::redirect('/admin/facturas/' . $facturaId);
    }

    public function generarCsr(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $repo = new ArcaRepo();
        $cuit = $repo->getConfig('cuit');
        if ($cuit === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Primero configurá el CUIT.'];
            Response::redirect('/admin/arca/config');
        }

        $storageDir = APP_BASE_DIR . '/storage/arca';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $keyRes = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if (!$keyRes) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error generando clave privada.'];
            Response::redirect('/admin/arca/config');
        }

        $keyPath = $storageDir . '/' . $cuit . '.key';
        if (!openssl_pkey_export($keyRes, $keyPem)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error exportando clave privada.'];
            Response::redirect('/admin/arca/config');
        }
        file_put_contents($keyPath, $keyPem);

        $dn = [
            'commonName' => $cuit,
            'organizationName' => $cuit,
            'countryName' => 'AR',
        ];
        $csrRes = openssl_csr_new($dn, $keyRes, ['digest_alg' => 'sha256']);
        if (!$csrRes) {
            unlink($keyPath);
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error generando CSR.'];
            Response::redirect('/admin/arca/config');
        }

        openssl_csr_export($csrRes, $csrOut);
        $csrPath = $storageDir . '/' . $cuit . '.csr';
        file_put_contents($csrPath, $csrOut);

        $repo->setConfig('key_path', $keyPath);
        $repo->setConfig('csr_pendiente', $csrOut);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'CSR generado correctamente. Copiá el contenido y subilo en el portal de ARCA.'];
        Response::redirect('/admin/arca/config');
    }

    public function cargarCertificado(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $repo = new ArcaRepo();
        $cuit = $repo->getConfig('cuit');
        if ($cuit === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Primero configurá el CUIT.'];
            Response::redirect('/admin/arca/config');
        }

        if (!isset($_FILES['certificado']) || $_FILES['certificado']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error al subir el archivo.'];
            Response::redirect('/admin/arca/config');
        }

        $content = file_get_contents($_FILES['certificado']['tmp_name']);
        if (!str_contains($content, '-----BEGIN CERTIFICATE-----')) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El archivo no parece un certificado X.509 válido (formato PEM).'];
            Response::redirect('/admin/arca/config');
        }

        $storageDir = APP_BASE_DIR . '/storage/arca';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $certPath = $storageDir . '/' . $cuit . '.crt';
        file_put_contents($certPath, $content);

        $repo->setConfig('cert_path', $certPath);
        $repo->setConfig('csr_pendiente', '');

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Certificado subido correctamente.'];
        Response::redirect('/admin/arca/config');
    }
}
