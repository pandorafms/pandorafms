START TRANSACTION;

ALTER TABLE `tevent_rule` DROP COLUMN `user_comment`;
ALTER TABLE `tevent_rule` DROP COLUMN `operator_user_comment`;

ALTER TABLE treport_content ADD check_unknowns_graph tinyint DEFAULT 0 NULL;

COMMIT;
