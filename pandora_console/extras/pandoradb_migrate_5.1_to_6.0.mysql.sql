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

