START TRANSACTION;

UPDATE `tagente` SET `id_os` = 100 WHERE `id_os` = 21 and (select `id_os` from `tconfig_os` WHERE `id_os` = 21 and `name` = 'Cluster');

DELETE FROM `tconfig_os` where `id_os` = 21 and `name` = 'Cluster';

-- ---------------------------------------------------------------------
-- Table `tagent_secondary_group`
-- ---------------------------------------------------------------------

create table IF NOT EXISTS `tagent_secondary_group`(
    `id` int unsigned not null auto_increment,
    `id_agent` int(10) unsigned NOT NULL,
    `id_group` mediumint(4) unsigned NOT NULL,
    PRIMARY KEY(`id`),
    FOREIGN KEY(`id_agent`) REFERENCES tagente(`id_agente`)
        ON DELETE CASCADE,
	FOREIGN KEY(`id_group`) REFERENCES tgrupo(`id_grupo`)
        ON DELETE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `tmetaconsole_agent_secondary_group`
-- ---------------------------------------------------------------------
create table IF NOT EXISTS `tmetaconsole_agent_secondary_group`(
    `id` int unsigned not null auto_increment,
    `id_agent` int(10) unsigned NOT NULL,
    `id_tagente` int(10) unsigned NOT NULL,
    `id_tmetaconsole_setup` int(10) NOT NULL,
    `id_group` mediumint(4) unsigned NOT NULL,
    PRIMARY KEY(`id`),
    FOREIGN KEY(`id_agent`) REFERENCES tmetaconsole_agent(`id_agente`)
        ON DELETE CASCADE,
	FOREIGN KEY(`id_group`) REFERENCES tgrupo(`id_grupo`)
        ON DELETE CASCADE,
	FOREIGN KEY (`id_tmetaconsole_setup`) REFERENCES tmetaconsole_setup(`id`)
		ON DELETE CASCADE
) engine=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE tagente ADD COLUMN `update_secondary_groups` tinyint(1) NOT NULL default '0';
ALTER TABLE tmetaconsole_agent ADD COLUMN `update_secondary_groups` tinyint(1) NOT NULL default '0';
ALTER TABLE tusuario_perfil ADD COLUMN `is_secondary` tinyint(1) NOT NULL default '0';

SET @st_oum721 = (SELECT IF(
	(SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tuser_task_scheduled') > 0, 
	"ALTER TABLE tuser_task_scheduled ADD (id_grupo int(10) unsigned NOT NULL Default 0)", 
	"0"
));

PREPARE pr_oum721 FROM @st_oum721;
EXECUTE pr_oum721;
DEALLOCATE PREPARE pr_oum721;

COMMIT;