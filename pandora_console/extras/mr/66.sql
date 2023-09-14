START TRANSACTION;

ALTER TABLE `ttrap` ADD COLUMN `utimestamp` INT UNSIGNED NOT NULL DEFAULT 0;

UPDATE ttrap SET utimestamp=UNIX_TIMESTAMP(timestamp);

CREATE TABLE IF NOT EXISTS `tgraph_analytics_filter` (
`id` INT NOT NULL auto_increment,
`filter_name` VARCHAR(45) NULL,
`user_id` VARCHAR(255) NULL,
`graph_modules` TEXT NULL,
`interval` INT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

CREATE TABLE IF NOT EXISTS `tconfig_os_version` (
  `id_os_version` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product` TEXT,
  `version` TEXT,
  `end_of_support` VARCHAR(10) DEFAULT NULL,
  PRIMARY KEY  (`id_os_version`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

UPDATE `twelcome_tip`
	SET title = 'Scheduled&#x20;downtimes',
         url = 'https://pandorafms.com/manual/en/documentation/04_using/11_managing_and_administration#scheduled_downtimes'
	WHERE title = 'planned&#x20;stops';

UPDATE tagente_modulo SET `tcp_send` = '2c' WHERE `tcp_send` = '2';
UPDATE tpolicy_modules SET `tcp_send` = '2c' WHERE `tcp_send` = '2';
UPDATE tnetwork_component SET `tcp_send` = '2c' WHERE `tcp_send` = '2';

ALTER TABLE `tsesion_filter_log_viewer`
CHANGE COLUMN `date_range` `custom_date` INT NULL DEFAULT NULL ,
CHANGE COLUMN `start_date_defined` `date` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `start_date_time` `date_text` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `start_date_date` `date_units` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `start_date_date_range` `date_init` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `start_date_time_range` `time_init` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `end_date_date_range` `date_end` VARCHAR(45) NULL DEFAULT NULL ,
CHANGE COLUMN `end_date_time_range` `time_end` VARCHAR(45) NULL DEFAULT NULL ;

ALTER TABLE `tsesion_filter`
CHANGE COLUMN `period` `date_text` VARCHAR(45) NULL DEFAULT NULL AFTER `user`;

ALTER TABLE `tsesion_filter`
ADD COLUMN `custom_date` INT NULL AFTER `user`,
ADD COLUMN `date` VARCHAR(45) NULL AFTER `custom_date`,
ADD COLUMN `date_units` VARCHAR(45) NULL AFTER `date_text`,
ADD COLUMN `date_init` VARCHAR(45) NULL AFTER `date_units`,
ADD COLUMN `time_init` VARCHAR(45) NULL AFTER `date_init`,
ADD COLUMN `date_end` VARCHAR(45) NULL AFTER `time_init`,
ADD COLUMN `time_end` VARCHAR(45) NULL AFTER `date_end`;

INSERT INTO `tconfig_os_version` (`id_os_version`, `product`, `version`, `end_of_support`) VALUES (1,'Windows.*','7.*','2020/01/14');
INSERT INTO `tconfig_os_version` (`id_os_version`, `product`, `version`, `end_of_support`) VALUES (2,'Cisco.*','IOS 3.4.3','2017/05/12');
INSERT INTO `tconfig_os_version` (`id_os_version`, `product`, `version`, `end_of_support`) VALUES (3,'Linux.*','Centos 7.*','2022/01/01');

COMMIT;
