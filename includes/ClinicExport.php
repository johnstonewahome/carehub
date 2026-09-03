<?php
declare(strict_types=1);

class ClinicExport
{
    public function __construct(private PDO $db)
    {
    }

    public function xlsxBytes(): string
    {
        $book = new ExcelWorkbook();
        $book->addSheet('Patients', [
            'Chart no',
            'First name',
            'Last name',
            'Sex',
            'Date of birth',
            'Phone',
            'Address',
            'PMSHX',
            'Medical history',
            'Created',
            'Updated',
        ], $this->rows(
            'SELECT chart_no, first_name, last_name, sex, dob, phone, address,
                    allergies, medical_history, created_at, updated_at
             FROM patients
             ORDER BY last_name, first_name, id'
        ));
        $book->addSheet('Visits', [
            'Visit id',
            'Chart no',
            'Patient',
            'Visited at',
            'Chief complaint',
            'Examination',
            'Diagnosis',
            'Treatment',
            'BP systolic',
            'BP diastolic',
            'Pulse',
            'Temp C',
            'Weight kg',
            'Notes',
            'Created',
        ], $this->rows(
            'SELECT v.id, p.chart_no, p.first_name || \' \' || p.last_name AS patient,
                    v.visited_at, v.chief_complaint, v.examination, v.diagnosis, v.treatment,
                    v.bp_systolic, v.bp_diastolic, v.pulse, v.temp_c, v.weight_kg, v.notes, v.created_at
             FROM visits v
             JOIN patients p ON p.id = v.patient_id
             ORDER BY v.visited_at DESC, v.id DESC'
        ));
        $book->addSheet('Visit medicines', [
            'Visit id',
            'Chart no',
            'Visited at',
            'Medicine',
            'Quantity',
            'Dose instructions',
        ], $this->rows(
            'SELECT vm.visit_id, p.chart_no, v.visited_at, m.name, vm.quantity, vm.dose_instructions
             FROM visit_medications vm
             JOIN visits v ON v.id = vm.visit_id
             JOIN patients p ON p.id = v.patient_id
             JOIN medicines m ON m.id = vm.medicine_id
             ORDER BY v.visited_at DESC, vm.id'
        ));
        $book->addSheet('Medicines', [
            'Name',
            'Generic name',
            'Form',
            'Strength',
            'Unit',
            'Quantity on hand',
            'Reorder level',
            'Notes',
            'Created',
            'Updated',
        ], $this->rows(
            'SELECT name, generic_name, form, strength, unit, quantity_on_hand,
                    reorder_level, notes, created_at, updated_at
             FROM medicines
             ORDER BY name, id'
        ));
        $book->addSheet('Stock movements', [
            'When',
            'Medicine',
            'Type',
            'Quantity',
            'Reason',
            'Visit id',
        ], $this->rows(
            'SELECT sm.created_at, m.name, sm.type, sm.quantity, sm.reason, sm.visit_id
             FROM stock_movements sm
             JOIN medicines m ON m.id = sm.medicine_id
             ORDER BY sm.created_at DESC, sm.id DESC'
        ));
        return $book->bytes();
    }

    /**
     * @return list<list<mixed>>
     */
    private function rows(string $sql): array
    {
        $stmt = $this->db->query($sql);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
            $out[] = array_values($row);
        }
        return $out;
    }
}
