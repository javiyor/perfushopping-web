<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\ProductRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ProductController
{
    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::notFound();
            return;
        }
        $repo = new ProductRepo();
        $p = $repo->find($id);
        if (!$p) {
            Response::notFound();
            return;
        }
        $variants = $repo->variants($id);
        $auth = new AuthService();
        $user = $auth->user();

        $share = $this->shareData($p, $id);

        echo View::page('product.php', [
            'product' => $p,
            'variants' => $variants,
            'user' => $user,
            'isWholesale' => $auth->isWholesaleApproved($user),
            'share' => $share,
            'pageTitle' => (string)($p['produ'] ?? ''),
            'head' => $this->ogHead($share),
        ]);
    }

    /** @param array<string,mixed> $p */
    private function shareData(array $p, int $id): array
    {
        $base = Format::baseUrl();
        $url = $base . '/p/' . $id;
        $title = trim((string)($p['produ'] ?? ''));
        $description = trim(preg_replace('/\s+/', ' ', strip_tags((string)($p['observ'] ?? ''))) ?? '');
        $description = mb_substr($description, 0, 180, 'UTF-8');
        $image = Format::absoluteUploadUrl((string)($p['imagen'] ?: ($p['image'] ?? '')));

        return [
            'url' => $url,
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'text' => trim($title . "\n" . $description),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url),
            'x' => 'https://twitter.com/intent/tweet?text=' . rawurlencode($title) . '&url=' . rawurlencode($url),
            'whatsapp' => 'https://wa.me/?text=' . rawurlencode($title . "\n" . $url),
            'telegram' => 'https://t.me/share/url?url=' . rawurlencode($url) . '&text=' . rawurlencode($title),
            'native' => [
                'title' => $title,
                'text' => $description !== '' ? $title . ' - ' . $description : $title,
                'url' => $url,
            ],
        ];
    }

    /** @param array<string,mixed> $share */
    private function ogHead(array $share): string
    {
        $esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $title = $esc($share['title']);
        $desc = $esc($share['description']);
        $url = $esc($share['url']);
        $img = $esc($share['image']);

        $tags = [];
        $tags[] = '<meta property="og:type" content="product" />';
        $tags[] = '<meta property="og:site_name" content="' . $esc(trim((string)Env::get('APP_NAME', 'Perfushopping'))) . '" />';
        $tags[] = '<meta property="og:url" content="' . $url . '" />';
        $tags[] = '<meta property="og:title" content="' . $title . '" />';
        $tags[] = '<meta property="og:description" content="' . $desc . '" />';
        if ($img !== '') {
            $tags[] = '<meta property="og:image" content="' . $img . '" />';
            $tags[] = '<meta property="og:image:alt" content="' . $title . '" />';
        }
        $tags[] = '<meta name="twitter:card" content="summary_large_image" />';
        $tags[] = '<meta name="twitter:title" content="' . $title . '" />';
        $tags[] = '<meta name="twitter:description" content="' . $desc . '" />';
        if ($img !== '') {
            $tags[] = '<meta name="twitter:image" content="' . $img . '" />';
        }
        return implode("\n    ", $tags) . "\n    ";
    }
}
