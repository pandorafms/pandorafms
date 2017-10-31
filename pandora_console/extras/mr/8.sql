START TRANSACTION;
ALTER TABLE tusuario ADD COLUMN `time_autorefresh` int(5) unsigned NOT NULL default '30';
ALTER TABLE treport_content ADD COLUMN lapse_calc tinyint(1) default '0';
ALTER TABLE treport_content ADD COLUMN lapse int(11) default '300';
ALTER TABLE treport_content ADD COLUMN visual_format tinyint(1) default '0';
ALTER TABLE treport_content_template ADD COLUMN lapse_calc tinyint(1) default '0';
ALTER TABLE treport_content_template ADD COLUMN lapse int(11) default '300';
ALTER TABLE treport_content_template ADD COLUMN visual_format tinyint(1) default '0';
COMMIT;