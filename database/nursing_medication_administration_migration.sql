-- Nursing medication administration record (MAR) foundation.
-- Apply after the existing pharmacy and inpatient/nursing migrations.
-- This table records administration events against an existing prescription and
-- dispensing queue item; it does not create prescriptions or deduct stock.
-- Multiple administrations may occur from one dispense, so uniqueness is not
-- constrained to the dispense ID until dose-schedule semantics are implemented.
CREATE TABLE IF NOT EXISTS nursing_medication_administrations (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  admission_id INT NOT NULL,
  patient_id INT NOT NULL,
  prescription_id INT NOT NULL,
  pharmacy_queue_id INT NOT NULL,
  medicine_id INT NOT NULL,
  scheduled_at DATETIME NULL,
  administered_at DATETIME NULL,
  status ENUM('Given','Omitted','Refused','Held','Not Available') NOT NULL,
  dose_given VARCHAR(120) NULL,
  route VARCHAR(80) NULL,
  administration_notes TEXT NULL,
  recorded_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mar_admission_time (admission_id, administered_at),
  INDEX idx_mar_patient_time (patient_id, administered_at),
  INDEX idx_mar_prescription (prescription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
