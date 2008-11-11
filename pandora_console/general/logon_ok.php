<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

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

require_once ("include/config.php");

check_login ();

/* Call all extensions login function */
extensions_call_login_function ();

require_once ("include/functions_reporting.php");

echo '<div class="msg" style="width:700px;">';
echo "<h1>" . __('Welcome to Pandora FMS Web Console') . "</h1>";
echo "<p>";
echo __('This is the Web Management System for Pandora FMS. From here you can manage its agents, alerts and incidents. Session is open while activity exists.');
echo "</p>";
echo '</div>';

// Private messages pending to read !
$sql = sprintf ("SELECT COUNT(id_mensaje) FROM tmensajes WHERE id_usuario_destino='%s' AND estado='FALSE';", $config["id_user"]);
$resultado = get_db_sql ($sql);
if ($resultado > 0) {
	echo '<h2>'.__('You have ').'<a href="index.php?sec=messages&sec2=operation/messages/message">'.$resultado.
	'<img src="images/email.png" border="0" />'.__(' unread message(s).').'</a></h2>';
}

// Site news !
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
echo '<div style="width:350px; float:left; padding-right: 30px;" id="leftcolumn">';
echo '<h2>' . __('Site news') . '</h2>';
$sql = "SELECT subject,timestamp,text,author FROM tnews ORDER by timestamp DESC LIMIT 3";
$news = get_db_all_rows_sql ($sql);
if ($news !== false) {
	echo '<table cellpadding="4" cellspacing="4" class="databox">';
	foreach ($news as $article) {
		echo '<tr><th><b>'.$article["subject"].'</b></th></tr>';
		echo '<tr><td>'.__('by').' <b>'.$article["author"].'</b> '.__('at').' <i>'.$article["timestamp"].'</i></td></tr>';
		echo '<tr><td class="datos">';
		echo nl2br ($article["text"]);
		echo '</td></tr>';
	}
	echo '</table>';
} else {
	echo '<div>'.__('No news articles at this moment').'</div>';
}
echo '</div>';

// Site stats
echo '<div style="width:300px; float:left; padding-left: 30px;" id="rightcolumn">';
$data = get_group_stats (0);

$table->class = "databox";
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->head = array ();
$table->data = array ();
$table->width = "100%";

$table->data[0][0] ='<b>'.__('Monitor health').'</b>';
$table->data[1][0] = '<img src="reporting/fgraph.php?tipo=progress&height=20&width=280&mode=0&percent='.$data["monitor_health"].'" title="'.$data["monitor_health"].'% '.__('of monitors up').'" />';

$table->data[2][0] = '<b>'.__('Data health').'</b>';
$table->data[3][0] = '<img src="reporting/fgraph.php?tipo=progress&height=20&width=280&mode=0&percent='.$data["data_health"].'" title="'.$data["data_health"].'% '.__('of data modules up').'" />';

$table->data[4][0] = '<b>'.__('Global health').'</b>';
$table->data[5][0] = '<img src="reporting/fgraph.php?tipo=progress&height=20&width=280&mode=0&percent='.$data["global_health"].'" title="'.$data["global_health"].'% '.__('of total modules up').'" />';

$table->data[6][0] = '<b>'.__('Module sanity').'</b>';
$table->data[7][0] = '<img src="reporting/fgraph.php?tipo=progress&height=20&width=280&mode=0&percent='.$data["module_sanity"].'" title="'.$data["module_sanity"].'% '.__('of total modules inited').'" />';

$table->data[8][0] = '<b>'.__('Alert level').'</b>';
$table->data[9][0] = '<img src="reporting/fgraph.php?tipo=progress&height=20&width=280&mode=0&percent='.$data["alert_level"].'" title="'.$data["alert_level"].'% '.__('of defined alerts not fired').'" />';

print_table ($table);
unset ($table);

echo '<table class="databox" cellpadding="4" cellspacing="4" width="100%">';
echo '<thead><th colspan="2">'.__('Pandora FMS Overview').'</th></thead><tbody>';

$cells = array ();
$cells[0][0] = __('Total agents');
$cells[0][1] = $data["total_agents"];
$cells[0]["color"] = "#000";
$cells[0]["href"] = "index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60";

$cells[1][0] = __('Total checks');
$cells[1][1] = $data["total_checks"];
$cells[1]["color"] = "#000";
$cells[1]["href"] = "index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60&status=1";

$cells[2][0] = __('Modules Down');
$cells[2][1] = $data["total_down"];
$cells[2]["color"] = "#f00";
$cells[2]["href"] = "index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60&status=0";

$cells[3][0] = __('Alerts defined');
$cells[3][1] = $data["total_alerts"];
$cells[3]["color"] = "#000";
$cells[3]["href"] = "index.php?sec=estado&sec2=operation/agentes/estado_alertas&refr=60";

$cells[4][0] = __('Users defined');
$cells[4][1] = count (list_users ());
$cells[4]["color"] = "#000";
$cells[4]["href"] = "index.php?sec=usuarios&sec2=operation/users/user";

foreach ($cells as $key => $row) {
	//Switch class around
	$class = (($key % 2) ? "datos2" : "datos");
	echo '<tr><td class="'.$class.'"><b>'.$row[0].'</b></td>';
	if ($row[1] === 0) {
		$row[1] = "-";
	}
	echo '<td class="'.$class.'" style="text-align:right;"><a class="big_data" href="'.$row["href"].'" style="color: '.$row["color"].';">'.$row[1].'</a></td></tr>';
}

echo '</tbody></table>';
echo '</div><div style="clear:both;">&nbsp;</div>'; //Clear the floats
echo '<div id="activity" style="width:700px;">';
// Show last activity from this user
echo "<h2>" . __('This is your last activity in Pandora FMS console') . "</h2>";

$table->width = '700px';
$table->data = array ();
$table->size = array ();
$table->size[2] = '130px';
$table->size[4] = '200px';
$table->head = array ();
$table->head[0] = __('User');
$table->head[1] = __('Action');
$table->head[2] = __('Date');
$table->head[3] = __('Source IP');
$table->head[4] = __('Comments');

$sql = sprintf ("SELECT id_usuario,accion,fecha,ip_origen,descripcion
				FROM tsesion
				WHERE (`utimestamp` > UNIX_TIMESTAMP(NOW()) - 604800) 
				AND `id_usuario` = '%s' ORDER BY `fecha` DESC LIMIT 5", $config["id_user"]);
$sessions = get_db_all_rows_sql ($sql);

if ($sessions === false)
	$sessions = array (); 

foreach ($sessions as $session) {
	$data = array ();
	
	$data[0] = '<strong>'.$session['id_usuario'].'</strong>';
	$data[1] = $session['accion'];
	$data[2] = $session['fecha'];
	$data[3] = $session['ip_origen'];
	$data[4] = $session['descripcion'];
	
	array_push ($table->data, $data);
}
print_table ($table);
echo "</div>"; // activity
?>
