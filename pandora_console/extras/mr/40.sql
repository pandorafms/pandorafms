START TRANSACTION;

UPDATE `talert_commands` SET name='Monitoring&#x20;Event' WHERE name='Pandora FMS Event';

COMMIT;