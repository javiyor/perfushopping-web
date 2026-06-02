<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class DemoTechRepo
{
    /** @return array<int, array<string,mixed>> */
    public function listUpcomingEvents(): array
    {
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $st = Db::pdo()->prepare('SELECT * FROM demo_tech_events WHERE active=1 AND monday_date>=:d ORDER BY monday_date ASC, start_time ASC');
        $st->execute([':d' => $today]);
        $rows = $st->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    /** @return array<int, array<string,mixed>> */
    public function listEvents(int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $st = Db::pdo()->query('SELECT * FROM demo_tech_events ORDER BY monday_date DESC, start_time DESC LIMIT ' . (int)$limit);
        $rows = $st ? $st->fetchAll() : [];
        return is_array($rows) ? $rows : [];
    }

    /** @return array<string,mixed>|null */
    public function findEvent(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM demo_tech_events WHERE id=:id LIMIT 1');
        $st->execute([':id' => $id]);
        $r = $st->fetch();
        return is_array($r) ? $r : null;
    }

    public function remainingSeats(int $eventId): int
    {
        $ev = $this->findEvent($eventId);
        if (!$ev) {
            return 0;
        }
        $cap = (int)($ev['capacity'] ?? 0);
        $st = Db::pdo()->prepare("SELECT COALESCE(SUM(COALESCE(attendees,1)),0) FROM demo_tech_registrations WHERE event_id=:e AND status<>'cancelled'");
        $st->execute([':e' => $eventId]);
        $used = (int)$st->fetchColumn();
        $rem = $cap - $used;
        return $rem > 0 ? $rem : 0;
    }

    /** @param array<string,mixed> $row */
    public function saveEvent(array $row): int
    {
        $id = (int)($row['id'] ?? 0);
        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');

        if ($id > 0) {
            $st = Db::pdo()->prepare('UPDATE demo_tech_events SET monday_date=:d, start_time=:st, end_time=:et, venue_name=:vn, venue_address=:va, capacity=:c, notes=:n, active=:a, updated_at=:u WHERE id=:id');
            $st->execute([
                ':d' => (string)$row['monday_date'],
                ':st' => (string)$row['start_time'],
                ':et' => (string)$row['end_time'],
                ':vn' => (string)$row['venue_name'],
                ':va' => $row['venue_address'] !== null ? (string)$row['venue_address'] : null,
                ':c' => (int)$row['capacity'],
                ':n' => $row['notes'] !== null ? (string)$row['notes'] : null,
                ':a' => (int)$row['active'],
                ':u' => $now,
                ':id' => $id,
            ]);
            return $id;
        }

        $st = Db::pdo()->prepare('INSERT INTO demo_tech_events (monday_date,start_time,end_time,venue_name,venue_address,capacity,notes,active,created_at,updated_at) VALUES (:d,:st,:et,:vn,:va,:c,:n,:a,:cr,:up)');
        $st->execute([
            ':d' => (string)$row['monday_date'],
            ':st' => (string)$row['start_time'],
            ':et' => (string)$row['end_time'],
            ':vn' => (string)$row['venue_name'],
            ':va' => $row['venue_address'] !== null ? (string)$row['venue_address'] : null,
            ':c' => (int)$row['capacity'],
            ':n' => $row['notes'] !== null ? (string)$row['notes'] : null,
            ':a' => (int)$row['active'],
            ':cr' => $now,
            ':up' => $now,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    /** @param array<string,mixed> $row */
    public function createRegistration(array $row): int
    {
        $eventId = (int)($row['event_id'] ?? 0);
        if ($eventId <= 0) {
            throw new \RuntimeException('Selecciona un horario.');
        }
        $ev = $this->findEvent($eventId);
        if (!$ev || (int)($ev['active'] ?? 0) !== 1) {
            throw new \RuntimeException('Evento no disponible.');
        }

        $need = (int)($row['attendees'] ?? 1);
        $need = $need > 0 ? $need : 1;
        if ($need > $this->remainingSeats($eventId)) {
            throw new \RuntimeException('No hay cupos disponibles para ese horario.');
        }

        $sql = 'INSERT INTO demo_tech_registrations
                (event_id, kind, name, email, phone, city, province, salon_name, salon_address, monday_date, attendees, notes, status, created_at, ip, user_agent)
                VALUES
                (:event_id, :kind, :name, :email, :phone, :city, :province, :salon_name, :salon_address, :monday_date, :attendees, :notes, \'new\', :created_at, :ip, :user_agent)';
        $st = Db::pdo()->prepare($sql);
        $st->execute([
            ':event_id' => $eventId,
            ':kind' => (string)$row['kind'],
            ':name' => (string)$row['name'],
            ':email' => $row['email'] !== null ? (string)$row['email'] : null,
            ':phone' => $row['phone'] !== null ? (string)$row['phone'] : null,
            ':city' => $row['city'] !== null ? (string)$row['city'] : null,
            ':province' => $row['province'] !== null ? (string)$row['province'] : null,
            ':salon_name' => $row['salon_name'] !== null ? (string)$row['salon_name'] : null,
            ':salon_address' => $row['salon_address'] !== null ? (string)$row['salon_address'] : null,
            ':monday_date' => (string)$ev['monday_date'],
            ':attendees' => (int)$need,
            ':notes' => $row['notes'] !== null ? (string)$row['notes'] : null,
            ':created_at' => (string)$row['created_at'],
            ':ip' => $row['ip'] !== null ? (string)$row['ip'] : null,
            ':user_agent' => $row['user_agent'] !== null ? (string)$row['user_agent'] : null,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    /** @return array<int, array<string,mixed>> */
    public function listRecentRegistrations(int $limit = 250): array
    {
        $limit = max(1, min(500, $limit));
        $st = Db::pdo()->query(
            'SELECT r.*, e.start_time, e.end_time, e.venue_name, e.venue_address, e.capacity, e.active AS event_active'
            . ' FROM demo_tech_registrations r'
            . ' LEFT JOIN demo_tech_events e ON e.id=r.event_id'
            . ' ORDER BY r.created_at DESC LIMIT ' . (int)$limit
        );
        $rows = $st ? $st->fetchAll() : [];
        return is_array($rows) ? $rows : [];
    }

    public function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['new', 'contacted', 'confirmed', 'cancelled'], true)) {
            throw new \RuntimeException('Estado invalido.');
        }
        $st = Db::pdo()->prepare('UPDATE demo_tech_registrations SET status=:s WHERE id=:id');
        $st->execute([':s' => $status, ':id' => $id]);
    }
}
