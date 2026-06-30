<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class CorreoRepo
{
    /** @return array<int, array<string,mixed>> */
    public function listAgencies(?string $stateId = null, ?string $cityName = null, ?bool $pickupAvailability = null, ?bool $packageReception = null, int $limit = 200): array
    {
        $params = [];
        $where = [];
        if ($stateId !== null && $stateId !== '') {
            $where[] = 'state_id = :sid';
            $params[':sid'] = $stateId;
        }
        if ($cityName !== null && $cityName !== '') {
            $where[] = 'city_name LIKE :city';
            $params[':city'] = '%' . $cityName . '%';
        }
        if ($pickupAvailability !== null) {
            $where[] = 'pickup_availability = :pa';
            $params[':pa'] = $pickupAvailability ? 1 : 0;
        }
        if ($packageReception !== null) {
            $where[] = 'package_reception = :pr';
            $params[':pr'] = $packageReception ? 1 : 0;
        }

        $sql = 'SELECT * FROM correo_agencies';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY state_name ASC, city_name ASC, agency_name ASC LIMIT ' . max(1, min(500, $limit));

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @param array<string,mixed> $agency */
    public function upsertAgency(array $agency): void
    {
        $agencyId = trim((string)($agency['agency_id'] ?? ''));
        if ($agencyId === '') {
            return;
        }

        $location = is_array($agency['location'] ?? null) ? $agency['location'] : [];
        $sql = '
            INSERT INTO correo_agencies (
                agency_id, agency_name, state_id, state_name, city_name,
                street_name, street_number, zip_code, phone, email, schedule,
                pickup_availability, package_reception, raw_json, updated_at
            ) VALUES (
                :agency_id, :agency_name, :state_id, :state_name, :city_name,
                :street_name, :street_number, :zip_code, :phone, :email, :schedule,
                :pickup_availability, :package_reception, :raw_json, NOW()
            )
            ON DUPLICATE KEY UPDATE
                agency_name = VALUES(agency_name),
                state_id = VALUES(state_id),
                state_name = VALUES(state_name),
                city_name = VALUES(city_name),
                street_name = VALUES(street_name),
                street_number = VALUES(street_number),
                zip_code = VALUES(zip_code),
                phone = VALUES(phone),
                email = VALUES(email),
                schedule = VALUES(schedule),
                pickup_availability = VALUES(pickup_availability),
                package_reception = VALUES(package_reception),
                raw_json = VALUES(raw_json),
                updated_at = NOW()
        ';

        Db::pdo()->prepare($sql)->execute([
            ':agency_id' => $agencyId,
            ':agency_name' => (string)($agency['agency_name'] ?? ''),
            ':state_id' => (string)($location['state_id'] ?? ''),
            ':state_name' => (string)($location['state_name'] ?? ''),
            ':city_name' => (string)($location['city_name'] ?? ''),
            ':street_name' => (string)($location['street_name'] ?? ''),
            ':street_number' => (string)($location['street_number'] ?? ''),
            ':zip_code' => (string)($location['zip_code'] ?? ''),
            ':phone' => (string)($agency['phone'] ?? ''),
            ':email' => (string)($agency['email'] ?? ''),
            ':schedule' => (string)($agency['schedule'] ?? ''),
            ':pickup_availability' => !empty($agency['pickup_availability']) ? 1 : 0,
            ':package_reception' => !empty($agency['package_reception']) ? 1 : 0,
            ':raw_json' => json_encode($agency, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
