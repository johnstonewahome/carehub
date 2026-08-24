<?php
declare(strict_types=1);

class Patients
{
    public function __construct(private PDO $db)
    {
    }

    public function search(string $q, int $limit = 50): array
    {
        $q = trim($q);
        if ($q === '') {
            $stmt = $this->db->query(
                'SELECT * FROM patients ORDER BY last_name, first_name LIMIT ' . (int) $limit
            );
            return $stmt->fetchAll();
        }
        $like = '%' . $q . '%';
        $stmt = $this->db->prepare(
            'SELECT * FROM patients
             WHERE chart_no LIKE ?
                OR first_name LIKE ?
                OR last_name LIKE ?
                OR phone LIKE ?
                OR CONCAT(first_name, " ", last_name) LIKE ?
             ORDER BY last_name, first_name
             LIMIT ' . (int) $limit
        );
        $stmt->execute([$like, $like, $like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function get(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM patients WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->assertRequired($data);
        $this->db->beginTransaction();
        try {
            $tmp = 'TMP-' . bin2hex(random_bytes(6));
            $stmt = $this->db->prepare(
                'INSERT INTO patients
                    (chart_no, first_name, last_name, sex, dob, phone, address, allergies, medical_history)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $tmp,
                $data['first_name'],
                $data['last_name'],
                $data['sex'],
                $data['dob'] !== '' ? $data['dob'] : null,
                $data['phone'] !== '' ? $data['phone'] : null,
                $data['address'] !== '' ? $data['address'] : null,
                $data['allergies'] !== '' ? $data['allergies'] : null,
                $data['medical_history'] !== '' ? $data['medical_history'] : null,
            ]);
            $id = (int) $this->db->lastInsertId();
            $chart = sprintf('CH-%04d', $id);
            $update = $this->db->prepare('UPDATE patients SET chart_no = ? WHERE id = ?');
            $update->execute([$chart, $id]);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): void
    {
        $this->assertRequired($data);
        $stmt = $this->db->prepare(
            'UPDATE patients
             SET first_name = ?, last_name = ?, sex = ?, dob = ?, phone = ?,
                 address = ?, allergies = ?, medical_history = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['sex'],
            $data['dob'] !== '' ? $data['dob'] : null,
            $data['phone'] !== '' ? $data['phone'] : null,
            $data['address'] !== '' ? $data['address'] : null,
            $data['allergies'] !== '' ? $data['allergies'] : null,
            $data['medical_history'] !== '' ? $data['medical_history'] : null,
            $id,
        ]);
    }

    private function assertRequired(array $data): void
    {
        if (trim($data['first_name'] ?? '') === '' || trim($data['last_name'] ?? '') === '') {
            throw new InvalidArgumentException('First and last name are required');
        }
        $sex = $data['sex'] ?? '';
        if (!in_array($sex, ['female', 'male', 'other'], true)) {
            throw new InvalidArgumentException('Choose a sex');
        }
    }
}
