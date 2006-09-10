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
<title>Pandora - Sistema de monitorizaci&oacute;n de Software Libre - Ayuda - VI. Auditor&iacute;a del Sistema</title>
<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css">
<style>
.ml25 {margin-left: 25px;}
div.logo {float:left;}
div.toc {padding-left: 200px;}
div.rayah {clear:both; border-top: 1px solid #708090; width: 100%;}
</style>

<div class='logo'>
<img src="../../images/logo_menu.gif" alt='logo'><h1>Ayuda de Pandora v1.2</h1>
</div>
<div class="toc">
<h1><a href="chap5.php">5. Eventos</a> « <a href="toc.php">&Iacute;ndice</a> » <a href="chap7.php">7. Servidores Pandora</a></h1>

</div>
<div class="rayah">
<p align='right'>Pandora es un proyecto de software GPL. &copy; Sancho Lerena 2003-2006, David Villanueva 2004-2006 y Ra&uacute;l Mateos 2004-2006.</p>
</div>

<h1><a name="6">6. Auditor&iacute;a del Sistema</a></h1>

<p>En Pandora las entradas de auditor&iacute;a muestran las acciones realizadas por cada usuario, as&iacute; como los intentos de acceso fallidos al sistema.</p>

<p>En la versi&oacute;n actual de Pandora (1.1),
adem&aacute;s se muestran operaciones que de alguna manera han intentado eludir el
sistema de seguridad, tales como intentos de borrar un incidente sobre el que
el usuario no tiene permiso, cambio del perfil de usuario sin tener permiso de
administraci&oacute;n de usuarios y cosas similares. Principalmente sirve para llevar
un seguimiento de las conexiones (login/logoff) de cada usuario.</p>

<p>En «Auditor&iacute;a del Sistema» del men&uacute; de administraci&oacute;n, tenemos todos los <i>Logs</i> de auditor&iacute;a, ordenados por
orden de aparici&oacute;n.</p>

<p>Mediante un filtro puede accederse &uacute;nicamente a los <i>Logs</i> de auditor&iacute;a que interesen al usuario,
pudiendo realizarse filtros por la acci&oacute;n que provoca el <i>Log</i>.</p>

<p>Las acciones posibles son todas las acciones diferentes que haya almacenadas en ese momento en la Base de Datos.</p>

<p class="center"><img src="images/image049.png"></p>

<p>Cada una de las entradas de la lista de Logs de Auditor&iacute;a aparece con informaci&oacute;n distribuida en las siguientes columnas:</p>

<p><b>Usuario:</b> Usuario que ha generado el evento (SYSTEM es un usuario especial del sistema)</p>
<p><b>Acci&oacute;n:</b> Acci&oacute;n que ha generado la entrada en el log de auditor&iacute;a</p>
<p><b>Fecha:</b> Fecha en que se ha creado la entrada de auditor&iacute;a</p>
<p><b>IP Origen:</b> Aparece la IP o el agente que ha provocado la entrada</p>
<p><b>Comentarios:</b> Aparece un comentario asociado a la acci&oacute;n concreta</p>

<h2><a name="61">6.1. Estad&iacute;sticas</a></h2>

<p>Aunque no existe una secci&oacute;n
especial para ver las estad&iacute;sticas de la auditor&iacute;a del sistema, s&iacute; podemos
emplear la gr&aacute;fica generada en la secci&oacute;n de usuarios para evaluar las acciones
de cada usuario, ya que esta gr&aacute;fica representa el n&uacute;mero total de entradas en
el log de auditor&iacute;a para cada usuario: los usuarios m&aacute;s activos del sistema
tendr&aacute;n m&aacute;s entradas.</p>

<p>Tambi&eacute;n aparecer&aacute;n entradas correspondientes a usuarios no v&aacute;lidos: son aquellas entradas generadas por intentos incorrectos de entrada en el sistema o similar.</p>

<p class="center"><img src="images/image050.png"></p>

</body>
</html>