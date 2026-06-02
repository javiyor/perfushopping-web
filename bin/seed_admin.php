<?php
declare(strict_types=1);

// Usage (from web/): php bin/seed_admin.php

require __DIR__ . '/../src/bootstrap.php';

use Perfushopping\Web\Infra\SmtpMailer;
use Perfushopping\Web\Repo\TokenRepo;
use Perfushopping\Web\Repo\UserRepo;
use Perfushopping\Web\Support\Env;

$email = Env::get('ADMIN_EMAIL', 'clientes@perfushopping.com.ar');
if (!$email) {
    throw new RuntimeException('ADMIN_EMAIL missing');
}

$repo = new UserRepo();
$u = $repo->findByEmail($email);
if (!$u) {
    $id = $repo->create($email, 'Perfushopping Admin', '', 'admin', 'none');
} else {
    $id = (int)$u['id'];
}

$token = bin2hex(random_bytes(24));
$hash = hash('sha256', $token);
(new TokenRepo())->create($id, 'activate', $hash, 48);

$url = rtrim(Env::get('APP_URL', 'https://perfushopping.ar'), '/') . '/activate?token=' . urlencode($token);

(new SmtpMailer())->send($email, 'Activacion Admin Perfushopping',
    '<p>Se genero un link de activacion para el admin:</p>' .
    '<p><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a></p>'
);

echo "OK. Activation link sent to {$email}\n";
