
CREATE TABLE `estado_consola` (
  `id_usuario` varchar(50) NOT NULL,
  `idPerfilActivo` int(5) NOT NULL,
  `idVistaActiva` int(5) NOT NULL,
  `menuX` int(5) NOT NULL,
  `menuY` int(5) NOT NULL,
  PRIMARY KEY  (`id_usuario`)
) ENGINE=MyISAM ;


CREATE TABLE `objeto_consola` (
  `id_objeto` int(5) NOT NULL auto_increment,
  `nom_img` varchar(50) NOT NULL,
  `tipo` varchar(2) NOT NULL,
  `left` int(5) NOT NULL,
  `top` int(5) NOT NULL,
  `id_tipo` varchar(20) NOT NULL,
  `idVista` int(5) NOT NULL,
  PRIMARY KEY  (`id_objeto`)
) ENGINE=MyISAM;


CREATE TABLE `perfil` (
  `idPerfil` int(5) NOT NULL auto_increment,
  `Nombre` varchar(50) NOT NULL,
  `Descripcion` varchar(250) NOT NULL,
  PRIMARY KEY  (`idPerfil`)
) ENGINE=MyISAM;

CREATE TABLE `perfil_vista` (
  `idPerfil` int(5) NOT NULL,
  `idVista` int(5) NOT NULL,
  `activa` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`idPerfil`,`idVista`)
) ENGINE=MyISAM ;

CREATE TABLE `relacion_estado` (
  `id_objeto` int(5) NOT NULL,
  `relacion` varchar(50) NOT NULL,
  PRIMARY KEY  (`id_objeto`)
) ENGINE=MyISAM ;

CREATE TABLE `relacion_objetos` (
  `idObjeto1` int(5) NOT NULL,
  `idObjeto2` int(5) NOT NULL,
  PRIMARY KEY  (`idObjeto1`,`idObjeto2`)
) ENGINE=MyISAM ;

CREATE TABLE `vistas_consola` (
  `idVista` int(5) NOT NULL auto_increment,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(250) NOT NULL,
  PRIMARY KEY  (`idVista`)
) ENGINE=MyISAM;
