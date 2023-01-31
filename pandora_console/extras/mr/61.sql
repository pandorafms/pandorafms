START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tsesion_filter` (
    `id_filter` INT NOT NULL AUTO_INCREMENT,
    `id_name` TEXT NULL,
    `text` TEXT NULL,
    `period` TEXT NULL,
    `ip` TEXT NULL,
    `type` TEXT NULL,
    `user` TEXT NULL,
    PRIMARY KEY (`id_filter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

COMMIT;
