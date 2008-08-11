<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2007
// Raul Mateos <raulofpandora@gmail.com>, 2005-2007

// Load global vars
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Link Management");
	require ("general/noaccess.php");
	return;
}

if (isset ($_POST["create"])) { // If create
	$subject = get_parameter ("subject");
	$text = get_parameter ("text");
	$timestamp = $ahora = date ("Y/m/d H:i:s");
	$author = $config['id_user'];
	
	$sql = "INSERT INTO tnews (subject, text, author, timestamp) VALUES ('$subject','$text', '$author', '$timestamp') ";
	$result = mysql_query ($sql_insert);
	if (! $result) {
		echo "<h3 class='error'>".__('create_no')."</h3>";
	} else {
		echo "<h3 class='suc'>".__('create_ok')."</h3>";
		$id_link = mysql_insert_id ();
	}
}

if (isset ($_POST["update"])) { // if update
	$id_news = get_parameter ("id_news");
	$subject = get_parameter ("subject");
	$text = get_parameter ("text");
	$timestamp = $ahora = date("Y/m/d H:i:s");
	$sql_update ="UPDATE tnews SET subject = '".$subject."', text ='".$text."', timestamp = '$timestamp' WHERE id_news = '".$id_news."'";
	$result = mysql_query($sql_update);
	if (! $result)
		echo "<h3 class='error'>".__('modify_no')."</h3>";
	else
		echo "<h3 class='suc'>".__('modify_ok')."</h3>";
}

if (isset ($_GET["borrar"])) { // if delete
	$id_news = get_parameter ("borrar");
	$sql_delete = "DELETE FROM tnews WHERE id_news = ".$id_news;
	$result = mysql_query ($sql_delete);
	if (! $result)
		echo "<h3 class='error'>".__('delete_no')."</h3>";
	else
		echo "<h3 class='suc'>".__('delete_ok')."</h3>";
}

// Main form view for Links edit
if ((isset ($_GET["form_add"])) || (isset ($_GET["form_edit"]))) {
	if (isset($_GET["form_edit"])) {
		$creation_mode = 0;
		$id_news = get_parameter ("id_news");
		$sql = 'SELECT * FROM tnews WHERE id_news = '.$id_news;
		$result = mysql_query ($sql);
		if ($row = mysql_fetch_array ($result)) {
			$subject = $row["subject"];
			$text = $row["text"];
			$author = $row["author"];
			$timestamp = $row["timestamp"];
		} else {
			echo "<h3 class='error'>".__('name_error')."</h3>";
		}
	} else { // form_add
		$creation_mode =1;
		$text = "";
		$subject = "";
		$author = $config['id_user'];
	}

	// Create news
	echo "<h2>".__('setup_screen')." &gt; ";
	echo __('site_news_management')."</h2>";
	echo '<table class="databox" cellpadding="4" cellspacing="4" width="500">';   
	echo '<form name="ilink" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/news">';
	if ($creation_mode == 1)
		echo "<input type='hidden' name='create' value='1'>";
	else
		echo "<input type='hidden' name='update' value='1'>";
	echo "<input type='hidden' name='id_news' value='"; 
	if (isset($id_news)) {
		echo $id_news;
	} 
	echo "'>";
	echo '<tr>
	<td class="datos">'.__('subject').'</td>
	<td class="datos"><input type="text" name="subject" size="35" value="'.$subject.'">';
	echo '<tr>
	<td class="datos2">'.__('text').'</td>
	<td class="datos2">
	<textarea rows=4 cols=50 name="text" >';
	echo $text;
	echo '</textarea></td>';
	echo '</tr>';	
	echo "</table>";
	echo "<table width='500px'>";
	echo "<tr><td align='right'>
	<input name='crtbutton' type='submit' class='sub upd' value='".__('update')."'>";
	echo '</form></td></tr></table>';
} else {  // Main list view for Links editor
	echo "<h2>".__('setup_screen')." &gt; ";
	echo  __('site_news_management')."</h3>";
	echo "<table cellpadding='4' cellspacing='4' class='databox' width=600>";
	echo "<th>".__('subject')."</th>";
	echo "<th>".__('author')."</th>";
	echo "<th>".__('timestamp')."</th>";
	echo "<th>".__('delete')."</th>";
	$sql = 'SELECT * FROM tnews ORDER BY timestamp';
	$result = mysql_query ($sql);
	$color = 1;
	while ($row=mysql_fetch_array($result)){
		if ($color == 1) {
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr><td class='$tdcolor'><b><a href='index.php?sec=gsetup&sec2=godmode/setup/news&form_edit=1&id_news=".$row["id_news"]."'>".$row["subject"]."</a></b></td>";

		echo "<td class='$tdcolor'>".$row["author"]."</b></td>";
		echo "<td class='$tdcolor'>".$row["timestamp"]."</b></td>";
		
		echo '<td class="'.$tdcolor.'" align="center"><a href="index.php?sec=gsetup&sec2=godmode/setup/news&id_news='.$row["id_news"].'&borrar='.$row["id_news"].'" onClick="if (!confirm(\' '.__('are_you_sure').'\')) return false;"><img border=0 src="images/cross.png"></a></td></tr>';
	}
	echo "</table>";
	echo "<table width='600'>";
	echo "<tr><td align='right'>";
	echo "<form method='post' action='index.php?sec=gsetup&sec2=godmode/setup/news&form_add=1'>";
	echo "<input type='submit' class='sub next' name='form_add' value='".__('add')."'>";
	echo "</form></table>";
}
?>
