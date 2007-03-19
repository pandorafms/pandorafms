<?PHP
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas

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

if (comprueba_login() == 0)
  	$id_user = $_SESSION["id_usuario"];
else
	$id_user = "";

if (give_acl($id_user, 0, "PM")!=1) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation",
	"Trying to access Network Profile Management");
	require ("general/noaccess.php");
	exit;
}
  
if (isset($_GET["delete"])){ // if delete
	$id_np = entrada_limpia ($_GET["delete"]);
	$sql_delete= "DELETE FROM tnetwork_profile WHERE id_np = ".$id_np;
	$result=mysql_query($sql_delete);
	if (! $result)
		echo "<h3 class='error'>".$lang_label["delete_no"]."</h3>";
	else
		echo "<h3 class='suc'>".$lang_label["delete_ok"]."</h3>";
	
	$result=mysql_query($sql_delete);
}

echo "<h2>".$lang_label["network_profile_management"]."</h2>";

echo "<table cellpadding=4 cellspacing=4 width=550>";
echo "<th>".$lang_label["name"]."</th>";
echo "<th>".$lang_label["description"]."</th>";
echo "<th>".$lang_label["number_of_modules"]."</th>";
$sql1='SELECT * FROM tnetwork_profile ORDER BY name';
$result=mysql_query($sql1);
$color=0;
while ($row=mysql_fetch_array($result)){
	if ($color == 1){
		$tdcolor = "datos";
		$color = 0;
		}
	else {
		$tdcolor = "datos2";
		$color = 1;
	}
	echo "<tr>
			<td class='$tdcolor'>
			<b><a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np=".$row["id_np"]."'>".$row["name"]."</A></B>
			</td>
			<td class='$tdcolor'>
			".$row["description"]."
			</td>
			<td class='$tdcolor' align='center'>
			<a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates&delete=".$row["id_np"]."'
				onClick='if (!confirm(\' ".$lang_label["are_you_sure"]."\'))
			return false;'>
			<img border='0' src='images/cancel.gif'></a>
			</td>
		</tr>";

}
echo "<tr><td colspan='3'><div class='raya'></div></td></tr>";
echo "<tr><td colspan='3' align='right'>";
echo "<form method=post action='index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np=-1'>";
echo "<input type='submit' class='sub' name='crt' value='".$lang_label["create"]."'>";
echo "</form></td></tr></table>";

?>