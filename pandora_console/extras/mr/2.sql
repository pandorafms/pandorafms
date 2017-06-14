START TRANSACTION;

ALTER TABLE tagent_custom_fields ADD is_password_type tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE treport_content ADD COLUMN historical_db tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE tpolicy_modules ADD COLUMN ip_target varchar(100) default '';

COMMIT;