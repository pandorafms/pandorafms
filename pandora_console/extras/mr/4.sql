START TRANSACTION;

alter table tusuario add autorefresh_white_list text not null default '';
ALTER TABLE tserver_export MODIFY name varchar(600) BINARY NOT NULL default '';

CREATE TABLE IF NOT EXISTS `tpolicy_groups` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`id_policy` int(10) unsigned default '0',
	`id_group` int(10) unsigned default '0',
	`policy_applied` tinyint(1) unsigned default '0',
	`pending_delete` tinyint(1) unsigned default '0',
	`last_apply_utimestamp` int(10) unsigned NOT NULL default 0,
	PRIMARY KEY  (`id`),
	UNIQUE (`id_policy`, `id_group`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

COMMIT;