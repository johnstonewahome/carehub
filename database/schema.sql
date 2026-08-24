-- CareHub clinic schema
-- Import in phpMyAdmin or via install.php
-- Default login after seed: admin@carehub.local / ChangeMe!23

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patients (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  chart_no VARCHAR(16) NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  sex ENUM('female','male','other') NOT NULL DEFAULT 'other',
  dob DATE NULL,
  phone VARCHAR(40) NULL,
  address VARCHAR(255) NULL,
  allergies TEXT NULL,
  medical_history TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY patients_chart_no (chart_no),
  KEY patients_name (last_name, first_name),
  KEY patients_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visits (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  patient_id INT UNSIGNED NOT NULL,
  visited_at DATETIME NOT NULL,
  chief_complaint TEXT NULL,
  examination TEXT NULL,
  diagnosis TEXT NULL,
  treatment TEXT NULL,
  bp_systolic SMALLINT UNSIGNED NULL,
  bp_diastolic SMALLINT UNSIGNED NULL,
  pulse SMALLINT UNSIGNED NULL,
  temp_c DECIMAL(4,1) NULL,
  weight_kg DECIMAL(5,1) NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY visits_patient_visited (patient_id, visited_at),
  CONSTRAINT visits_patient_fk FOREIGN KEY (patient_id) REFERENCES patients (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS medicines (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(160) NOT NULL,
  generic_name VARCHAR(160) NULL,
  form ENUM('tablet','syrup','injection','cream','other') NOT NULL DEFAULT 'tablet',
  strength VARCHAR(80) NULL,
  unit VARCHAR(40) NOT NULL DEFAULT 'tablets',
  quantity_on_hand DECIMAL(12,2) NOT NULL DEFAULT 0,
  reorder_level DECIMAL(12,2) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY medicines_name (name),
  KEY medicines_generic (generic_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  medicine_id INT UNSIGNED NOT NULL,
  type ENUM('in','out','adjust') NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,
  reason VARCHAR(255) NULL,
  visit_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY stock_medicine_created (medicine_id, created_at),
  KEY stock_visit (visit_id),
  CONSTRAINT stock_medicine_fk FOREIGN KEY (medicine_id) REFERENCES medicines (id),
  CONSTRAINT stock_visit_fk FOREIGN KEY (visit_id) REFERENCES visits (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visit_medications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  visit_id INT UNSIGNED NOT NULL,
  medicine_id INT UNSIGNED NOT NULL,
  quantity DECIMAL(12,2) NOT NULL,
  dose_instructions VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY visit_meds_visit (visit_id),
  KEY visit_meds_medicine (medicine_id),
  CONSTRAINT visit_meds_visit_fk FOREIGN KEY (visit_id) REFERENCES visits (id),
  CONSTRAINT visit_meds_medicine_fk FOREIGN KEY (medicine_id) REFERENCES medicines (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

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
