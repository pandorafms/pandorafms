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
// Load global vars
require("include/config.php");

if (comprueba_login() == 0) {

echo "<h2>".__('incident_manag')." &gt; ";
echo __('find_crit')."</h2>";
echo "<div style='width:645'>";
echo "<div style='float:right;'><img src='images/pulpo_lupa.png' class='bot' align='left'></div>";	
?>
<div style='float:left;'>
<table width="500" cellpadding="4" cellspacing="4" class='databox'>
<form name="busqueda" method="post" action="index.php?sec=incidencias&sec2=operation/incidents/incident">
<tr>
<td class="datos"><?php echo __('user') ?>
<td class="datos">
<select name="usuario" class="w120">
	<option value=""><?php echo __('all') ?></option>
	<?php 
	$sql1='SELECT * FROM tusuario ORDER BY id_usuario';
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		echo "<option>".$row["id_usuario"]."</option>";
	}
	?>
</select>
<tr><td class="datos2"><?php echo __('free_text_search') ?>
<td class="datos2"><input type="text" size="45" name="texto"></tr>
<tr><td class="datos" colspan="2"><i><?php echo __('free_text_search_msg') ?></i></td></tr>
</table>
<table width="500">
<tr><td align="right" colspan="3">
<?php echo "<input name='uptbutton' type='submit' class='sub search' value='".__('search')."'>"; ?>

</form>
</table>
</div>
</div>
<?php 

} // end page
?>
