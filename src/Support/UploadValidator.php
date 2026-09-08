<?php
declare(strict_types=1);
namespace Perfushopping\Web\Support;
final class UploadValidator {
    public const ALLOWED_MIME = ['image/jpeg','image/png','image/webp','application/pdf'];
    public const ALLOWED_EXT = ['jpg','jpeg','png','webp','pdf'];
    public const MAX_SIZE = 5 * 1024 * 1024;
    public static function validate(array $file): void {
        if ($file['error'] !== UPLOAD_ERR_OK) throw new \RuntimeException('Upload error '.$file['error']);
        if ($file['size'] > self::MAX_SIZE) throw new \RuntimeException('Archivo muy grande (max 5MB)');
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) throw new \RuntimeException('Extensión no permitida');
        $finfo = finfo_open(FILEINFO_MIME_TYPE); $mime = finfo_file($finfo, $file['tmp_name']); finfo_close($finfo);
        if (!in_array($mime, self::ALLOWED_MIME, true)) throw new \RuntimeException('MIME no permitido: '.$mime);
        if (str_contains(file_get_contents($file['tmp_name'], false, null, 0, 4), '<?php')) throw new \RuntimeException('Archivo PHP no permitido');
    }
    public static function store(array $file, string $dir): string {
        self::validate($file);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $name = bin2hex(random_bytes(16)).'.'.$ext;
        $dest = rtrim($dir,'/').'/'.$name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) throw new \RuntimeException('No se pudo guardar');
        return $name;
    }
}
