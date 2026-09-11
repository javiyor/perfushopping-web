<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\Marketing\ProductContentRepo;
use Perfushopping\Web\Repo\Marketing\QuizRepo;
use Perfushopping\Web\Repo\Marketing\RoutineRepo;

final class RecommendationService
{
    private QuizRepo $quizRepo;
    private ProductContentRepo $productRepo;
    private RoutineRepo $routineRepo;

    public function __construct()
    {
        $this->quizRepo = new QuizRepo();
        $this->productRepo = new ProductContentRepo();
        $this->routineRepo = new RoutineRepo();
    }

    /**
     * @param array<int,int> $answers option_id => option_id
     * @return array{needs:array<string,int>,products:array<int,array<string,mixed>>,routines:array<int,array<string,mixed>>,tags:array<string,array<int,string>>}
     */
    public function recommend(int $quizId, array $answers): array
    {
        $questions = $this->quizRepo->findQuestions($quizId);
        $collectedTags = [];
        foreach ($questions as $q) {
            foreach ($q['options'] as $opt) {
                $oid = (int)$opt['id'];
                if (!in_array($oid, $answers, true)) continue;
                foreach ($opt['tags'] as $tag) {
                    if (!is_array($tag) || empty($tag['taxonomy_key']) || !isset($tag['term_value'])) continue;
                    $key = (string)$tag['taxonomy_key'];
                    $val = (string)$tag['term_value'];
                    $collectedTags[$key][$val] = true;
                }
            }
        }

        $flatTags = [];
        foreach ($collectedTags as $key => $vals) {
            $flatTags[$key] = array_keys($vals);
        }

        $rules = $this->quizRepo->findRules($quizId);
        $ruleTargets = [];
        foreach ($rules as $rule) {
            if (!$this->matches($rule['conditions'] ?? [], $flatTags)) continue;
            if ($this->matches($rule['excluded_conditions'] ?? [], $flatTags)) continue;
            $type = (string)$rule['target_type'];
            $tid = (int)$rule['target_id'];
            $ruleTargets[$type][$tid] = ($ruleTargets[$type][$tid] ?? 0) + (int)$rule['score'];
        }

        $products = [];
        foreach ($this->loadAllActiveProducts() as $p) {
            $pid = (int)$p['idprodu'];
            $tags = $this->productRepo->findTags($pid);
            $score = $this->scoreTags($flatTags, $tags);
            if (isset($ruleTargets['product'][$pid])) {
                $score += $ruleTargets['product'][$pid];
            }
            if ($score <= 0) continue;
            $p['_score'] = $score;
            $products[$pid] = $p;
        }

        $routines = [];
        foreach ($this->routineRepo->findAll(true) as $r) {
            $rid = (int)$r['id'];
            $score = 0;
            if (isset($ruleTargets['routine'][$rid])) {
                $score += $ruleTargets['routine'][$rid];
            }
            $needs = $this->routineRepo->findNeeds($rid);
            foreach ($needs as $n) {
                if (isset($collectedTags['need'][(string)$n['slug']])) {
                    $score += 10;
                }
            }
            if ($score <= 0) continue;
            $r['_score'] = $score;
            $routines[$rid] = $r;
        }

        uasort($products, static fn($a, $b) => ($b['_score'] <=> $a['_score']));
        uasort($routines, static fn($a, $b) => ($b['_score'] <=> $a['_score']));

        return [
            'needs' => array_map(static fn($v) => array_keys($v), $collectedTags),
            'products' => array_slice(array_values($products), 0, 8),
            'routines' => array_slice(array_values($routines), 0, 4),
            'tags' => $flatTags,
        ];
    }

    /** @param array<string,array<int,string>> $collected */
    private function matches(array $conditions, array $collected): bool
    {
        if (!$conditions) return false;
        foreach ($conditions as $tag) {
            if (!is_array($tag) || empty($tag['taxonomy_key']) || !isset($tag['term_value'])) continue;
            $key = (string)$tag['taxonomy_key'];
            $val = (string)$tag['term_value'];
            if (in_array($val, $collected[$key] ?? [], true)) return true;
        }
        return false;
    }

    /** @param array<string,array<int,string>> $collected @param array<string,array<int,string>> $productTags */
    private function scoreTags(array $collected, array $productTags): int
    {
        $score = 0;
        foreach ($collected as $key => $vals) {
            foreach ($vals as $val) {
                if (in_array($val, $productTags[$key] ?? [], true)) {
                    $score += 5;
                }
            }
        }
        return $score;
    }

    /** @return array<int,array<string,mixed>> */
    private function loadAllActiveProducts(): array
    {
        $st = \Perfushopping\Web\Infra\Db::pdo()->query('
            SELECT p.idprodu, p.produ, p.precio, p.precio1, p.imagen, p.iva, i.tiva
            FROM producto p
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE p.enweb = 1
            ORDER BY p.produ ASC
            LIMIT 500
        ');
        return $st->fetchAll() ?: [];
    }
}
