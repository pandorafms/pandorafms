START TRANSACTION;

ALTER TABLE `treport_content_sla_combined` ADD `id_agent_module_failover` int(10) unsigned NOT NULL;

ALTER TABLE `treport_content` ADD COLUMN `failover_mode` tinyint(1) DEFAULT '0';
ALTER TABLE `treport_content` ADD COLUMN `failover_type` tinyint(1) DEFAULT '0';

ALTER TABLE `treport_content_template` ADD COLUMN `failover_mode` tinyint(1) DEFAULT '1';
ALTER TABLE `treport_content_template` ADD COLUMN `failover_type` tinyint(1) DEFAULT '1';

ALTER TABLE `tmodule_relationship` ADD COLUMN `type` ENUM('direct', 'failover') DEFAULT 'direct';

COMMIT;