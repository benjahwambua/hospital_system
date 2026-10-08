-- HMS Explicit User Permissions
-- Run after database/access_control_migration.sql.
-- Existing non-super users receive explicit assignments matching their
-- current legacy role access. Unknown roles receive no implicit access.
-- Super users retain unrestricted access.

INSERT INTO user_module_access
    (user_id, module_id, can_view, can_create, can_edit, can_delete, can_approve)
SELECT
    u.id, am.id, 1, 1, 1, 1,
    CASE WHEN am.module_key = 'staff_leave' THEN 0 ELSE 1 END
FROM users u
JOIN access_modules am ON am.active = 1
WHERE COALESCE(u.is_super, 0) = 0
  AND (
       (am.module_key = 'front_desk' AND LOWER(COALESCE(u.role,'')) IN ('admin','receptionist','reception'))
    OR (am.module_key = 'clinical' AND LOWER(COALESCE(u.role,'')) IN ('admin','doctor','nurse','receptionist','reception'))
    OR (am.module_key = 'laboratory' AND LOWER(COALESCE(u.role,'')) IN ('admin','lab','lab_tech'))
    OR (am.module_key = 'radiology' AND LOWER(COALESCE(u.role,'')) IN ('admin','doctor','nurse','radiologist'))
    OR (am.module_key = 'pharmacy' AND LOWER(COALESCE(u.role,'')) IN ('admin','pharmacist'))
    OR (am.module_key = 'maternity' AND LOWER(COALESCE(u.role,'')) IN ('admin','doctor','nurse'))
    OR (am.module_key = 'finance' AND LOWER(COALESCE(u.role,'')) IN ('admin','cashier','accountant'))
    OR (am.module_key = 'procurement' AND LOWER(COALESCE(u.role,'')) IN ('admin','procurement','storekeeper','stores'))
    OR (am.module_key = 'finance_admin' AND LOWER(COALESCE(u.role,'')) IN ('admin','accountant'))
    OR (am.module_key = 'staff_leave' AND LOWER(COALESCE(u.role,'')) IN ('admin','doctor','nurse','receptionist','reception','lab','lab_tech','radiologist','pharmacist','cashier','accountant','procurement','storekeeper','stores'))
  )
ON DUPLICATE KEY UPDATE
    can_view=VALUES(can_view),
    can_create=VALUES(can_create),
    can_edit=VALUES(can_edit),
    can_delete=VALUES(can_delete),
    can_approve=VALUES(can_approve);

-- Administration remains super-user only.
