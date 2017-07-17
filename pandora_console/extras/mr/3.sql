START TRANSACTION;

ALTER TABLE tagent_custom_fields ADD is_password_type tinyint(1) NOT NULL DEFAULT 0;

COMMIT;
