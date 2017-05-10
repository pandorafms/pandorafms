START TRANSACTION;

ALTER TABLE tusuario add default_event_filter int(10) unsigned NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS `treset_pass` (
    `id` bigint(10) unsigned NOT NULL auto_increment,
    `id_user` varchar(100) NOT NULL default '',
    `cod_hash` varchar(100) NOT NULL default '',
    `reset_time` int(10) unsigned NOT NULL default 0,
    PRIMARY KEY (`id`) 
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE tgis_map_connection SET conection_data = '{"type":"OSM","url":"http://tile.openstreetmap.org/${z}/${x}/${y}.png"}' where id_tmap_connection = 1;

ALTER TABLE tpolicy_modules MODIFY post_process double(24,15) default 0;

COMMIT;