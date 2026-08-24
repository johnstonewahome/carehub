<?php
declare(strict_types=1);

class StockException extends RuntimeException
{
}

class Stock
{
    public function __construct(private PDO $db)
    {
    }

    public function quantityOnHand(int $medicineId): float
    {
        $stmt = $this->db->prepare('SELECT quantity_on_hand FROM medicines WHERE id = ?');
        $stmt->execute([$medicineId]);
        $value = $stmt->fetchColumn();
        if ($value === false) {
            throw new StockException('Medicine not found');
        }
        return (float) $value;
    }

    public function isLowStock(int $medicineId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT quantity_on_hand, reorder_level FROM medicines WHERE id = ?'
        );
        $stmt->execute([$medicineId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new StockException('Medicine not found');
        }
        return (float) $row['quantity_on_hand'] <= (float) $row['reorder_level'];
    }

    public function receive(int $medicineId, float $quantity, string $reason): void
    {
        $this->assertPositive($quantity, 'Receive quantity must be greater than zero');
        $this->change($medicineId, $quantity, 'in', $reason, null);
    }

    public function adjust(int $medicineId, float $delta, string $reason): void
    {
        if ($delta == 0.0) {
            throw new StockException('Adjust quantity cannot be zero');
        }
        if (trim($reason) === '') {
            throw new StockException('Adjustments need a reason');
        }
        $this->change($medicineId, $delta, 'adjust', $reason, null);
    }

    public function dispense(
        int $visitId,
        int $medicineId,
        float $quantity,
        string $doseInstructions
    ): void {
        $this->assertPositive($quantity, 'Dispense quantity must be greater than zero');

        $this->transact(function () use ($visitId, $medicineId, $quantity, $doseInstructions): void {
            $this->lockAndApply($medicineId, -$quantity);
            $medStmt = $this->db->prepare(
                'INSERT INTO visit_medications (visit_id, medicine_id, quantity, dose_instructions)
                 VALUES (?, ?, ?, ?)'
            );
            $medStmt->execute([$visitId, $medicineId, $this->qty($quantity), $doseInstructions]);

            $moveStmt = $this->db->prepare(
                'INSERT INTO stock_movements (medicine_id, type, quantity, reason, visit_id)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $moveStmt->execute([
                $medicineId,
                'out',
                $this->qty($quantity),
                'Used on visit',
                $visitId,
            ]);
        });
    }

    private function change(
        int $medicineId,
        float $delta,
        string $type,
        string $reason,
        ?int $visitId
    ): void {
        $this->transact(function () use ($medicineId, $delta, $type, $reason, $visitId): void {
            $this->lockAndApply($medicineId, $delta);
            $stmt = $this->db->prepare(
                'INSERT INTO stock_movements (medicine_id, type, quantity, reason, visit_id)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $recorded = $type === 'adjust' ? $delta : abs($delta);
            $stmt->execute([
                $medicineId,
                $type,
                $this->qty($recorded),
                $reason,
                $visitId,
            ]);
        });
    }

    private function transact(callable $fn): void
    {
        $own = !$this->db->inTransaction();
        if ($own) {
            $this->db->beginTransaction();
        }
        try {
            $fn();
            if ($own) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($own && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function lockAndApply(int $medicineId, float $delta): void
    {
        $stmt = $this->db->prepare(
            'SELECT quantity_on_hand FROM medicines WHERE id = ? FOR UPDATE'
        );
        $stmt->execute([$medicineId]);
        $current = $stmt->fetchColumn();
        if ($current === false) {
            throw new StockException('Medicine not found');
        }
        $next = round((float) $current + $delta, 2);
        if ($next < 0) {
            throw new StockException(
                'Not enough on hand (have ' . $this->qty((float) $current) . ')'
            );
        }
        $update = $this->db->prepare(
            'UPDATE medicines SET quantity_on_hand = ? WHERE id = ?'
        );
        $update->execute([$this->qty($next), $medicineId]);
    }

    private function assertPositive(float $quantity, string $message): void
    {
        if ($quantity <= 0) {
            throw new StockException($message);
        }
    }

    private function qty(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
