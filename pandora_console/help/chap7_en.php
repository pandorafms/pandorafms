<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2004-2006
?>
<html>
<head>
<title>Pandora - The Free Monitoring System Help</title>
<link rel="stylesheet" href="../include/styles/pandora.css" type="text/css">
<style>
div.logo {float:left;}
div.toc {padding-left: 200px;}
div.rayah {clear:both; border-top: 1px solid #708090; width: 100%;}
</style>
</head>

<body>
<div class='logo'>
<img src="../images/logo_menu.gif" alt='logo'><h1>Pandora Help v1.2</h1>
</div>
<div class="toc">
<h1><a href="chap6_en.php">6. System audit</a> « <a href="toc_en.php">Table of Contents</a> » <a href="chap8_en.php">8. Data Base maintenance</a></h1>

</div>
<div class="rayah">
<p align='right'>Pandora is a GPL Software project. &copy; Sancho Lerena 2003-2005, David villanueva 2004-2005, Alex Arnal 2005, Ra&uacute;l Mateos 2004-2005.</p>
</div>

<a name="7"><h1>7. Server Configuration</h1></a>

<p>All the configurable parameters in Pandora can be set in the "Pandora Setup" section, in the Administration menu.</p>

<p class="center"><img src="images/image051.png"></p>

These parameters are:</p>

<p><b>Language:</b> In following versions or revisions of the actual Pandora version will support more languages. At the moment version 1.1 only supports English, Spanish, Euskera and Bable.</p>
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

<h2><a name="71">7.1. Links</a></h2>

<p>Links to different Internet or private network links can be configured in Pandora. These could be search engines, applications or company Intranets.</p>

<p>The links configured in Pandora can be edited through the "Pandora Setup"&gt;"Links" option in the Administration menu.</p>

<p class="center"><img src="images/image052.png"></p>

<p>A new link is created by clicking on "Create".The link can be then edited:</p>

<p class="center"><img src="images/image053.png"></p>


</body>
</html>