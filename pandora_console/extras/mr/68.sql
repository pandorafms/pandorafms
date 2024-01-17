START TRANSACTION;

ALTER TABLE `tncm_agent_data`
ADD COLUMN `id_agent_data` int not null default 0 AFTER `script_type`;

COMMIT;