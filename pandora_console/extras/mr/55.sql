START TRANSACTION;

ALTER TABLE `tdashboard` MODIFY `name` TEXT NOT NULL DEFAULT '';

COMMIT;