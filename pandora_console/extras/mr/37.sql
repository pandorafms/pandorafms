START TRANSACTION;

ALTER TABLE trecon_task modify column `id_network_profile` text;

COMMIT;