START TRANSACTION;

ALTER TABLE treport_content ADD check_unknowns_graph tinyint DEFAULT 0 NULL;

COMMIT;
