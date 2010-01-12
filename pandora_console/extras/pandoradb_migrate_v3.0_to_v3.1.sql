ALTER TABLE tagente ADD `timezone_offset` TINYINT(2) NOT NULL default '0';
ALTER TABLE tagente ADD `icon_path` VARCHAR(127) NOT NULL default 'images/status_sets/default/agent_no_data_ball.png';
ALTER TABLE tagente ADD `update_gis_data` TINYINT(1) NOT NULL default '1';

-- GIS extension Tables

CREATE  TABLE IF NOT EXISTS `tgis_data` (
  `id_tgis_data` INT NOT NULL AUTO_INCREMENT ,
  `longitude` DOUBLE NOT NULL ,
  `latitude` DOUBLE NOT NULL ,
  `altitude` DOUBLE NULL ,
  `start_timestamp` TIMESTAMP NOT NULL ,
  `end_timestamp` TIMESTAMP NULL ,
  `description` TEXT NULL ,
  `last_known_postiion` TINYINT(1) NULL ,
  `tagente_id_agente` INT(10) UNSIGNED NOT NULL ,
  PRIMARY KEY (`id_tgis_data`) ,
  INDEX `last_known_position_index` (`last_known_postiion` ASC) ,
  INDEX `start_timestamp_index` (`start_timestamp` ASC) ,
  INDEX `end_timestamp_index` (`end_timestamp` ASC) )
ENGINE = InnoDB
COMMENT = 'Table to store GIS information of the agents';

CREATE  TABLE IF NOT EXISTS `tgis_map_connection` (
  `id_tmap_connection` INT NOT NULL ,
  `conection_data` TEXT NULL ,
  PRIMARY KEY (`id_tmap_connection`) )
ENGINE = InnoDB
COMMENT = 'Table to store the map connection information';

CREATE  TABLE IF NOT EXISTS `tgis_map` (
  `id_tgis_map` INT NOT NULL ,
  `map_name` VARCHAR(63) NOT NULL ,
  `initial_longitude` DOUBLE NULL ,
  `initial_latitude` DOUBLE NULL ,
  `initial_altitude` DOUBLE NULL ,
  `map_background` VARCHAR(127) NULL ,
  `tmap_connection_id_tmap_connection` INT NOT NULL ,
  `default_longitude` DOUBLE NULL ,
  `default_latitude` DOUBLE NULL ,
  `default_altitude` DOUBLE NULL ,
  PRIMARY KEY (`id_tgis_map`) ,
  INDEX `fk_tgis_map_tmap_connection1` (`tmap_connection_id_tmap_connection` ASC) ,
  CONSTRAINT `fk_tgis_map_tmap_connection1`
    FOREIGN KEY (`tmap_connection_id_tmap_connection` )
    REFERENCES `tgis_map_connection` (`id_tmap_connection` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Table containing information about a gis map';

CREATE  TABLE IF NOT EXISTS `tgis_map_layer` (
  `id_tmap_layer` INT NOT NULL ,
  `layer_name` VARCHAR(45) NOT NULL ,
  `view_layer` TINYINT(1) NOT NULL DEFAULT TRUE ,
  `tgis_map_id_tgis_map` INT NOT NULL ,
  `tgrupo_id_grupo` MEDIUMINT(4) UNSIGNED NOT NULL ,
  PRIMARY KEY (`id_tmap_layer`, `tgis_map_id_tgis_map`) ,
  INDEX `fk_tmap_layer_tgis_map1` (`tgis_map_id_tgis_map` ASC) ,
  CONSTRAINT `fk_tmap_layer_tgis_map1`
    FOREIGN KEY (`tgis_map_id_tgis_map` )
    REFERENCES `tgis_map` (`id_tgis_map` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION )
ENGINE = InnoDB
COMMENT = 'Table containing information about the map layers';

