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
<title>Pandora - The Free Monitoring System Help - IX. Pandora Configuration</title>
<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css">
<style>
div.logo {float:left;}
div.toc {padding-left: 200px;}
div.rayah {border-top: 1px solid #708090; width: 100%;}
div.rayah2 {clear:both; border-top: 1px solid #708090; width: 100%; padding-bottom: 35px;}
</style>
</head>

<body>
<div class='logo'>
<img src="../../images/logo_menu.gif" alt='logo'><h1>Pandora Help v1.2</h1>
</div>
<div class="toc">
<h1><a href="chap8.php">8. Database maintenance</a> « <a href="toc.php">Table of Contents</a></h1>

</div>
<div class="rayah2"></div>

<a name="9"><h1>9. Pandora Configuration</h1></a>

<p>All the configurable parameters in Pandora can be set in the "Pandora Setup" section, in the Administration menu.</p>

<p class="center"><img src="images/image051.png"></p>

These parameters are:</p>

<p><b>Language:</b> In following versions or revisions of the actual Pandora version will support more languages. At the moment version 1.2 supports English, Spanish, Bable, Italian, French, Catalan and Portuguese of Brazil.</p>
<p><b>Page block size:</b> Maximum size of the lists in the event, incident and audit log sections.</p>
<p><b>Max. days before compact data:</b> This parameter controls data compacting. From the
number of days in this parameter the data starts getting compacted. For large
amounts of data it is recommended to set this parameter to a number between 14
and 28; for systems with less data load or very powerful systems, a number
between 30 and 50 will be enough.</p>
<p><b>Max. days before purge:</b> This parameter controls how long the data is kept
before it is permanently deleted. The recommended value is 60. For systems with
little resources or large work load the recommended value is between 40 and 50.</p>
<p><b>Graphic resolution (1 low, 5 high):</b> This value represents the precision of the
interpolation logarithm to generate the graphics.</p>
<p><b>Compact interpolation (Hours: 1 fine, 10 medium, 20 bad):</b> This is the grade of compression used to compact the Data Base, being 1 the lowest compression rate and 20 the highest. A value above 12 means a considerable data loss. It's not recommended to use value above 6 if the data needs to be
represented graphically in large time intervals.</p>

<h2><a name="91">9.1. Links</a></h2>

<p>Links to different Internet or private network links can be configured in Pandora. These could be search engines, applications or company Intranets.</p>

<p>The links configured in Pandora can be edited through the "Pandora Setup"&gt;"Links" option in the Administration menu.</p>

<p class="center"><img src="images/image052.png"></p>

<p>A new link is created by clicking on "Create".The link can be then edited:</p>

<p class="center"><img src="images/image053.png"></p>


<div class="rayah">
<p align='right'>Pandora FMS is Free Software Project, licensed under GPL terms.<br> &copy; Sancho Lerena 2003-2006, David villanueva 2004-2006, Alex Arnal 2005, Ra&uacute;l Mateos 2004-2006.</p>
</div>
</body>
</html>