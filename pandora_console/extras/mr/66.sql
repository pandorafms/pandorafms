START TRANSACTION;

ALTER TABLE `treport_content`  ADD COLUMN `cat_security_hardening` INT NOT NULL DEFAULT 0;

COMMIT;