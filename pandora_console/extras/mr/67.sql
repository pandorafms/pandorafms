START TRANSACTION;

ALTER TABLE `tevent_rule` DROP COLUMN `user_comment`;
ALTER TABLE `tevent_rule` DROP COLUMN `operator_user_comment`;

COMMIT;