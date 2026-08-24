-- CareHub clinic database
-- SQLite. The live file is created from this script (database/carehub.sqlite).
-- Default login after seed: admin@carehub.local / ChangeMe!23

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS patients (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  chart_no TEXT NOT NULL UNIQUE,
  first_name TEXT NOT NULL,
  last_name TEXT NOT NULL,
  sex TEXT NOT NULL DEFAULT 'other' CHECK (sex IN ('female', 'male', 'other')),
  dob TEXT NULL,
  phone TEXT NULL,
  address TEXT NULL,
  allergies TEXT NULL,
  medical_history TEXT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS patients_name ON patients (last_name, first_name);
CREATE INDEX IF NOT EXISTS patients_phone ON patients (phone);

CREATE TABLE IF NOT EXISTS visits (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  patient_id INTEGER NOT NULL,
  visited_at TEXT NOT NULL,
  chief_complaint TEXT NULL,
  examination TEXT NULL,
  diagnosis TEXT NULL,
  treatment TEXT NULL,
  bp_systolic INTEGER NULL,
  bp_diastolic INTEGER NULL,
  pulse INTEGER NULL,
  temp_c REAL NULL,
  weight_kg REAL NULL,
  notes TEXT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (patient_id) REFERENCES patients (id)
);

CREATE INDEX IF NOT EXISTS visits_patient_visited ON visits (patient_id, visited_at);

CREATE TABLE IF NOT EXISTS medicines (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  generic_name TEXT NULL,
  form TEXT NOT NULL DEFAULT 'tablet' CHECK (form IN ('tablet', 'syrup', 'injection', 'cream', 'other')),
  strength TEXT NULL,
  unit TEXT NOT NULL DEFAULT 'tablets',
  quantity_on_hand REAL NOT NULL DEFAULT 0,
  reorder_level REAL NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS medicines_name ON medicines (name);
CREATE INDEX IF NOT EXISTS medicines_generic ON medicines (generic_name);

CREATE TABLE IF NOT EXISTS stock_movements (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  medicine_id INTEGER NOT NULL,
  type TEXT NOT NULL CHECK (type IN ('in', 'out', 'adjust')),
  quantity REAL NOT NULL,
  reason TEXT NULL,
  visit_id INTEGER NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (medicine_id) REFERENCES medicines (id),
  FOREIGN KEY (visit_id) REFERENCES visits (id)
);

CREATE INDEX IF NOT EXISTS stock_medicine_created ON stock_movements (medicine_id, created_at);
CREATE INDEX IF NOT EXISTS stock_visit ON stock_movements (visit_id);

CREATE TABLE IF NOT EXISTS visit_medications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  visit_id INTEGER NOT NULL,
  medicine_id INTEGER NOT NULL,
  quantity REAL NOT NULL,
  dose_instructions TEXT NULL,
  FOREIGN KEY (visit_id) REFERENCES visits (id),
  FOREIGN KEY (medicine_id) REFERENCES medicines (id)
);

CREATE INDEX IF NOT EXISTS visit_meds_visit ON visit_medications (visit_id);
CREATE INDEX IF NOT EXISTS visit_meds_medicine ON visit_medications (medicine_id);

INSERT INTO users (name, email, password_hash) VALUES
('Clinic admin', 'admin@carehub.local', '$2y$10$UDUnB.gUGRZ/GsSNHTiKpOAce3.LsPH/x89Rsz4QaFi1JW6/HhoNO');

INSERT INTO patients (chart_no, first_name, last_name, sex, dob, phone, address, allergies, medical_history) VALUES
('CH-0001', 'Ama', 'Boateng', 'female', '1988-03-14', '0244111222', '12 Ridge Road', 'Penicillin', 'Asthma since childhood'),
('CH-0002', 'Daniel', 'Cole', 'male', '1975-11-02', '0205550180', '8 Market Lane', '', 'Hypertension'),
('CH-0003', 'Priya', 'Nair', 'female', '1994-07-21', '0279001440', '4 Palm Court', 'Sulfa drugs', '');

INSERT INTO medicines (name, generic_name, form, strength, unit, quantity_on_hand, reorder_level, notes) VALUES
('Amoxil capsules', 'Amoxicillin', 'tablet', '500 mg', 'capsules', 40, 20, 'Keep dry'),
('Paracetamol tablets', 'Paracetamol', 'tablet', '500 mg', 'tablets', 12, 30, 'Low stock on purpose for the home strip'),
('ORS sachets', 'Oral rehydration salts', 'other', '20.5 g', 'sachets', 80, 25, ''),
('Tetanus toxoid', 'Tetanus toxoid', 'injection', '0.5 ml', 'vials', 8, 10, 'Cold chain'),
('Hydrocortisone cream', 'Hydrocortisone', 'cream', '1%', 'tubes', 15, 5, '');
