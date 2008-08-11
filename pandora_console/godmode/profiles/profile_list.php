<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2007
// Raul Mateos <raulofpandora@gmail.com>, 2005-2007

// Global variables
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Profile Management");
	require ("general/noaccess.php");
	return;
}

// Profile deletion
if (isset($_GET["delete_profile"])){ // if any parameter is modified
	$id_perfil= entrada_limpia($_GET["delete_profile"]);
	// Delete profile
	$query_del1="DELETE FROM tperfil WHERE id_perfil = '".$id_perfil."'";
	$query_del2="DELETE FROM tusuario_perfil WHERE id_perfil = '".$id_perfil."'";
	$resq1=mysql_query($query_del1);
		if (! $resq1)
			echo "<h3 class='error'>".__('delete_profile_no')."</h3>";
		else
			echo "<h3 class='suc'>".__('delete_profile_ok')."</h3>";
	$resq1=mysql_query($query_del2);
	unset($id_perfil); // forget it to show list
}
// Profile creation
elseif (isset($_GET["new_profile"])){ // create a new profile
	$id_perfil = -1;
	$name = "";
	$incident_view = "0";
	$incident_edit = "0";
	$incident_create = "0";
	$incident_management = "0";
	$agent_view = "0";
	$agent_edit ="0";
	$alert_edit = "0";
	$user_management = "0";
	$db_management = "0";
	$alert_management = "0";
	$pandora_management = "0";
} elseif (isset($_GET["edit_profile"])){ // Edit profile (read data to show in form)
	// Profile edit
	$id_perfil= entrada_limpia($_GET["edit_profile"]);
	$query_del1="SELECT * FROM tperfil WHERE id_perfil = '".$id_perfil."'";
	$resq1=mysql_query($query_del1);
	$rowq1=mysql_fetch_array($resq1);
	if (!$rowq1){
		echo "<h3 class='error'>".__('profile_error')."</h3>";
		echo "</table>";
		include ("general/footer.php");
		exit;
	}
	else

	$name = $rowq1["name"];
	$incident_view = $rowq1["incident_view"];
	$incident_edit = $rowq1["incident_edit"];
	$incident_management = $rowq1["incident_management"];
	$agent_view = $rowq1["agent_view"];
	$agent_edit =$rowq1["agent_edit"];
	$alert_edit = $rowq1["alert_edit"];
	$user_management = $rowq1["user_management"];
	$db_management = $rowq1["db_management"];
	$alert_management = $rowq1["alert_management"];
	$pandora_management = $rowq1["pandora_management"];
} elseif (isset($_GET["update_data"])){ // Update or Create a new record (writes on DB)
	// Profile edit
	$incident_view = "0";
	$incident_edit = "0";
	$incident_create = "0";
	$incident_management = "0";
	$agent_view = "0";
	$agent_edit ="0";
	$alert_edit = "0";
	$user_management = "0";
	$db_management = "0";
	$alert_management = "0";
	$pandora_management = "0";
	$id_perfil= entrada_limpia($_POST["id_perfil"]);
	$name = entrada_limpia($_POST["name"]);
	
	if (isset ($_POST["incident_view"]))$incident_view = entrada_limpia($_POST["incident_view"]);
	if (isset ($_POST["incident_edit"])) $incident_edit = entrada_limpia($_POST["incident_edit"]);
	if (isset ($_POST["incident_management"])) $incident_management = entrada_limpia($_POST["incident_management"]);
	if (isset ($_POST["agent_view"]))  $agent_view = entrada_limpia($_POST["agent_view"]);
	if (isset ($_POST["agent_edit"])) $agent_edit =entrada_limpia($_POST["agent_edit"]);
	if (isset ($_POST["alert_edit"])) $alert_edit = entrada_limpia($_POST["alert_edit"]);
	if (isset ($_POST["user_management"])) $user_management = entrada_limpia($_POST["user_management"]);
	if (isset ($_POST["db_management"])) $db_management = entrada_limpia($_POST["db_management"]);			
	if (isset ($_POST["alert_management"])) $alert_management = entrada_limpia($_POST["alert_management"]);
	if (isset ($_POST["pandora_management"])) $pandora_management = entrada_limpia($_POST["pandora_management"]);
	
	// update or insert ??
	
	if ($id_perfil == -1) { // INSERT
		$query = "INSERT INTO tperfil (name,incident_view,incident_edit,incident_management, agent_view,agent_edit,alert_edit,user_management,db_management,alert_management,pandora_management) VALUES 
		('".$name."','".$incident_view."','".$incident_edit."','".$incident_management."','".$agent_view."','".$agent_edit."','".$alert_edit."','".$user_management."','".$db_management."','".$alert_management."','".$pandora_management."')";
		// echo "DEBUG: ".$query;
		$res=mysql_query($query);
		if ($res)
			echo "<h3 class='suc'>".__('create_profile_ok')."</h3>";
		else {
			echo "<h3 class='error'>".__('create_profile_no')."</h3>";
		}

	} else { // UPDATE
		$query ="UPDATE tperfil SET 
		name = '$name',
		incident_view = $incident_view,
		incident_edit = $incident_edit,
		incident_management = $incident_management,
		agent_view = $agent_view,
		agent_edit = $agent_edit,
		alert_edit = $alert_edit,
		user_management = $user_management,
		db_management = $db_management,
		alert_management = $alert_management,
		pandora_management = $pandora_management 
		WHERE id_perfil = $id_perfil ";
		// echo "DEBUG: ".$query;
		$res=mysql_query($query);
		echo "<h3 class='suc'>".__('profile_upd')."</h3>";
	}
 	unset($id_perfil);
}
echo '<h2>'.__('profile_title').' &gt; ';
echo (isset($_GET["new_profile"])) ?
	(__('create_profile').'</h2>'):
	(
	(isset($_GET["edit_profile"]))?
	(__('update_profile').'></h2>'):
	(__('definedprofiles').'</h2>')
	);
// Form to manage date
if (isset ($id_perfil)){ // There are values defined, let's show form with data for INSERT or UPDATE
	echo "<table width='400' cellpadding='4' cellspacing='4' class='databox'>";
	echo "<tr>";
	echo "<form method='post' action='index.php?sec=gperfiles&sec2=godmode/profiles/profile_list&update_data'>";
	echo "<input type=hidden name=id_perfil value='".$id_perfil."'>";
	echo "
	<td class=datos>".__('profile_name')."</td>
	<td class=datos>
	<input name='name' type=text size='27' value='".$name."'></td></tr>
	<tr><td class=datos2>".__('incident_view')."</td>
	<td class=datos2>
	<input name='incident_view' type=checkbox class='chk' value='1' ";
	if ($incident_view == 1) echo "checked"; echo "></td></tr>
	<tr><td class=datos>".__('incident_edit')."</td>
	<td class=datos>
	<input name='incident_edit' type=checkbox class='chk' value='1' ";
	if ($incident_edit == 1) echo "checked";echo "></td></tr>
	<tr><td class=datos2>".__('manage_incidents')."</td>
	<td class=datos2>
	<input name='incident_management' type=checkbox class='chk' value='1' ";
	if ($incident_management == 1) echo "checked";echo "></td></tr>
	<tr><td class=datos>".__('view_agents')."</td>
	<td class=datos>
	<input name='agent_view' type=checkbox class='chk' value='1' ";
	if ($agent_view == 1) echo "checked";echo "></td></tr>
	<tr><td class=datos2>".__('agent_edit')."</td>
	<td class=datos2>
	<input name='agent_edit' type=checkbox class='chk' value='1' ";
	if ($agent_edit == 1) echo "checked";echo "></td></tr>
	<tr><td class=datos>".__('alert_edit')."</td>
	<td class=datos>
	<input name='alert_edit' type=checkbox class='chk' value='1' ";
	if ($alert_edit == 1) echo "checked";echo "></td></tr>
	<tr><td class=datos2>".__('manage_users')."</td>
	<td class=datos2>
	<input name='user_management' class='chk' type=checkbox value='1' ";
	if ($user_management == 1) echo "checked";echo "></td></tr>
	<tr><td class=datos>".__('manage_db')."</td>
	<td class=datos>
	<input name='db_management' class='chk' type=checkbox value='1' ";
	if ($db_management == 1) echo "checked";echo "></td></tr>
	<tr><td class=datos2>".__('manage_alerts')."</td>
	<td class=datos2>
	<input name='alert_management' class='chk' type=checkbox value='1' ";
	if ($alert_management == 1) echo "checked";echo "></td></tr>
	<tr><td class=datos>".__('pandora_management')."</td>
	<td class=datos>
	<input name='pandora_management' class='chk' type=checkbox value='1' ";
	if ($pandora_management == 1) echo "checked";echo "></td></tr>
	";
	echo "</table>";
	echo "<table width='400'>";
	echo "<tr><td align='right'>";
	
	if (isset($_GET["new_profile"])){
		echo "
		<input name='crtbutton' type='submit' class='sub wand' value='".__('create')."'>";
	}
	if (isset($_GET["edit_profile"])){
		echo "
		<input name='uptbutton' type='submit' class='sub upd' value='" .__('update')."'>";
	}
	echo "</td></tr></table>";
	
} else { // View list data
	$color=1;
	?>
	<table cellpadding='4' cellspacing='4' class='databox' width='750px'>
	<?php
	$query_del1="SELECT * FROM tperfil";
	$resq1=mysql_query($query_del1);
	echo "<tr>";
	echo "<th width='180px'><font size=1>".__('profiles');
	echo "</th><th width='40px'>
	<font size=1>IR<a href='#' class='tipp'>&nbsp;<span>".$help_label["IR"]."</span></a>";
	echo "</th><th width='40px'>
	<font size=1>IW<a href='#' class='tipp'>&nbsp;<span>".$help_label["IW"]."</span></a>";
	echo "</th><th width='40px'>
	<font size=1>IM<a href='#' class='tipp'>&nbsp;<span>".$help_label["IM"]."</span></a>";
	echo "</th><th width='40px'>
	<font size=1>AR<a href='#' class='tipp'>&nbsp;<span>".$help_label["AR"]."</span></a>";
	echo "</th><th width='40px'>
	<font size=1>AW<a href='#' class='tipp'>&nbsp;<span>".$help_label["AW"]."</span></a>";
	echo "</th><th width='40px'>
	<font size=1>LW<a href='#' class='tipp'>&nbsp;<span>".$help_label["LW"]."</span></a>";
	echo "</th><th width='40px'>
	<font size=1>UM<a href='#' class='tipp'>&nbsp;<span>".$help_label["UM"]."</span></a>";
	echo "</th><th width='40px'>
	<font size=1>DM<a href='#' class='tipp'>&nbsp;<span>".$help_label["DM"]."</span></a>";
	echo "</th><th width='40px'>
	<font size=1>LM<a href='#' class='tipp'>&nbsp;<span>".$help_label["LM"]."</span></a>";
	echo "</th><th width='40px'>
	<font size=1>PM<a href='#' class='tipp'>&nbsp;<span>".$help_label["PM"]."</span></a>";
	echo "</th><th width='40px'>".__('delete')."</th></tr>";
	while ($rowdup=mysql_fetch_array($resq1)){
		$id_perfil = $rowdup["id_perfil"];
		$nombre=$rowdup["name"];
		$incident_view = $rowdup["incident_view"];
		$incident_edit = $rowdup["incident_edit"];
		$incident_management = $rowdup["incident_management"];
		$agent_view = $rowdup["agent_view"];
		$agent_edit =$rowdup["agent_edit"];
		$alert_edit = $rowdup["alert_edit"];
		$user_management = $rowdup["user_management"];
		$db_management = $rowdup["db_management"];
		$alert_management = $rowdup["alert_management"];
		$pandora_management = $rowdup["pandora_management"];
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<td class='$tdcolor'><a href='index.php?sec=gperfiles&amp;sec2=godmode/profiles/profile_list&amp;edit_profile=".$id_perfil."'><b>".$nombre."</b></a>";
		
		echo "</td><td class='$tdcolor'>";
		if ($incident_view == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "</td><td class='$tdcolor'>";
		if ($incident_edit == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "</td><td class='$tdcolor'>";
		if ($incident_management == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "</td><td class='$tdcolor'>";
		if ($agent_view == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "</td><td class='$tdcolor'>";
		if ($agent_edit == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "</td><td class='$tdcolor'>";
		if ($alert_edit == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "</td><td class='$tdcolor'>";
		if ($user_management == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "</td><td class='$tdcolor'>";
		if ($db_management == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "<td class='$tdcolor'>";
		if ($alert_management == 1) echo "<img src='images/ok.png' border=0>";
			
		echo "</td><td class='$tdcolor'>";
		if ($pandora_management == 1) echo "<img src='images/ok.png' border=0>";
		echo "<td class='$tdcolor' align='center'><a href='index.php?sec=gagente&sec2=godmode/profiles/profile_list&delete_profile=".$id_perfil."' onClick='if (!confirm(\' ".__('are_you_sure')."\')) return false;'><img border='0' src='images/cross.png'></a></td></tr>";
		
	}
		echo "</table>";
		echo '</tr></table>';
		echo '<table width="750">';
		echo '<tr><td align="right">';
		echo "<form method=post action='index.php?sec=gperfiles&sec2=godmode/profiles/profile_list&new_profile=1'>";
		echo "<input type='submit' class='sub next' name='crt' value='".__('create_profile')."'>";
		echo "</form></table>";
}
?>
