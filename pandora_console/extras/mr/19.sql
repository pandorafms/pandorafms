START TRANSACTION;

-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlayout_template` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` varchar(50)  NOT NULL,
	`id_group` INTEGER UNSIGNED NOT NULL,
	`background` varchar(200)  NOT NULL,
	`height` INTEGER UNSIGNED NOT NULL default 0,
	`width` INTEGER UNSIGNED NOT NULL default 0,
	`background_color` varchar(50) NOT NULL default '#FFF',
	`is_favourite` INTEGER UNSIGNED NOT NULL default 0,
	PRIMARY KEY(`id`)
)  ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlayout_template_data` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_layout_template` INTEGER UNSIGNED NOT NULL default 0,
	`pos_x` INTEGER UNSIGNED NOT NULL default 0,
	`pos_y` INTEGER UNSIGNED NOT NULL default 0,
	`height` INTEGER UNSIGNED NOT NULL default 0,
	`width` INTEGER UNSIGNED NOT NULL default 0,
	`label` TEXT,
	`image` varchar(200) DEFAULT "",
	`type` tinyint(1) UNSIGNED NOT NULL default 0,
	`period` INTEGER UNSIGNED NOT NULL default 3600,
	`module_name` mediumint(8) unsigned NOT NULL default '0',
	`agent_name` int(10) unsigned NOT NULL default 0,
	`id_layout_linked` INTEGER unsigned NOT NULL default '0',
	`parent_item` INTEGER UNSIGNED NOT NULL default 0,
	`enable_link` tinyint(1) UNSIGNED NOT NULL default 1,
	`id_metaconsole` int(10) NOT NULL default 0,
	`id_group` INTEGER UNSIGNED NOT NULL default 0,
	`id_custom_graph` INTEGER UNSIGNED NOT NULL default 0,
	`border_width` INTEGER UNSIGNED NOT NULL default 0,
	`type_graph` varchar(50) NOT NULL default 'area',
	`label_position` varchar(50) NOT NULL default 'down',
	`border_color` varchar(200) DEFAULT "",
	`fill_color` varchar(200) DEFAULT "",
	`show_statistics` tinyint(2) NOT NULL default '0',
	`id_layout_linked_weight` int(10) NOT NULL default '0',
	`element_group` int(10) NOT NULL default '0',
	`show_on_top` tinyint(1) NOT NULL default '0',
	`clock_animation` varchar(60) NOT NULL default "analogic_1",
	`time_format` varchar(60) NOT NULL default "time",
	`timezone` varchar(60) NOT NULL default "Europe/Madrid",
	PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

COMMIT;