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
<title>Pandora - Sistema de monitorizaci&oacute;n de Software Libre - Ayuda - VIII. Mantenimiento de la Base de Datos</title>
<link rel="stylesheet" href="../../include/styles/help.css" type="text/css">
</head>

<body>

<div class='logo'>
<img src="../../images/pandora_logo_head.png" alt='logo'><h1>Ayuda de Pandora FMS 1.3</h1>
</div>
<div class="toc">
<h1><a href="chap7.php">7. Servidores Pandora</a> « <a href="toc.php">&Iacute;ndice</a> » 9. Configuraci&oacute;n de Pandora</h1>

</div>
<div class="rayah2"></div>

<h1><a name="8">8. Mantenimiento de la Base de Datos</a></h1>

<p>La base de datos es el n&uacute;cleo de
Pandora. En esta base de datos reside toda la informaci&oacute;n recolectada por las
m&aacute;quinas monitorizadas, toda la informaci&oacute;n definida por el administrador as&iacute;
como todos los eventos, incidentes e informaci&oacute;n de auditor&iacute;a que han ido
ocurriendo en el sistema a lo largo del tiempo.</p>

<p>Es evidente que el rendimiento y
fiabilidad de este m&oacute;dulo es vital para el funcionamiento correcto de Pandora.
Es necesario hacer un mantenimiento peri&oacute;dico de la base de datos, para ello se
pueden utilizar los comandos est&aacute;ndar de MySQL.</p>

<p>Como el tama&ntilde;o de la base de
batos aumentar&aacute; de manera lineal, utilizamos un m&eacute;todo de compactaci&oacute;n para
reducir el n&uacute;mero de datos almacenados sin perder la informaci&oacute;n que se
considere necesaria, en particular las distintas gr&aacute;ficas generadas a partir de
la informaci&oacute;n procesada.</p>

<p>Pinchando en «Mantenimiento BBDD» del men&uacute; de administraci&oacute;n aparece la configuraci&oacute;n en tiempo real de la Base de
Datos con la configuraci&oacute;n que se ha definido en «Configuraci&oacute;n», en el mismo
men&uacute; de administraci&oacute;n, para la compactaci&oacute;n y eliminaci&oacute;n de los datos.</p>

<p class="center"><img src="images/image054.png"></p>

<h2><a name="81">8.1. Informaci&oacute;n de la Base de Datos</a></h2>

<p>Desde «Gesti&oacute;n de incidentes» &gt; «Informaci&oacute;n de la Base de Datos» en el men&uacute; de operaci&oacute;n, se accede a dos tipos de estad&iacute;sticas gr&aacute;ficas de la base de datos por agentes:</p>

<ul>
<li>Una gr&aacute;fica con el n&uacute;mero de m&oacute;dulos que tienen configurado cada uno de los agentes.</li>
<li>Una gr&aacute;fica con el n&uacute;mero de paquetes enviado por cada
uno de los agentes. Siendo un paquete el conjunto de datos relacionados con los
m&oacute;dulos que env&iacute;a un agente cada intervalo de tiempo.</li>
</ul>

<p class="center">
<img src="images/image055.png"><br>
<img src="images/image056.png"><br>
<img src="images/image057.png"><br>
</p>

<p>Estas gr&aacute;ficas coinciden con las que se ven a trav&eacute;s de «Ver agentes» &gt; «Estad&iacute;sticas», en el men&uacute; de administraci&oacute;n.</p>

<h2><a name="82">8.2. Purgado Manual de la Base de Datos</a></h2>

<p>Pandora incorpora potentes
herramientas para el purgado manual por un Administrador de la mayor&iacute;a de los
datos guardados en la Base de Datos, ya sean estos datos procedentes de los
agentes o del propio servidor.</p>

<h2><a name="83">8.3. Depuraci&oacute;n de Datos procedentes de los agentes</a></h2>

<h3><a name="831">8.3.1. Depuraci&oacute;n de datos concretos de un m&oacute;dulo</a></h3>

<p>El proceso de depuraci&oacute;n de datos
concretos de un m&oacute;dulo sirve para eliminar aquellas entradas que se salen de
rango por cualquier raz&oacute;n (fallo de agente, valor real pero fuera de escala,
pruebas, errores en la BD, etc.). Eliminar datos falsos, incorrectos o
simplemente molestos permite que la escala de las gr&aacute;ficas sea m&aacute;s «real» y
permita mostrar los datos sin picos ni escalas irreales.</p>

<p>Desde «Mantenimiento BBDD» &gt; «Depurar BD» en el men&uacute; de administraci&oacute;n se pueden borrar los datos recibidos por
el m&oacute;dulo de un agente concreto que est&eacute;n fuera de un rango determinado.</p>

<p class="center"><img src="images/image058.png"></p>

<p>Los valores introducidos: agente, m&oacute;dulo, m&iacute;nimo y m&aacute;ximo sirven para delimitar los datos correctos. 
Cualquier dato que se salga de ese par de par&aacute;metros, ser&aacute; eliminado.</p>

<p>Por ejemplo, en un tipo de m&oacute;dulo
que registra n&uacute;mero de procesos, nos interesan valores entre 0 y 100, puede que
tengamos valores muy por encima de 100, pero generalmente ser&aacute;n errores, ruido
o situaciones especiales. Podemos incluir como m&iacute;nimo un 0, y como m&aacute;ximo un
100. Valores tales como -1, 101 &oacute; 100000, ser&aacute;n eliminados permanentemente de
la Base de Datos.</p>

<h3><a name="832">8.3.2. Depuraci&oacute;n de todos los datos de un agente</a></h3>

<p>Desde «Mantenimiento BBDD» &gt; «Depuraci&oacute;n BBDD», men&uacute; de administraci&oacute;n se pueden borrar todos los datos recibidos
por un agente concreto que est&eacute;n fuera de un rango determinado.</p>

<p>Se pueden borrar los datos con los siguientes par&aacute;metros que se configuran en «Borrar datos»:</p>

<ul>
<li>Borrar todos los datos</li>
<li>Borrar los datos con m&aacute;s de tres meses</li>
<li>Borrar los datos con m&aacute;s de treinta d&iacute;as</li>
<li>Borrar los datos con m&aacute;s de dos semanas</li>
<li>Borrar los datos con m&aacute;s de una semana</li>
<li>Borrar los datos con m&aacute;s de tres d&iacute;as</li>
<li>Borrar los datos con m&aacute;s de un d&iacute;a</li>
</ul>

<p class="center"><img src="images/image059.png"></p>

<h2><a name="84">8.4. Depuraci&oacute;n de Datos procedentes del sistema</a></h2>

<h3><a name="841">8.4.1. Depuraci&oacute;n de datos de auditor&iacute;a</a></h3>

<p>Desde «Mantenimiento BBDD» &gt; «BBDD de auditor&iacute;a» en el men&uacute; de administraci&oacute;n, se pueden borrar todos los datos de
auditor&iacute;a generados por el sistema.</p>

<p>Se pueden borrar los datos con los siguientes par&aacute;metros que se configuran en «Borrar Datos».</p>

<ul>
<li>Borrar los datos de auditor&iacute;a excepto el &uacute;ltimo trimestre</li>
<li>Borrar los datos de auditor&iacute;a excepto los &uacute;ltimos 30 d&iacute;as</li>
<li>Borrar los datos de auditor&iacute;a excepto las &uacute;ltimas dos semanas</li>
<li>Borrar los datos de auditor&iacute;a excepto la &uacute;ltima semana</li>
<li>Borrar los datos de auditor&iacute;a excepto los &uacute;ltimos tres d&iacute;as</li>
<li>Borrar los datos de auditor&iacute;a excepto el &uacute;ltimo d&iacute;a</li>
<li>Borrar todos los datos de auditor&iacute;a</li>
</ul>

<p class="center"><img src="images/image060.png"></p>

<h3><a name="842">8.4.2. Depuraci&oacute;n de datos de eventos</a></h3>

<p>Desde «Mantenimiento BBDD» &gt; «BBDD de eventos», men&uacute; de administraci&oacute;n, se pueden borrar todos los datos de auditor&iacute;a
generados por el sistema.</p>
<p>Se pueden borrar los datos con los siguientes par&aacute;metros que se configuran en «Borrar Datos»:</p>

<ul>
<li>Borrar los datos de eventos excepto el &uacute;ltimo trimestre.</li>
<li>Borrar los datos de eventos excepto los &uacute;ltimos 30 d&iacute;as</li>
<li>Borrar los datos de eventos excepto las &uacute;ltimas dos semanas</li>
<li>Borrar los datos de eventos excepto la &uacute;ltima semana</li>
<li>Borrar los datos de eventos excepto los &uacute;ltimos tres d&iacute;as</li>
<li>Borrar los datos de eventos excepto el &uacute;ltimo d&iacute;a</li>
<li>Borrar todos los datos de eventos</li>
</ul>

<p class="center"><img src="images/image061.png"></p>

<div class="rayah">
<p align='right'>Pandora es un proyecto de software libre con licencia GPL. <br>&copy; Sancho Lerena 2003-2006, David Villanueva 2004-2006 y Ra&uacute;l Mateos 2004-2006.</p>
</div>
</body>
</html>