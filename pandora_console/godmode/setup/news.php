<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2007
// Raul Mateos <raulofpandora@gmail.com>, 2005-2007

// Load global vars
if (comprueba_login() == 0)
   $id_user = $_SESSION["id_usuario"];
   if (give_acl($id_user, 0, "PM")==1) {

	if (isset($_POST["create"])){ // If create
		$subject = entrada_limpia($_POST["subject"]);
		$text = entrada_limpia($_POST["text"]);
		$timestamp = $ahora=date("Y/m/d H:i:s");
		$author = $id_user;
		
		$sql_insert="INSERT INTO tnews (subject, text, author, timestamp) VALUES ('$subject','$text', '$author', '$timestamp') ";
		$result=mysql_query($sql_insert);	
		if (! $result)
			echo "<h3 class='error'>".$lang_label["create_no"]."</h3>";
		else {
			echo "<h3 class='suc'>".$lang_label["create_ok"]."</h3>";
			$id_link = mysql_insert_id();
		}
	}

	if (isset($_POST["update"])){ // if update
		$id_news = entrada_limpia($_POST["id_news"]);
		$subject = entrada_limpia($_POST["subject"]);
		$text = entrada_limpia($_POST["text"]);
		$timestamp = $ahora=date("Y/m/d H:i:s");
    	$sql_update ="UPDATE tnews SET subject = '".$subject."', text ='".$text."', timestamp = '$timestamp' WHERE id_news = '".$id_news."'";
		$result=mysql_query($sql_update);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["modify_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["modify_ok"]."</h3>";
	}
	
	if (isset($_GET["borrar"])){ // if delete
		$id_news = entrada_limpia($_GET["borrar"]);
		$sql_delete= "DELETE FROM tnews WHERE id_news = ".$id_news;
		$result=mysql_query($sql_delete);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["delete_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["delete_ok"]."</h3>";

	}

	// Main form view for Links edit
	if ((isset($_GET["form_add"])) or (isset($_GET["form_edit"]))){
		if (isset($_GET["form_edit"])){
			$creation_mode = 0;
				$id_news = entrada_limpia($_GET["id_news"]);
				$sql1='SELECT * FROM tnews WHERE id_news = '.$id_news;
				$result=mysql_query($sql1);
				if ($row=mysql_fetch_array($result)){
						$subject = $row["subject"];
						$text = $row["text"];
						$author = $row["author"];
						$timestamp = $row["timestamp"];
                	}
				else echo "<h3 class='error'>".$lang_label["name_error"]."</h3>";
		} else { // form_add
			$creation_mode =1;
			$text = "";
			$subject = "";
			$author = $id_user;
		}

		// Create news
        echo "<h2>".$lang_label["setup_screen"]." &gt; ";
		echo $lang_label["site_news_management"]."</h2>";
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
		<td class="datos">'.$lang_label["subject"].'</td>
		<td class="datos"><input type="text" name="subject" size="35" value="'.$subject.'">';
		echo '<tr>
		<td class="datos2">'.$lang_label["text"].'</td>
		<td class="datos2">
		<textarea rows=4 cols=50 name="text" >';
		echo $text;
		echo '</textarea></td>';
		echo '</tr>';	
		echo "</table>";
		echo "<table width='500px'>";
		echo "<tr><td align='right'>
		<input name='crtbutton' type='submit' class='sub upd' value='".$lang_label["update"]."'>";
		echo '</form></td></tr></table>';
	}

	else {  // Main list view for Links editor
		echo "<h2>".$lang_label["setup_screen"]." &gt; ";
		echo  $lang_label["site_news_management"]."</h3>";
		echo "<table cellpadding='4' cellspacing='4' class='databox' width=600>";
		echo "<th>".$lang_label["subject"]."</th>";
		echo "<th>".$lang_label["author"]."</th>";
		echo "<th>".$lang_label["timestamp"]."</th>";
		echo "<th>".$lang_label["delete"]."</th>";
		$sql1='SELECT * FROM tnews ORDER BY timestamp';
		$result=mysql_query($sql1);
		$color=1;
		while ($row=mysql_fetch_array($result)){
			if ($color == 1){
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
			
			echo '<td class="'.$tdcolor.'" align="center"><a href="index.php?sec=gsetup&sec2=godmode/setup/news&id_news='.$row["id_news"].'&borrar='.$row["id_news"].'" onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;"><img border=0 src="images/cross.png"></a></td></tr>';
		}
			echo "</table>";
			echo "<table width='600'>";
			echo "<tr><td align='right'>";
			echo "<form method='post' action='index.php?sec=gsetup&sec2=godmode/setup/news&form_add=1'>";
			echo "<input type='submit' class='sub next' name='form_add' value='".$lang_label["add"]."'>";
			echo "</form></table>";
} // Fin bloque else
} else  {
			audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Link Management");
			require ("general/noaccess.php");
	}

?>