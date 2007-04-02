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
		$name = entrada_limpia($_POST["name"]);
		$link = entrada_limpia($_POST["link"]);
		$sql_insert="INSERT INTO tlink (name,link) VALUES ('$name','$link') ";
		$result=mysql_query($sql_insert);	
		if (! $result)
			echo "<h3 class='error'>".$lang_label["create_link_no"]."</h3>";
		else {
			echo "<h3 class='suc'>".$lang_label["create_link_ok"]."</h3>"; 
			$id_link = mysql_insert_id();
		}
	}

	if (isset($_POST["update"])){ // if update
		$id_link = entrada_limpia($_POST["id_link"]);
		$name = entrada_limpia($_POST["name"]);
		$link = entrada_limpia($_POST["link"]);
    	$sql_update ="UPDATE tlink SET name = '".$name."', link ='".$link."'  WHERE id_link = '".$id_link."'";
		$result=mysql_query($sql_update);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["modify_link_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["modify_link_ok"]."</h3>";
	}
	
	if (isset($_GET["borrar"])){ // if delete
		$id_link = entrada_limpia($_GET["borrar"]);
		$sql_delete= "DELETE FROM tlink WHERE id_link = ".$id_link;
		$result=mysql_query($sql_delete);
		if (! $result)
			echo "<h3 class='error'>".$lang_label["delete_link_no"]."</h3>";
		else
			echo "<h3 class='suc'>".$lang_label["delete_link_ok"]."</h3>"; 

	}

	// Main form view for Links edit
	if ((isset($_GET["form_add"])) or (isset($_GET["form_edit"]))){
		if (isset($_GET["form_edit"])){
			$creation_mode = 0;
				$id_link = entrada_limpia($_GET["id_link"]);
				$sql1='SELECT * FROM tlink WHERE id_link = '.$id_link;
				$result=mysql_query($sql1);
				if ($row=mysql_fetch_array($result)){
						$nombre = $row["name"];
				$link = $row["link"];
                	}
				else echo "<h3 class='error'>".$lang_label["name_error"]."</h3>";
		} else { // form_add
			$creation_mode =1;
			$nombre = "";
			$link = "";
		}

		// Create link
        echo "<h2>".$lang_label["setup_screen"]."</h2>";
		echo "<h3>".$lang_label["link_management"]."<a href='help/".$help_code."/chap9.php#91' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
		echo '<table class="fon" cellpadding="3" cellspacing="3" width="500">';   
		echo '<form name="ilink" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/links">';
        	if ($creation_mode == 1)
				echo "<input type='hidden' name='create' value='1'>";
			else
				echo "<input type='hidden' name='update' value='1'>";
		echo "<input type='hidden' name='id_link' value='"; ?> 
		<?php if (isset($id_link)) {echo $id_link;} ?>
		<?php
		echo "'>";
		echo '<tr><td class="lb" rowspan="2" width="5"><td class="datos">'.$lang_label["link_name"].'<td class="datos"><input type="text" name="name" size="35" value="'.$nombre.'">';
		echo '<tr><td class="datos2">'.$lang_label["link"].'<td class="datos2"><input type="text" name="link" size="35" value="'.$link.'">';
		echo '<tr><td colspan="5"><div class="raya"></div></td></tr>';
		echo "<tr><td colspan='3' align='right'><input name='crtbutton' type='submit' class='sub' value='".$lang_label["update"]."'>";
		echo '</form></table>';
	}

	else {  // Main list view for Links editor
		echo "<h2>".$lang_label["setup_screen"]."</h2>";
		echo "<h3>".$lang_label["link_management"]."<a href='help/".$help_code."/chap9.php#9' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
		echo "<table cellpadding=3 cellspacing=3>";
		echo "<th class='w180'>".$lang_label["link_name"];
		echo "<th class='w80'>".$lang_label["delete"];
		$sql1='SELECT * FROM tlink ORDER BY name';
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
			echo "<tr><td class='$tdcolor'><b><a href='index.php?sec=gsetup&sec2=godmode/setup/links&form_edit=1&id_link=".$row["id_link"]."'>".$row["name"]."</a></b>";
			echo '<td class="'.$tdcolor.'" align="center"><a href="index.php?sec=gsetup&sec2=godmode/setup/links&id_link='.$row["id_link"].'&borrar='.$row["id_link"].'" onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;"><img border=0 src="images/cross.png"></a>';
		}
			echo "<tr><td colspan='2'><div class='raya'></div></td>";
			echo "<tr><td colspan='2' align='right'>";
			echo "<form method='post' action='index.php?sec=gsetup&sec2=godmode/setup/links&form_add=1'>";
			echo "<input type='submit' class='sub next' name='form_add' value='".$lang_label["add"]."'>";
			echo "</form></table>";
} // Fin bloque else
} else  {
			audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Link Management");
			require ("general/noaccess.php");
	}

?>