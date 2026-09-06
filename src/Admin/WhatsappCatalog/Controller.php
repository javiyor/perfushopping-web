<?php

declare(strict_types=1);

namespace Perfushopping\Web\Admin\WhatsappCatalog;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Response;
use function\header\send;
use function\json\decode;
use function\json\encode;

final class Controller
{
    /** @var string Ruta del archivo CSV generado */
    private const CSV_FILE = __DIR__ . '/../../../../catalog_products.csv';

    /** Genera el catálogo y lo guarda en disco */
    public static function generate(): void
    {
        // Verificar permisos de admin usando AdminAuthService
        $auth = new AdminAuthService();
        if (!$auth->user()) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Inicia sesión para continuar.'];
            Response::redirect('/admin/login');
            exit;
        }

        if (!$auth->checkPermiso('productos')) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No tenés permisos para acceder a esta sección.'];
            Response::redirect('/admin');
            exit;
        }

        $result = Generator::generate();

        if ($result === false) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error al generar el catálogo. Revisar logs del servidor.'];
            Response::redirect('/admin/whatsApp-catalog?status=error');
            exit;
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Catálogo generado correctamente en: ' . basename($result)];
        Response::redirect('/admin/whatsApp-catalog?status=ok');
        exit;
    }

    /** Descarga el archivo catalog_products.csv al usuario */
    public static function download(): void
    {
        // Verificar permisos de admin usando AdminAuthService
        $auth = new AdminAuthService();
        if (!$auth->user()) {
            http_response_code(401);
            echo 'Acceso no autorizado';
            exit;
        }

        if (!$auth->checkPermiso('productos')) {
            http_response_code(403);
            echo 'No tenés permisos para acceder a esta sección.';
            exit;
        }

        if (!file_exists(self::CSV_FILE)) {
            http_response_code(404);
            echo 'Archivo no generado aún. Ejecute la generación primero.';
            return;
        }

        // Headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="catalog_products.csv"');
        header('Pragma: public');
        header('Expires: 0');

        // Leer y enviar el archivo
        $file = fopen(self::CSV_FILE, 'r');

        if ($file) {
            // Enviar encabezado
            $headers = fgetcsv($file, 8192, ',');
            // Reconstruir encabezado CSV
            header("Content-Type: text/csv; charset=utf-8");
            header("Content-Disposition: attachment; filename=\"catalog_products.csv\";");
            header("Pragma: public");
            header("Expires: 0");

            // Leer y output todas las filas
            while (($row = fgetcsv($file, 8192, ',')) !== false) {
                $num = count($row);
                $output = '';
                for ($i = 0; $i < $num; $i++) {
                    // Escape CSV field (handle commas, quotes)
                    $output .= $i > 0 ? ',' : '';
                    $output .= '"' . str_replace('"', '""', $row[$i]) . '"';
                }
                // Print each line
                print $output . "\n";
            }
            fclose($file);
        }
    }

    /** Muestra el formulario de configuración y estado del catálogo */
    public static function index(): void
    {
        // Verificar permisos de admin usando AdminAuthService
        $auth = new AdminAuthService();
        if (!$auth->user()) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Inicia sesión para continuar.'];
            Response::redirect('/admin/login');
            exit;
        }

        if (!$auth->checkPermiso('productos')) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No tenés permisos para acceder a esta sección.'];
            Response::redirect('/admin');
            exit;
        }

        $csvExists = file_exists(self::CSV_FILE);
        $csvModTime = $csvExists ? date('d/m/Y H:i', filemtime(self::CSV_FILE)) : 'Nunca';
        $csvSize = $csvExists ? round(filesize(self::CSV_FILE) / 1024) . ' KB' : '0 KB';

        // Contar registros si el archivo existe
        $recordCount = 0;
        if ($csvExists) {
            $handle = fopen(self::CSV_FILE, 'r');
            if ($handle) {
                $recordCount = 0;
                while (fgetcsv($handle, 8192, ',') !== false) {
                    $recordCount++;
                }
                fclose($handle);
                // Subtract header
                $recordCount = max(0, $recordCount - 1);
            }
        }

        // Renderizar vista usando PHP inline (sin twig para evitar más dependencias)
        $title = 'Catálogo WhatsApp - Productos';

        // Cargar plantilla - buscar en la estructura de vistas
        $layoutFile = __DIR__ . '/../../../templates/admin/layout.php';

        if (file_exists($layoutFile)) {
            // Extraer solo el contenido principal o renderizar con variables
            extract([
                'title' => $title,
                'csvExists' => $csvExists,
                'csvModTime' => $csvModTime,
                'csvSize' => $csvSize,
                'recordCount' => $recordCount,
                'page' => 'whatsAppCatalog',
            ]);

            // Cargar contenido específico del catálogo
            $catalogContent = file_exists(__DIR__ . '/../../../templates/admin/whats_catalog.php')
                ? include __DIR__ . '/../../../templates/admin/whats_catalog.php'
                : self::defaultCatalogView($csvExists, $csvModTime, $csvSize, $recordCount);

            // Cargar layout
            require $layoutFile;
        } else {
            // Fallback sin layout
            self::defaultCatalogView($csvExists, $csvModTime, $csvSize, $recordCount);
        }
    }

    /** Vista por defecto del catálogo si no hay plantilla específica */
    private static function defaultCatalogView(bool $csvExists, string $csvModTime, string $csvSize, int $recordCount): void
    {
        echo "<!DOCTYPE html>\n";
        echo "<html lang='es' dir='ltr'>\n";
        echo "<head>\n";
        echo "    <meta charset='utf-8'>\n";
        echo "    <title>" . htmlspecialchars($title) . "</title>\n";
        echo "    <meta name='viewport' content='width=device-width, initial-scale=1'>\n";
        echo "    <link rel='stylesheet' href='/css/bootstrap.min.css'>\n";
        echo "    <style>body { padding: 2rem; }</style>\n";
        echo "</head>\n";
        echo "<body>\n";
        echo "    <div class='container'>\n";
        echo "        <h1>" . htmlspecialchars($title) . "</h1>\n";
        echo "        <div class='alert alert-info'>\n";
        echo "            <h4>Catálogo WhatsApp Business</h4>\n";
        echo "            <p>Archivo: catalog_products.csv</p>\n";
        echo "            <p>Última actualización: " . htmlspecialchars($csvModTime) . "</p>\n";
        echo "            <p>Tamaño: " . htmlspecialchars($csvSize) . "</p>\n";
        echo "            <p>Registros: " . htmlspecialchars($recordCount) . "</p>\n";
        echo "        </div>\n";

        if (!$csvExists) {
            echo "        <p>El archivo aún no se ha generado.</p>\n";
            echo "        <a href='/admin/whatsApp-catalog/generate' class='btn btn-primary'>Generar catálogo ahora</a>\n";
        } else {
            echo "        <a href='/admin/whatsApp-catalog/download' class='btn btn-success' download>
                    <i class='fas fa-download'></i> Descargar catálogo
                </a>\n";
            echo "        <a href='/admin/whatsApp-catalog/generate' class='btn btn-secondary'>Re-generar</a>\n";
        }

        echo "        <hr>\n";
        echo "        <p><small>El catálogo se puede configurar para generarse automáticamente cada X minutos mediante cron job.</small></p>\n";
        echo "    </div>\n";
        echo "</body>\n";
        echo "</html>\n";
    }
}