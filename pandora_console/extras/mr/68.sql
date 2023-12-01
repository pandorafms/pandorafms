START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tmerge_error` (
    `id` int(10) NOT NULL auto_increment,
    `id_node` int(10) default 0,
    `phase` int(10) default 0,
    `step` int(10) default 0,
    `msg` LONGTEXT default "",
    `action` text default "",
    `utimestamp` int(20) unsigned NOT NULL default 0,
    PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tmerge_error` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `tmerge_steps` (
    `id` int(10) NOT NULL auto_increment,
    `id_node` int(10) default 0,
    `phase` int(10) default 0,
    `total` int(10) default 0,
    `step` int(10) default 0,
    `debug` varchar(1024) default "",
    `action` varchar(100) default "",
    `affected` varchar(100) default "",
    `query` mediumtext default "",
    `utimestamp` int(20) unsigned NOT NULL default 0,
    PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tmerge_steps` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `tmerge_queries` (
    `steps` int(10) NOT NULL auto_increment,
    `action` varchar(100) default "",
    `affected` varchar(100) default "",
    `utimestamp` int(20) unsigned NOT NULL default 0,
    `query` LONGTEXT NOT NULL default "",
    PRIMARY KEY  (`steps`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4;

ALTER TABLE `tmerge_queries` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

COMMIT;
