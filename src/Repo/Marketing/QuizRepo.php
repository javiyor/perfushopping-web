<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo\Marketing;

use Perfushopping\Web\Infra\Db;

final class QuizRepo
{
    public function findAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM cms_quizzes';
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        return Db::pdo()->query($sql)->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_quizzes WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_quizzes WHERE slug = :s AND active = 1 LIMIT 1');
        $st->execute([':s' => $slug]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function save(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $slug = $this->uniqueSlug(trim((string)($data['slug'] ?? '')), $id);
        if ($slug === '') {
            $slug = $this->uniqueSlug(trim((string)($data['name'] ?? '')), $id);
        }
        $fields = [
            ':n' => trim((string)($data['name'] ?? '')),
            ':s' => $slug,
            ':t' => trim((string)($data['title'] ?? '')),
            ':d' => trim((string)($data['description'] ?? '')),
            ':a' => (int)($data['active'] ?? 1),
            ':so' => (int)($data['sort_order'] ?? 0),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_quizzes SET name=:n, slug=:s, title=:t, description=:d, active=:a, sort_order=:so WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_quizzes (name, slug, title, description, active, sort_order) VALUES (:n, :s, :t, :d, :a, :so)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_quizzes WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function findQuestions(int $quizId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_quiz_questions WHERE quiz_id = :id AND active = 1 ORDER BY sort_order ASC, id ASC');
        $st->execute([':id' => $quizId]);
        $questions = $st->fetchAll() ?: [];
        foreach ($questions as $i => $q) {
            $questions[$i]['options'] = $this->findOptions((int)$q['id']);
        }
        return $questions;
    }

    /** @return array<int,array<string,mixed>> */
    public function findOptions(int $questionId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_quiz_options WHERE question_id = :id ORDER BY sort_order ASC, id ASC');
        $st->execute([':id' => $questionId]);
        $rows = $st->fetchAll() ?: [];
        foreach ($rows as $i => $r) {
            $rows[$i]['tags'] = $this->decodeTags($r['tags'] ?? '');
        }
        return $rows;
    }

    public function saveQuestion(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $fields = [
            ':qid' => (int)($data['quiz_id'] ?? 0),
            ':q' => trim((string)($data['question'] ?? '')),
            ':so' => (int)($data['sort_order'] ?? 0),
            ':a' => (int)($data['active'] ?? 1),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_quiz_questions SET quiz_id=:qid, question=:q, sort_order=:so, active=:a WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_quiz_questions (quiz_id, question, sort_order, active) VALUES (:qid, :q, :so, :a)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function deleteQuestion(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_quiz_questions WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    public function saveOption(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $tags = is_array($data['tags'] ?? null) ? $data['tags'] : [];
        $fields = [
            ':qid' => (int)($data['question_id'] ?? 0),
            ':l' => trim((string)($data['label'] ?? '')),
            ':t' => json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':so' => (int)($data['sort_order'] ?? 0),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_quiz_options SET question_id=:qid, label=:l, tags=:t, sort_order=:so WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_quiz_options (question_id, label, tags, sort_order) VALUES (:qid, :l, :t, :so)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function deleteOption(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_quiz_options WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function findRules(int $quizId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM cms_recommendation_rules WHERE quiz_id = :id AND active = 1 ORDER BY priority DESC, id ASC');
        $st->execute([':id' => $quizId]);
        $rows = $st->fetchAll() ?: [];
        foreach ($rows as $i => $r) {
            $rows[$i]['conditions'] = $this->decodeTags($r['conditions'] ?? '');
            $rows[$i]['excluded_conditions'] = $this->decodeTags($r['excluded_conditions'] ?? '');
        }
        return $rows;
    }

    public function saveRule(array $data): int
    {
        $id = (int)($data['id'] ?? 0);
        $conditions = is_array($data['conditions'] ?? null) ? $data['conditions'] : [];
        $excluded = is_array($data['excluded_conditions'] ?? null) ? $data['excluded_conditions'] : [];
        $fields = [
            ':qid' => (int)($data['quiz_id'] ?? 0),
            ':n' => trim((string)($data['name'] ?? '')),
            ':p' => (int)($data['priority'] ?? 0),
            ':c' => json_encode($conditions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':e' => json_encode($excluded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':tt' => trim((string)($data['target_type'] ?? '')),
            ':ti' => (int)($data['target_id'] ?? 0),
            ':sc' => (int)($data['score'] ?? 0),
            ':a' => (int)($data['active'] ?? 1),
        ];
        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE cms_recommendation_rules SET quiz_id=:qid, name=:n, priority=:p, conditions=:c, excluded_conditions=:e, target_type=:tt, target_id=:ti, score=:sc, active=:a WHERE id=:i');
            $st->execute($fields + [':i' => $id]);
            return $id;
        }
        $st = Db::pdo()->prepare('INSERT INTO cms_recommendation_rules (quiz_id, name, priority, conditions, excluded_conditions, target_type, target_id, score, active) VALUES (:qid, :n, :p, :c, :e, :tt, :ti, :sc, :a)');
        $st->execute($fields);
        return (int)Db::pdo()->lastInsertId();
    }

    public function deleteRule(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM cms_recommendation_rules WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    private function decodeTags(mixed $value): array
    {
        if (is_array($value)) return $value;
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) return $decoded;
        }
        return [];
    }

    private function uniqueSlug(string $base, int $excludeId): string
    {
        $base = trim($base);
        if ($base === '') $base = 'recomendador';
        $slug = preg_replace('/[^a-z0-9-]+/u', '-', mb_strtolower($base)) ?? $base;
        $slug = trim($slug, '-');
        if ($slug === '') $slug = 'recomendador';
        $candidate = $slug;
        $i = 2;
        while (true) {
            $st = Db::pdo()->prepare('SELECT id FROM cms_quizzes WHERE slug = :s AND id != :e LIMIT 1');
            $st->execute([':s' => $candidate, ':e' => $excludeId]);
            if (!$st->fetch()) break;
            $candidate = $slug . '-' . $i++;
        }
        return $candidate;
    }
}
