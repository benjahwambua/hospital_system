CREATE TABLE IF NOT EXISTS admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    ward_name VARCHAR(100) NOT NULL,
    bed_number INT NOT NULL,
    admission_date DATETIME NOT NULL,
    reason_for_admission TEXT,
    attending_doctor VARCHAR(100),
    status ENUM('Admitted', 'Discharged', 'Transferred') DEFAULT 'Admitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL
);




-- First, let's check if the table exists and add the missing column
ALTER TABLE admissions 
ADD COLUMN IF NOT EXISTS ward_name VARCHAR(100) NOT NULL AFTER patient_id,
ADD COLUMN IF NOT EXISTS bed_number INT NOT NULL AFTER ward_name;

-- Ensure the status column exists for the logic to work
ALTER TABLE admissions 
ADD COLUMN IF NOT EXISTS status ENUM('Admitted', 'Discharged', 'Transferred') DEFAULT 'Admitted';