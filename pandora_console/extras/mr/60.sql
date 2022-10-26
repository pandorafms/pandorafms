START TRANSACTION;

ALTER TABLE treport_content ADD COLUMN use_prefix_notation tinyint(1) default '1';
ALTER TABLE treport_content_template ADD COLUMN use_prefix_notation tinyint(1) default '1';

COMMIT;
