-- MySQL dump 10.10
--
-- Host: localhost    Database: pandora
-- ------------------------------------------------------
-- Server version	5.0.24a-Debian_9-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `estado_consola`
--

DROP TABLE IF EXISTS `estado_consola`;
CREATE TABLE `estado_consola` (
  `id_usuario` varchar(50) NOT NULL,
  `idPerfilActivo` int(5) NOT NULL,
  `idVistaActiva` int(5) NOT NULL,
  `menuX` int(5) NOT NULL,
  `menuY` int(5) NOT NULL,
  PRIMARY KEY  (`id_usuario`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `objeto_consola`
--

DROP TABLE IF EXISTS `objeto_consola`;
CREATE TABLE `objeto_consola` (
  `id_objeto` int(5) NOT NULL auto_increment,
  `nom_img` varchar(50) NOT NULL,
  `tipo` varchar(2) NOT NULL,
  `left` int(5) NOT NULL,
  `top` int(5) NOT NULL,
  `id_tipo` varchar(20) NOT NULL,
  `idVista` int(5) NOT NULL,
  PRIMARY KEY  (`id_objeto`)
) ENGINE=MyISAM AUTO_INCREMENT=37 DEFAULT CHARSET=latin1;

--
-- Table structure for table `perfil`
--

DROP TABLE IF EXISTS `perfil`;
CREATE TABLE `perfil` (
  `idPerfil` int(5) NOT NULL auto_increment,
  `Nombre` varchar(50) NOT NULL,
  `Descripcion` varchar(250) NOT NULL,
  PRIMARY KEY  (`idPerfil`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Table structure for table `perfil_vista`
--

DROP TABLE IF EXISTS `perfil_vista`;
CREATE TABLE `perfil_vista` (
  `idPerfil` int(5) NOT NULL,
  `idVista` int(5) NOT NULL,
  `activa` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`idPerfil`,`idVista`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `relacion_estado`
--

DROP TABLE IF EXISTS `relacion_estado`;
CREATE TABLE `relacion_estado` (
  `id_objeto` int(5) NOT NULL,
  `relacion` varchar(50) NOT NULL,
  PRIMARY KEY  (`id_objeto`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `relacion_objetos`
--

DROP TABLE IF EXISTS `relacion_objetos`;
CREATE TABLE `relacion_objetos` (
  `idObjeto1` int(5) NOT NULL,
  `idObjeto2` int(5) NOT NULL,
  PRIMARY KEY  (`idObjeto1`,`idObjeto2`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `taddress`
--

DROP TABLE IF EXISTS `taddress`;
CREATE TABLE `taddress` (
  `id_a` bigint(20) unsigned NOT NULL auto_increment,
  `ip` varchar(15) NOT NULL default '',
  `ip_pack` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_a`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `taddress_agent`
--

DROP TABLE IF EXISTS `taddress_agent`;
CREATE TABLE `taddress_agent` (
  `id_ag` bigint(20) unsigned NOT NULL auto_increment,
  `id_a` bigint(20) unsigned NOT NULL default '0',
  `id_agent` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_ag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tagent_access`
--

DROP TABLE IF EXISTS `tagent_access`;
CREATE TABLE `tagent_access` (
  `id_ac` bigint(20) unsigned NOT NULL auto_increment,
  `id_agent` int(11) NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `utimestamp` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id_ac`),
  KEY `agent_index` (`id_agent`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tagente`
--

DROP TABLE IF EXISTS `tagente`;
CREATE TABLE `tagente` (
  `id_agente` mediumint(8) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `direccion` varchar(100) default '',
  `comentarios` varchar(255) default '',
  `id_grupo` int(10) unsigned NOT NULL default '0',
  `ultimo_contacto` datetime NOT NULL default '2004-01-01 00:00:00',
  `modo` tinyint(1) NOT NULL default '0',
  `intervalo` int(11) NOT NULL default '0',
  `id_os` tinyint(3) unsigned default '0',
  `os_version` varchar(100) default '',
  `agent_version` varchar(100) default '',
  `ultimo_contacto_remoto` datetime default '0000-00-00 00:00:00',
  `disabled` tinyint(2) NOT NULL default '0',
  `agent_type` int(2) unsigned NOT NULL default '0',
  `id_server` int(10) unsigned default '0',
  PRIMARY KEY  (`id_agente`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tagente_datos`
--

DROP TABLE IF EXISTS `tagente_datos`;
CREATE TABLE `tagente_datos` (
  `id_agente_datos` bigint(10) unsigned NOT NULL auto_increment,
  `id_agente_modulo` mediumint(8) unsigned NOT NULL default '0',
  `datos` double(18,2) default NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_agente` mediumint(8) unsigned NOT NULL default '0',
  `utimestamp` int(10) unsigned default '0',
  PRIMARY KEY  (`id_agente_datos`),
  KEY `data_index2` (`id_agente`,`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tagente_datos_inc`
--

DROP TABLE IF EXISTS `tagente_datos_inc`;
CREATE TABLE `tagente_datos_inc` (
  `id_adi` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente_modulo` bigint(20) NOT NULL default '0',
  `datos` bigint(12) default NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id_adi`),
  KEY `data_inc_index_1` (`id_agente_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tagente_datos_string`
--

DROP TABLE IF EXISTS `tagente_datos_string`;
CREATE TABLE `tagente_datos_string` (
  `id_tagente_datos_string` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(11) NOT NULL default '0',
  `datos` tinytext NOT NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_agente` bigint(4) unsigned NOT NULL default '0',
  `utimestamp` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id_tagente_datos_string`),
  KEY `data_string_index_1` (`id_agente_modulo`),
  KEY `data_string_index_2` (`id_agente`),
  KEY `data_string_index_3` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tagente_estado`
--

DROP TABLE IF EXISTS `tagente_estado`;
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
  `inc_lastrealvalue` double(18,2) NOT NULL default '0.00',
  `current_interval` int(10) unsigned NOT NULL default '0',
  `processed_by_server` varchar(100) default NULL,
  PRIMARY KEY  (`id_agente_estado`),
  KEY `status_index_1` (`id_agente_modulo`),
  KEY `status_index_2` (`id_agente_modulo`,`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tagente_modulo`
--

DROP TABLE IF EXISTS `tagente_modulo`;
CREATE TABLE `tagente_modulo` (
  `id_agente_modulo` bigint(100) unsigned NOT NULL auto_increment,
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
  `flag` tinyint(3) unsigned default '0',
  PRIMARY KEY  (`id_agente_modulo`),
  KEY `tam_agente` (`id_agente`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `talert_snmp`
--

DROP TABLE IF EXISTS `talert_snmp`;
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
  `last_fired` datetime NOT NULL default '2005-01-01 00:00:00',
  `max_alerts` int(11) NOT NULL default '1',
  `min_alerts` int(11) NOT NULL default '1',
  `internal_counter` int(2) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_as`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `talerta`
--

DROP TABLE IF EXISTS `talerta`;
CREATE TABLE `talerta` (
  `id_alerta` int(10) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `comando` varchar(100) default '',
  `descripcion` varchar(255) default '',
  PRIMARY KEY  (`id_alerta`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `talerta_agente_modulo`
--

DROP TABLE IF EXISTS `talerta_agente_modulo`;
CREATE TABLE `talerta_agente_modulo` (
  `id_aam` int(11) unsigned NOT NULL auto_increment,
  `id_agente_modulo` int(11) NOT NULL default '0',
  `id_alerta` int(11) NOT NULL default '0',
  `al_campo1` varchar(255) default '',
  `al_campo2` varchar(255) default '',
  `al_campo3` mediumtext,
  `descripcion` varchar(255) default '',
  `dis_max` bigint(12) default NULL,
  `dis_min` bigint(12) default NULL,
  `time_threshold` int(11) NOT NULL default '0',
  `last_fired` datetime NOT NULL default '2001-01-01 00:00:00',
  `max_alerts` int(4) NOT NULL default '1',
  `times_fired` int(11) NOT NULL default '0',
  `module_type` int(11) NOT NULL default '0',
  `min_alerts` int(4) NOT NULL default '0',
  `internal_counter` int(4) default '0',
  PRIMARY KEY  (`id_aam`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tattachment`
--

DROP TABLE IF EXISTS `tattachment`;
CREATE TABLE `tattachment` (
  `id_attachment` bigint(20) unsigned NOT NULL auto_increment,
  `id_incidencia` bigint(20) NOT NULL default '0',
  `id_usuario` varchar(60) NOT NULL default '',
  `filename` varchar(255) NOT NULL default '',
  `description` varchar(150) default '',
  `size` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id_attachment`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tconfig`
--

DROP TABLE IF EXISTS `tconfig`;
CREATE TABLE `tconfig` (
  `id_config` int(10) unsigned NOT NULL auto_increment,
  `token` varchar(100) NOT NULL default '',
  `value` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_config`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tconfig_os`
--

DROP TABLE IF EXISTS `tconfig_os`;
CREATE TABLE `tconfig_os` (
  `id_os` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) default '',
  `icon_name` varchar(100) default '',
  PRIMARY KEY  (`id_os`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tevento`
--

DROP TABLE IF EXISTS `tevento`;
CREATE TABLE `tevento` (
  `id_evento` bigint(20) unsigned NOT NULL auto_increment,
  `id_agente` bigint(20) NOT NULL default '0',
  `id_usuario` varchar(60) NOT NULL default '0',
  `id_grupo` bigint(20) NOT NULL default '0',
  `estado` int(10) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `evento` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_evento`),
  KEY `indice_1` (`id_agente`,`id_evento`),
  KEY `indice_2` (`timestamp`,`id_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tgrupo`
--

DROP TABLE IF EXISTS `tgrupo`;
CREATE TABLE `tgrupo` (
  `id_grupo` mediumint(8) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `icon` varchar(50) default NULL,
  `parent` tinyint(4) NOT NULL default '-1',
  PRIMARY KEY  (`id_grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tincidencia`
--

DROP TABLE IF EXISTS `tincidencia`;
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
  PRIMARY KEY  (`id_incidencia`),
  KEY `incident_index_1` (`id_usuario`,`id_incidencia`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tlanguage`
--

DROP TABLE IF EXISTS `tlanguage`;
CREATE TABLE `tlanguage` (
  `id_language` varchar(5) NOT NULL default '',
  `name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_language`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tlink`
--

DROP TABLE IF EXISTS `tlink`;
CREATE TABLE `tlink` (
  `id_link` int(10) unsigned zerofill NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `link` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_link`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tmensajes`
--

DROP TABLE IF EXISTS `tmensajes`;
CREATE TABLE `tmensajes` (
  `id_mensaje` bigint(20) unsigned NOT NULL auto_increment,
  `id_usuario_origen` varchar(100) NOT NULL default '',
  `id_usuario_destino` varchar(100) NOT NULL default '',
  `mensaje` tinytext NOT NULL,
  `timestamp` datetime NOT NULL default '2005-01-01 00:00:00',
  `subject` varchar(255) NOT NULL default '',
  `estado` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_mensaje`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tmodule_group`
--

DROP TABLE IF EXISTS `tmodule_group`;
CREATE TABLE `tmodule_group` (
  `id_mg` bigint(20) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL default '',
  PRIMARY KEY  (`id_mg`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tnetwork_component`
--

DROP TABLE IF EXISTS `tnetwork_component`;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tnetwork_component_group`
--

DROP TABLE IF EXISTS `tnetwork_component_group`;
CREATE TABLE `tnetwork_component_group` (
  `id_sg` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(200) NOT NULL default '',
  `parent` mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (`id_sg`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tnetwork_profile`
--

DROP TABLE IF EXISTS `tnetwork_profile`;
CREATE TABLE `tnetwork_profile` (
  `id_np` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) default '',
  PRIMARY KEY  (`id_np`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tnetwork_profile_component`
--

DROP TABLE IF EXISTS `tnetwork_profile_component`;
CREATE TABLE `tnetwork_profile_component` (
  `id_npc` mediumint(8) unsigned NOT NULL auto_increment,
  `id_nc` mediumint(8) unsigned NOT NULL default '0',
  `id_np` mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_npc`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tnota`
--

DROP TABLE IF EXISTS `tnota`;
CREATE TABLE `tnota` (
  `id_nota` mediumint(8) unsigned NOT NULL auto_increment,
  `id_usuario` varchar(100) NOT NULL default '0',
  `timestamp` tinyblob NOT NULL,
  `nota` mediumtext NOT NULL,
  PRIMARY KEY  (`id_nota`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tnota_inc`
--

DROP TABLE IF EXISTS `tnota_inc`;
CREATE TABLE `tnota_inc` (
  `id_nota_inc` mediumint(8) unsigned NOT NULL auto_increment,
  `id_incidencia` mediumint(9) NOT NULL default '0',
  `id_nota` mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (`id_nota_inc`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `torigen`
--

DROP TABLE IF EXISTS `torigen`;
CREATE TABLE `torigen` (
  `origen` varchar(100) NOT NULL default ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tperfil`
--

DROP TABLE IF EXISTS `tperfil`;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `trecon_task`
--

DROP TABLE IF EXISTS `trecon_task`;
CREATE TABLE `trecon_task` (
  `id_rt` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` varchar(250) NOT NULL default '',
  `type` tinyint(3) unsigned NOT NULL default '0',
  `subnet` varchar(20) NOT NULL default '',
  `id_network_server` int(10) unsigned NOT NULL default '0',
  `id_network_profile` int(10) unsigned NOT NULL default '0',
  `create_incident` tinyint(3) unsigned NOT NULL default '0',
  `id_group` int(10) unsigned NOT NULL default '1',
  `utimestamp` bigint(20) unsigned NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '0',
  `interval` int(10) unsigned NOT NULL default '1440',
  PRIMARY KEY  (`id_rt`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tserver`
--

DROP TABLE IF EXISTS `tserver`;
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
  PRIMARY KEY  (`id_server`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tsesion`
--

DROP TABLE IF EXISTS `tsesion`;
CREATE TABLE `tsesion` (
  `ID_sesion` bigint(4) unsigned NOT NULL auto_increment,
  `ID_usuario` varchar(60) NOT NULL default '0',
  `IP_origen` varchar(100) NOT NULL default '',
  `accion` varchar(100) NOT NULL default '',
  `descripcion` varchar(200) NOT NULL default '',
  `fecha` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`ID_sesion`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `ttipo_modulo`
--

DROP TABLE IF EXISTS `ttipo_modulo`;
CREATE TABLE `ttipo_modulo` (
  `id_tipo` smallint(5) unsigned NOT NULL auto_increment,
  `nombre` varchar(100) NOT NULL default '',
  `categoria` int(11) NOT NULL default '0',
  `descripcion` varchar(100) NOT NULL default '',
  `icon` varchar(100) default NULL,
  PRIMARY KEY  (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `ttrap`
--

DROP TABLE IF EXISTS `ttrap`;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='SNMP Trap table';

--
-- Table structure for table `tusuario`
--

DROP TABLE IF EXISTS `tusuario`;
CREATE TABLE `tusuario` (
  `id_usuario` varchar(60) NOT NULL default '0',
  `nombre_real` varchar(125) NOT NULL default '',
  `password` varchar(45) default NULL,
  `comentarios` varchar(200) default NULL,
  `fecha_registro` datetime NOT NULL default '0000-00-00 00:00:00',
  `direccion` varchar(100) default '',
  `telefono` varchar(100) default '',
  `nivel` tinyint(1) NOT NULL default '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tusuario_perfil`
--

DROP TABLE IF EXISTS `tusuario_perfil`;
CREATE TABLE `tusuario_perfil` (
  `id_up` bigint(20) unsigned NOT NULL auto_increment,
  `id_usuario` varchar(100) NOT NULL default '',
  `id_perfil` int(20) NOT NULL default '0',
  `id_grupo` int(11) NOT NULL default '0',
  `assigned_by` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`id_up`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `vistas_consola`
--

DROP TABLE IF EXISTS `vistas_consola`;
CREATE TABLE `vistas_consola` (
  `idVista` int(5) NOT NULL auto_increment,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(250) NOT NULL,
  PRIMARY KEY  (`idVista`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

