<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\PromoTarjetaRepo;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\Share;
use Perfushopping\Web\Support\View;

final class PromoTarjetasController
{
    public function index(array $params): void
    {
        $promos = (new PromoTarjetaRepo())->findActivos();

        $shareByPromo = [];
        foreach ($promos as $promo) {
            $id = (int)($promo['id'] ?? 0);
            if ($id > 0) {
                $shareByPromo[$id] = $this->shareData($promo, $id);
            }
        }

        echo View::page('promo-tarjetas.php', [
            'promos' => $promos,
            'shareByPromo' => $shareByPromo,
            'pageTitle' => 'Promociones Bancarias Vigentes — Perfushopping',
        ]);
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            Response::notFound();
            return;
        }

        $promo = (new PromoTarjetaRepo())->findPublicoById($id);
        if (!$promo) {
            Response::notFound();
            return;
        }

        $share = $this->shareData($promo, $id);

        echo View::page('promo-tarjeta.php', [
            'promo' => $promo,
            'share' => $share,
            'pageTitle' => 'Promoción ' . trim((string)($promo['banco'] ?? '')) . ' — Perfushopping',
            'head' => Share::ogHead($share, 'article'),
        ]);
    }

    /** @param array<string,mixed> $p */
    private function shareData(array $p, int $id): array
    {
        $base = Format::baseUrl();
        $url = $base . '/promociones/' . $id;

        $esCredito = (string)($p['tipo_tarjeta'] ?? '') === 'credito';
        $tipoLabel = $esCredito ? 'Crédito' : 'Débito';
        $banco = trim((string)($p['banco'] ?? ''));

        $titleParts = [];
        if ($banco !== '') {
            $titleParts[] = $banco;
        }
        $titleParts[] = 'Promoción';
        $titleParts[] = $tipoLabel;
        $title = implode(' · ', $titleParts);

        $descParts = [];
        foreach (['descripcion', 'detalle_promo'] as $k) {
            $v = trim(strip_tags((string)($p[$k] ?? '')));
            if ($v !== '') {
                $descParts[] = $v;
            }
        }
        $description = trim(preg_replace('/\s+/', ' ', implode(' — ', $descParts)) ?? '');
        $description = mb_substr($description, 0, 180, 'UTF-8');

        $image = $this->promoImageUrl((string)($p['imagen'] ?? ''));
        if ($image !== '' && !str_starts_with($image, 'http://') && !str_starts_with($image, 'https://')) {
            $image = $base . $image;
        }

        return Share::data($url, $title, $description, $image);
    }

    private function promoImageUrl(string $img): string
    {
        $img = trim($img);
        if ($img === '') {
            return '';
        }
        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://') || str_starts_with($img, '/')) {
            return $img;
        }
        return '/upload/' . $img;
    }
}
