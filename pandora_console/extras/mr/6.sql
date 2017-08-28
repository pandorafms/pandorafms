START TRANSACTION;

ALTER TABLE tagente MODIFY COLUMN `cascade_protection_module` int(10) unsigned NOT NULL default '0';

COMMIT;

CREATE TABLE IF NOT EXISTS treset_pass_history (
    id int(10) unsigned NOT NULL auto_increment,
    id_user varchar(60) NOT NULL,
    reset_moment datetime NOT NULL,
    success tinyint(1) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;