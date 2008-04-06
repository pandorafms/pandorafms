-- Pandora FMS - the Free Monitoring System
-- ========================================
-- Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
-- Please see http://pandora.sourceforge.net for full contribution list

-- This program is free software; you can redistribute it and/or
-- modify it under the terms of the GNU General Public License
-- as published by the Free Software Foundation for version 2.
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

-- PLEASE NO NOT USE MULTILINE COMMENTS 
-- Because Pandora Installer don't understand them
-- and fails creating database !!!
--------------------------------------------------------------
-- Pandora FMS official tables for 2.0 version              --
--------------------------------------------------------------

CREATE TABLE `taddress` (
  `id_a` bigint(20) unsigned NOT NULL auto_increment,
  `ip` varchar(15) NOT NULL default '',
  `ip_pack` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `taddress_agent` (
  `id_ag` bigint(20) unsigned NOT NULL auto_increment,
  `id_a` bigint(20) unsigned NOT NULL default '0',
  `id_agent` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_ag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tagent_access` (
  `id_ac` bigint(20) unsigned NOT NULL auto_increment,
  `id_agent` int(11) NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `utimestamp` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id_ac`),
  KEY `agent_index` (`id_agent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tagente` (
  `id_agente` mediumint(8) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `direccion` varchar(100) default '',
  `comentarios` varchar(255) default '',
  `id_grupo` int(10) unsigned NOT NULL default '0',
  `ultimo_contacto` datetime NOT NULL default '0000-00-00 00:00:00',
  `modo` tinyint(1) NOT NULL default '0',
  `intervalo` int(11) NOT NULL default '300',
  `id_os` int(10) unsigned default '0',
  `os_version` varchar(100) default '',
  `agent_version` varchar(100) default '',
  `ultimo_contacto_remoto` datetime default '0000-00-00 00:00:00',
  `disabled` tinyint(2) NOT NULL default '0',
  `id_network_server` int(4) unsigned default '0',
  `id_plugin_server` int(4) unsigned default '0',
  `id_prediction_server` int(4) unsigned default '0',
  `id_wmi_server` int(4) unsigned default '0',
  `id_parent` mediumint(8) unsigned default '0',
  PRIMARY KEY  (`id_agente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tagente_datos` (
  `id_agente_datos` bigint(10) unsigned NOT NULL auto_increment,
  `id_agente_modulo` mediumint(8) unsigned NOT NULL default '0',
  `datos` double(18,2) default NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_agente` mediumint(8) unsigned NOT NULL default '0',
  `utimestamp` int(10) unsigned default '0',
  PRIMARY KEY  (`id_agente_datos`),
  KEY `data_index2` (`id_agente`,`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tagent_data_image` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `id_agent_module` mediumint(8) unsigned NOT NULL default '0',
  `id_agent` mediumint(8) unsigned NOT NULL default '0',
  `blob` mediumblob NOT NULL,
  `filename` varchar(255) default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `utimestamp` int(10) unsigned default '0',
  PRIMARY KEY  (`id`),
  KEY `img_idx1` (`id_agent`,`id_agent_module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tagente_datos_inc` (
  `id_adi` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente_modulo` bigint(20) NOT NULL default '0',
  `datos` double(18,2) default NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `utimestamp` int(10) unsigned default '0',
  PRIMARY KEY  (`id_adi`),
  KEY `data_inc_index_1` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tagente_datos_string` (
  `id_tagente_datos_string` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(11) NOT NULL default '0',
  `datos` text NOT NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_agente` bigint(4) unsigned NOT NULL default '0',
  `utimestamp` int(10) unsigned NOT NULL default 0,
  PRIMARY KEY  (`id_tagente_datos_string`),
  KEY `data_string_index_1` (`id_agente`,`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tagente_estado` (
  `id_agente_estado` int(10) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(20) NOT NULL default '0',
  `datos` varchar(255) NOT NULL default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `cambio` int(11) NOT NULL default '0',
  `estado` int(11) NOT NULL default '0',
  `id_agente` int(11) NOT NULL default '0',
  `last_try` datetime default NULL,
  `utimestamp` bigint(20) NOT NULL default '0',
  `current_interval` int(10) unsigned NOT NULL default '0',
  `running_by` int(10) unsigned NULL default 0,
  `last_execution_try` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id_agente_estado`),
  KEY `status_index_1` (`id_agente_modulo`),
  KEY `status_index_2` (`id_agente_modulo`,`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 

id_modulo now uses tmodule 

1 - Data server modules (agent related modules)
2 - Network server modules
4 - Plugin server
5 - Predictive server
6 - WMI server

*/

CREATE TABLE `tagente_modulo` (
  `id_agente_modulo` bigint(14) unsigned NOT NULL auto_increment,
  `id_agente` int(11) NOT NULL default '0',
  `id_tipo_modulo` int(11) NOT NULL default '0',
  `descripcion` varchar(100) NOT NULL default '',
  `nombre` varchar(100) NOT NULL default '',
  `max` bigint(20) default '0',
  `min` bigint(20) default '0',
  `module_interval` int(4) unsigned default '0',
  `tcp_port` int(4) unsigned default '0',
  `tcp_send` varchar(150) default '',
  `tcp_rcv` varchar(100) default '',
  `snmp_community` varchar(100) default '',
  `snmp_oid` varchar(255) default '0',
  `ip_target` varchar(100) default '',
  `id_module_group` int(4) unsigned default '0',
  `flag` tinyint(3) unsigned default '1',
  `id_modulo` int(11) unsigned NULL default 0,
  `disabled` tinyint(3) unsigned default '0',
  `id_export` tinyint(3) unsigned default '0',
  `plugin_user` varchar(250) default '',
  `plugin_pass` varchar(250) default '',
  `plugin_parameter` text default '',
  `id_plugin` int(11) default '0',
  `post_process` double(18,2) default NULL,
  `prediction_module` bigint(14) default '0',
  `max_timeout` tinyint(3) unsigned default '0',
  PRIMARY KEY (`id_agente_modulo`, `id_agente`),
  KEY `tam_agente` (`id_agente`),
  KEY `tam_plugin` (`id_plugin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- snmp_oid is also used for WMI query

CREATE TABLE `talert_snmp` (
  `id_as` int(10) unsigned NOT NULL auto_increment,
  `id_alert` int(10) unsigned NOT NULL default '0',
  `al_field1` varchar(100) NOT NULL default '',
  `al_field2` varchar(255) NOT NULL default '',
  `al_field3` varchar(255) NOT NULL default '',
  `description` varchar(255) default '',
  `alert_type` int(2) unsigned NOT NULL default '0',
  `agent` varchar(100) default '',
  `custom_oid` varchar(200) default '',
  `oid` varchar(255) NOT NULL default '',
  `time_threshold` int(11) NOT NULL default '0',
  `times_fired` int(2) unsigned NOT NULL default '0',
  `last_fired` datetime NOT NULL default '0000-00-00 00:00:00',
  `max_alerts` int(11) NOT NULL default '1',
  `min_alerts` int(11) NOT NULL default '1',
  `internal_counter` int(2) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_as`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `talerta` (
  `id_alerta` int(10) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `comando` varchar(255) default '',
  `descripcion` varchar(255) default '',
  PRIMARY KEY  (`id_alerta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcompound_alert` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `id_aam` int(11) NOT NULL default '0',
  `operation` enum('NOP', 'AND','OR','XOR','NAND','NOR','NXOR'),
  PRIMARY KEY  (`id`, `id_aam`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `talerta_agente_modulo` (
  `id_aam` int(11) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(11) NOT NULL default '0',
  `id_alerta` int(11) NOT NULL default '0',
  `al_campo1` varchar(255) default '',
  `al_campo2` varchar(255) default '',
  `al_campo3` mediumtext NOT NULL,
  `descripcion` varchar(255) default '',
  `dis_max` double(18,2) default NULL,
  `dis_min` double(18,2) default NULL,
  `time_threshold` int(11) NOT NULL default '0',
  `last_fired` datetime NOT NULL default '0000-00-00 00:00:00',
  `max_alerts` int(4) NOT NULL default '1',
  `times_fired` int(11) NOT NULL default '0',
  `module_type` int(11) NOT NULL default '0',
  `min_alerts` int(4) NOT NULL default '0',
  `internal_counter` int(4) default '0',
  `alert_text` varchar(255) default '',
  `disable` int(4) default '0',
  `time_from` TIME default '00:00:00',
  `time_to` TIME default '00:00:00',
  `id_agent` int(11) default NULL,
  `monday` tinyint(3) default '1',
  `tuesday` tinyint(3) default '1',
  `wednesday` tinyint(3) default '1',
  `thursday` tinyint(3) default '1',
  `friday` tinyint(3) default '1',
  `saturday` tinyint(3) default '1',
  `sunday` tinyint(3) default '1',
  `recovery_notify` tinyint(3) default '0',
  `priority` tinyint(4) default '0',
  `al_f2_recovery` varchar(255) NOT NULL default '',
  `al_f3_recovery` mediumtext NOT NULL default '',
  PRIMARY KEY  (`id_aam`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tattachment` (
  `id_attachment` bigint(20) unsigned NOT NULL auto_increment,
  `id_incidencia` bigint(20) NOT NULL default '0',
  `id_usuario` varchar(60) NOT NULL default '',
  `filename` varchar(255) NOT NULL default '',
  `description` varchar(150) default '',
  `size` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id_attachment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tconfig` (
  `id_config` int(10) unsigned NOT NULL auto_increment,
  `token` varchar(100) NOT NULL default '',
  `value` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tconfig_os` (
  `id_os` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) default '',
  `icon_name` varchar(100) default '',
  PRIMARY KEY  (`id_os`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tevento` (
  `id_evento` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente` bigint(20) NOT NULL default '0',
  `id_usuario` varchar(60) NOT NULL default '0',
  `id_grupo` bigint(20) NOT NULL default '0',
  `estado` int(10) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `evento` varchar(255) NOT NULL default '',
  `utimestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_evento`),
  KEY `indice_1` (`id_agente`,`id_evento`),
  KEY `indice_2` (`utimestamp`,`id_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tgrupo` (
  `id_grupo` mediumint(8) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `icon` varchar(50) default NULL,
  `parent` tinyint(4) NOT NULL default '-1',
  `disabled` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id_grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tincidencia` (
  `id_incidencia` bigint(20) unsigned NOT NULL auto_increment,
  `inicio` datetime NOT NULL default '0000-00-00 00:00:00',
  `cierre` datetime NOT NULL default '0000-00-00 00:00:00',
  `titulo` varchar(100) NOT NULL default '',
  `descripcion` mediumtext NOT NULL,
  `id_usuario` varchar(100) NOT NULL default '',
  `origen` varchar(100) NOT NULL default '',
  `estado` int(11) NOT NULL default '0',
  `prioridad` int(11) NOT NULL default '0',
  `id_grupo` mediumint(9) NOT NULL default '0',
  `actualizacion` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_creator` varchar(60) default NULL,
  `notify_email` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (`id_incidencia`),
  KEY `incident_index_1` (`id_usuario`,`id_incidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tlanguage` (
  `id_language` varchar(6) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tlink` (
  `id_link` int(10) unsigned zerofill NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `link` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tmensajes` (
  `id_mensaje` bigint(20) unsigned NOT NULL auto_increment,
  `id_usuario_origen` varchar(100) NOT NULL default '',
  `id_usuario_destino` varchar(100) NOT NULL default '',
  `mensaje` tinytext NOT NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `subject` varchar(255) NOT NULL default '',
  `estado` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_mensaje`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tmodule_group` (
  `id_mg` bigint(20) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  PRIMARY KEY  (`id_mg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tnetwork_component` (
  `id_nc` mediumint(12) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `description` varchar(250) default NULL,
  `id_group` mediumint(9) NOT NULL default '1',
  `type` smallint(6) NOT NULL default '6',
  `max` bigint(20) NOT NULL default '0',
  `min` bigint(20) NOT NULL default '0',
  `module_interval` mediumint(8) unsigned NOT NULL default '0',
  `tcp_port` int(10) unsigned NOT NULL default '0',
  `tcp_send` varchar(255) NOT NULL,
  `tcp_rcv` varchar(255) NOT NULL default 'NULL',
  `snmp_community` varchar(255) NOT NULL default 'NULL',
  `snmp_oid` varchar(400) NOT NULL,
  `id_module_group` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id_nc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tnetwork_component_group` (
  `id_sg` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `parent` mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (`id_sg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tnetwork_profile` (
  `id_np` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) default '',
  PRIMARY KEY  (`id_np`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tnetwork_profile_component` (
  `id_npc` mediumint(8) unsigned NOT NULL auto_increment,
  `id_nc` mediumint(8) unsigned NOT NULL default '0',
  `id_np` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_npc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tnota` (
  `id_nota` mediumint(8) unsigned NOT NULL auto_increment,
  `id_usuario` varchar(100) NOT NULL default '0',
  `timestamp` tinyblob NOT NULL,
  `nota` mediumtext NOT NULL,
  PRIMARY KEY  (`id_nota`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tnota_inc` (
  `id_nota_inc` mediumint(8) unsigned NOT NULL auto_increment,
  `id_incidencia` mediumint(9) NOT NULL default '0',
  `id_nota` mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (`id_nota_inc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `torigen` (
  `origen` varchar(100) NOT NULL default ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tperfil` (
  `id_perfil` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(60) NOT NULL default '',
  `incident_edit` int(11) NOT NULL default '0',
  `incident_view` int(11) NOT NULL default '0',
  `incident_management` int(11) NOT NULL default '0',
  `agent_view` int(11) NOT NULL default '0',
  `agent_edit` int(11) NOT NULL default '0',
  `alert_edit` int(11) NOT NULL default '0',
  `user_management` int(11) NOT NULL default '0',
  `db_management` int(11) NOT NULL default '0',
  `alert_management` int(11) NOT NULL default '0',
  `pandora_management` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id_perfil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `trecon_task` (
  `id_rt` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) NOT NULL default '',
  `type` tinyint(3) unsigned NOT NULL default '0',
  `subnet` varchar(64) NOT NULL default '',
  `id_network_server` int(10) unsigned NOT NULL default '0',
  `id_network_profile` int(10) unsigned NOT NULL default '0',
  `create_incident` tinyint(3) unsigned NOT NULL default '0',
  `id_group` int(10) unsigned NOT NULL default '1',
  `utimestamp` bigint(20) unsigned NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '0',
  `interval_sweep` int(10) unsigned NOT NULL default '0',
  `id_network_server_assigned` int(10) unsigned NOT NULL default '0',
  `extended_info`  varchar(250) default NULL,
  `extended_value` varchar(250) default NULL,
  PRIMARY KEY  (`id_rt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tserver` (
  `id_server` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `ip_address` varchar(100) NOT NULL default '',
  `status` int(11) NOT NULL default '0',
  `laststart` datetime NOT NULL default '0000-00-00 00:00:00',
  `keepalive` datetime NOT NULL default '0000-00-00 00:00:00',
  `snmp_server` tinyint(3) unsigned NOT NULL default '0',
  `network_server` tinyint(3) unsigned NOT NULL default '0',
  `data_server` tinyint(3) unsigned NOT NULL default '0',
  `master` tinyint(3) unsigned NOT NULL default '0',
  `checksum` tinyint(3) unsigned NOT NULL default '0',
  `description` varchar(255) default NULL,
  `recon_server` tinyint(3) unsigned NOT NULL default '0',
  `version` varchar(20) NOT NULL default '',
  `plugin_server` tinyint(3) unsigned NOT NULL default '0',
  `prediction_server` tinyint(3) unsigned NOT NULL default '0',
  `wmi_server` tinyint(3) unsigned NOT NULL default '0',
  `export_server` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_server`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tsesion` (
  `ID_sesion` bigint(4) unsigned NOT NULL auto_increment,
  `ID_usuario` varchar(60) NOT NULL default '0',
  `IP_origen` varchar(100) NOT NULL default '',
  `accion` varchar(100) NOT NULL default '',
  `descripcion` varchar(200) NOT NULL default '',
  `fecha` datetime NOT NULL default '0000-00-00 00:00:00',
  `utimestamp` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ID_sesion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `ttipo_modulo` (
  `id_tipo` smallint(5) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `categoria` int(11) NOT NULL default '0',
  `descripcion` varchar(100) NOT NULL default '',
  `icon` varchar(100) default NULL,
  PRIMARY KEY  (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `ttrap` (
  `id_trap` bigint(20) unsigned NOT NULL auto_increment,
  `source` varchar(50) NOT NULL default '',
  `oid` varchar(255) NOT NULL default '',
  `oid_custom` varchar(255) default '',
  `type` int(11) NOT NULL default '0',
  `type_custom` varchar(100) default '',
  `value` varchar(255) default '',
  `value_custom` varchar(255) default '',
  `alerted` smallint(6) NOT NULL default '0',
  `status` smallint(6) NOT NULL default '0',
  `id_usuario` varchar(150) default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id_trap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tusuario` (
  `id_usuario` varchar(60) NOT NULL default '0',
  `nombre_real` varchar(125) NOT NULL default '',
  `password` varchar(45) default NULL,
  `comentarios` varchar(200) default NULL,
  `fecha_registro` datetime NOT NULL default '0000-00-00 00:00:00',
  `direccion` varchar(100) default '',
  `telefono` varchar(100) default '',
  `nivel` tinyint(1) NOT NULL default '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tusuario_perfil` (
  `id_up` bigint(20) unsigned NOT NULL auto_increment,
  `id_usuario` varchar(100) NOT NULL default '',
  `id_perfil` int(20) NOT NULL default '0',
  `id_grupo` int(11) NOT NULL default '0',
  `assigned_by` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_up`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tnews` (
  `id_news` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `author` varchar(255)  NOT NULL DEFAULT '',
  `subject` varchar(255)  NOT NULL DEFAULT '',
  `text` TEXT NOT NULL,
  `timestamp` DATETIME  NOT NULL DEFAULT 0,
  PRIMARY KEY(`id_news`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tgraph` (
  `id_graph` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_user` varchar(100) NOT NULL default '',
  `name` varchar(150) NOT NULL default '',
  `description` TEXT NOT NULL,
  `period` int(11) NOT NULL default '0',
  `width` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `height` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `private` tinyint(1) UNSIGNED NOT NULL default 0,
  `events` tinyint(1) UNSIGNED NOT NULL default 0,
  PRIMARY KEY(`id_graph`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tgraph_source` (
  `id_gs` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_graph` int(11) NOT NULL default 0,
  `id_agent_module` int(11) NOT NULL default 0,
  `weight` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`id_gs`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `treport` (
  `id_report` INTEGER UNSIGNED NOT NULL  AUTO_INCREMENT,
  `id_user` varchar(100) NOT NULL default '',
  `name` varchar(150) NOT NULL default '',
  `description` TEXT NOT NULL,
  `private` tinyint(1) UNSIGNED NOT NULL default 0,
  PRIMARY KEY(`id_report`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `treport_content` (
  `id_rc` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_report` INTEGER UNSIGNED NOT NULL default 0,
  `id_gs` INTEGER UNSIGNED NOT NULL default 0,
  `id_agent_module` int(11) NOT NULL default 0,
  `type` tinyint(1) UNSIGNED NOT NULL default 0,
  `period` int(11) NOT NULL default 0,
  `sla_max` int(11) NOT NULL default 0,
  `sla_min` int(11) NOT NULL default 0,
  `sla_limit` int(11) NOT NULL default 0,
  PRIMARY KEY(`id_rc`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

/*

treport_content descripcion
type valid values
  0 - Simple graph
  1 - User/Combined graph
  2 - SLA
  3 - Event report
  4 - Alert report
  5 - Monitor report
  6 - Avg. value
  7 - Max. value
  8 - Min. value

id_gs is for combined graph pprimary key id

*/

CREATE TABLE `tlayout` (
  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50)  NOT NULL,
  `id_group` INTEGER UNSIGNED NOT NULL,
  `background` varchar(200)  NOT NULL,
  `fullscreen` tinyint(1) UNSIGNED NOT NULL default 0,
  `height` INTEGER UNSIGNED NOT NULL default 0,
  `width` INTEGER UNSIGNED NOT NULL default 0,
  PRIMARY KEY(`id`)
)  ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tlayout_data` (
  `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_layout` INTEGER UNSIGNED NOT NULL default 0,
  `pos_x` INTEGER UNSIGNED NOT NULL default 0,
  `pos_y` INTEGER UNSIGNED NOT NULL default 0,
  `height` INTEGER UNSIGNED NOT NULL default 0,
  `width` INTEGER UNSIGNED NOT NULL default 0,
  `label` varchar(200) DEFAULT "",
  `image` varchar(200) DEFAULT "",
  `type` tinyint(1) UNSIGNED NOT NULL default 0,
  `period` INTEGER UNSIGNED NOT NULL default 3600,
  `id_agente_modulo` mediumint(8) unsigned NOT NULL default '0',
  `id_layout_linked` INTEGER unsigned NOT NULL default '0',
  `parent_item` INTEGER UNSIGNED NOT NULL default 0,
  `label_color` varchar(20) DEFAULT "",
  `no_link_color` tinyint(1) UNSIGNED NOT NULL default 0,
  PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE tplugin (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(200) NOT NULL,
    `description` mediumtext default "",
    `max_timeout` int(4) UNSIGNED NOT NULL default 0,
    `execute`varchar(250) NOT NULL,
    `net_dst_opt` varchar(50) default '',
    `net_port_opt` varchar(50) default '',
    `user_opt` varchar(50) default '',
    `pass_opt` varchar(50) default '',
    PRIMARY KEY(`id`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 


CREATE TABLE `tmodule` (
  `id_module` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY (`id_module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `tserver_export` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `preffix` varchar(100) NOT NULL default '',
  `interval` int(5) unsigned NOT NULL default '300',
  `ip_server` varchar(100) NOT NULL default '',
  `connect_mode` tinyint(2) NOT NULL default '0',
  `id_export_server` int(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Used to implement fast login (using a predefined hash)

CREATE TABLE `tquicksession` (
  `id` int(20) unsigned NOT NULL auto_increment,
  `id_user` varchar(250) NOT NULL default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `pwdhash` varchar(250) NOT NULL default '',
  PRIMARY KEY  (`id`)
);

CREATE TABLE `tserver_export_data` (
  `id` int(20) unsigned NOT NULL auto_increment,
  `id_export_server` int(10) unsigned default NULL,
  `agent` varchar(100) NOT NULL default '',
  `type` varchar(50) NOT NULL default '',
  `module` varchar(100) NOT NULL default '',
  `value` varchar(100) NOT NULL default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

