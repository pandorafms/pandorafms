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
-- Dumping data for table `estado_consola`
--


/*!40000 ALTER TABLE `estado_consola` DISABLE KEYS */;
LOCK TABLES `estado_consola` WRITE;
INSERT INTO `estado_consola` VALUES ('admin',1,1,100,100);
UNLOCK TABLES;
/*!40000 ALTER TABLE `estado_consola` ENABLE KEYS */;

--
-- Dumping data for table `objeto_consola`
--


/*!40000 ALTER TABLE `objeto_consola` DISABLE KEYS */;
LOCK TABLES `objeto_consola` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `objeto_consola` ENABLE KEYS */;

--
-- Dumping data for table `perfil`
--


/*!40000 ALTER TABLE `perfil` DISABLE KEYS */;
LOCK TABLES `perfil` WRITE;
INSERT INTO `perfil` VALUES (1,'perfil por defecto','perfil por defecto');
UNLOCK TABLES;
/*!40000 ALTER TABLE `perfil` ENABLE KEYS */;

--
-- Dumping data for table `perfil_vista`
--


/*!40000 ALTER TABLE `perfil_vista` DISABLE KEYS */;
LOCK TABLES `perfil_vista` WRITE;
INSERT INTO `perfil_vista` VALUES (1,1,1);
UNLOCK TABLES;
/*!40000 ALTER TABLE `perfil_vista` ENABLE KEYS */;

--
-- Dumping data for table `relacion_estado`
--


/*!40000 ALTER TABLE `relacion_estado` DISABLE KEYS */;
LOCK TABLES `relacion_estado` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `relacion_estado` ENABLE KEYS */;

--
-- Dumping data for table `relacion_objetos`
--


/*!40000 ALTER TABLE `relacion_objetos` DISABLE KEYS */;
LOCK TABLES `relacion_objetos` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `relacion_objetos` ENABLE KEYS */;

--
-- Dumping data for table `vistas_consola`
--


/*!40000 ALTER TABLE `vistas_consola` DISABLE KEYS */;
LOCK TABLES `vistas_consola` WRITE;
INSERT INTO `vistas_consola` VALUES (1,'Main Board','');
UNLOCK TABLES;
/*!40000 ALTER TABLE `vistas_consola` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

