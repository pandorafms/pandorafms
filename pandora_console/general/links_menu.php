<?php
// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

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

$sql = 'SELECT link, name FROM tlink ORDER BY name';
$result = get_db_all_rows_sql ($sql);
if ($result !== false) {
	echo '<div class="tit bg4">:: '.__('Links').' ::</div>';
	echo '<div class="menu"><ul>';
	foreach ($result as $link) {
		echo '<li class="links"><a href="'.$link["link"].'" target="_new">'.$link["name"].'</a></li>';
	}
	echo '</ul></div>';
}
?>
