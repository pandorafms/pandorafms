ALTER TABLE tagente ADD `timezone_offset` TINYINT(2) NULL DEFAULT '0' COMMENT 'nuber of hours of diference with the server timezone' ;
ALTER TABLE tagente ADD `icon_path` VARCHAR(127) NULL DEFAULT NULL COMMENT 'path in the server to the image of the icon representing the agent' ;
ALTER TABLE tagente ADD `update_gis_data` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'set it to one to update the position data (altitude, longitude, latitude) when getting information from the agent or to 0 to keep the last value and don\'t update it' ;
ALTER TABLE tagente ADD `last_latitude` DOUBLE NULL COMMENT 'last latitude of the agent' ;
ALTER TABLE tagente ADD `last_longitude` DOUBLE NULL COMMENT 'last longitude of the agent' ;
ALTER TABLE tagente ADD `last_altitude` DOUBLE NULL COMMENT 'last altitude of the agent' ;

ALTER TABLE `tgraph_source` CHANGE `weight` `weight` float(5,3) UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `tserver_export` ADD `timezone_offset` TINYINT(2) NULL DEFAULT '0' COMMENT 'Nuber of hours of diference with the server timezone';

ALTER TABLE `tserver` ADD `lag_time` int(11) NOT NULL default 0;
ALTER TABLE `tserver` ADD `lag_modules` int(11) NOT NULL default 0;
ALTER TABLE `tserver` ADD `total_modules_running` int(11) NOT NULL default 0;
ALTER TABLE `tserver` ADD `my_modules` int(11) NOT NULL default 0;

ALTER TABLE `tagente_modulo` ADD `custom_string_1` text default '';
ALTER TABLE `tagente_modulo` ADD `custom_string_2` text default '';
ALTER TABLE `tagente_modulo` ADD `custom_string_3` text default '';
ALTER TABLE `tagente_modulo` ADD `custom_integer_1` int(10) default 0;
ALTER TABLE `tagente_modulo` ADD `custom_integer_2` int(10) default 0;

ALTER TABLE tagente_datos_string DROP id_tagente_datos_string;
CREATE INDEX idx_utimestamp USING BTREE ON tagente_datos_string(utimestamp);

ALTER TABLE tagente_datos DROP id_agente_datos;
CREATE INDEX idx_utimestamp USING BTREE ON tagente_datos(utimestamp);

CREATE INDEX idx_agente USING BTREE ON tagente_estado(id_agente);
CREATE INDEX idx_template_action USING BTREE ON talert_templates(id_alert_action);
CREATE INDEX idx_template_module USING BTREE ON talert_template_modules(id_agent_module);
CREATE INDEX idx_agentmodule USING BTREE ON tevento(id_agentmodule);

DROP INDEX `status_index_2` on tagente_estado;
CREATE INDEX idx_status USING BTREE ON tagente_estado (estado);

ALTER TABLE tagent_access DROP id_ac;
CREATE INDEX idx_utimestamp USING BTREE ON tagent_access(utimestamp);

ALTER TABLE tusuario ADD `timezone` varchar(50) default '';

-- GIS extension Tables and DATA

-- GIS is disabled by default
INSERT INTO tconfig (`token`, `value`) VALUES ('activate_gis', '0');

-- Realtime statistics on/off and interval
INSERT INTO tconfig (`token`, `value`) VALUES ('realtimestats', '1');
INSERT INTO tconfig (`token`, `value`) VALUES ('stats_interval', '300');


-- -----------------------------------------------------
-- Table `tgis_data`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_data` (
  `id_tgis_data` INT NOT NULL AUTO_INCREMENT COMMENT 'key of the table' ,
  `longitude` DOUBLE NOT NULL ,
  `latitude` DOUBLE NOT NULL ,
  `altitude` DOUBLE NULL ,
  `start_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'timestamp on wich the agente started to be in this position' ,
  `end_timestamp` TIMESTAMP NULL COMMENT 'timestamp on wich the agent was placed for last time on this position' ,
  `description` TEXT NULL COMMENT 'description of the region correoponding to this placemnt' ,
  `manual_placement` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 to show that the position cames from the agent, 1 to show that the position was established manualy' ,
  `number_of_packages` INT NOT NULL DEFAULT 1 COMMENT 'Number of data packages received with this position from the start_timestampa to the_end_timestamp' ,
  `tagente_id_agente` INT(10) NOT NULL COMMENT 'reference to the agent' ,
  PRIMARY KEY (`id_tgis_data`) ,
  INDEX `start_timestamp_index` (`start_timestamp` ASC) ,
  INDEX `end_timestamp_index` (`end_timestamp` ASC) )
ENGINE = InnoDB
COMMENT = 'Table to store GIS information of the agents';

-- -----------------------------------------------------
-- Table `tgis_map`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map` (
  `id_tgis_map` INT NOT NULL AUTO_INCREMENT COMMENT 'table identifier' ,
  `map_name` VARCHAR(63) NOT NULL COMMENT 'Name of the map' ,
  `initial_longitude` DOUBLE NULL COMMENT 'longitude of the center of the map when it\'s loaded' ,
  `initial_latitude` DOUBLE NULL COMMENT 'latitude of the center of the map when it\'s loaded' ,
  `initial_altitude` DOUBLE NULL COMMENT 'altitude of the center of the map when it\'s loaded' ,
  `zoom_level` TINYINT(2) NULL DEFAULT '1' COMMENT 'Zoom level to show when the map is loaded.' ,
  `map_background` VARCHAR(127) NULL COMMENT 'path on the server to the background image of the map' ,
  `default_longitude` DOUBLE NULL COMMENT 'default longitude for the agents placed on the map' ,
  `default_latitude` DOUBLE NULL COMMENT 'default latitude for the agents placed on the map' ,
  `default_altitude` DOUBLE NULL COMMENT 'default altitude for the agents placed on the map' ,
  `group_id` INT(10) NOT NULL DEFAULT 0 COMMENT 'Group that owns the map' ,
  `default_map` TINYINT(1) NULL DEFAULT 0 COMMENT '1 if this is the default map, 0 in other case',
  PRIMARY KEY (`id_tgis_map`),
  INDEX `map_name_index` (`map_name` ASC)
)
ENGINE = InnoDB
COMMENT = 'Table containing information about a gis map';

-- -----------------------------------------------------
-- Table `tgis_map_connection`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_connection` (
  `id_tmap_connection` INT NOT NULL AUTO_INCREMENT COMMENT 'table id' ,
  `conection_name` VARCHAR(45) NULL COMMENT 'Name of the connection (name of the base layer)' ,
  `connection_type` VARCHAR(45) NULL COMMENT 'Type of map server to connect' ,
  `conection_data` TEXT NULL COMMENT 'connection information (this can probably change to fit better the possible connection parameters)' ,
  `num_zoom_levels` TINYINT(2) NULL COMMENT 'Number of zoom levels available' ,
  `default_zoom_level` TINYINT(2) NOT NULL DEFAULT 16 COMMENT 'Default Zoom Level for the connection' ,
  `default_longitude` DOUBLE NULL COMMENT 'default longitude for the agents placed on the map' ,
  `default_latitude` DOUBLE NULL COMMENT 'default latitude for the agents placed on the map' ,
  `default_altitude` DOUBLE NULL COMMENT 'default altitude for the agents placed on the map' ,
  `initial_longitude` DOUBLE NULL COMMENT 'longitude of the center of the map when it\'s loaded' ,
  `initial_latitude` DOUBLE NULL COMMENT 'latitude of the center of the map when it\'s loaded' ,
  `initial_altitude` DOUBLE NULL COMMENT 'altitude of the center of the map when it\'s loaded' ,
  `group_id` INT(10) NOT NULL DEFAULT 0 COMMENT 'Group that owns the map',
  PRIMARY KEY (`id_tmap_connection`) )
ENGINE = InnoDB
COMMENT = 'Table to store the map connection information';

-- -----------------------------------------------------
-- Table `tgis_map_has_tgis_map_connection`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_has_tgis_map_connection` (
  `tgis_map_id_tgis_map` INT NOT NULL COMMENT 'reference to tgis_map' ,
  `tgis_map_connection_id_tmap_connection` INT NOT NULL COMMENT 'reference to tgis_map_connection' ,
  `modification_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last Modification Time of the Connection' ,
  `default_map_connection` TINYINT(1) NULL DEFAULT FALSE COMMENT 'Flag to mark the default map connection of a map' ,
  PRIMARY KEY (`tgis_map_id_tgis_map`, `tgis_map_connection_id_tmap_connection`) ,
  INDEX `fk_tgis_map_has_tgis_map_connection_tgis_map1` (`tgis_map_id_tgis_map` ASC) ,
  INDEX `fk_tgis_map_has_tgis_map_connection_tgis_map_connection1` (`tgis_map_connection_id_tmap_connection` ASC) ,
  CONSTRAINT `fk_tgis_map_has_tgis_map_connection_tgis_map1`
    FOREIGN KEY (`tgis_map_id_tgis_map` )
    REFERENCES `tgis_map` (`id_tgis_map` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tgis_map_has_tgis_map_connection_tgis_map_connection1`
    FOREIGN KEY (`tgis_map_connection_id_tmap_connection` )
    REFERENCES `tgis_map_connection` (`id_tmap_connection` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Table to asociate a connection to a gis map';

-- -----------------------------------------------------
-- Table `tgis_map_layer`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_layer` (
  `id_tmap_layer` INT NOT NULL AUTO_INCREMENT COMMENT 'table id' ,
  `layer_name` VARCHAR(45) NOT NULL COMMENT 'Name of the layer ' ,
  `view_layer` TINYINT(1) NOT NULL DEFAULT TRUE COMMENT 'True if the layer must be shown' ,
  `layer_stack_order` TINYINT(3) NULL DEFAULT 0 COMMENT 'Number of order of the layer in the layer stack, bigger means upper on the stack.\n' ,
  `tgis_map_id_tgis_map` INT NOT NULL COMMENT 'reference to the map containing the layer' ,
  `tgrupo_id_grupo` MEDIUMINT(4) UNSIGNED NOT NULL COMMENT 'reference to the group shown in the layer' ,
  PRIMARY KEY (`id_tmap_layer`) ,
  INDEX `fk_tmap_layer_tgis_map1` (`tgis_map_id_tgis_map` ASC) ,
  INDEX `fk_tmap_layer_tgrupo1` (`tgrupo_id_grupo` ASC) ,
  CONSTRAINT `fk_tmap_layer_tgis_map1`
    FOREIGN KEY (`tgis_map_id_tgis_map` )
    REFERENCES `tgis_map` (`id_tgis_map` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tmap_layer_tgrupo1`
    FOREIGN KEY (`tgrupo_id_grupo` )
    REFERENCES `tgrupo` (`id_grupo` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Table containing information about the map layers';

-- -----------------------------------------------------
-- Table `tgis_map_layer_has_tagente`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_layer_has_tagente` (
  `tgis_map_layer_id_tmap_layer` INT NOT NULL ,
  `tagente_id_agente` INT(10) UNSIGNED NOT NULL ,
  PRIMARY KEY (`tgis_map_layer_id_tmap_layer`, `tagente_id_agente`) ,
  INDEX `fk_tgis_map_layer_has_tagente_tgis_map_layer1` (`tgis_map_layer_id_tmap_layer` ASC) ,
  INDEX `fk_tgis_map_layer_has_tagente_tagente1` (`tagente_id_agente` ASC) ,
  CONSTRAINT `fk_tgis_map_layer_has_tagente_tgis_map_layer1`
    FOREIGN KEY (`tgis_map_layer_id_tmap_layer` )
    REFERENCES `tgis_map_layer` (`id_tmap_layer` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tgis_map_layer_has_tagente_tagente1`
    FOREIGN KEY (`tagente_id_agente` )
    REFERENCES `tagente` (`id_agente` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Table to define wich agents are shown in a layer';

-- -----------------------------------------------------
-- Table `tgroup_stat`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tgroup_stat` (
  `id_group` int(10) unsigned NOT NULL default '0',
  `modules` int(10) unsigned NOT NULL default '0',
  `normal` int(10) unsigned NOT NULL default '0',
  `critical` int(10) unsigned NOT NULL default '0',
  `warning` int(10) unsigned NOT NULL default '0',
  `unknown` int(10) unsigned NOT NULL default '0',
  `non-init` int(10) unsigned NOT NULL default '0',
  `alerts` int(10) unsigned NOT NULL default '0',
  `alerts_fired` int(10) unsigned NOT NULL default '0',
  `agents` int(10) unsigned NOT NULL default '0',
  `agents_uknown` int(10) unsigned NOT NULL default '0',
  `utimestamp` int(20) unsigned NOT NULL default 0,
  PRIMARY KEY  (`id_group`)
) ENGINE=InnoDB 
COMMENT = 'Table to store global system stats per group';
DEFAULT CHARSET=utf8

-- -----------------------------------------------------
-- Table `tagente_datos_log4x`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `tagente_datos_log4x` (
  `id_tagente_datos_log4x` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(10) unsigned NOT NULL default '0',

  `severity` text NOT NULL,
  `message` text NOT NULL,
  `stacktrace` text NOT NULL,

  `utimestamp` int(20) unsigned NOT NULL default 0,
  PRIMARY KEY  (`id_tagente_datos_log4x`),
  KEY `data_log4x_index_1` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


