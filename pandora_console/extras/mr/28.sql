
 START TRANSACTION;

ALTER TABLE `talert_templates` MODIFY COLUMN `type` ENUM('regex','max_min','max','min','equal','not_equal','warning','critical','onchange','unknown','always','not_normal');

DELETE FROM `tevent_response` WHERE `name` LIKE 'Create&#x20;Integria&#x20;IMS&#x20;incident&#x20;from&#x20;event';


INSERT INTO `tnotification_source_user` (`id_source`, `id_user`, `enabled`, `also_mail`) VALUES ((SELECT `id` FROM `tnotification_source` WHERE `description`="Official&#x20;communication"), "admin", 1, 0);
UPDATE `tnotification_source` SET `enabled`=1 WHERE `description` = 'System&#x20;status' OR `description` = 'Official&#x20;communication';
UPDATE `tnotification_source` SET `icon`="icono_logo_pandora.png" WHERE `description` = 'Official&#x20;communication';

COMMIT;
