START TRANSACTION;

ALTER TABLE `tlayout` ADD `is_favourite` int(1) NOT NULL DEFAULT 0;

SELECT max(unified_filters_id) INTO @max FROM tsnmp_filter;
UPDATE tsnmp_filter tsf,(SELECT @max:= @max) m SET tsf.unified_filters_id = @max:= @max + 1 where tsf.unified_filters_id=0;

ALTER TABLE tgraph ADD COLUMN `fullscale` tinyint(1) UNSIGNED NOT NULL default '0';

ALTER TABLE treport_content ADD COLUMN hide_no_data tinyint(1) DEFAULT 0;

COMMIT;