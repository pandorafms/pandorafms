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
<title>Pandora - Sistema de monitorizaci&oacute;n de Software Libre - Ayuda - IX. Configuraci&oacute;n de Pandora</title>
<link rel="stylesheet" href="../../include/styles/help.css" type="text/css">
</head>

<body>

<div class='logo'>
<img src="../../images/logo_menu.gif" alt='logo'><h1>Ayuda de Pandora FMS 1.3</h1>
</div>
<div class="toc">
<h1><a href="chap8.php">8. Mantenimiento de la Base de Datos</a></h1> « <a href="toc.php">&Iacute;ndice</a></h1>

</div>
<div class="rayah2"></div>

<h1><a name="9">9. Configuraci&oacute;n de Pandora</a></h1>

<p>Desde «Configuraci&oacute;n» en el men&uacute; de administraci&oacute;n se puede acceder a los par&aacute;metros configurables de Pandora</p>
<p class="center"><img src="images/image051.png"></p>

<p>Estos par&aacute;metros son:</p>

<p><b>C&oacute;digo de lenguaje para Pandora.</b> En sucesivas versiones o ampliaciones de la versi&oacute;n
actual pueden aparecer nuevos idiomas. En la versi&oacute;n 1.2 est&aacute;n soportados ingl&eacute;s, franc&eacute;s, catal&aacute;n, portugu&eacute;s de Brasil, bable y espa&ntilde;ol (castellano).</p>

<p><b>Tama&ntilde;o de bloque para la paginaci&oacute;n</b>. Tama&ntilde;o m&aacute;ximo de las listas en la secci&oacute;n de eventos,
incidentes y Logs de auditor&iacute;a.</p>

<p><b>M&aacute;x. d&iacute;as antes de comprimir datos</b>. Este par&aacute;metro controla la compactaci&oacute;n de los datos. A
partir del n&uacute;mero de d&iacute;as indicados, se comienzan a compactar datos. Para datos
con un gran volumen, se recomienda un n&uacute;mero entre 14 y 28, para sistemas con
poca carga o muy potentes, un n&uacute;mero entre 30 y 50.</p>

<p><b>M&aacute;x. d&iacute;as antes de eliminar datos</b>. Este par&aacute;metro controla el m&aacute;ximo total de d&iacute;as que
pueden tener los datos antes de ser definitivamente eliminados de la base de
datos. Un valor recomendado es 60. Para sistemas con pocos recursos o con mucha
carga, est&aacute; recomendado un valor entre 40 y 50.</p>

<p><b>Resoluci&oacute;n de los gr&aacute;ficos</b> (1 baja, 5 alta). Representa la precisi&oacute;n empleada en el
algoritmo de interpolaci&oacute;n empleado para generar los gr&aacute;ficos.</p>

<p><b>Interpolaci&oacute;n de la compactaci&oacute;n</b> (Horas: 1 bueno, 10 medio, 20 malo). Indica el grado de
compresi&oacute;n utilizado para la compactaci&oacute;n de las bases de datos, siendo el valor
1 el de menor y 20 el de mayor compactaci&oacute;n. Un valor por encima de 12
representa una seria degradaci&oacute;n de los valores compactados. No se recomienda
en general utilizar un valor superior a 6 si se emplea la funcionalidad de los
gr&aacute;ficos para comparar tendencias en rangos de tiempo amplios.</p>

<h2><a name="91">9.1. Enlaces</a></h2>
<p>En Pandora se pueden configurar
enlaces a diversos enlaces de Internet o redes internas, tales como motores de
b&uacute;squeda, aplicaciones o sitios de la Intranet corporativa. </p>
<p>Para ver los enlaces que hay configurados accedemos a «Configuraci&oacute;n» &gt; «Enlaces», en el men&uacute; de administraci&oacute;n.

<p class="center"><img src="images/image052.png"></p>

<p>Para crear un enlace nuevo se pincha en «Crear» y podemos editar el nuevo enlace:</p>

<p class="center"><img src="images/image053.png"></p>

<div class="rayah">
<p align='right'>Pandora es un proyecto de software libre con licencia GPL. <br>&copy; Sancho Lerena 2003-2006, David Villanueva 2004-2006 y Ra&uacute;l Mateos 2004-2006.</p>
</div>
</body>
</html>