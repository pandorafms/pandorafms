START TRANSACTION;

INSERT INTO `tconfig` (`token`, `value`) VALUES ('custom_module_units', '{"bytes":"bytes","entries":"entries","files":"files","hits":"hits","sessions":"sessions","users":"users","ºC":"ºC","ºF":"ºF"}');
ALTER TABLE `tevent_filter` MODIFY `severity` TEXT NOT NULL;

COMMIT;