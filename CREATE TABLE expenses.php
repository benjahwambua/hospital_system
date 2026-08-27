CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(10, 2) NOT NULL,
    category VARCHAR(50),
    description TEXT,
    date_incurred DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS encounters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    presenting_complaint TEXT,
    hpc TEXT,
    medical_history TEXT,
    surgical_history TEXT,
    family_history TEXT,
    drug_history TEXT,
    allergies TEXT,
    social_history TEXT,
    review_systems TEXT,
    physical_exam TEXT,
    diagnosis TEXT,
    management_plan TEXT,
    doctor_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);





ALTER TABLE patients ADD COLUMN diagnosis TEXT;