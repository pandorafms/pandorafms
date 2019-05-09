START TRANSACTION;

DELETE FROM 'tevent_response' WHERE 'name' LIKE 'Create&#x20;Integria&#x20;IMS&#x20;incident&#x20;from&#x20;event';

ALTER TABLE `tlayout_data` ADD COLUMN `cache_expiration` INTEGER UNSIGNED NOT NULL DEFAULT 0;

COMMIT;