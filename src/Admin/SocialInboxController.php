<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\SocialInboxRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Service\Meta\WhatsAppChannel;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class SocialInboxController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('clientes');

        $tab = (string)($_GET['tab'] ?? 'unassigned');
        if (!in_array($tab, ['unassigned', 'mine', 'closed'], true)) {
            $tab = 'unassigned';
        }
        $q = trim((string)($_GET['q'] ?? ''));
        $selectedId = (int)($_GET['c'] ?? 0);

        $repo = new SocialInboxRepo();
        $conversations = $repo->listConversations($tab, (int)$adminUser['id'], $q);

        if ($selectedId <= 0 && $conversations) {
            $selectedId = (int)$conversations[0]['id'];
        }

        $selected = $selectedId > 0 ? $repo->getConversation($selectedId) : null;
        $messages = [];
        $notes = [];
        if ($selected) {
            $repo->markRead((int)$selected['id']);
            $selected['unread_count'] = 0;
            $messages = $repo->listMessages((int)$selected['id']);
            $notes = $repo->listNotes((int)$selected['id']);
        }

        echo View::adminPage('admin/mensajes/inbox.php', [
            'adminUser' => $adminUser,
            'tab' => $tab,
            'q' => $q,
            'conversations' => $conversations,
            'selected' => $selected,
            'messages' => $messages,
            'notes' => $notes,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Mensajes',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function take(array $params): void
    {
        $adminUser = (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);

        $conversationId = (int)($_POST['conversation_id'] ?? 0);
        if ($conversationId <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Conversación inválida.'];
            Response::redirect('/admin/mensajes');
        }

        $ok = (new SocialInboxRepo())->takeConversation($conversationId, (int)$adminUser['id']);
        $_SESSION['admin_flash'] = $ok
            ? ['type' => 'ok', 'text' => 'Conversación tomada correctamente.']
            : ['type' => 'info', 'text' => 'La conversación ya fue tomada por otro usuario o está cerrada.'];
        Response::redirect('/admin/mensajes?tab=mine&c=' . $conversationId);
    }

    public function release(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);
        $conversationId = (int)($_POST['conversation_id'] ?? 0);
        if ($conversationId > 0) {
            (new SocialInboxRepo())->releaseConversation($conversationId);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Conversación liberada.'];
        }
        Response::redirect('/admin/mensajes?tab=unassigned&c=' . $conversationId);
    }

    public function close(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);
        $conversationId = (int)($_POST['conversation_id'] ?? 0);
        if ($conversationId > 0) {
            (new SocialInboxRepo())->closeConversation($conversationId);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Conversación cerrada.'];
        }
        Response::redirect('/admin/mensajes?tab=closed&c=' . $conversationId);
    }

    public function reopen(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);
        $conversationId = (int)($_POST['conversation_id'] ?? 0);
        if ($conversationId > 0) {
            (new SocialInboxRepo())->reopenConversation($conversationId);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Conversación reabierta y enviada a cola.'];
        }
        Response::redirect('/admin/mensajes?tab=unassigned&c=' . $conversationId);
    }

    public function note(array $params): void
    {
        $adminUser = (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);
        $conversationId = (int)($_POST['conversation_id'] ?? 0);
        $note = trim((string)($_POST['note'] ?? ''));
        if ($conversationId > 0 && $note !== '') {
            (new SocialInboxRepo())->addNote($conversationId, (int)$adminUser['id'], $note);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Nota guardada.'];
        }
        Response::redirect('/admin/mensajes?c=' . $conversationId . '&tab=' . urlencode((string)($_POST['tab'] ?? 'unassigned')));
    }

    public function responder(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);

        $conversationId = (int)($_POST['conversation_id'] ?? 0);
        $tab = (string)($_POST['tab'] ?? 'mine');
        $text = trim((string)($_POST['text'] ?? ''));
        $back = '/admin/mensajes?tab=' . urlencode($tab) . '&c=' . $conversationId;

        $repo = new SocialInboxRepo();
        $conv = $conversationId > 0 ? $repo->getConversation($conversationId) : null;
        if (!$conv) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Conversación inválida.'];
            Response::redirect('/admin/mensajes');
        }
        if (($conv['status'] ?? '') === 'cerrado') {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'La conversación está cerrada.'];
            Response::redirect($back);
        }
        if ($text === '') {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'Escribí un mensaje para enviar.'];
            Response::redirect($back);
        }
        if (($conv['channel'] ?? '') !== 'whatsapp') {
            $_SESSION['admin_flash'] = ['type' => 'info', 'text' => 'El envío por este canal llega en el Paso 2 (Instagram/Messenger).'];
            Response::redirect($back);
        }

        $phone = preg_replace('/\D/', '', (string)($conv['phone'] ?? '')) ?? '';
        if ($phone === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El contacto no tiene un teléfono válido.'];
            Response::redirect($back);
        }
        if (!WhatsAppChannel::isConfigured()) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'WhatsApp no está configurado (WA_ACCESS_TOKEN / WA_PHONE_NUMBER_ID en el .env).'];
            Response::redirect($back);
        }

        $text = mb_substr($text, 0, 4096);
        $res = (new WhatsAppChannel())->sendText($phone, $text);
        if ($res['ok']) {
            $repo->insertOutbound($conversationId, $text, $res['wamid'], 'sent', null);
            $repo->touchAfterOutbound($conversationId, mb_substr($text, 0, 255));
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Mensaje enviado por WhatsApp.'];
        } else {
            $repo->insertOutbound($conversationId, $text, null, 'failed', $res['error']);
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se pudo enviar: ' . (string)$res['error']];
        }
        Response::redirect($back);
    }
}
