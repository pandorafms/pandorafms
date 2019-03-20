START TRANSACTION;

ALTER TABLE `treport_content` ADD COLUMN `show_extended_events` tinyint(1) default '0';

-- ----------------------------------------------------------------------
-- Add column in table `tagent_custom_fields`
-- ----------------------------------------------------------------------
ALTER TABLE tagent_custom_fields ADD COLUMN `combo_values` VARCHAR(255) DEFAULT '';

COMMIT;
