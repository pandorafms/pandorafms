START TRANSACTION;

DELETE FROM tconfig WHERE token = 'refr';

INSERT INTO `tmodule_inventory` (`id_module_inventory`, `id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) VALUES (37,2,'CPU','CPU','','Brand;Clock;Model','',0,2);

INSERT INTO `tmodule_inventory` (`id_module_inventory`, `id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) VALUES (38,2,'RAM','RAM','','Size','',0,2);

INSERT INTO `tmodule_inventory` (`id_module_inventory`, `id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) VALUES (39,2,'NIC','NIC','','NIC;Mac;Speed','',0,2);

INSERT INTO `tmodule_inventory` (`id_module_inventory`, `id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`,`script_mode`) VALUES (40,2,'Software','Software','','PKGINST;VERSION;NAME','',0,2);

COMMIT;
