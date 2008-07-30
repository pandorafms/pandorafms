<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006-2007 Jose Navarro jose@jnavarro.net
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

$sql='SELECT link,name FROM tlink ORDER BY name';
$result = get_db_all_rows_sql ($sql);
if ($result !== false){
?>
	<div class="tit bg4">:: <?php echo $lang_label["links_header"] ?> ::</div>
	<div class="menul" id="link">
<?php
	foreach ($result as $row){
		echo "<div class='linkli'><ul class='mn'><li><a href='".$row["link"]."' target='_new' class='mn'>".$row["name"]."</a></li></ul></div>";
	}
	echo "</div>";
}
?>
