START TRANSACTION;

-- ---------------------------------------------------------------------
-- Table `tlayout_template`
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
-- Table `tlayout_template_data`
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tlayout_template_data` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_layout_template` INTEGER UNSIGNED NOT NULL,
	`pos_x` INTEGER UNSIGNED NOT NULL default 0,
	`pos_y` INTEGER UNSIGNED NOT NULL default 0,
	`height` INTEGER UNSIGNED NOT NULL default 0,
	`width` INTEGER UNSIGNED NOT NULL default 0,
	`label` TEXT,
	`image` varchar(200) DEFAULT "",
	`type` tinyint(1) UNSIGNED NOT NULL default 0,
	`period` INTEGER UNSIGNED NOT NULL default 3600,
	`module_name` text NOT NULL,
	`agent_name` varchar(600) BINARY NOT NULL default '',
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
	PRIMARY KEY(`id`),
	FOREIGN KEY (`id_layout_template`) REFERENCES tlayout_template(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Rename column is_secondary to no_hierarchy in `tusuario _perfil`
-- ---------------------------------------------------------------------
ALTER TABLE `tusuario_perfil` ADD COLUMN `no_hierarchy` tinyint(1) NOT NULL DEFAULT 0;
UPDATE `tusuario_perfil` SET `no_hierarchy` = `is_secondary`;
ALTER TABLE `tusuario_perfil` DROP COLUMN `is_secondary`;

UPDATE `talert_commands` SET name='Monitoring&#x20;Event' WHERE name='Pandora&#x20;FMS&#x20;Event';

-- -----------------------------------------------------
-- Table `tgis_map_layer_groups`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tgis_map_layer_groups` (
	`layer_id` INT NOT NULL,
	`group_id` MEDIUMINT(4) UNSIGNED NOT NULL,
	`agent_id` INT(10) UNSIGNED NOT NULL COMMENT 'Used to link the position to the group',
	PRIMARY KEY (`layer_id`, `group_id`),
	FOREIGN KEY (`layer_id`) REFERENCES `tgis_map_layer` (`id_tmap_layer`) ON DELETE CASCADE,
	FOREIGN KEY (`group_id`) REFERENCES `tgrupo` (`id_grupo`) ON DELETE CASCADE,
	FOREIGN KEY (`agent_id`) REFERENCES `tagente` (`id_agente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

COMMIT;