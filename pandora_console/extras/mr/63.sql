START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tagent_filter` (
  `id_filter`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_name` VARCHAR(600) NOT NULL,
  `id_group_filter` INT NOT NULL DEFAULT 0,
  `group_id` INT NOT NULL DEFAULT 0,
  `recursion` TEXT,
  `status` INT NOT NULL DEFAULT -1,
  `search` TEXT,
  `id_os` INT NOT NULL DEFAULT 0,
  `policies` TEXT,
  `search_custom` TEXT,
  `ag_custom_fields` TEXT,
  PRIMARY KEY  (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

COMMIT;