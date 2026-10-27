-- Phased surgical safety checklist (Sign In / Time Out / Sign Out).
-- Apply once to a backed-up test database after database/theatre_surgery_migration.sql.
-- Each phase is an immutable completed snapshot; incomplete drafts may be updated.
CREATE TABLE IF NOT EXISTS theatre_safety_checklist_phases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    phase ENUM('Sign In','Time Out','Sign Out') NOT NULL,
    items_json JSON NOT NULL,
    notes VARCHAR(2000) NULL,
    recorded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    UNIQUE KEY uq_theatre_case_phase (case_id, phase),
    KEY idx_theatre_safety_recorded_by (recorded_by),
    CONSTRAINT fk_theatre_safety_case FOREIGN KEY (case_id) REFERENCES theatre_cases(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
