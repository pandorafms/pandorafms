START TRANSACTION;

ALTER TABLE tpolicy_group_agents CONVERT TO CHARACTER SET UTF8MB4;
ALTER TABLE tevent_sound CONVERT TO CHARACTER SET UTF8MB4;
ALTER TABLE tsesion_filter CONVERT TO CHARACTER SET UTF8MB4;

COMMIT;
