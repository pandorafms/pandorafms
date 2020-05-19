START TRANSACTION;

ALTER TABLE trecon_task add column `rcmd_enabled` TINYINT(1) UNSIGNED DEFAULT 0 AFTER `wmi_enabled`;

COMMIT;
