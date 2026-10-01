-- Additive rollout policy. Existing accounts, progress and payment data are untouched.
ALTER TABLE fcc_partner_rollout_control
 ADD COLUMN IF NOT EXISTS all_enabled TINYINT UNSIGNED NOT NULL DEFAULT 0;
