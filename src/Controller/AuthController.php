<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Infra\SmtpMailer;
use Perfushopping\Web\Repo\MetaRepo;
use Perfushopping\Web\Repo\TokenRepo;
use Perfushopping\Web\Repo\TermsRepo;
use Perfushopping\Web\Repo\UserRepo;
use Perfushopping\Web\Repo\AffiliateRepo;
use Perfushopping\Web\Repo\WholesaleRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class AuthController
{
    public function loginForm(array $params): void
    {
        echo View::page('auth/login.php', ['csrf' => Csrf::token(), 'flash' => $_SESSION['flash'] ?? null]);
        unset($_SESSION['flash']);
    }

    public function login(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);
        $email = trim((string)($_POST['email'] ?? ''));
        $pass = (string)($_POST['password'] ?? '');
        $u = (new UserRepo())->findByEmail($email);
        if (!$u || !is_string($u['password_hash'] ?? null) || !password_verify($pass, (string)$u['password_hash'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Usuario o clave incorrectos.'];
            Response::redirect('/login');
        }
        if (!empty($u['disabled_at'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Usuario deshabilitado.'];
            Response::redirect('/login');
        }
        $_SESSION['user_id'] = (int)$u['id'];
        (new UserRepo())->touchLogin((int)$u['id']);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Sesion iniciada.'];
        Response::redirect('/');
    }

    public function registerForm(array $params): void
    {
        echo View::page('auth/register.php', ['csrf' => Csrf::token(), 'flash' => $_SESSION['flash'] ?? null]);
        unset($_SESSION['flash']);
    }

    public function register(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);
        $email = trim((string)($_POST['email'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $wantWholesale = (string)($_POST['want_wholesale'] ?? '') === '1';
        $acceptTerms = (string)($_POST['accept_terms'] ?? '') === '1';

        if ($email === '' || $name === '') {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Completa email y nombre.'];
            Response::redirect('/register');
        }
        if (!$acceptTerms) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Debes aceptar Terminos, Privacidad y Programa de referidos para registrarte.'];
            Response::redirect('/register');
        }
        $repo = new UserRepo();
        if ($repo->findByEmail($email)) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Ese email ya esta registrado.'];
            Response::redirect('/register');
        }
        $status = $wantWholesale ? 'pending' : 'none';
        $role = ($email === Env::get('ADMIN_EMAIL', 'clientes@perfushopping.com.ar')) ? 'admin' : 'customer';
        $id = $repo->create($email, $name, $phone, $role, $status);

        // Create affiliate code for every user
        try {
            (new AffiliateRepo())->ensureForUser($id);
        } catch (\Throwable $e) {
            // ignore if tables not applied yet
        }

        // Assign referrer (first touch) if cookie exists
        $refCode = $_COOKIE['ref_code'] ?? null;
        if (is_string($refCode) && $refCode !== '') {
            try {
                $aff = (new AffiliateRepo())->findByCode($refCode);
                if ($aff && (int)$aff['user_id'] !== $id) {
                    $repo->setAffiliateReferrerIfEmpty($id, (int)$aff['user_id']);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Store terms acceptance
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $terms = new TermsRepo();
        $terms->accept($id, 'site_v1', is_string($ip) ? $ip : null, is_string($ua) ? $ua : null);
        $terms->accept($id, 'privacy_v1', is_string($ip) ? $ip : null, is_string($ua) ? $ua : null);
        $terms->accept($id, 'affiliate_v1', is_string($ip) ? $ip : null, is_string($ua) ? $ua : null);

        // Send activation email
        $token = bin2hex(random_bytes(24));
        $hash = hash('sha256', $token);
        (new TokenRepo())->create($id, 'activate', $hash, 48);
        $url = rtrim(Env::get('APP_URL', 'https://perfushopping.ar'), '/') . '/activate?token=' . urlencode($token);
        (new SmtpMailer())->send($email, 'Activa tu cuenta Perfushopping',
            '<p>Hola ' . htmlspecialchars($name) . ',</p>' .
            '<p>Para activar tu cuenta, hace click:</p>' .
            '<p><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a></p>' .
            '<p>Si no fuiste vos, ignora este email.</p>'
        );

        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Te enviamos un email de activacion.'];
        Response::redirect('/login');
    }

    public function logout(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);
        unset($_SESSION['user_id']);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Sesion cerrada.'];
        Response::redirect('/');
    }

    public function activateForm(array $params): void
    {
        $token = (string)($_GET['token'] ?? '');
        echo View::page('auth/activate.php', ['csrf' => Csrf::token(), 'token' => $token, 'flash' => $_SESSION['flash'] ?? null]);
        unset($_SESSION['flash']);
    }

    public function activate(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);
        $token = (string)($_POST['token'] ?? '');
        $pass = (string)($_POST['password'] ?? '');
        if ($token === '' || strlen($pass) < 8) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Token invalido o clave muy corta (minimo 8).'];
            Response::redirect('/activate?token=' . urlencode($token));
        }
        $hash = hash('sha256', $token);
        $t = (new TokenRepo())->findUsable($hash, 'activate');
        if (!$t) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Token vencido o usado.'];
            Response::redirect('/login');
        }
        $userId = (int)$t['user_id'];
        (new UserRepo())->setPassword($userId, password_hash($pass, PASSWORD_DEFAULT));
        (new TokenRepo())->markUsed((int)$t['id']);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Cuenta activada. Ya podes iniciar sesion.'];
        Response::redirect('/login');
    }

    public function wholesaleRequestForm(array $params): void
    {
        $auth = new AuthService();
        $u = $auth->requireLogin();
        $meta = new MetaRepo();
        echo View::page('auth/wholesale_request.php', [
            'csrf' => Csrf::token(),
            'user' => $u,
            'provincias' => $meta->provincias(),
            'flash' => $_SESSION['flash'] ?? null,
        ]);
        unset($_SESSION['flash']);
    }

    public function wholesaleRequest(array $params): void
    {
        $auth = new AuthService();
        $u = $auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $data = [
            'razon_social' => trim((string)($_POST['razon_social'] ?? '')),
            'cuit' => trim((string)($_POST['cuit'] ?? '')),
            'address' => trim((string)($_POST['address'] ?? '')),
            'city' => trim((string)($_POST['city'] ?? '')),
            'postal_code' => trim((string)($_POST['postal_code'] ?? '')),
            'province_codprov' => (int)($_POST['province_codprov'] ?? 0),
            'notes' => trim((string)($_POST['notes'] ?? '')),
        ];
        foreach (['razon_social','cuit','address','city','postal_code'] as $k) {
            if ($data[$k] === '') {
                $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Completa todos los campos.'];
                Response::redirect('/wholesale/request');
            }
        }
        if ($data['province_codprov'] <= 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Selecciona provincia.'];
            Response::redirect('/wholesale/request');
        }
        (new WholesaleRepo())->submit((int)$u['id'], $data);
        (new UserRepo())->setWholesaleStatus((int)$u['id'], 'pending', (int)($u['cliente_id'] ?? 0) ?: null);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Solicitud enviada. La aprobacion es manual.'];
        Response::redirect('/');
    }
}
