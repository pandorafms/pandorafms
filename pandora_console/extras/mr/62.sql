START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tmonitor_filter` ( 
  `id_filter`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_name` VARCHAR(600) NOT NULL,
  `id_group_filter` INT NOT NULL DEFAULT 0,
  `ag_group` INT NOT NULL DEFAULT 0,
  `recursion` TEXT,
  `status` INT NOT NULL DEFAULT -1,
  `ag_modulename` TEXT,
  `ag_freestring` TEXT,
  `tag_filter` TEXT,
  `moduletype` TEXT,
  `module_option` INT DEFAULT 1,
  `modulegroup` INT NOT NULL DEFAULT -1,
  `min_hours_status` TEXT,
  `datatype` TEXT,
  `not_condition` TEXT,
  `ag_custom_fields` TEXT,
  PRIMARY KEY (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

COMMIT;
