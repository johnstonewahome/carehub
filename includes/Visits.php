<?php
declare(strict_types=1);

class Visits
{
    public function __construct(private PDO $db, private Stock $stock)
    {
    }

    public function get(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT v.*, p.chart_no, p.first_name, p.last_name, p.allergies
             FROM visits v
             JOIN patients p ON p.id = v.patient_id
             WHERE v.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['medications'] = $this->medications($id);
        return $row;
    }

    public function forPatient(int $patientId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM visits WHERE patient_id = ? ORDER BY visited_at DESC, id DESC'
        );
        $stmt->execute([$patientId]);
        $visits = $stmt->fetchAll();
        foreach ($visits as &$visit) {
            $visit['medications'] = $this->medications((int) $visit['id']);
        }
        unset($visit);
        return $visits;
    }

    public function recent(int $limit = 8): array
    {
        $stmt = $this->db->query(
            'SELECT v.id, v.visited_at, v.chief_complaint, v.diagnosis,
                    p.id AS patient_id, p.chart_no, p.first_name, p.last_name
             FROM visits v
             JOIN patients p ON p.id = v.patient_id
             ORDER BY v.visited_at DESC, v.id DESC
             LIMIT ' . (int) $limit
        );
        return $stmt->fetchAll();
    }

    public function create(int $patientId, array $fields, array $medicines): int
    {
        $visitedAt = $fields['visited_at'] ?? '';
        if ($visitedAt === '') {
            throw new InvalidArgumentException('Visit date is required');
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO visits (
                    patient_id, visited_at, chief_complaint, examination, diagnosis, treatment,
                    bp_systolic, bp_diastolic, pulse, temp_c, weight_kg, notes
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $patientId,
                $this->normalizeDateTime($visitedAt),
                $fields['chief_complaint'] !== '' ? $fields['chief_complaint'] : null,
                $fields['examination'] !== '' ? $fields['examination'] : null,
                $fields['diagnosis'] !== '' ? $fields['diagnosis'] : null,
                $fields['treatment'] !== '' ? $fields['treatment'] : null,
                $fields['bp_systolic'] !== '' ? (int) $fields['bp_systolic'] : null,
                $fields['bp_diastolic'] !== '' ? (int) $fields['bp_diastolic'] : null,
                $fields['pulse'] !== '' ? (int) $fields['pulse'] : null,
                $fields['temp_c'] !== '' ? $fields['temp_c'] : null,
                $fields['weight_kg'] !== '' ? $fields['weight_kg'] : null,
                $fields['notes'] !== '' ? $fields['notes'] : null,
            ]);
            $visitId = (int) $this->db->lastInsertId();
            foreach ($medicines as $line) {
                $medicineId = (int) ($line['medicine_id'] ?? 0);
                $qty = (float) ($line['quantity'] ?? 0);
                if ($medicineId <= 0 || $qty <= 0) {
                    continue;
                }
                $this->stock->dispense(
                    $visitId,
                    $medicineId,
                    $qty,
                    (string) ($line['dose_instructions'] ?? '')
                );
            }
            $this->db->commit();
            return $visitId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function medications(int $visitId): array
    {
        $stmt = $this->db->prepare(
            'SELECT vm.*, m.name, m.strength, m.unit, m.form
             FROM visit_medications vm
             JOIN medicines m ON m.id = vm.medicine_id
             WHERE vm.visit_id = ?
             ORDER BY vm.id'
        );
        $stmt->execute([$visitId]);
        return $stmt->fetchAll();
    }

    private function normalizeDateTime(string $value): string
    {
        $dt = date_create($value);
        if (!$dt) {
            throw new InvalidArgumentException('Visit date is not valid');
        }
        return $dt->format('Y-m-d H:i:s');
    }
}
