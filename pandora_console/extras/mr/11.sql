START TRANSACTION;

ALTER TABLE `tlayout` ADD `is_favourite` int(1) NOT NULL DEFAULT 0;

SELECT max(unified_filters_id) INTO @max FROM tsnmp_filter;
UPDATE tsnmp_filter tsf,(SELECT @max:= @max) m SET tsf.unified_filters_id = @max:= @max + 1 where tsf.unified_filters_id=0;

COMMIT;