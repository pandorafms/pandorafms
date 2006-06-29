<?php 

// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Cargamos variables globales
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");
if (comprueba_login() == 0) 
 	if ((give_acl($id_user, 0, "PM")==1) or (dame_admin($id_user)==1)) {	
	if (isset($_GET["update"])){
		$block_size=$_POST["block_size"];
		$language_code=$_POST["language_code"];
		$days_compact=$_POST["days_compact"];
		$days_purge=$_POST["days_purge"];
		$config_graph_res=$_POST["graph_res"];
		$config_step_compact=$_POST["step_compact"];
		$config_graph_order=$_POST["graph_order"];
		$config_truetype=$_POST["truetype"];
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$block_size."' WHERE TOKEN='block_size'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$language_code."' WHERE TOKEN='language_code'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$days_purge."' WHERE TOKEN='days_purge'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$days_compact." ' WHERE TOKEN='days_compact'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config_graph_res."' WHERE TOKEN='graph_res'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config_step_compact."' WHERE TOKEN='step_compact'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config_truetype."' WHERE TOKEN='truetype'");
		$result2=mysql_query("UPDATE tconfig SET VALUE='".$config_graph_order."' WHERE TOKEN='graph_order'");
	}	
	echo "<h2>".$lang_label["setup_screen"]."</h2>";
	echo "<h3>".$lang_label["general_config"]."<a href='help/".substr($language_code,0,2)."/chap7.php#7' target='_help'><img src='images/ayuda.gif' border='0' class='help'></a></h3>";
	echo "<form name='setup' method='POST' action='index.php?sec=godmode/setup/setup&update=1'>";
	echo '<table width="500" cellpadding="3" cellspacing="3">';
	echo '<tr><td class="lb" rowspan="8" width="5"></td><td class="datos">'.$lang_label["language_code"];
	echo '<td class="datos"><select name="language_code" onChange="javascript:this.form.submit();">';
	
	$sql="SELECT * FROM tlanguage";
	$result=mysql_query($sql);

	// This combo is dedicated to Raul... beautiful interface for dirty minds :-D
	$result2=mysql_query("SELECT * FROM tlanguage WHERE id_language = '$language_code'");
	if ($row2=mysql_fetch_array($result2)){
		echo '<option value="'.$row2["id_language"].'">'.$row2["name"];
	}
		
	while ($row=mysql_fetch_array($result)){
		echo "<option value=".$row["id_language"].">".$row["name"];
	}
	echo '</select>';
			
	echo '<tr><td class="datos">'.$lang_label["block_size"];
	echo '<td class="datos"><input type="text" name="block_size" size=5 value="'.$block_size.'">';
	
	echo '<tr><td class="datos">'.$lang_label["days_compact"];
	echo '<td class="datos"><input type="text" name="days_compact" size=5 value="'.$days_compact.'">';
	
	echo '<tr><td class="datos">'.$lang_label["days_purge"];
	echo '<td class="datos"><input type="text" name="days_purge" size=5 value="'.$days_purge.'">';
	
	echo '<tr><td class="datos">'.$lang_label["graph_res"];
	echo '<td class="datos"><input type="text" name="graph_res" size=5 value="'.$config_graph_res.'">';
	
	echo '<tr><td class="datos">'.$lang_label["step_compact"];
	echo '<td class="datos"><input type="text" name="step_compact" size=5 value="'.$config_step_compact.'">';

	echo '<tr><td class="datos">'.$lang_label["graph_order"];
	echo '<td class="datos"><select name="graph_order">';
	if ($config_graph_order==0) {
		echo '<option value="0">'.$lang_label["left_right"].'</option>';
		echo '<option value="1">'.$lang_label["right_left"].'</option>';
	}
	else {
		echo '<option value="1">'.$lang_label["right_left"].'</option>';
		echo '<option value="0">'.$lang_label["left_right"].'</option>';
	}
	
	echo '<tr><td class="datos">'.$lang_label["truetype"];
	echo '<td class="datos"><select name="truetype">';
	if ($config_truetype==1) {
		echo '<option value="1">'.$lang_label["active"].'</option>';
		echo '<option value="0">'.$lang_label["disabled"].'</option>';
	}
	else {
		echo '<option value="0">'.$lang_label["disabled"].'</option>';
		echo '<option value="1">'.$lang_label["active"].'</option>';
	}
	echo "<tr><td colspan='3'><div class='noraya'></div></td></tr>";
	echo "<tr><td colspan='3' align='right'>";
	echo '<input type="submit" class="sub" value="'.$lang_label["update"].'">';
	echo "</table>";
}
else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Database Management");
		require ("general/noaccess.php");
	}