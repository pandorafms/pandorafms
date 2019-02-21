START TRANSACTION;

ALTER TABLE `treport` ADD COLUMN `orientation` varchar(25) NOT NULL default 'vertical';

CREATE TABLE IF NOT EXISTS `tnetwork_matrix` (
    `id` int(10) unsigned NOT NULL auto_increment,
    `source` varchar(60) default '',
    `destination` varchar(60) default '',
    `utimestamp` bigint(20) default 0,
    `bytes` int(18) unsigned default 0,
    `pkts` int(18) unsigned default 0,
    PRIMARY KEY (`id`),
    UNIQUE (`source`, `destination`, `utimestamp`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 ;


COMMIT;
