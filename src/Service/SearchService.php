<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\ProductRepo;

final class SearchService
{
    private int $limitProducts = 12;
    private int $limitOther = 6;

    public function search(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [
                'query' => '',
                'products' => [],
                'brands' => [],
                'categories' => [],
                'needs' => [],
                'routines' => [],
                'articles' => [],
                'videos' => [],
            ];
        }
        $like = '%' . $q . '%';
        return [
            'query' => $q,
            'products' => $this->products($q),
            'brands' => $this->brands($like),
            'categories' => $this->categories($like),
            'needs' => $this->needs($like),
            'routines' => $this->routines($like),
            'articles' => $this->articles($like),
            'videos' => $this->videos($like),
        ];
    }

    private function products(string $q): array
    {
        return (new ProductRepo())->list(['q' => $q, 'limit' => $this->limitProducts]);
    }

    private function brands(string $like): array
    {
        $st = Db::pdo()->prepare('SELECT codsub, nomsub FROM subrubro WHERE nomsub LIKE :q ORDER BY nomsub ASC LIMIT ' . $this->limitOther);
        $st->execute([':q' => $like]);
        return $st->fetchAll() ?: [];
    }

    private function categories(string $like): array
    {
        $st = Db::pdo()->prepare('SELECT codrub, nomrub FROM rubros WHERE nomrub LIKE :q ORDER BY nomrub ASC LIMIT ' . $this->limitOther);
        $st->execute([':q' => $like]);
        return $st->fetchAll() ?: [];
    }

    private function needs(string $like): array
    {
        $st = Db::pdo()->prepare('SELECT id, name, slug FROM cms_needs WHERE active = 1 AND (name LIKE :q OR description LIKE :q) ORDER BY name ASC LIMIT ' . $this->limitOther);
        $st->execute([':q' => $like]);
        return $st->fetchAll() ?: [];
    }

    private function routines(string $like): array
    {
        $st = Db::pdo()->prepare('SELECT id, name, slug FROM cms_routines WHERE active = 1 AND (name LIKE :q OR description LIKE :q) ORDER BY name ASC LIMIT ' . $this->limitOther);
        $st->execute([':q' => $like]);
        return $st->fetchAll() ?: [];
    }

    private function articles(string $like): array
    {
        $st = Db::pdo()->prepare('SELECT id, title, slug FROM cms_articles WHERE active = 1 AND (title LIKE :q OR excerpt LIKE :q) ORDER BY title ASC LIMIT ' . $this->limitOther);
        $st->execute([':q' => $like]);
        return $st->fetchAll() ?: [];
    }

    private function videos(string $like): array
    {
        $st = Db::pdo()->prepare('SELECT id, title, slug FROM cms_videos WHERE active = 1 AND (title LIKE :q OR description_short LIKE :q) ORDER BY title ASC LIMIT ' . $this->limitOther);
        $st->execute([':q' => $like]);
        return $st->fetchAll() ?: [];
    }
}
