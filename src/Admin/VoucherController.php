<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\EmpresaRepo;
use Perfushopping\Web\Repo\BancoCuentaRepo;
use Perfushopping\Web\Repo\BancoMovimientoRepo;
use Perfushopping\Web\Repo\CajaRepo;
use Perfushopping\Web\Repo\SucursalRepo;
use Perfushopping\Web\Repo\VoucherRegaloRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Support\Pdf;
use Perfushopping\Web\Support\Response;
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

        $voucher = null;
        try {
            $voucherRepo = new VoucherRegaloRepo();
            $voucher = $voucherRepo->crear($data);
            $concepto = 'Cobro emisión voucher '.$voucher['codigo'];
            if ($data['forma_pago'] !== 'efectivo') {
                $movementId = (new BancoMovimientoRepo())->create($data['banco_cuenta_id'], 'credito', 'voucher_regalo', (int)$voucher['id'], $concepto, $importeCents, date('Y-m-d'), (int)$adminUser['id']);
                if ($movementId <= 0) throw new \RuntimeException('No se pudo registrar el ingreso bancario del voucher.');
            } else {
                $cajaRepo = new CajaRepo();
                $sucursalId = (int)($data['sucursal_id'] ?? 0);
                $apertura = $sucursalId > 0 ? $cajaRepo->aperturaActiva($sucursalId, $auth->getTurno(), date('Y-m-d')) : null;
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
        $branches = array_values(array_filter((new SucursalRepo())->findAll(), static fn(array $s): bool => (int)($s['activo'] ?? 0) === 1));
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'perfushopping.ar');
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        $scheme = $secure ? 'https' : 'http';
        $voucherUrl = $scheme . '://' . $host . '/voucher/' . rawurlencode($codigo);
        $formato = (string)($_GET['formato'] ?? 'a5') === 'etiqueta' ? 'etiqueta' : 'a5';
        $descargar = strtolower((string)($_GET['descargar'] ?? ''));
        if (in_array($descargar, ['pdf','jpg'], true)) {
            $jpeg = $this->voucherJpeg($voucher, $empresa, $branches, $formato);
            $width = $formato === 'etiqueta' ? 1181 : 1748;
            $height = $formato === 'etiqueta' ? 709 : 2480;
            $stem = preg_replace('/[^A-Z0-9-]/', '', $codigo) ?: 'voucher-regalo';
            if ($descargar === 'jpg') {
                header('Content-Type: image/jpeg');
                header('Content-Disposition: attachment; filename="'.$stem.'-'.$formato.'.jpg"');
                echo $jpeg;
                return;
            }
            $pdf = $this->jpegToPdf($jpeg, $width, $height, $formato);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.$stem.'-'.$formato.'.pdf"');
            echo $pdf;
            return;
        }

        $logo = !empty($empresa['logo']) ? Format::uploadUrl((string)$empresa['logo']) : '';
        echo View::render('admin/vouchers/view.php', [
            'voucher'=>$voucher,
            'empresa'=>$empresa,
            'branches'=>$branches,
            'logoUrl'=>$logo,
            'voucherUrl'=>$voucherUrl,
            'formato'=>$formato,
        ]);
    }

    private function voucherJpeg(array $voucher, array $empresa, array $branches, string $formato): string
    {
        if (!function_exists('imagecreatetruecolor')) throw new \RuntimeException('La extensión GD es necesaria para generar la imagen del voucher.');
        $label = $formato === 'etiqueta';
        $width = $label ? 1181 : 1748;
        $height = $label ? 709 : 2480;
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 35, 42, 54);
        $gold = imagecolorallocate($image, 191, 151, 63);
        imagefill($image, 0, 0, $white);
        $font = $this->fontPath();
        $draw = function (string $text, int $x, int $y, int $size, bool $bold = false, int $color = 0) use ($image, $font, $ink): void {
            $color = $color ?: $ink;
            if ($font !== null && function_exists('imagettftext')) {
                imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
                return;
            }
            imagestring($image, 5, $x, max(0, $y - 15), $text, $color);
        };
        $margin = $label ? 46 : 96;
        $y = $label ? 70 : 120;

        $logoPath = $this->logoPath((string)($empresa['logo'] ?? ''));
        if ($logoPath !== '' && is_file($logoPath)) {
            $logo = @imagecreatefromstring((string)file_get_contents($logoPath));
            if ($logo) {
                $lw = imagesx($logo); $lh = imagesy($logo);
                $maxW = $label ? 210 : 300; $maxH = $label ? 95 : 150;
                $scale = min($maxW / max(1,$lw), $maxH / max(1,$lh));
                $dw = (int)round($lw * $scale); $dh = (int)round($lh * $scale);
                imagecopyresampled($image, $logo, $margin, $y, 0, 0, $dw, $dh, $lw, $lh);
                imagedestroy($logo);
            }
        }
        $companyX = $label ? 290 : $margin;
        $companyY = $y + ($label ? 36 : 190);
        $draw((string)($empresa['razon_emp'] ?? $empresa['nomemp'] ?? 'PERFUSHOPPING'), $companyX, $companyY, $label ? 22 : 32, true);
        if (!empty($empresa['cuit'])) $draw('CUIT '.$empresa['cuit'], $companyX, $companyY + ($label ? 30 : 42), $label ? 16 : 20);
        if (!$label && !empty($empresa['dire_emp'])) $draw((string)$empresa['dire_emp'], $companyX, $companyY + 82, 18);
        if (!$label && !empty($empresa['telefono'])) $draw('Tel. '.$empresa['telefono'], $companyX, $companyY + 112, 18);
        $y = $label ? 205 : 330;

        if (!$label) {
            $draw('SUCURSALES ACTIVAS', $margin, $y, 22, true, $gold); $y += 40;
            foreach ($branches as $branch) {
                $line = trim((string)($branch['nomsuc'] ?? 'Sucursal') . ' — ' . (string)($branch['direccion'] ?? '') . ' — Tel. ' . (string)($branch['telefono'] ?? ''));
                foreach ($this->wrapText($line, 92) as $branchLine) {
                    $draw($branchLine, $margin, $y, 17); $y += 27;
                }
            }
            $y += 28;
        } else {
            foreach ($branches as $branch) {
                $line = trim((string)($branch['nomsuc'] ?? 'Sucursal') . ': ' . (string)($branch['direccion'] ?? '') . ' Tel. ' . (string)($branch['telefono'] ?? ''));
                foreach ($this->wrapText($line, 105) as $branchLine) {
                    $draw($branchLine, $margin, $y, 11); $y += 15;
                }
            }
            $y += 5;
        }

        $draw('VOUCHER DE REGALO', $margin, $y, $label ? 26 : 46, true, $gold); $y += $label ? 48 : 80;
        $draw('Para: '.(string)$voucher['destinatario_nombre'], $margin, $y, $label ? 22 : 34, true); $y += $label ? 42 : 64;
        $draw('De: '.(string)$voucher['remitente_nombre'].' · Tel. '.(string)$voucher['remitente_telefono'], $margin, $y, $label ? 16 : 24); $y += $label ? 48 : 86;
        $draw('VALOR: '.Format::moneyFromCents((int)$voucher['importe_cents']), $margin, $y, $label ? 27 : 48, true); $y += $label ? 54 : 100;
        imagesetthickness($image, $label ? 2 : 4);
        imagerectangle($image, $margin, $y - ($label ? 36 : 64), $width - $margin, $y + ($label ? 40 : 74), $gold);
        $draw((string)$voucher['codigo'], $margin + 14, $y, $label ? 30 : 58, true);
        $y += $label ? 66 : 130;
        $draw('Código de un solo uso. Presentalo al momento de pagar.', $margin, $y, $label ? 14 : 20);

        ob_start();
        imagejpeg($image, null, 92);
        $jpeg = (string)ob_get_clean();
        imagedestroy($image);
        return $jpeg;
    }

    private function wrapText(string $text, int $width): array
    {
        $text = trim((string)preg_replace('/\s+/u', ' ', $text));
        if ($text === '') return [];
        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    private function fontPath(): ?string
    {
        foreach ([
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            'C:/Windows/Fonts/arial.ttf',
        ] as $font) if (is_file($font)) return $font;
        return null;
    }

    private function logoPath(string $logo): string
    {
        if ($logo === '') return '';
        $path = parse_url($logo, PHP_URL_PATH) ?: $logo;
        $root = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
        $base = defined('APP_BASE_DIR') ? (string)APP_BASE_DIR : dirname(__DIR__, 2);
        foreach ([rtrim($root, '/\\').'/'.ltrim($path, '/\\'), rtrim($base, '/\\').'/public/'.ltrim($path, '/\\'), rtrim($base, '/\\').'/'.ltrim($path, '/\\')] as $candidate) {
            if (is_file($candidate)) return $candidate;
        }
        return '';
    }

    private function jpegToPdf(string $jpeg, int $imageWidth, int $imageHeight, string $formato): string
    {
        $pageWidth = ($formato === 'etiqueta' ? 100 : 148) * 72 / 25.4;
        $pageHeight = ($formato === 'etiqueta' ? 60 : 210) * 72 / 25.4;
        $content = "q\n{$pageWidth} 0 0 {$pageHeight} 0 0 cm\n/Im0 Do\nQ\n";
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
