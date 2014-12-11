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