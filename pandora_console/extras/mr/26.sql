START TRANSACTION;

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

ALTER TABLE `treport_content` ADD COLUMN `show_extended_events` tinyint(1) default '0';

UPDATE `treport_content` SET type="netflow_summary" WHERE type="netflow_pie" OR type="netflow_statistics";

UPDATE `tnetflow_filter` SET aggregate="dstip" WHERE aggregate NOT IN ("dstip", "srcip", "dstport", "srcport");

ALTER TABLE tagent_custom_fields ADD COLUMN `combo_values` VARCHAR(255) DEFAULT '';

ALTER TABLE `trecon_task` ADD COLUMN `summary` text;

COMMIT;
