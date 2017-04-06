START TRANSACTION;
CREATE TABLE IF NOT EXISTS `tnueva2` (
	`id_a` int(10) unsigned NOT NULL auto_increment,
	`ip` varchar(60) NOT NULL default '',
	`ip_pack` int(10) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id_a`),
	KEY `ip` (`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
COMMIT;
