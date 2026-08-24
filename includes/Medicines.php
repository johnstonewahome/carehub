<?php
declare(strict_types=1);

class Medicines
{
    public function __construct(private PDO $db, private Stock $stock)
    {
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT * FROM medicines ORDER BY name'
        )->fetchAll();
    }

    public function search(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return $this->all();
        }
        $like = '%' . $q . '%';
        $stmt = $this->db->prepare(
            'SELECT * FROM medicines
             WHERE name LIKE ? OR generic_name LIKE ? OR strength LIKE ?
             ORDER BY name'
        );
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function get(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM medicines WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function lowStock(): array
    {
        return $this->db->query(
            'SELECT * FROM medicines
             WHERE quantity_on_hand <= reorder_level
             ORDER BY quantity_on_hand ASC, name'
        )->fetchAll();
    }

    public function create(array $data): int
    {
        $this->assertRequired($data);
        $stmt = $this->db->prepare(
            'INSERT INTO medicines
                (name, generic_name, form, strength, unit, quantity_on_hand, reorder_level, notes)
             VALUES (?, ?, ?, ?, ?, 0, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['generic_name'] !== '' ? $data['generic_name'] : null,
            $data['form'],
            $data['strength'] !== '' ? $data['strength'] : null,
            $data['unit'] !== '' ? $data['unit'] : 'units',
            $data['reorder_level'] !== '' ? $data['reorder_level'] : 0,
            $data['notes'] !== '' ? $data['notes'] : null,
        ]);
        $id = (int) $this->db->lastInsertId();
        $initial = (float) ($data['initial_quantity'] ?? 0);
        if ($initial > 0) {
            $this->stock->receive($id, $initial, 'Opening stock');
        }
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $this->assertRequired($data);
        $stmt = $this->db->prepare(
            'UPDATE medicines
             SET name = ?, generic_name = ?, form = ?, strength = ?, unit = ?,
                 reorder_level = ?, notes = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['generic_name'] !== '' ? $data['generic_name'] : null,
            $data['form'],
            $data['strength'] !== '' ? $data['strength'] : null,
            $data['unit'] !== '' ? $data['unit'] : 'units',
            $data['reorder_level'] !== '' ? $data['reorder_level'] : 0,
            $data['notes'] !== '' ? $data['notes'] : null,
            $id,
        ]);
    }

    public function movements(int $medicineId): array
    {
        $stmt = $this->db->prepare(
            'SELECT sm.*, p.chart_no, p.first_name, p.last_name
             FROM stock_movements sm
             LEFT JOIN visits v ON v.id = sm.visit_id
             LEFT JOIN patients p ON p.id = v.patient_id
             WHERE sm.medicine_id = ?
             ORDER BY sm.created_at DESC, sm.id DESC
             LIMIT 80'
        );
        $stmt->execute([$medicineId]);
        return $stmt->fetchAll();
    }

    private function assertRequired(array $data): void
    {
        if (trim($data['name'] ?? '') === '') {
            throw new InvalidArgumentException('Medicine name is required');
        }
        $form = $data['form'] ?? '';
        if (!in_array($form, ['tablet', 'syrup', 'injection', 'cream', 'other'], true)) {
            throw new InvalidArgumentException('Choose a form');
        }
    }
}
