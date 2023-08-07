START TRANSACTION;

ALTER TABLE `treport_content`  ADD COLUMN `cat_security_hardening` INT NOT NULL DEFAULT 0;

ALTER TABLE `treport_content`  ADD COLUMN `ignore_skipped` INT NOT NULL DEFAULT 0;

COMMIT;