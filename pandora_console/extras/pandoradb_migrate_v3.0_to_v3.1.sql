ALTER TABLE tagente ADD `timezone_offset` TINYINT(2) NULL DEFAULT '0' COMMENT 'nuber of hours of diference with the server timezone' ;
ALTER TABLE tagente ADD `icon_path` VARCHAR(127) NULL DEFAULT NULL COMMENT 'path in the server to the image of the icon representing the agent' ;
ALTER TABLE tagente ADD `update_gis_data` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'set it to one to update the position data (altitude, longitude, latitude) when getting information from the agent or to 0 to keep the last value and don\'t update it' ;
ALTER TABLE tagente ADD `last_latitude` DOUBLE NULL COMMENT 'last latitude of the agent' ;
ALTER TABLE tagente ADD `last_longitude` DOUBLE NULL COMMENT 'last longitude of the agent' ;
ALTER TABLE tagente ADD `last_altitude` DOUBLE NULL COMMENT 'last altitude of the agent' ;

ALTER TABLE `tgraph_source` CHANGE `weight` `weight` float(5,3) UNSIGNED NOT NULL DEFAULT 0;

-- GIS extension Tables

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
  `manual_placement` TINYINT(1) NULL DEFAULT 0 COMMENT '0 to show that the position cames from the agent, 1 to show that the position was established manualy' ,
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
  PRIMARY KEY (`id_tgis_map`) )
ENGINE = InnoDB
COMMENT = 'Table containing information about a gis map';

-- -----------------------------------------------------
-- Table `tgis_map_connection`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_connection` (
  `id_tmap_connection` INT NOT NULL AUTO_INCREMENT COMMENT 'table id' ,
  `conection_data` TEXT NULL COMMENT 'connection information (this can probably change to fit better the possible connection parameters)' ,
  `tgis_map_id_tgis_map` INT NOT NULL COMMENT 'reference to the map that uses this connection' ,
  PRIMARY KEY (`id_tmap_connection`) ,
  INDEX `fk_tgis_map_connection_tgis_map1` (`tgis_map_id_tgis_map` ASC) ,
  CONSTRAINT `fk_tgis_map_connection_tgis_map1`
    FOREIGN KEY (`tgis_map_id_tgis_map` )
    REFERENCES `tgis_map` (`id_tgis_map` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Table to store the map connection information';

-- -----------------------------------------------------
-- Table `tgis_map_layer`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `tgis_map_layer` (
  `id_tmap_layer` INT NOT NULL AUTO_INCREMENT COMMENT 'table id' ,
  `layer_name` VARCHAR(45) NOT NULL COMMENT 'Name of the layer ' ,
  `view_layer` TINYINT(1) NOT NULL DEFAULT TRUE COMMENT 'True if the layer must be shown' ,
  `tgis_map_id_tgis_map` INT NOT NULL COMMENT 'reference to the map containing the layer' ,
  `tgrupo_id_grupo` MEDIUMINT(4) UNSIGNED NOT NULL COMMENT 'reference to the group shown in the layer' ,
  PRIMARY KEY (`id_tmap_layer`, `tgis_map_id_tgis_map`) ,
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
