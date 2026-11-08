<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\EmpresaRepo;
use Perfushopping\Web\Repo\BancoCuentaRepo;
use Perfushopping\Web\Repo\BancoMovimientoRepo;
use Perfushopping\Web\Repo\CajaRepo;
use Perfushopping\Web\Repo\SucursalRepo;
use Perfushopping\Web\Repo\VoucherDisenoRepo;
use Perfushopping\Web\Repo\VoucherRegaloRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\UploadValidator;
use Perfushopping\Web\Support\View;

final class VoucherController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');
        $branchRows = (new SucursalRepo())->findAll();
        $branches = array_values(array_filter($branchRows, static fn(array $s): bool => (int)($s['activo'] ?? 0) === 1));
        $formasPagoActivas = (new \Perfushopping\Web\Repo\FormaPagoRepo())->findActivas();
        $metodosCobro = array_values(array_filter($formasPagoActivas, static fn(array $f): bool => in_array((string)($f['codigo'] ?? ''), ['efectivo','transferencia','tarjeta','mercadopago'], true)));
        $voucherCode = strtoupper(trim((string)($_GET['generado'] ?? '')));
        echo View::adminPage('admin/vouchers/list.php', [
            'adminUser'=>$adminUser,
            'vouchers'=>(new VoucherRegaloRepo())->recientes(),
            'branches'=>$branches,
            'banks'=>(new BancoCuentaRepo())->findAll(),
            'formasPago'=>$metodosCobro,
            'selectedBranchId'=>$auth->getSucursalId(),
            'generatedVoucher'=>$voucherCode !== '' ? (new VoucherRegaloRepo())->findByCode($voucherCode) : null,
            'voucherDesign'=>(new VoucherDisenoRepo())->get(),
            'csrf'=>Csrf::token(),
            'pageTitle'=>'Vouchers regalo',
        ]);
    }

    public function generate(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);

        $monto = (float)str_replace(',', '.', (string)($_POST['importe'] ?? '0'));
        $importeCents = (int)round($monto * 100);
        $data = [
            'importe_cents'=>$importeCents,
            'destinatario_nombre'=>trim((string)($_POST['destinatario_nombre'] ?? '')),
            'destinatario_dni'=>trim((string)($_POST['destinatario_dni'] ?? '')),
            'destinatario_telefono'=>trim((string)($_POST['destinatario_telefono'] ?? '')),
            'remitente_nombre'=>trim((string)($_POST['remitente_nombre'] ?? '')),
            'remitente_telefono'=>trim((string)($_POST['remitente_telefono'] ?? '')),
            'forma_pago'=>trim((string)($_POST['forma_pago'] ?? 'efectivo')),
            'banco_cuenta_id'=>(int)($_POST['banco_cuenta_id'] ?? 0),
            'sucursal_id'=>(int)($_POST['sucursal_id'] ?? 0) ?: $auth->getSucursalId(),
            'created_by'=>(int)$adminUser['id'],
        ];
        if ((int)($data['sucursal_id'] ?? 0) > 0) {
            $branch = (new SucursalRepo())->findById((int)$data['sucursal_id']);
            if (!$branch || (int)($branch['activo'] ?? 0) !== 1) {
                $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'Elegí una sucursal activa.'];
                Response::redirect('/admin/vouchers');
            }
        }
        if ($importeCents <= 0 || $data['destinatario_nombre'] === ''
            || $data['remitente_nombre'] === '' || $data['remitente_telefono'] === '') {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'Completá importe, nombre de quien recibe y nombre y teléfono de quien envía el regalo.'];
            Response::redirect('/admin/vouchers');
        }
        $mediosAceptados = ['efectivo','transferencia','tarjeta','mercadopago'];
        $requiereBanco = in_array($data['forma_pago'], ['transferencia','tarjeta','mercadopago'], true);
        if (!in_array($data['forma_pago'], $mediosAceptados, true)
            || ($requiereBanco && (!$data['banco_cuenta_id'] || !(new BancoCuentaRepo())->findById($data['banco_cuenta_id'])))) {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'Seleccioná un medio de cobro válido; para transferencia, tarjeta o Mercado Pago, elegí la cuenta receptora.'];
            Response::redirect('/admin/vouchers');
        }

        $cajaRepo = new CajaRepo();
        $apertura = (int)($data['sucursal_id'] ?? 0) > 0
            ? $cajaRepo->aperturaActiva((int)$data['sucursal_id'], $auth->getTurno(), date('Y-m-d'))
            : null;
        $data['caja_apertura_id'] = (int)($apertura['id'] ?? 0) ?: null;
        $voucher = null;
        try {
            $voucherRepo = new VoucherRegaloRepo();
            $voucher = $voucherRepo->crear($data);
            $concepto = 'Cobro emisión voucher '.$voucher['codigo'];
            if ($data['forma_pago'] !== 'efectivo') {
                $movementId = (new BancoMovimientoRepo())->create($data['banco_cuenta_id'], 'credito', 'voucher_regalo', (int)$voucher['id'], $concepto, $importeCents, date('Y-m-d'), (int)$adminUser['id']);
                if ($movementId <= 0) throw new \RuntimeException('No se pudo registrar el ingreso bancario del voucher.');
            } else {
                $movementId = $apertura
                    ? $cajaRepo->agregarMovimiento((int)$apertura['id'], 'ingreso', $concepto, $importeCents, (int)$adminUser['id'])
                    : $cajaRepo->agregarMovimientoGeneral('ingreso', 'voucher_regalo', (int)$voucher['id'], $concepto, $importeCents, (int)$adminUser['id'], date('Y-m-d'));
                if ($movementId <= 0) throw new \RuntimeException('No se pudo registrar el cobro en caja del voucher.');
            }
            Response::redirect('/admin/vouchers?generado=' . urlencode((string)$voucher['codigo']));
        } catch (\Throwable $e) {
            if ($voucher) (new VoucherRegaloRepo())->anularEmision((int)$voucher['id']);
            error_log('VoucherController::generate: '.$e->getMessage());
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'No se pudo generar el voucher. Revisá los datos e intentá nuevamente.'];
            Response::redirect('/admin/vouchers');
        }
    }

    public function cancel(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        try {
            if ($id <= 0) throw new \RuntimeException('Voucher inválido.');
            (new VoucherRegaloRepo())->anularConReintegro($id, (int)$adminUser['id']);
            $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Voucher anulado y reintegro registrado en el medio de cobro original.'];
        } catch (\Throwable $e) {
            error_log('VoucherController::cancel: '.$e->getMessage());
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>$e->getMessage()];
        }
        Response::redirect('/admin/vouchers');
    }

    public function saveDesign(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);
        $repo = new VoucherDisenoRepo();
        $current = $repo->get();
        $imagePath = (string)($current['modelo_imagen'] ?? '');
        try {
            $file = $_FILES['modelo_imagen'] ?? null;
            if (is_array($file) && (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                UploadValidator::validate($file);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
                if ($finfo) finfo_close($finfo);
                if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
                    throw new \RuntimeException('La foto debe ser JPG, PNG o WEBP.');
                }
                $root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? (defined('APP_BASE_DIR') ? APP_BASE_DIR : dirname(__DIR__, 2))), '/\\');
                $uploadDir = $root . '/upload/vouchers';
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    throw new \RuntimeException('No se pudo preparar la carpeta para guardar la foto.');
                }
                $fileName = UploadValidator::store($file, $uploadDir);
                $imagePath = '/upload/vouchers/' . $fileName;
            }

            $design = [
                'modelo_imagen'=>$imagePath,
                'titulo'=>trim((string)($_POST['titulo'] ?? '')),
                'cinta'=>trim((string)($_POST['cinta'] ?? '')),
                'mensaje'=>trim((string)($_POST['mensaje'] ?? '')),
                'frase_inferior'=>trim((string)($_POST['frase_inferior'] ?? '')),
            ];
            if ($design['titulo'] === '' || $design['cinta'] === '' || $design['mensaje'] === '' || $design['frase_inferior'] === '') {
                throw new \InvalidArgumentException('Completá los textos del diseño del voucher.');
            }
            $repo->save($design);
            $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Diseño del voucher actualizado.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>$e->getMessage()];
        }
        Response::redirect('/admin/vouchers#voucherDesign');
    }

    public function publicView(array $params): void
    {
        $codigo = strtoupper(trim((string)($params['code'] ?? '')));
        $voucher = $codigo !== '' ? (new VoucherRegaloRepo())->findByCode($codigo) : null;
        if (!$voucher) {
            http_response_code(404);
            echo 'Voucher no encontrado.';
            return;
        }

        $empresa = (new EmpresaRepo())->getDefault() ?? [];
        $design = (new VoucherDisenoRepo())->get();
        $branches = array_values(array_filter((new SucursalRepo())->findAll(), static fn(array $s): bool => (int)($s['activo'] ?? 0) === 1));
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'perfushopping.ar');
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $scheme = $secure ? 'https' : 'http';
        $voucherUrl = $scheme . '://' . $host . '/voucher/' . rawurlencode($codigo);
        $formato = (string)($_GET['formato'] ?? 'a5') === 'etiqueta' ? 'etiqueta' : 'a5';
        $logo = !empty($empresa['logo']) ? Format::uploadUrl((string)$empresa['logo']) : '';
        $modeloUrl = !empty($design['modelo_imagen']) ? Format::uploadUrl((string)$design['modelo_imagen']) : '';
        echo View::render('admin/vouchers/view.php', [
            'voucher'=>$voucher,
            'empresa'=>$empresa,
            'design'=>$design,
            'branches'=>$branches,
            'logoUrl'=>$logo,
            'modeloUrl'=>$modeloUrl,
            'voucherUrl'=>$voucherUrl,
            'formato'=>$formato,
        ]);
    }

    public function downloadPdf(array $params): void
    {
        $codigo = strtoupper(trim((string)($params['code'] ?? '')));
        if (!(new VoucherRegaloRepo())->findByCode($codigo)) {
            Response::json(['ok'=>false,'error'=>'Voucher no encontrado.'], 404);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $dataUrl = (string)($input['image'] ?? '');
        if (!preg_match('/^data:image\/jpeg;base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $match) || strlen($dataUrl) > 16000000) {
            Response::json(['ok'=>false,'error'=>'La imagen para el PDF no es válida.'], 422);
            return;
        }
        $jpeg = base64_decode($match[1], true);
        $imageInfo = $jpeg !== false ? getimagesizefromstring($jpeg) : false;
        if (!$imageInfo || ($imageInfo['mime'] ?? '') !== 'image/jpeg' || (int)$imageInfo[0] > 5000 || (int)$imageInfo[1] > 7000) {
            Response::json(['ok'=>false,'error'=>'La imagen no tiene dimensiones o formato válidos.'], 422);
            return;
        }
        $formato = (string)($input['formato'] ?? 'a5') === 'etiqueta' ? 'etiqueta' : 'a5';
        $pdf = $this->jpegToPdf($jpeg, (int)$imageInfo[0], (int)$imageInfo[1], $formato);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="voucher-'.preg_replace('/[^A-Z0-9-]/', '', $codigo).'-'.$formato.'.pdf"');
        echo $pdf;
    }

    private function jpegToPdf(string $jpeg, int $imageWidth, int $imageHeight, string $formato): string
    {
        $pageWidth = ($formato === 'etiqueta' ? 100 : 148) * 72 / 25.4;
        $pageHeight = ($formato === 'etiqueta' ? 60 : 210) * 72 / 25.4;
        $scale = min($pageWidth / max(1, $imageWidth), $pageHeight / max(1, $imageHeight));
        $drawWidth = $imageWidth * $scale;
        $drawHeight = $imageHeight * $scale;
        $offsetX = ($pageWidth - $drawWidth) / 2;
        $offsetY = ($pageHeight - $drawHeight) / 2;
        $content = sprintf("q\n%.3f 0 0 %.3f %.3f %.3f cm\n/Im0 Do\nQ\n", $drawWidth, $drawHeight, $offsetX, $offsetY);
        $objects = [
            1=>'<< /Type /Catalog /Pages 2 0 R >>',
            2=>'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3=>'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.round($pageWidth,2).' '.round($pageHeight,2).'] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>',
            4=>'<< /Type /XObject /Subtype /Image /Width '.$imageWidth.' /Height '.$imageHeight.' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpeg)." >>\nstream\n".$jpeg."\nendstream",
            5=>'<< /Length '.strlen($content)." >>\nstream\n".$content."endstream",
        ];
        $out = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $id=>$object) { $offsets[$id] = strlen($out); $out .= $id." 0 obj\n".$object."\nendobj\n"; }
        $xref = strlen($out); $out .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i=1;$i<=5;$i++) $out .= sprintf("%010d 00000 n \n", $offsets[$i]);
        return $out."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }
}
