-- ============================================================
--  School Clinic Records System  -  Database Schema + Seed Data
--  Database : clinic_db  (MySQL / MariaDB, InnoDB, utf8mb4)
--
--  HOW TO IMPORT
--   1. In phpMyAdmin create a database (locally: clinic_db,
--      on InfinityFree: the one from Control Panel > MySQL Databases).
--   2. Select that database, open the Import tab, choose this file.
--
--  Default login:  admin / admin123   (nurse account: nurse / nurse123)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS visit_medicines;
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS medicines;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- users : clinic staff who can log in to the system
-- ------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(100) NOT NULL,
    role          ENUM('admin','nurse') NOT NULL DEFAULT 'nurse',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- patients : students, faculty and staff with a clinic record
-- ------------------------------------------------------------
CREATE TABLE patients (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_number          VARCHAR(30)  NOT NULL UNIQUE COMMENT 'Student / employee ID',
    first_name         VARCHAR(60)  NOT NULL,
    last_name          VARCHAR(60)  NOT NULL,
    patient_type       ENUM('Student','Faculty','Staff') NOT NULL DEFAULT 'Student',
    course_grade       VARCHAR(60)  NULL COMMENT 'Course/year or department',
    section            VARCHAR(30)  NULL,
    sex                ENUM('Male','Female') NOT NULL,
    birthdate          DATE         NULL,
    contact_no         VARCHAR(20)  NULL,
    guardian_name      VARCHAR(100) NULL,
    guardian_contact   VARCHAR(20)  NULL,
    blood_type         ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown') NOT NULL DEFAULT 'Unknown',
    allergies          TEXT NULL,
    medical_conditions TEXT NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_name (last_name, first_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- medicines : clinic medicine inventory
-- ------------------------------------------------------------
CREATE TABLE medicines (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    dosage_form   VARCHAR(50)  NOT NULL COMMENT 'Tablet, Capsule, Syrup ...',
    unit          VARCHAR(20)  NOT NULL DEFAULT 'pcs',
    stock_qty     INT UNSIGNED NOT NULL DEFAULT 0,
    reorder_level INT UNSIGNED NOT NULL DEFAULT 10,
    expiry_date   DATE NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- visits : one clinic consultation of a patient
--   patient deleted -> visits deleted (CASCADE)
--   staff deleted   -> visit kept, attended_by set to NULL
-- ------------------------------------------------------------
CREATE TABLE visits (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id     INT UNSIGNED NOT NULL,
    visit_date     DATETIME NOT NULL,
    complaint      VARCHAR(255) NOT NULL,
    temperature    DECIMAL(4,1) NULL COMMENT 'Celsius',
    blood_pressure VARCHAR(10)  NULL COMMENT 'e.g. 120/80',
    pulse_rate     SMALLINT UNSIGNED NULL COMMENT 'beats per minute',
    diagnosis      VARCHAR(255) NULL,
    treatment      TEXT NULL,
    disposition    ENUM('Returned to class','Observation','Sent home','Referred to hospital')
                   NOT NULL DEFAULT 'Returned to class',
    attended_by    INT UNSIGNED NULL,
    notes          TEXT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_visit_date (visit_date),
    CONSTRAINT fk_visit_patient FOREIGN KEY (patient_id)
        REFERENCES patients(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_visit_user FOREIGN KEY (attended_by)
        REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- visit_medicines : medicines dispensed during a visit
--   (junction table: many visits <-> many medicines)
--   a medicine that was already dispensed cannot be deleted (RESTRICT)
-- ------------------------------------------------------------
CREATE TABLE visit_medicines (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_id    INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    quantity    INT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_vm_visit FOREIGN KEY (visit_id)
        REFERENCES visits(id) ON DELETE CASCADE,
    CONSTRAINT fk_vm_medicine FOREIGN KEY (medicine_id)
        REFERENCES medicines(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  SEED DATA (sample records for demo)
-- ============================================================

INSERT INTO users (username, password_hash, full_name, role) VALUES
('admin', '$2y$10$xUzDDRBjZAPXHHcsOB4XIOy8cBYuyYKFOaDCo0ou0PDk4JwXYDQnW', 'Clinic Administrator', 'admin'),
('nurse', '$2y$10$zAOuQ0dxaKMCNeJcOUHgze.Ujc6rphwqNuXLw4qQe8T67fEqrO60G', 'Maria Santos, RN',     'nurse');

INSERT INTO patients (id_number, first_name, last_name, patient_type, course_grade, section, sex, birthdate, contact_no, guardian_name, guardian_contact, blood_type, allergies, medical_conditions) VALUES
('2023-00145', 'Juan',     'Dela Cruz', 'Student', 'BSIT 3',             'A', 'Male',   '2004-03-12', '09171234567', 'Rosa Dela Cruz',  '09181112233', 'O+',  NULL,                    NULL),
('2024-00211', 'Angela',   'Reyes',     'Student', 'BSED 2',             'B', 'Female', '2005-07-28', '09223456789', 'Mario Reyes',     '09193334455', 'A+',  'Penicillin',            'Asthma'),
('2022-00087', 'Mark',     'Villanueva','Student', 'BSA 4',              'C', 'Male',   '2003-11-02', '09351239876', 'Liza Villanueva', '09175556677', 'B+',  NULL,                    NULL),
('2025-00302', 'Kristine', 'Gonzales',  'Student', 'BSN 1',              'A', 'Female', '2006-01-19', '09451237777', 'Ramon Gonzales',  '09087778899', 'AB+', 'Seafood, Shellfish',    NULL),
('2023-00190', 'Paolo',    'Mendoza',   'Student', 'BSCS 3',             'B', 'Male',   '2004-09-05', '09564561234', 'Celia Mendoza',   '09279990011', 'O-',  NULL,                    'Migraine'),
('FAC-0012',   'Elena',    'Bautista',  'Faculty', 'College of Education', NULL, 'Female', '1985-05-14', '09173219876', NULL,          NULL,          'A-',  NULL,                    'Hypertension'),
('STF-0031',   'Roberto',  'Garcia',    'Staff',   'Maintenance Office', NULL, 'Male',   '1979-12-30', '09981234567', NULL,              NULL,          'O+',  'Aspirin',               'Type 2 Diabetes'),
('2024-00255', 'Sofia',    'Lim',       'Student', 'BSBA 2',             'A', 'Female', '2005-04-22', '09061237654', 'Henry Lim',       '09170001122', 'B-',  NULL,                    NULL);

INSERT INTO medicines (name, dosage_form, unit, stock_qty, reorder_level, expiry_date) VALUES
('Paracetamol 500mg',          'Tablet',   'pcs',    120, 30, DATE_ADD(CURDATE(), INTERVAL 14 MONTH)),
('Ibuprofen 200mg',            'Tablet',   'pcs',     45, 20, DATE_ADD(CURDATE(), INTERVAL 10 MONTH)),
('Mefenamic Acid 500mg',       'Capsule',  'pcs',     12, 20, DATE_ADD(CURDATE(), INTERVAL 8 MONTH)),
('Cetirizine 10mg',            'Tablet',   'pcs',     60, 15, DATE_ADD(CURDATE(), INTERVAL 20 DAY)),
('Loperamide 2mg',             'Capsule',  'pcs',     25, 10, DATE_ADD(CURDATE(), INTERVAL 12 MONTH)),
('Oral Rehydration Salts',     'Sachet',   'sachet',   6, 10, DATE_ADD(CURDATE(), INTERVAL 18 MONTH)),
('Povidone-Iodine 10%',        'Solution', 'bottle',   8,  3, DATE_ADD(CURDATE(), INTERVAL 24 MONTH));

INSERT INTO visits (patient_id, visit_date, complaint, temperature, blood_pressure, pulse_rate, diagnosis, treatment, disposition, attended_by, notes) VALUES
(1, TIMESTAMP(CURDATE(), '08:15:00'),  'Headache and mild fever',   37.8, '110/70', 88,  'Viral fever',            'Paracetamol given, advised rest and fluids', 'Sent home',            2, 'Guardian informed via phone.'),
(2, TIMESTAMP(CURDATE(), '09:40:00'),  'Difficulty of breathing',   36.9, '120/80', 102, 'Mild asthma attack',     'Used own inhaler, observed for 30 minutes',  'Observation',          2, 'Has penicillin allergy - noted.'),
(4, TIMESTAMP(CURDATE(), '07:50:00'),  'Itchy skin rashes',         36.6, '110/80', 76,  'Allergic reaction',      'Cetirizine given',                            'Returned to class',    1, 'Ate shrimp during lunch.'),
(3, DATE_SUB(NOW(), INTERVAL 1 DAY),   'Stomach ache',              36.7, '120/80', 80,  'Hyperacidity',           'Advised light meal',                          'Returned to class',    2, NULL),
(5, DATE_SUB(NOW(), INTERVAL 2 DAY),   'Severe headache',           36.8, '130/85', 84,  'Migraine',               'Ibuprofen given, rested in clinic',           'Returned to class',    2, NULL),
(8, DATE_SUB(NOW(), INTERVAL 3 DAY),   'Dysmenorrhea',              36.5, '100/70', 78,  'Dysmenorrhea',           'Mefenamic acid given, hot compress',          'Returned to class',    1, NULL),
(7, DATE_SUB(NOW(), INTERVAL 5 DAY),   'Dizziness',                 36.6, '150/95', 90,  'Elevated blood pressure','Rested, BP rechecked after 20 minutes',       'Referred to hospital', 1, 'Advised to consult physician.'),
(1, DATE_SUB(NOW(), INTERVAL 9 DAY),   'Wound on knee',             36.5, '110/70', 75,  'Abrasion',               'Wound cleaned with povidone-iodine, dressed', 'Returned to class',    2, 'Fell during PE class.'),
(6, DATE_SUB(NOW(), INTERVAL 12 DAY),  'Headache',                  36.7, '140/90', 82,  'Tension headache',       'Paracetamol given',                           'Returned to class',    2, NULL),
(3, DATE_SUB(NOW(), INTERVAL 20 DAY),  'Loose bowel movement',      37.1, '110/70', 86,  'Acute gastroenteritis',  'Loperamide and ORS given',                    'Sent home',            1, NULL);

INSERT INTO visit_medicines (visit_id, medicine_id, quantity) VALUES
(1, 1, 2),
(3, 4, 1),
(5, 2, 1),
(6, 3, 1),
(8, 7, 1),
(9, 1, 1),
(10, 5, 2),
(10, 6, 2);
