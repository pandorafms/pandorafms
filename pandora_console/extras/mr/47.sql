START TRANSACTION;

ALTER TABLE `tusuario` ADD COLUMN `integria_user_level_user` VARCHAR(60);
ALTER TABLE `tusuario` ADD COLUMN `integria_user_level_pass` VARCHAR(45);
ALTER TABLE `tperfil` DROP COLUMN `incident_view`;
ALTER TABLE `tperfil` DROP COLUMN `incident_edit`;
ALTER TABLE `tperfil` DROP COLUMN `incident_management`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field16` TEXT NOT NULL AFTER `al_field15`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field17` TEXT NOT NULL AFTER `al_field16`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field18` TEXT NOT NULL AFTER `al_field17`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field19` TEXT NOT NULL AFTER `al_field18`;
ALTER TABLE `talert_snmp_action` ADD COLUMN `al_field20` TEXT NOT NULL AFTER `al_field19`;

SET @st_oum755 = (SELECT IF(
    (SELECT COUNT(*) FROM tconfig WHERE token LIKE 'meta_custom_logo' AND value like 'logo_pandora_metaconsola.png') > 0,
    "UPDATE `tconfig` set value = 'pandoraFMS_metaconsole_full.svg' WHERE token LIKE 'meta_custom_logo'",
	"SELECT 1"
));

PREPARE pr_oum755 FROM @st_oum755;
EXECUTE pr_oum755;
DEALLOCATE PREPARE pr_oum755;

UPDATE `tconfig` set value = 'lato.ttf' WHERE token LIKE 'custom_report_front_font';
UPDATE `tconfig` set value = 'lato.ttf' WHERE token LIKE 'fontpath';

COMMIT;
