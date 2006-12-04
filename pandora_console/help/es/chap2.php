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
<title>Pandora - Sistema de monitorizaci&oacute;n de Software Libre - Ayuda - II. Usuarios</title>
<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css">
<style>
div.logo {float:left;}
div.toc {padding-left: 200px;}
div.rayah {clear:both; border-top: 1px solid #708090; width: 100%;}
</style>

<div class='logo'>
<img src="../../images/logo_menu.gif" alt='logo'><h1>Ayuda de Pandora v1.2</h1>
</div>
<div class="toc">
<h1><a href="chap1.php">1. Introducci&oacute;n</a> « <a href="toc.php">&Iacute;ndice</a> » <a href="chap3.php">3. Agentes</a></h1>

</div>
<div class="rayah">
<p align='right'>Pandora es un proyecto de software GPL. &copy; Sancho Lerena 2003-2006, David Villanueva 2004-2006 y Ra&uacute;l Mateos 2004-2006.</p>
</div>

<h1><a name="2">2. Usuarios</a></h1>

<p>En Pandora se definen usuarios para la operativa diaria y se les asignan uno o varios perfiles.</p>

<p>Un perfil es una lista de lo que se puede y no se puede hacer sobre un grupo, como por ejemplo «ver incidentes», «gestionar bases de datos», etc.</p>

<p>En cada usuario se define a que grupos de agentes se puede acceder y el perfil de administraci&oacute;n que se tendr&aacute;
en cada uno de ellos. Cada usuario puede pertenecer a uno o m&aacute;s grupos, y tiene
asignado un perfil a cada grupo que pertenezca.</p>

<p>Los grupos definen elementos en com&uacute;n y cada agente esta asignado a un grupo (y s&oacute;lo a uno).
Los grupos contienen agentes e incidentes.</p>

<h2><a name="21">2.1. Gesti&oacute;n de perfiles</a></h2>

<p>La caracter&iacute;stica de gesti&oacute;n de
perfiles en Pandora permite definir roles espec&iacute;ficos para cada usuario de
Pandora y poder crear una jerarqu&iacute;a de usuarios ordenados por su perfil dentro
de la compa&ntilde;&iacute;a. As&iacute; se pueden crear operadores con accesos s&oacute;lo de lectura,
coordinadores de un grupo espec&iacute;fico de Agentes o administradores totales del
sistema</p>
<p>La creaci&oacute;n de un perfil en
Pandora se realiza desde Gesti&oacute;n de perfiles dentro del men&uacute; de Administraci&oacute;n.
En esta p&aacute;gina se encuentran todos los perfiles existentes, por defecto hay cinco perfiles creados:</p>
<ul>
<li>Operator (Read)</li>
<li>Operator (Write)</li>
<li>Chief Operator</li>
<li>Group coordinator</li>
<li>Pandora Administrator</li>
</ul>

<p>Para crear un perfil lo hacemos desde «Gesti&oacute;n de perfiles» &gt; «Crear perfil» dentro del men&uacute; de Administraci&oacute;n.</p>

<p class="center"><img src="images/image002.png"></p>

<p>A un nuevo perfil se le puede a&ntilde;adir cualquiera de los siguientes roles:</p>

<ul>
<li>Ver incidentes (IR)</li>
<li>Editar incidentes (IW)</li>
<li>Gesti&oacute;n de incidentes (IM)</li>
<li>Ver agentes (AR). Permite ver los datos de los agentes, as&iacute; como los eventos generados por ellos</li>
<li>Editar agentes (AW). Permite modificar los m&oacute;dulos de los agentes</li>
<li>Editar alertas (LW). Permite modificar las alertas asignadas a los agentes</li>
<li>Gesti&oacute;n de usuarios (UM). Permite modificar los usuarios y sus roles</li>
<li>Gesti&oacute;n de Base de Datos (DM). Permite la manipulaci&oacute;n de la Base de Datos (Global)</li>
<li>Gesti&oacute;n de alertas (LM). Permite la definici&oacute;n de las alertas (Global)</li>
<li>Gesti&oacute;n de Pandora (PM). Opciones de configuraciones generales</li>
</ul>

<h2><a name="22">2.2. A&ntilde;adir un usuario</a></h2>

Para a&ntilde;adir un usuario lo hacemos desde «Gesti&oacute;n de usuarios» &gt; «Crear Usuario» dentro del men&uacute; de administraci&oacute;n.</p>

<p>Para crear un usuario se deben completar al menos los datos de identificador, contrase&ntilde;a (dos veces) y perfil global dentro de Pandora.</p>

<p>El perfil global define a un usuario como Administrador o como Usuario est&aacute;ndar dentro de Pandora.</p>

<p>Los usuarios con perfil «Administrador» tendr&aacute;n permisos totales en el servidor.</p>

<p class="center"><img src="images/image003.png"></p>

<p>Para los usuarios con perfil «usuario est&aacute;ndar» se definir&aacute;n los perfiles que tienen en cada uno de los
grupos a los que tiene acceso una vez se ha creado el usuario, y actualizar su configuraci&oacute;n.</p>

<p class="center"><img src="images/image004.png"></p>

<p>Para borrar un perfil de un usuario bastar&aacute; con pulsar en el icono <img src="../../images/cancel.gif">
correspondiente que hay a la derecha de cada perfil.</p>

<h2><a name="23">2.3. Borrar un usuario</a></h2>

<p>Para borrar un usuario bastar&aacute; en pulsar en el icono <img src="../../images/cancel.gif">
correspondiente que hay a la derecha del usuario en la lista de usuarios accesible desde «Gesti&oacute;n de usuarios», en el men&uacute; de administraci&oacute;n.</p>

<p class="center"><img src="images/image005.png"></p>

<h2><a name="24">2.4. Estad&iacute;sticas</a></h2>

<p>Existe una gr&aacute;fica que muestra la actividad de cada
usuario, reflejando el n&uacute;mero de eventos de auditor&iacute;a que ha generado cada uno
de ellos. Suele ser un reflejo de la actividad de cada usuario.</p>

<p>Para mostrar esta gr&aacute;fica, se accede desde «Usuarios» &gt; «Estad&iacute;sticas», en
el men&uacute; de operaci&oacute;n.</p>

<p class="center"><img src="images/image006.png"></p>

<h2><a name="25">2.5. Mensajes a usuarios</a></h2>

<p>Desde «Mensajes», en el men&uacute; de operaci&oacute;n podemos enviar mensajes a otros usuarios y leer los mensajes que hemos recibido. No se almacenan los correos
que se han enviado.</p>

<h3><a name="251">2.5.1. Mensajes a grupos</a></h3>
<p>Desde «Mensajes» &gt; «Mensajes a grupos» en el men&uacute; de operaci&oacute;n podemos enviar mensajes a grupos de usuarios. No se almacenan los correos que se han enviado.
</p>

</body>
</html>