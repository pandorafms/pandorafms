START TRANSACTION;

ALTER TABLE `tncm_queue`
ADD COLUMN `id_agent_data` bigint unsigned AFTER `id_script`,
ADD CONSTRAINT `fk_tncm_queue_tncm_agent_data` FOREIGN KEY (`id_agent_data`) REFERENCES `tncm_agent_data`(`id`) ON UPDATE CASCADE ON DELETE SET NULL;

COMMIT;
