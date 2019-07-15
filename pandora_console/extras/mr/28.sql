START TRANSACTION;

ALTER TABLE `treport_content` ADD COLUMN `current_month` TINYINT(1) DEFAULT '1';

ALTER TABLE `treport_content_template` ADD COLUMN `current_month` TINYINT(1) DEFAULT '1';

ALTER TABLE `talert_commands` ADD COLUMN `fields_hidden` text;

ALTER TABLE `talert_templates` MODIFY COLUMN `type` ENUM('regex','max_min','max','min','equal','not_equal','warning','critical','onchange','unknown','always','not_normal');

DELETE FROM `tevent_response` WHERE `name` LIKE 'Create&#x20;Integria&#x20;IMS&#x20;incident&#x20;from&#x20;event';
INSERT INTO `tnews` (`id_news`, `author`, `subject`, `text`, `timestamp`) VALUES (NULL,'admin','Welcome&#x20;to&#x20;Pandora&#x20;FMS&#x20;Console', '&amp;lt;p&#x20;style=&quot;text-align:&#x20;center;&#x20;font-size:&#x20;13px;&quot;&amp;gt;Hello,&#x20;congratulations,&#x20;if&#x20;you&apos;ve&#x20;arrived&#x20;here&#x20;you&#x20;already&#x20;have&#x20;an&#x20;operational&#x20;monitoring&#x20;console.&#x20;Remember&#x20;that&#x20;our&#x20;forums&#x20;and&#x20;online&#x20;documentation&#x20;are&#x20;available&#x20;24x7&#x20;to&#x20;get&#x20;you&#x20;out&#x20;of&#x20;any&#x20;trouble.&#x20;You&#x20;can&#x20;replace&#x20;this&#x20;message&#x20;with&#x20;a&#x20;personalized&#x20;one&#x20;at&#x20;Admin&#x20;tools&#x20;-&amp;amp;gt;&#x20;Site&#x20;news.&amp;lt;/p&amp;gt;&#x20;',NOW());


INSERT INTO `tnotification_source_user` (`id_source`, `id_user`, `enabled`, `also_mail`) VALUES ((SELECT `id` FROM `tnotification_source` WHERE `description`="Official&#x20;communication"), "admin", 1, 0);
UPDATE `tnotification_source` SET `enabled`=1 WHERE `description` = 'System&#x20;status' OR `description` = 'Official&#x20;communication';
UPDATE `tnotification_source` SET `icon`="icono_logo_pandora.png" WHERE `description` = 'Official&#x20;communication';

-- ---------------------------------------------------------------------
-- Table `tvisual_console_items_cache`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tvisual_console_elements_cache` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `vc_id` INTEGER UNSIGNED NOT NULL,
    `vc_item_id` INTEGER UNSIGNED NOT NULL,
    `user_id` VARCHAR(60) DEFAULT NULL,
    `data` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expiration` INTEGER UNSIGNED NOT NULL COMMENT 'Seconds to expire',
    PRIMARY KEY(`id`),
    FOREIGN KEY(`vc_id`) REFERENCES `tlayout`(`id`)
    ON DELETE CASCADE,
    FOREIGN KEY(`vc_item_id`) REFERENCES `tlayout_data`(`id`)
    ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `tusuario`(`id_user`)
    ON DELETE CASCADE ON UPDATE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `tlayout_data` ADD COLUMN `cache_expiration` INTEGER UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_level_user` VARCHAR(60);
ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_level_pass` VARCHAR(45);
ALTER TABLE `tusuario` ADD COLUMN `ehorus_user_level_enabled` TINYINT(1) DEFAULT '1';

COMMIT;
