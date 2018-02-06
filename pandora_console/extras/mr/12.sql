START TRANSACTION;

ALTER TABLE tcontainer_item ADD COLUMN type_graph tinyint(1) unsigned default '0';

COMMIT;