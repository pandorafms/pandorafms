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
<title>Pandora - Sistema de monitorizaci&oacute;n de Software Libre - Ayuda - IV. Gesti&oacute;n de incidentes</title>
<link rel="stylesheet" href="../../include/styles/help.css" type="text/css">
</head>

<body>

<div class='logo'>
<img src="../../images/pandora_logo_head.png" alt='logo'><h1>Ayuda de Pandora FMS 1.3</h1>
</div>
<div class="toc">
<h1><a href="chap3.php">3. Agentes</a> « <a href="toc.php">&Iacute;ndice</a> » <a href="chap5.php">5. Eventos</a></h1>

</div>
<div class="rayah2"></div>

<h1><a name="4">4. Gesti&oacute;n de incidentes</a></h1>

<p>En el proceso de monitorizaci&oacute;n de sistemas, adem&aacute;s de
recibir y procesar datos con el fin de monitorizar &eacute;stos en espacios temporales
definidos, es necesario hacer un seguimiento de los posibles incidentes que
ocurran en dichos sistemas. </p>
<p>Para ello hay configurado un
gestor de incidentes donde cada usuario puede abrir incidentes explicando lo
sucedido en la red y actualizarlos con comentarios y archivos cada vez que haya
alguna novedad. </p>
<p>Este sistema permite un trabajo
en equipo, con diferentes roles y sistemas de «<i>workflow</i>» que permiten
que un incidente pueda pasar de un grupo a otro, y que miembros de diferentes
grupos, y diferentes individuos, puedan trabajar sobre un mismo incidente,
compartiendo informaci&oacute;n y archivos.</p>
<p>Accediendo a «Incidentes», en el men&uacute; de operaci&oacute;n nos aparece una lista con todos los incidentes ordenados por
orden de actualizaci&oacute;n. Mediante los filtros se puede acceder s&oacute;lo a los incidentes que al usuario le interesen.</p>

<p class="center"><img src="images/image034.png"></p>

<p>Los filtros se pueden combinar, pudiendo realizarse filtros por los siguientes campos:</p>
<ul>
<li>
<b>Filtro por estado del incidente</b>. Donde se pueden ver:
<p class="ml25">- Todos los incidentes</p>
<p class="ml25">- Incidentes activos</p>
<p class="ml25">- Incidentes cerrados</p>
<p class="ml25">- Incidentes rechazados</p>
<p class="ml25">- Incidentes expirados</p>
</li>
<li>
<b>Filtro por prioridad</b>. Donde se pueden ver:
<p class="ml25">- De toda prioridad</p>
<p class="ml25">- De prioridad informativa</p>
<p class="ml25">- De prioridad baja </p>
<p class="ml25">- De prioridad media</p>
<p class="ml25">- De prioridad alta</p>
<p class="ml25">- De prioridad muy alta</p>
<p class="ml25">- De mantenimiento</p>
</li>
<li>
<b>Filtro por grupos</b>. Donde se pueden ver los incidentes asociados a cada uno de los grupos que existen en Pandora.
</li>
</ul>
<br>
<p>En la lista de incidentes, cada uno de ellos aparece con informaci&oacute;n distribuida en las siguientes columnas:</p>

<p><b>ID:</b> Identificador del incidente.</p>
<p><b>Estado:</b> Estado en el que se encuentra el incidente mediante los siguientes iconos:</p>

<p class="ml25"><img src="../../images/dot_red.png"> Cuando el incidente est&aacute; activo</p>
<p class="ml25"><img src="../../images/dot_yellow.png"> Cuando el incidente est&aacute; activo y tiene comentarios</p>
<p class="ml25"><img src="../../images/dot_blue.png"> Cuando el incidente ha sido rechazado</p>
<p class="ml25"><img src="../../images/dot_green.png"> Cuando el incidente est&aacute; cerrado</p>
<p class="ml25"><img src="../../images/dot_white.png"> Cuando el incidente ha expirado</p>

<p><b>Nombre del Incidente:</b> Nombre que se le ha asignado al incidente</p>
<p><b>Prioridad:</b> Aparece la prioridad que tiene asignada el incidente mediante los siguientes iconos:</p>

<p class="ml25"><img src="../../images/dot_red.png"><img src="../../images/dot_red.png"><img src="../../images/dot_red.png"> Prioridad muy alta</p>
<p class="ml25"><img src="../../images/dot_yellow.png"><img src="../../images/dot_red.png"><img src="../../images/dot_red.png"> Prioridad alta</p>
<p class="ml25"><img src="../../images/dot_yellow.png"><img src="../../images/dot_yellow.png"><img src="../../images/dot_red.png"> Prioridad media</p>
<p class="ml25"><img src="../../images/dot_green.png"><img src="../../images/dot_yellow.png"><img src="../../images/dot_yellow.png"> Prioridad baja</p>
<p class="ml25"><img src="../../images/dot_green.png"><img src="../../images/dot_green.png"><img src="../../images/dot_yellow.png"> Prioridad de tarea informativa</p>
<p class="ml25"><img src="../../images/dot_green.png"><img src="../../images/dot_green.png"><img src="../../images/dot_green.png"> Prioridad de tarea de Mantenimiento</p>

<p><b>Grupo:</b> Define el grupo al que se ha asociado el incidente. Un
incidente s&oacute;lo puede pertenecer a un &uacute;nico grupo.</p>
<p><b>Actualizado el:</b> &Uacute;ltima vez que se produjo alguna actualizaci&oacute;n en el incidente.</p>
<p><b>Origen:</b> Etiqueta que se aplica para asignar un origen al
incidente. Puede ser seleccionada de una lista que se almacena en la base de
datos. Aunque la lista de or&iacute;genes es fija y predefinida, puede ser modificada
por el administrador en la base de datos.</p>
<p><b>Propietario:</b> Usuario que tiene asignado actualmente el incidente.
No confundir con el creador del incidente, ya que el incidente ha
podido cambiar de manos. El propietario puede siempre asignar el incidente a
otro usuario, as&iacute; como cualquier usuario con privilegios de gesti&oacute;n de
incidentes sobre el grupo al que pertenezca el incidente.</p>

<h2><a name="41">4.1. A&ntilde;adir un incidente</a></h2>

<p>Para crear un incidente bastar&aaacute; con acceder a «Gesti&oacute;n de incidentes» &gt; «Nuevo incidente», dentro del men&uacute; de operaci&oacute;n.</p>

<p class="center"><img src="images/image035.png"></p>

<p>Una vez se han completado los campos del incidente se pulsa en el bot&oacute;n «Crear».</p>

<h2><a name="42">4.2. Seguimiento de un incidente</a></h2>

<p>El seguimiento de los incidentes abiertos se realiza desde «Gesti&oacute;n de incidentes», men&uacute; de operaci&oacute;n.</p>

<p>Elegimos un incidente concreto en la columna «Nombre de incidente».</p>

<p>En la p&aacute;gina que se nos muestra podemos acceder a los datos de configuraci&oacute;n del incidente y a las distintas notas y archivos a&ntilde;adidos al mismo.</p>

<p>En la primera parte tenemos la configuraci&oacute;n del incidente.</p>

<p class="center"><img src="images/image036.png"></p>

<p>Desde este formulario se pueden actualizar los siguientes campos:</p>
<ul>
<li><b>Nombre del incidente</b></li>
<li><b>Due&ntilde;o del incidente</b></li>
<li><b>Estado del incidente</b></li>
<li><b>Origen del incidente</b></li>
<li><b>Grupo al que esta asociado el incidente</b></li>
<li><b>Prioridad del incidente</b></li>
</ul>
<p>Pinchando en el bot&oacute;n «Actualizar incidente» se grabaran los cambios realizados.</p>

<h3><a name="421">4.2.1. A&ntilde;adir notas a un incidente</a></h3>

<p>Para a&ntilde;adir Notas al incidente, pinchamos en «Insertar nota», nos aparecer&aacute; una p&aacute;gina que contiene un &aacute;rea de texto:</p>
<p class="center"><img src="images/image037.png"></p>

<p>Una vez terminado el texto, pulsamos en el bot&oacute;n «A&ntilde;adir» y aparecer&aacute; la nota que acabamos de a&ntilde;adir en la secci&oacute;n «Notas asociadas al incidente»</p>
<p class="center"><img src="images/image038.png"></p>

<p>Cualquier usuario con derechos de lectura de un incidente puede agregar una nota. S&oacute;lo los propietarios del incidente o de las notas pueden borrarlas.</p>

<h3><a name="422">4.2.2. A&ntilde;adir archivos a un incidente</a></h3>

<p>En algunas ocasiones es interesante asociar a un incidente una imagen, un archivo de configuraci&oacute;n o cualquier tipo de archivo.</p>

<p>Para ello desde la parte «A&ntilde;adir archivo» se busca el archivo en la m&aacute;quina local y se a&ntilde;ade al servidor pinchando en el bot&oacute;n «Subir».</p>

<p>Cualquier usuario con derechos de lectura de un incidente puede agregar un archivo. S&oacute;lo los propietarios del incidente o de los archivos pueden borrarlos.</p>

<p class="center"><img src="images/image039.png"></p>

<p>Para el seguimiento de la incidencia, desde la misma se puede acceder a todos los archivos adjuntados en «Ficheros adjuntos».</p>

<p class="center"><img src="images/image040.png"></p>

<h2><a name="43">4.3. Buscar incidencia</a></h2>

<p>Si se desea buscar un incidente concreto dentro de todos los incidentes que hay creados en Pandora, adem&aacute;s de los filtros que se han visto al principio del apartado,
se puede hacer una b&uacute;squeda m&aacute;s concreta desde «Gesti&oacute;n de incidentes» &gt; «Buscar incidentes», en el men&uacute; de operaci&oacute;n.</p>

<p class="center"><img src="images/image041.png"></p>

<p>Desde aqu&iacute; se puede buscar cualquier cadena de texto introducida como subcadena dentro del incidente,
haciendo un filtro por el usuario que creo &eacute;l mismo.</p>

<p>Las b&uacute;squedas se realizan sobre
el t&iacute;tulo del incidente o sobre el contenido del mismo, pero no sobre las notas
asociadas ni sobre los archivos adjuntos. Tambi&eacute;n se pueden combinar estas
b&uacute;squedas con los filtros sobre grupo, prioridad o estado del incidente.</p>

<h2><a name="44">4.4. Estad&iacute;sticas</a></h2>

<p>Desde «Gesti&oacute;n de incidentes» &gt; «Estad&iacute;sticas» en el men&uacute; de operaci&oacute;n, se accede a cinco tipos de estad&iacute;sticas gr&aacute;ficas de los incidentes:</p>

<ul>
<li><b>Estado de los incidentes</b></li>
<li><b>Prioridades asignadas a los incidentes</b></li>
<li><b>Usuarios que tienen abierto un incidente</b></li>
<li><b>Incidentes por grupos</b></li>
<li><b>Or&iacute;genes de los incidentes</b></li>
</ul>

<p class="center">
<img src="images/image042.png"><br>
<img src="images/image043.png"><br>
<img src="images/image044.png"><br>
</p>

<div class="rayah">
<p align='right'>Pandora es un proyecto de software libre con licencia GPL. <br>&copy; Sancho Lerena 2003-2006, David Villanueva 2004-2006 y Ra&uacute;l Mateos 2004-2006.</p>
</div>
</body>
</html>