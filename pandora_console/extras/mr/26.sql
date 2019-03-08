START TRANSACTION;

-- ----------------------------------------------------------------------
-- Add column in table `tagent_custom_fields`
-- ----------------------------------------------------------------------
ALTER TABLE tagent_custom_fields ADD COLUMN `combo_values` VARCHAR(255) DEFAULT '';

COMMIT;
