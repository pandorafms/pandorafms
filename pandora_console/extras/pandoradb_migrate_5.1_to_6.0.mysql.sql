-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------

ALTER TABLE tlayout DROP COLUMN fullscreen;

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------

ALTER TABLE tlayout_data DROP COLUMN no_link_color;
ALTER TABLE tlayout_data DROP COLUMN label_color;
ALTER TABLE tlayout_data ADD COLUMN `border_width` INTEGER UNSIGNED NOT NULL default 0;
ALTER TABLE tlayout_data ADD COLUMN `border_color` varchar(200) DEFAULT "";
ALTER TABLE tlayout_data ADD COLUMN `fill_color` varchar(200) DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `tconfig_os`
-- ---------------------------------------------------------------------

INSERT INTO `tconfig_os` (`name`, `description`, `icon_name`) VALUES ('Mainframe', 'Mainframe agent', 'so_mainframe.png');

-- ---------------------------------------------------------------------
-- Table `ttag_module`
-- ---------------------------------------------------------------------
ALTER TABLE ttag_module ADD COLUMN `id_policy_module` int(10) NOT NULL DEFAULT 0;

UPDATE ttag_module AS t1
SET t1.id_policy_module = (
	SELECT t2.id_policy_module
	FROM tagente_modulo AS t2
	WHERE t1.id_agente_modulo = t2.id_agente_modulo);

/* 2014/12/10 */
-- ----------------------------------------------------------------------
-- Table `tuser_double_auth`
-- ----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tuser_double_auth` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_user` varchar(60) NOT NULL,
	`secret` varchar(20) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`id_user`),
	FOREIGN KEY (`id_user`) REFERENCES tusuario(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------
-- Table `ttipo_modulo`
-- ----------------------------------------------------------------------
INSERT INTO `ttipo_modulo` VALUES (5,'generic_data_inc_abs',0,'Generic numeric incremental (absolute)','mod_data_inc_abs.png');

-- ---------------------------------------------------------------------
-- Table `tusuario`
-- ---------------------------------------------------------------------
ALTER TABLE `tusuario` ADD COLUMN `strict_acl` tinyint(1) DEFAULT 0;

-- ---------------------------------------------------------------------
-- Table `talert_commands`
-- ---------------------------------------------------------------------
UPDATE `talert_commands` SET `fields_descriptions` = '[\"Destination&#x20;address\",\"Subject\",\"Text\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]', `fields_values` = '[\"\",\"\",\"_html_editor_\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]' WHERE `id` = 1 AND `name` = 'eMail';

-- ---------------------------------------------------------------------
-- Table `tconfig`
-- ---------------------------------------------------------------------
INSERT INTO `tconfig` (`token`, `value`) VALUES  ('post_process_custom_values', '{"0.00000038580247":"Seconds&#x20;to&#x20;months","0.00000165343915":"Seconds&#x20;to&#x20;weeks","0.00001157407407":"Seconds&#x20;to&#x20;days","0.01666666666667":"Seconds&#x20;to&#x20;minutes","0.00000000093132":"Bytes&#x20;to&#x20;Gigabytes","0.00000095367432":"Bytes&#x20;to&#x20;Megabytes","0.0009765625":"Bytes&#x20;to&#x20;Kilobytes","0.00000001653439":"Timeticks&#x20;to&#x20;weeks","0.00000011574074":"Timeticks&#x20;to&#x20;days"}')