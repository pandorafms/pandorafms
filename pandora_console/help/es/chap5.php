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
<title>Pandora - Sistema de monitorizaci&oacute;n de Software Libre - Ayuda - V. Eventos</title>
<link rel="stylesheet" href="../../include/styles/help.css" type="text/css">
</head>

<body>

<div class='logo'>
<img src="../../images/pandora_logo_head.png" alt='logo'><h1>Ayuda de Pandora FMS 1.3</h1>
</div>
<div class="toc">
<h1><a href="chap4.php">4. Gesti&oacute;n de incidentes</a> « <a href="toc.php">&Iacute;ndice</a> » <a href="chap6.php">6. Auditor&iacute;a del Sistema</a></h1>

</div>
<div class="rayah2"></div>

<h1><a name="5">5. Eventos</a></h1>

<p>En Pandora un evento es cualquier cambio an&oacute;malo que se produce en un agente.</p>

<p>Queda registrado como evento cuando un agente se cae o se levanta, cuando un monitor falla o cambia de estado o cuando se env&iacute;a una alarma.</p>

<p>Generalmente un evento est&aacute; precedido
de un problema en los sistemas que se est&aacute;n monitorizando. Para evitar que
estos problemas queden sin ser estudiados se ha definido una forma de
validarlos y borrarlos en el caso de que el problema pueda ignorarse o se haya
solucionado.</p>

<p>Desde «Ver eventos» en el men&uacute; de operaci&oacute;n se accede a los eventos ordenados por orden de entrada en el sistema, viendo los primeros los m&aacute;s actualizados.</p>

<p class="center"><img src="images/image045.png"></p>
<br>
<p>En la lista de eventos, cada uno de ellos aparece con informaci&oacute;n distribuida en las siguientes columnas:</p>

<p><b>Estado:</b> Estado en el que se encuentra el incidente mediante los siguientes iconos:</p>
<p class="ml25"><img src="../../images/dot_green.gif"> Cuando el evento ha sido validado</p>
<p class="ml25"><img src="../../images/dot_red.gif"> Cuando el evento no ha sido validado</p>
<p><b>Nombre del evento:</b> Nombre asociado por Pandora al evento.</p>
<p><b>Nombre del agente:</b> Agente en el que ha ocurrido el evento.</p>
<p><b>Nombre del grupo:</b> Grupo al que pertenece el agente en el que ha ocurrido el evento.</p>
<p><b>ID de usuario:</b> Usuario que ha validado el evento.</p>
<p><b>Fecha y hora:</b> Fecha y hora de aparici&oacute;n del evento o de validaci&oacute;n en caso de estar validado.</p>
<p><b>Acci&oacute;n:</b> Acci&oacute;n que se puede ejecutar sobre el evento:</p>
<p class="ml25"><img src="../../images/ok.png"> Pinchando en este icono se validar&aacute; el evento y desaparecer&aacute; el icono</p>
<p class="ml25"><img src="../../images/cross.png"> Pinchando en este icono se borrar&aacute; (y desaparecer&aacute;) el evento</p>

<p>Los eventos tambi&eacute;n se pueden validar y eliminar, marc&aacute;ndolos en la columna de la derecha y pinchando en «Validar» o «Borrar».</p>

<h2><a name="51">5.1. Estad&iacute;sticas</a></h2>

<p>Desde «Ver eventos» &gt; «Estad&iacute;sticas», men&uacute; de operaci&oacute;n, se accede a tres tipos de estad&iacute;sticas gr&aacute;ficas de los eventos:</p>

<ul>
<li>Gr&aacute;fica con los eventos totales divididos en revisados y no revisados
<p class="center"><img src="images/image046.png"></p>
</li>
<li>Gr&aacute;fica con los eventos totales divididos por los usuarios que los han validado
<p class="center"><img src="images/image047.png"></p>
</li>
<li>Gr&aacute;fica con los eventos totales divididos por el grupo al que pertenecen los agentes que ocasionan el evento
<p class="center"><img src="images/image048.png"></p>
</li>
</ul>

<div class="rayah">
<p align='right'>Pandora es un proyecto de software libre con licencia GPL. <br>&copy; Sancho Lerena 2003-2006, David Villanueva 2004-2006 y Ra&uacute;l Mateos 2004-2006.</p>
</div>
</body>
</html>