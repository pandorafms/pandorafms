START TRANSACTION;

ALTER TABLE tagente MODIFY COLUMN `cascade_protection_module` int(10) unsigned NOT NULL default '0';

CREATE TABLE IF NOT EXISTS treset_pass_history (
	id int(10) unsigned NOT NULL auto_increment,
	id_user varchar(60) NOT NULL,
	reset_moment datetime NOT NULL,
	success tinyint(1) NOT NULL,
	PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE tserver ADD COLUMN exec_proxy tinyint(1) UNSIGNED NOT NULL default 0;

ALTER TABLE tevent_response ADD COLUMN server_to_exec int(10) unsigned NOT NULL DEFAULT 0;

INSERT INTO tmodule VALUES (8, 'Wux module');

INSERT INTO ttipo_modulo VALUES (25,'web_analysis', 8, 'Web analysis data', 'module-wux.png');

COMMIT;