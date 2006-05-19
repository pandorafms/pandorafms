<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2004-2006
?>

<?php
if (isset($_SESSION["id_usuario"])) {
?>
<div class="bg">
	<div class="imgl"><img src="images/upper-left-corner.gif" width="5" height="5" alt=""></div>
	<div class="tit"><?php echo $lang_label["operation_header"] ?></div>
	<div class="imgr"><img src="images/upper-right-corner.gif" width="5" height="5" alt=""></div>
</div>
<div id="menuop">
	<div id="op">

<?php
    if (give_acl($_SESSION["id_usuario"], 0, "AR")==1) {
		if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/estado_grupo") {
			echo '<div id="op1s"><ul class="mn"><li><a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_grupo&amp;refr=60" class="mn">'.$lang_label["view_agents"].'</a></li></ul></div>';
		}
		else echo '<div id="op1"><ul class="mn"><li><a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_grupo&amp;refr=60" class="mn">'.$lang_label["view_agents"].'</a></li></ul></div>';
		if (isset($_GET["sec"]) && $_GET["sec"] == "estado"){
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/estado_agente") {
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60' class='mn'>".$lang_label["agent_detail"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60' class='mn'>".$lang_label["agent_detail"]."</a></li></ul></div>";
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/estado_alertas") {
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_alertas&amp;refr=60' class='mn'>".$lang_label["alert_detail"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_alertas&amp;refr=60' class='mn'>".$lang_label["alert_detail"]."</a></li></ul></div>";
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/status_monitor") {
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60' class='mn'>".$lang_label["detailed_monitoragent_state"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60' class='mn'>".$lang_label["detailed_monitoragent_state"]."</a></li></ul></div>";
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/exportada") {
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/exportdata' class='mn'>".$lang_label["export_data"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/exportdata' class='mn'>".$lang_label["export_data"]."</a></li></ul></div>";
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/estadisticas") {
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estadisticas' class='mn'>".$lang_label["statistics"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estadisticas' class='mn'>".$lang_label["statistics"]."</a></li></ul></div>";
		}
	}
	if (give_acl($_SESSION["id_usuario"], 0, "AR")==1) {
		if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/servers/view_server") {
			echo '<div id="op2s"><ul class="mn"><li><a href="index.php?sec=estado_server&amp;sec2=operation/servers/view_server&amp;refr=60" class="mn">'.$lang_label["view_servers"].'</a></li></ul></div>';
		}
		else echo '<div id="op2"><ul class="mn"><li><a href="index.php?sec=estado_server&amp;sec2=operation/servers/view_server&amp;refr=60" class="mn">'.$lang_label["view_servers"].'</a></li></ul></div>';
	}
	if (give_acl($_SESSION["id_usuario"], 0, "IR")==1) {
		if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/incidents/incident") {
			echo '<div id="op3s"><ul class="mn"><li><a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident" class="mn">'.$lang_label["manage_incidents"].'</a></li></ul></div>';
		}
		else echo '<div id="op3"><ul class="mn"><li><a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident" class="mn">'.$lang_label["manage_incidents"].'</a></li></ul></div>';
		if (isset($_GET["sec"]) && $_GET["sec"] == "incidencias"){
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/incidents/incident_search") {
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=incidencias&amp;sec2=operation/incidents/incident_search' class='mn'>".$lang_label["search_incident"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=incidencias&amp;sec2=operation/incidents/incident_search' class='mn'>".$lang_label["search_incident"]."</a></li></ul></div>";
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/incidents/incident_statistics") {
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=incidencias&amp;sec2=operation/incidents/incident_statistics' class='mn'>".$lang_label["statistics"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=incidencias&amp;sec2=operation/incidents/incident_statistics' class='mn'>".$lang_label["statistics"]."</a></li></ul></div>";
		}
	}
	if (give_acl($_SESSION["id_usuario"], 0, "AR")==1) {
		if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/events/events") {
			echo '<div id="op4s"><ul class="mn"><li><a href="index.php?sec=eventos&amp;sec2=operation/events/events" class="mn">'.$lang_label["view_events"].'</a></li></ul></div>';
		}
		else echo '<div id="op4"><ul class="mn"><li><a href="index.php?sec=eventos&amp;sec2=operation/events/events" class="mn">'.$lang_label["view_events"].'</a></li></ul></div>';
		if (isset($_GET["sec"]) && $_GET["sec"] == "eventos"){
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/events/event_statistics") {
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=eventos&amp;sec2=operation/events/event_statistics' class='mn'>".$lang_label["statistics"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=eventos&amp;sec2=operation/events/event_statistics' class='mn'>".$lang_label["statistics"]."</a></li></ul></div>";
		}
	}
		if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/users/user") {
			echo '<div id="op5s"><ul class="mn"><li><a href="index.php?sec=usuarios&amp;sec2=operation/users/user" class="mn">'.$lang_label["view_users"].'</a></li></ul></div>';
		}
		else echo '<div id="op5"><ul class="mn"><li><a href="index.php?sec=usuarios&amp;sec2=operation/users/user" class="mn">'.$lang_label["view_users"].'</a></li></ul></div>';
		
		if (isset($_GET["sec"]) && $_GET["sec"] == "usuarios"){
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/users/user_edit"){
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=usuarios&amp;sec2=operation/users/user_edit&ver=".$_SESSION["id_usuario"]."' class='mn'>".$lang_label["index_myuser"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=usuarios&amp;sec2=operation/users/user_edit&ver=".$_SESSION["id_usuario"]."' class='mn'>".$lang_label["index_myuser"]."</a></li></ul></div>";
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/users/user_statistics") {
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=usuarios&amp;sec2=operation/users/user_statistics' class='mn'>".$lang_label["statistics"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=usuarios&amp;sec2=operation/users/user_statistics' class='mn'>".$lang_label["statistics"]."</a></li></ul></div>";
		
		}
		if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/snmpconsole/snmp_view") {
			echo '<div id="op6s"><ul class="mn"><li><a href="index.php?sec=snmpconsole&amp;sec2=operation/snmpconsole/snmp_view&amp;refr=30" class="mn">'.$lang_label["SNMP_console"].'</a></li></ul></div>';
		}
		else echo '<div id="op6"><ul class="mn"><li><a href="index.php?sec=snmpconsole&amp;sec2=operation/snmpconsole/snmp_view&amp;refr=30" class="mn">'.$lang_label["SNMP_console"].'</a></li></ul></div>';

		if (isset($_GET["sec"]) && $_GET["sec"] == "snmpconsole"){
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/snmpconsole/snmp_alert") {
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=snmpconsole&amp;sec2=operation/snmpconsole/snmp_alert' class='mn'>".$lang_label["snmp_console_alert"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=snmpconsole&amp;sec2=operation/snmpconsole/snmp_alert' class='mn'>".$lang_label["snmp_console_alert"]."</a></li></ul></div>";
		}
		if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/messages/message" && !isset($_GET["nuevo_g"])) {
			echo '<div id="op7s"><ul class="mn"><li><a href="index.php?sec=messages&amp;sec2=operation/messages/message" class="mn">'. $lang_label["messages"].'</a></li></ul></div>';
		}
		else echo '<div id="op7"><ul class="mn"><li><a href="index.php?sec=messages&amp;sec2=operation/messages/message" class="mn">'. $lang_label["messages"].'</a></li></ul></div>';
		if (isset($_GET["sec"]) && $_GET["sec"] == "messages"){
			if(isset($_GET["sec2"]) && isset($_GET["nuevo_g"])) {
				echo "<div id='arrows'><ul class='mn'><li><a href='index.php?sec=messages&amp;sec2=operation/messages/message&amp;nuevo_g' class='mn'>".$lang_label["messages_g"]."</a></li></ul></div>";
			}
			else echo "<div id='arrow'><ul class='mn'><li><a href='index.php?sec=messages&amp;sec2=operation/messages/message&amp;nuevo_g' class='mn'>".$lang_label["messages_g"]."</a></li></ul></div>";
		}
?>		
	</div>
</div>	
<?php
}
?>