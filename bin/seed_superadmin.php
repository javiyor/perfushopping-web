<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Perfushopping\Web\Infra\Db;

$pdo = Db::pdo();

$st = $pdo->query('SELECT COUNT(*) FROM admin_users');
if ((int)$st->fetchColumn() > 0) {
    echo "Ya existen usuarios admin.\n";
    exit(0);
}

$username = 'admin';
$password = 'admin123';
$nombre = 'Super Admin';
$email = 'admin@perfushopping.com';
$hash = password_hash($password, PASSWORD_DEFAULT);

$ins = $pdo->prepare('INSERT INTO admin_users (username, password_hash, nombre, email, rol, activo, created_at, updated_at) VALUES (:u, :p, :n, :e, :r, 1, NOW(), NOW())');
$ins->execute([':u' => $username, ':p' => $hash, ':n' => $nombre, ':e' => $email, ':r' => 'superadmin']);

echo "Superadmin creado:\n";
echo "  Usuario: admin\n";
echo "  Clave: admin123\n";
echo "  Rol: superadmin\n";
echo "  CAMBIA LA CLAVE cuanto antes.\n";
