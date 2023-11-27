START TRANSACTION;

-- Delete table tagent_access
DROP TABLE tagent_access;

ALTER TABLE treport_content ADD check_unknowns_graph tinyint DEFAULT 0 NULL;

ALTER TABLE `tdashboard`
ADD COLUMN `date_range` TINYINT NOT NULL DEFAULT 0 AFTER `cells_slideshow`,
ADD COLUMN `date_from` INT NOT NULL DEFAULT 0 AFTER `date_range`,
ADD COLUMN `date_to` INT NOT NULL DEFAULT 0 AFTER `date_from`;

COMMIT;
