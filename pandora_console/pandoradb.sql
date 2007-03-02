/*
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006-2007 Jose Navarro jose@jnavarro.net
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
Create Database if not exists
*/
create database if not exists `pandora`;
USE `pandora`;

# Database: pandora
# Table: 'tagent_access'
# 
CREATE TABLE `tagent_access` (
  `id_ac` bigint(20) unsigned NOT NULL auto_increment,
  `id_agent` int(8) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `utimestamp` integer unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_ac`),
  KEY `agent_index` (`id_agent`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tagente'
# 
CREATE TABLE `tagente` (
  `id_agente` int(8) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `direccion` varchar(100) default '',
  `comentarios` varchar(255) default '',
  `id_grupo` int(8) unsigned NOT NULL default '0',
  `ultimo_contacto` datetime NOT NULL default '0000-00-00 00:00:00',
  `modo` tinyint(1) unsigned NOT NULL default '0',
  `intervalo` int(8) unsigned NOT NULL default '300',
  `id_os` tinyint(4) unsigned default '0',
  `os_version` varchar(100) default '',
  `agent_version` varchar(100) default '',
  `ultimo_contacto_remoto` datetime default '0000-00-00 00:00:00',
  `disabled` tinyint(2) unsigned NOT NULL default '0',
  `agent_type` tinyint(2) unsigned NOT NULL default '0',
  `id_server` int(4) unsigned default '0',
  PRIMARY KEY  (`id_agente`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tagente_datos'
# 
CREATE TABLE `tagente_datos` (
  `id_agente_datos` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(10) unsigned NOT NULL default '0',
  `datos` double(18,2) default NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_agente` mediumint(8) unsigned NOT NULL default '0',
  `utimestamp` integer unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_agente_datos`),
  KEY `data_index_1` (`id_agente_modulo`, `id_agente` ),
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tagente_datos_inc'
# 
CREATE TABLE `tagente_datos_inc` (
  `id_adi` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente_modulo` mediumint(8) unsigned NOT NULL default '0',
  `datos` double(18,2) default NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id_adi`),
  KEY `data_inc_index_1` (`id_agente_modulo`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tagente_datos_string'
# 
CREATE TABLE `tagente_datos_string` (
  `id_tagente_datos_string` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(10) unsigned NOT NULL default '0',
  `datos` tinytext NOT NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_agente` mediumint(8) unsigned NOT NULL default '0',
  `utimestamp` integer unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_tagente_datos_string`),
  KEY `data_index_1` (`id_agente_modulo`, `id_agente` ),
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tagente_estado'
# 
CREATE TABLE `tagente_estado` (
  `id_agente_estado` int(10) unsigned NOT NULL auto_increment,
  `id_agente_modulo` mediumint(8) unsigned NOT NULL default '0',
  `datos` varchar(255) NOT NULL default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `cambio` tinyint(2) unsigned NOT NULL default '0',
  `estado` tinyint(2) unsigned NOT NULL default '0',
  `id_agente` mediumint(8) unsigned NOT NULL default '0',
  `last_try` datetime default NULL,
  `utimestamp` integer unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_agente_estado`),
  KEY `status_index_2` (`id_agente_modulo`,`estado`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tagente_modulo'
# 
CREATE TABLE `tagente_modulo` (
  `id_agente_modulo` int(10) unsigned NOT NULL auto_increment,
  `id_agente` int(10) unsigned NOT NULL default '0',
  `id_tipo_modulo` int(10) unsigned NOT NULL default '0',
  `descripcion` varchar(100) NOT NULL default '',
  `nombre` varchar(100) NOT NULL default '',
  `max` int(10) default '0',
  `min` int(10) default '0',
  `module_interval` int(8) unsigned default '0',
  `tcp_port` int(5) unsigned default '0',
  `tcp_send` varchar(150) default '',
  `tcp_rcv` varchar(100) default '',
  `snmp_community` varchar(100) default '',
  `snmp_oid` varchar(255) default '0',
  `ip_target` varchar(100) default '',
  `id_module_group` int(4) unsigned default '0',
  `flag` tinyint(3) unsigned default '0',
  PRIMARY KEY  (`id_agente_modulo`),
  KEY `tam_agente` (`id_agente`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'talert_snmp'
# 
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
  `time_threshold` int(10) unsigned NOT NULL default '0',
  `times_fired` int(2) unsigned NOT NULL default '0',
  `last_fired` datetime NOT NULL default '0000-00-00 00:00:00',
  `max_alerts` tinyint(4) unsigned NOT NULL default '1',
  `min_alerts` tinyint(4) unsigned NOT NULL default '1',
  `internal_counter` int(2) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_as`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'talerta'
# 
CREATE TABLE `talerta` (
  `id_alerta` int(10) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `comando` varchar(100) default '',
  `descripcion` varchar(255) default '',
  PRIMARY KEY  (`id_alerta`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'talerta_agente_modulo'
# 

CREATE TABLE `talerta_agente_modulo` (
  `id_aam` int(10) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(10) unsigned NOT NULL default '0',
  `id_alerta` int(10) unsigned NOT NULL default '0',
  `al_campo1` varchar(255) default '',
  `al_campo2` varchar(255) default '',
  `al_campo3` mediumtext,
  `descripcion` varchar(255) default '',
  `dis_max` int(8) default NULL,
  `dis_min` int(8) default NULL,
  `time_threshold` int(8) NOT NULL default '0',
  `last_fired` datetime NOT NULL default '0000-00-00 00:00:00',
  `max_alerts` tinyint(4) unsigned NOT NULL default '1',
  `times_fired` tinyint(4) unsigned NOT NULL default '0',
  `module_type` tinyint(4) unsigned NOT NULL default '0',
  `min_alerts` tinyint(4) unsigned NOT NULL default '0',
  `internal_counter` tinyint(4) unsigned default '0',
  PRIMARY KEY  (`id_aam`)
) TYPE=InnoDB;

# Database: pandora
# Table: 'tattachment'
# 
CREATE TABLE `tattachment` (
  `id_attachment` int(10) unsigned NOT NULL auto_increment,
  `id_incidencia` int(10) unsigned NOT NULL default '0',
  `id_usuario` varchar(60) NOT NULL default '',
  `filename` varchar(255) NOT NULL default '',
  `description` varchar(150) default '',
  `size` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_attachment`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tconfig'
# 
CREATE TABLE `tconfig` (
  `id_config` int(10) unsigned NOT NULL auto_increment,
  `token` varchar(100) NOT NULL default '',
  `value` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_config`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tconfig_os'
# 
CREATE TABLE `tconfig_os` (
  `id_os` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) default '',
  `icon_name` varchar(100) default '',
  PRIMARY KEY  (`id_os`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tevento'
# 
CREATE TABLE `tevento` (
  `id_evento` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente` int(10) unsigned NOT NULL default '0',
  `id_usuario` varchar(60) NOT NULL default '0',
  `id_grupo` int(10) unsigned NOT NULL default '0',
  `estado` tinyint(4) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `evento` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_evento`),
  KEY `indice_1` (`id_agente`),
  KEY `indice_2` (`id_grupo`),
  KEY `indice_3` (`id_usuario`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tgrupo'
# 
CREATE TABLE `tgrupo` (
  `id_grupo` mediumint(8) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `icon` varchar(50) default NULL,
  PRIMARY KEY  (`id_grupo`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tincidencia'
# 
CREATE TABLE `tincidencia` (
  `id_incidencia` int(10) unsigned NOT NULL auto_increment,
  `inicio` datetime NOT NULL default '0000-00-00 00:00:00',
  `cierre` datetime NOT NULL default '0000-00-00 00:00:00',
  `titulo` varchar(100) NOT NULL default '',
  `descripcion` mediumtext NOT NULL,
  `id_usuario` varchar(100) NOT NULL default '',
  `origen` varchar(100) NOT NULL default '',
  `estado` tinyint(4) unsigned NOT NULL default '0',
  `prioridad` tinyint(4) unsigned NOT NULL default '0',
  `id_grupo` int(10) unsigned NOT NULL default '0',
  `actualizacion` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_creator` varchar(60) default NULL,
  PRIMARY KEY  (`id_incidencia`),
  KEY `incident_index_1` (`id_usuario`,`id_incidencia`)
  KEY `indident_index_2` (`id_grupo`),
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tlanguage'
# 
CREATE TABLE `tlanguage` (
  `id_language` char(6) NOT NULL default '',
  `name` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id_language`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tlink'
# 
CREATE TABLE `tlink` (
  `id_link` int(10) unsigned zerofill NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `link` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_link`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tmensajes'
# 
CREATE TABLE `tmensajes` (
  `id_mensaje` int(10) unsigned NOT NULL auto_increment,
  `id_usuario_origen` varchar(100) NOT NULL default '',
  `id_usuario_destino` varchar(100) NOT NULL default '',
  `mensaje` tinytext NOT NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `subject` varchar(255) NOT NULL default '',
  `estado` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_mensaje`),
  KEY `msg_index_1` (`id_usuario_origen`),
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tmodule_group'
# 
CREATE TABLE `tmodule_group` (
  `id_mg` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  PRIMARY KEY  (`id_mg`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tnota'
# 
CREATE TABLE `tnota` (
  `id_nota` mediumint(8) unsigned NOT NULL auto_increment,
  `id_usuario` varchar(100) NOT NULL default '0',
  `timestamp` tinyblob NOT NULL,
  `nota` mediumtext NOT NULL,
  PRIMARY KEY  (`id_nota`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tnota_inc'
# 
CREATE TABLE `tnota_inc` (
  `id_nota_inc` mediumint(8) unsigned NOT NULL auto_increment,
  `id_incidencia` mediumint(8) unsigned  NOT NULL default '0',
  `id_nota` mediumint(8) unsigned  NOT NULL default '0',
  PRIMARY KEY  (`id_nota_inc`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'torigen'
# 
CREATE TABLE `torigen` (
  `origen` varchar(100) NOT NULL default ''
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tperfil'
# 
CREATE TABLE `tperfil` (
  `id_perfil` mediumint(8) unsigned  NOT NULL auto_increment,
  `name` varchar(60) NOT NULL default '',
  `incident_edit` tinyint(3) unsigned  NOT NULL default '0',
  `incident_view` tinyint(3) unsigned  NOT NULL default '0',
  `incident_management` tinyint(3) unsigned  NOT NULL default '0',
  `agent_view` tinyint(3) unsigned  NOT NULL default '0',
  `agent_edit` tinyint(3) unsigned  NOT NULL default '0',
  `alert_edit` tinyint(3) unsigned  NOT NULL default '0',
  `user_management` tinyint(3) unsigned  NOT NULL default '0',
  `db_management` tinyint(3) unsigned  NOT NULL default '0',
  `alert_management` tinyint(3) unsigned  NOT NULL default '0',
  `pandora_management` tinyint(3) unsigned  NOT NULL default '0',
  PRIMARY KEY  (`id_perfil`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tserver'
# 
CREATE TABLE `tserver` (
  `id_server` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `ip_address` varchar(100) NOT NULL default '',
  `status` tinyint(3) unsigned  NOT NULL default '0',
  `laststart` datetime NOT NULL default '0000-00-00 00:00:00',
  `keepalive` datetime NOT NULL default '0000-00-00 00:00:00',
  `snmp_server` tinyint(3) unsigned  NOT NULL default '1',
  `network_server` tinyint(3) unsigned  NOT NULL default '0',
  `data_server` tinyint(3) unsigned  NOT NULL default '0',
  `master` tinyint(3) unsigned  NOT NULL default '1',
  `checksum` tinyint(3) unsigned  NOT NULL default '1',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_server`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tsesion'
# 
CREATE TABLE `tsesion` (
  `ID_sesion` bigint(4) unsigned NOT NULL auto_increment,
  `ID_usuario` varchar(60) NOT NULL default '0',
  `IP_origen` varchar(100) NOT NULL default '',
  `accion` varchar(100) NOT NULL default '',
  `descripcion` varchar(200) NOT NULL default '',
  `fecha` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`ID_sesion`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'ttipo_modulo'
# 
CREATE TABLE `ttipo_modulo` (
  `id_tipo` smallint(5) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `categoria` tinyint(3) unsigned  NOT NULL default '0',
  `descripcion` varchar(100) NOT NULL default '',
  `icon` varchar(100) default NULL,
  PRIMARY KEY  (`id_tipo`)
) TYPE=InnoDB; 

# Database: pandora
# Table: 'ttrap'
# 
CREATE TABLE `ttrap` (
  `id_trap` int(10) unsigned NOT NULL auto_increment,
  `source` varchar(50) NOT NULL default '',
  `oid` varchar(255) NOT NULL default '',
  `oid_custom` varchar(255) default '',
  `type` tinyint(3) unsigned  NOT NULL default '0',
  `type_custom` varchar(100) default '',
  `value` varchar(255) default '',
  `value_custom` varchar(255) default '',
  `alerted` tinyint(3) unsigned  NOT NULL default '0',
  `status`tinyint(3) unsigned  NOT NULL default '0',
  `id_usuario` varchar(150) default '',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `utimestamp` integer unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_trap`)
) TYPE=InnoDB COMMENT='SNMP Trap table'; 

# Database: pandora
# Table: 'tusuario'
# 
CREATE TABLE `tusuario` (
  `id_usuario` varchar(60) NOT NULL default '0',
  `nombre_real` varchar(125) NOT NULL default '',
  `password` varchar(45) default NULL,
  `comentarios` varchar(200) default NULL,
  `fecha_registro` datetime NOT NULL default '0000-00-00 00:00:00',
  `direccion` varchar(100) default '',
  `telefono` varchar(100) default '',
  `nivel` tinyint(1) NOT NULL default '0'
) TYPE=InnoDB; 

# Database: pandora
# Table: 'tusuario_perfil'
# 
CREATE TABLE `tusuario_perfil` (
  `id_up` int(10) unsigned NOT NULL auto_increment,
  `id_usuario` varchar(100) NOT NULL default '',
  `id_perfil` tinyint(3) unsigned  NOT NULL default '0',
  `id_grupo` tinyint(3) unsigned  NOT NULL default '0',
  `assigned_by` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_up`)
) TYPE=InnoDB; 

