<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, <slerena@gmail.com>
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

$sql1='SELECT * FROM tlink ORDER BY name';
$result=mysql_query($sql1);
if ($row=mysql_fetch_array($result)){
?>
	<div class="tit bg4">:: <?php echo $lang_label["links_header"] ?> ::</div>
	<div class="menul" id="link">
<?php
	$sql1='SELECT * FROM tlink ORDER BY name';
	$result2=mysql_query($sql1);
		while ($row2=mysql_fetch_array($result2)){
			echo "<div class='linkli'><ul class='mn'><li><a href='".$row2["link"]."' target='_new' class='mn'>".$row2["name"]."</a></li></ul></div>";
		}
	echo "</div>";
}
?>