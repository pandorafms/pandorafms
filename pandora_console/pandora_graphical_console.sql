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
) ENGINE=MyISAM ;

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
) ENGINE=MyISAM AUTO_INCREMENT=2 ;

--
-- Table structure for table `perfil`
--

DROP TABLE IF EXISTS `perfil`;
CREATE TABLE `perfil` (
  `idPerfil` int(5) NOT NULL auto_increment,
  `Nombre` varchar(50) NOT NULL,
  `Descripcion` varchar(250) NOT NULL,
  PRIMARY KEY  (`idPerfil`)
) ENGINE=MyISAM AUTO_INCREMENT=2 ;

--
-- Table structure for table `perfil_vista`
--

DROP TABLE IF EXISTS `perfil_vista`;
CREATE TABLE `perfil_vista` (
  `idPerfil` int(5) NOT NULL,
  `idVista` int(5) NOT NULL,
  `activa` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`idPerfil`,`idVista`)
) ENGINE=MyISAM ;

--
-- Table structure for table `relacion_estado`
--

DROP TABLE IF EXISTS `relacion_estado`;
CREATE TABLE `relacion_estado` (
  `id_objeto` int(5) NOT NULL,
  `relacion` varchar(50) NOT NULL,
  PRIMARY KEY  (`id_objeto`)
) ENGINE=MyISAM ;

--
-- Table structure for table `relacion_objetos`
--

DROP TABLE IF EXISTS `relacion_objetos`;
CREATE TABLE `relacion_objetos` (
  `idObjeto1` int(5) NOT NULL,
  `idObjeto2` int(5) NOT NULL,
  PRIMARY KEY  (`idObjeto1`,`idObjeto2`)
) ENGINE=MyISAM ;

--
-- Table structure for table `vistas_consola`
--

DROP TABLE IF EXISTS `vistas_consola`;
CREATE TABLE `vistas_consola` (
  `idVista` int(5) NOT NULL auto_increment,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(250) NOT NULL,
  PRIMARY KEY  (`idVista`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

