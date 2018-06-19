START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tdatabase` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`host` varchar(100) default '',
	`os_port` int(4) unsigned default '22',
	`os_user` varchar(100) default '',
	`db_port` int(4) unsigned default '3306',
	`status` tinyint(1) unsigned default '0',
	`action` tinyint(1) unsigned default '0',
	`last_error` varchar(255) default '',
	PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8 ;

ALTER TABLE `tagent_module_inventory` ADD COLUMN `custom_fields` MEDIUMBLOB NOT NULL;

INSERT INTO `tmodule_inventory` (`id_os`, `name`, `description`, `interpreter`, `data_format`, `code`, `block_mode`) VALUES (7,'Cisco&#x20;Interface&#x20;Remote&#x20;Inventory&#x20;SSH','Remote&#x20;inventory&#x20;module&#x20;to&#x20;get&#x20;all&#x20;cards&#x20;in&#x20;a&#x20;Cisco','/usr/bin/perl','Name;Card&#x20;Name;ID/Serial','IyEvdXNyL2Jpbi9wZXJsIAojCiMgKGMpIEFydGljYSAyMDE4CiMgVGhpcyBpcyBhbiBFbnRlcnByaXNlIGludmVudG9yeSBzY3JpcHQgZm9yIFBhbmRvcmEgRk1TIDdORwojIFRoaXMgc2NyaXB0IHdpbGwgY29sbGVjdCBhIHJlbW90ZSBDSVNDTyBjb25maWd1cmF0aW9uCiMgUkVNRU1CRVIgVE8gQUNUSVZBVEUgSVQgQVMgQkxPQ0sgTU9ERSAhISEKCnVzZSB3YXJuaW5nczsKdXNlIHN0cmljdDsKdXNlIE5ldDo6U1NIOjpFeHBlY3Q7CgojIExvYWQgY29uZmlnIGZyb20gY29tbWFuZCBsaW5lCmlmICgkI0FSR1YgIT0gMikgewogICAgcHJpbnQgIlVzYWdlOiAkMCA8VGFyZ2V0IElQPiA8VXNlcm5hbWU+IDxQYXNzd29yZD4gPEVuYWJsZSBwYXNzd29yZD5cbiI7CiAgICBleGl0IDE7Cn0KCm15ICR0aW1lb3V0ID0gNTsKbXkgJHRhcmdldCA9ICRBUkdWWzBdOwpteSAkdXNlcm5hbWUgPSAkQVJHVlsxXTsKbXkgJHBhc3N3b3JkID0gJEFSR1ZbMl07Cm15ICRlbmFibGVfcGFzcyA9ICRBUkdWWzNdOwoKbXkgJHNzaCA9IE5ldDo6U1NIOjpFeHBlY3QtPm5ldygKICAgIGhvc3QgPT4gJHRhcmdldCwKICAgIHVzZXIgPT4gJHVzZXJuYW1lLAogICAgcGFzc3dvcmQgPT4gJHBhc3N3b3JkLAogICAgcmF3X3B0eSA9PiAxLAogICAgdGltZW91dCA9PiAkdGltZW91dAopOwokc3NoLT5sb2dpbigxKSBvciBkaWUgIlVuYWJsZSB0byBzdGFydDogJCEiOwokc3NoLT5zZW5kKCJlbiIpOwokc3NoLT53YWl0Zm9yKCcvUGFzc3dvcmQ6L2knKTsKJHNzaC0+c2VuZCgkZW5hYmxlX3Bhc3MpOwokc3NoLT53YWl0Zm9yKCcvXCMvJyk7CiRzc2gtPnNlbmQoInRlcm1pbmFsIGxlbmd0aCAwIik7CiRzc2gtPndhaXRmb3IoJy9cIy8nKTsKJHNzaC0+ZWF0KCRzc2gtPnBlZWsoKSk7ICMgdG8gcmVtb3ZlIGN1cnJlbnQgb3V0cHV0CiRzc2gtPnNlbmQoInNob3cgcnVubmluZy1jb25maWciKTsKCiMgcmV0dXJucyB0aGUgbmV4dCBsaW5lLCByZW1vdmluZyBpdCBmcm9tIHRoZSBpbnB1dCBzdHJlYW06Cm15ICRsaW5lOwokc3NoLT5yZWFkX2xpbmUoKTsgIyB0byByZW1vdmUgc2hvdyBydW5uaW5nLWNvbmZpZwp3aGlsZSAoZGVmaW5lZCgkbGluZSA9ICRzc2gtPnJlYWRfbGluZSgpKSkgewogICAgcHJpbnQgJGxpbmUgLiAiXG4iOwp9Cg==',0);

COMMIT;