<?php
// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnológicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
?>
<html>
<head>
<title>Pandora - Sistema de monitorizaci&oacute;n de Software Libre - Ayuda - VII. Configuraci&oacute;n de Servidor</title>
<link rel="stylesheet" href="../../include/styles/help.css" type="text/css">
</head>

<body>

<div class='logo'>
<img src="../../images/logo_menu.gif" alt='logo'><h1>Ayuda de Pandora FMS 1.3</h1>
</div>
<div class="toc">
<h1><a href="chap6.php">6. Auditor&iacute;a del Sistema</a> « <a href="toc.php">&Iacute;ndice</a> » <a href="chap8.php">8. Mantenimiento de la Base de Datos</a></h1>

</div>
<div class="rayah2"></div>

<h1><a name="7">7. Servidores Pandora</a></h1>

<p>En Pandora FMS 1.3 hay tres tipos diferentes de servidores, Servidor de red, 
Servidor de datos y servidor SNMP.</p>

<p>Desde «Servidores Pandora» en el men&uacute; de operaci&oacute;n se puede
acceder a la lista de los Servidores Pandora.</p>

<p class="center"><img src="images/servers1.png"></p>
<p>Se muestran los siguientes campos:</p>
  <ul>
  <li>
   <b>Nombre:</b> Nombre del servidor.
  </li>
  <li>
    <b>Estado:</b> Estado del servidor. Si es verde todo est&aacute; correto,
	si est&aacute; rojo, hay fallos.
  </li>
  <li>
    <b>Direcci&oacute;n IP:</b> Direcci&oacute;n IP del Servidor.
  </li>
  <li>
    <b>Descripci&oacute;n:</b> Descripci&oacute;n del Servidor.
  </li>
  <li>
    <b>Red:</b> Marca el servidor de red.
  </li>
  <li>
    <b>Datos:</b> Marca el servidor de datos.
  </li>
  <li>
    <b>SNMP:</b> Marca el servidor SNMP.
  </li>
  <li>
    <b>Principal:</b> Marcado cuando el servidor es el <i>Master</i>
	y no marcado cuando es de <i>backup</i>.
  </li>
  <li>
    <b>Check:</b>
  </li>
  <li>
    <b>Arrancado el:</b> Fecha y hora de inicio del servidor.
  </li>
  <li>
    <b>Actualizado el:</b> Fecha y hora de la &uacute;ltima actualizaci&oacute;n.
  </li>
  <li>
     <b>Acci&oacute;n:</b> Iconos para modificar las propiedades del servidor o
	 borrarlo (esta opci&oacute;n s&oacute;lo aparece en el men&uacute; de 
	 administraci&oacute;n).
  </li>
  </ul>
     <p>Desde «Gesti&oacute;n» en el men&uacute; de administraci&oacute;n se puede acceder a la lista de los Servidores Pandora que est&aacute;n configurados y administrarlos.</p>
  <p>
   Para borrar un servidor utilizamos el icono
   <img src="../../images/cross.png">
   </p>
   <p>
   Para modificar un servidor utilizamos el icono 
	<img src="../../images/config.gif"> 
   </p>
   <p>
    Si estamos modificando un servidor podemos cambiar las siguientes 
	propiedades: Nombre, direcci&oacute;n IP y descripci&oacute;n.
   </p>
<p class="center"><img src="images/servers2.png"></p>

<div class="rayah">
<p align='right'>Pandora es un proyecto de software libre con licencia GPL. <br>&copy; Sancho Lerena 2003-2006, David Villanueva 2004-2006 y Ra&uacute;l Mateos 2004-2006.</p>
</div>
</body>
</html>