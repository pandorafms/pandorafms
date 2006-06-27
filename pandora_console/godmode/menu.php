<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2004-2006
?>
<?php
if (comprueba_login() == 0){
	$id_user = $_SESSION["id_usuario"];
	if ( (give_acl($id_user, 0, "LM")==1) OR (give_acl($id_user, 0, "AW")==1 ) OR (give_acl($id_user, 0, "PM")==1) OR (give_acl($id_user, 0, "DM")==1) OR (give_acl($id_user, 0, "UM")==1 )){

?>

<div class="bg">
	<div class="imgl"><img src="images/upper-left-corner.gif" width="5" height="5" alt=""></div>
	<div class="tit"><?php echo $lang_label["godmode_header"] ?></div>
	<div class="imgr"><img src="images/upper-right-corner.gif" width="5" height="5" alt=""></div>
</div>
<div id="menug">
	<div id="god">
	
<?php 
	if ((give_acl($id_user, 0, "AW")==1)){
		if (isset($_GET["sec2"]) && ($_GET["sec2"] == "godmode/agentes/modificar_agente" || $_GET["sec2"] == "godmode/agentes/configurar_agente")){
			echo '<div id="god1s">';
		}
		else echo '<div id="god1">';
		echo '<ul class="mn"><li><a href="index.php?sec=gagente&amp;sec2=godmode/agentes/modificar_agente" class="mn">'.$lang_label["manage_agents"].'</a></li></ul></div>';
		
		if (isset($_GET["sec"]) && $_GET["sec"] == "gagente"){
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/agentes/manage_config"){
				echo "<div id='arrowgs1'>";
			}
			else echo "<div id='arrowg1'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gagente&amp;sec2=godmode/agentes/manage_config' class='mn'>".$lang_label["manage_config"]."</a></li></ul></div>";
			
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/grupos/lista_grupos"){
				echo "<div id='arrowgs1'>";
			}
			else echo "<div id='arrowg1'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gagente&amp;sec2=godmode/grupos/lista_grupos' class='mn'>".$lang_label["manage_groups"]."</a></li></ul></div>";
		}
	}
	if ((give_acl($id_user, 0, "LM")==1)){
		if (isset($_GET["sec2"]) && ($_GET["sec2"] == "godmode/alertas/modificar_alerta" || $_GET["sec2"] == "godmode/alertas/configurar_alerta")){
			echo '<div id="god2s">';
		}
		else echo '<div id="god2">';
		echo '<ul class="mn"><li><a href="index.php?sec=galertas&amp;sec2=godmode/alertas/modificar_alerta" class="mn">'.$lang_label["manage_alerts"].'</a></li></ul></div>';
	}
	if ((give_acl($id_user, 0, "UM")==1)){
		if (isset($_GET["sec2"]) && ($_GET["sec2"] == "godmode/usuarios/lista_usuarios" || $_GET["sec2"] == "godmode/grupos/configurar_grupo")){
			echo '<div id="god3s">';
		}
		else echo '<div id="god3">';
		echo '<ul class="mn"><li><a href="index.php?sec=gusuarios&amp;sec2=godmode/usuarios/lista_usuarios" class="mn">'.$lang_label["manage_users"].'</a></li></ul></div>';
	}
	if ( (give_acl($id_user, 0, "PM")==1)){
		if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/perfiles/lista_perfiles"){
			echo '<div id="god4s">';
		}
		else echo '<div id="god4">';
		echo '<ul class="mn"><li><a href="index.php?sec=gperfiles&amp;sec2=godmode/perfiles/lista_perfiles" class="mn">'.$lang_label["manage_profiles"].'</a></li></ul></div>';
		
		if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/servers/modificar_server"){
			echo '<div id="god5s">';
        }
		else echo '<div id="god5">';
		echo '<ul class="mn"><li><a href="index.php?sec=gservers&amp;sec2=godmode/servers/modificar_server" class="mn">'.$lang_label["manage_servers"].'</a></li></ul></div>';
        
		if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/admin_access_logs"){
			echo '<div id="god6s">';
		}
		else echo '<div id="god6">';
		echo '<ul class="mn"><li><a href="index.php?sec=glog&amp;sec2=godmode/admin_access_logs" class="mn">'.$lang_label["system_audit"].'</a></li></ul></div>';
		
		if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/setup/setup"){
			echo '<div id="god7s">';
		}
		else echo '<div id="god7">';
		echo '<ul class="mn"><li><a href="index.php?sec=gsetup&amp;sec2=godmode/setup/setup" class="mn">'.$lang_label["setup_screen"].'</a></li></ul></div>';
		
		if (isset($_GET["sec"]) && $_GET["sec"] == "gsetup"){
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/setup/links"){
				echo "<div id='arrowgs1'>";
			}
			else echo "<div id='arrowg1'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gsetup&amp;sec2=godmode/setup/links' class='mn'>".$lang_label["setup_links"]."</a></li></ul></div>";
		}
	}
	if ((give_acl($id_user, 0, "DM")==1)){
		if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/db/db_main"){
			echo '<div id="god8s">';
		}
		else echo '<div id="god8">';
		echo '<ul class="mn"><li><a href="index.php?sec=gdbman&amp;sec2=godmode/db/db_main" class="mn">'.$lang_label["db_maintenance"].'</a></li></ul></div>';
		
		if (isset($_GET["sec"]) && $_GET["sec"] == "gdbman"){
			if (isset($_GET["sec2"]) && ($_GET["sec2"] == "godmode/db/db_info" || $_GET["sec2"] == "godmode/db/db_info_data")){
				echo "<div id='arrowgs1'>";
			}
			else echo "<div id='arrowg1'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_info' class='mn'>".$lang_label["db_info"]."</a></li></ul></div>";
			
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/db/db_purge"){
				echo "<div id='arrowgs2'>";
			}
			else echo "<div id='arrowg2'>";			
			echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_purge' class='mn'>".$lang_label["db_purge"]."</a></li></ul></div>";
			
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/db/db_refine"){
				echo "<div id='arrowgs3'>";
			}
			else echo "<div id='arrowg3'>";			
			echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_refine' class='mn'>".$lang_label["db_refine"]."</a></li></ul></div>";
			
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/db/db_audit"){
				echo "<div id='arrowgs4'>";
			}
			else echo "<div id='arrowg4'>";			
			echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_audit' class='mn'>".$lang_label["db_audit"]."</a></li></ul></div>";
			
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/db/db_event"){
				echo "<div id='arrowgs5'>";
			}
			else echo "<div id='arrowg5'>";			
			echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_event' class='mn'>".$lang_label["db_event"]."</a></li></ul></div>";
		}
	}
?>
	</div>
</div>
<?php
	} // end verify access to this menu
}
?>