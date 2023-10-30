START TRANSACTION;

ALTER TABLE `tncm_queue`
ADD COLUMN `id_agent_data` bigint unsigned AFTER `id_script`;

COMMIT;
