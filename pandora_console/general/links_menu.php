<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2004-2006

$sql1='SELECT * FROM tlink ORDER BY name';
$result=mysql_query($sql1);
if ($row=mysql_fetch_array($result)){
?>
<div class="bg4">
	<div class="imgl"><img src="images/upper-left-corner.gif" width="5" height="5" alt=""></div>
	<div class="tit">:: <?php echo $lang_label["links_header"] ?> ::</div>
	<div class="imgr"><img src="images/upper-right-corner.gif" width="5" height="5" alt=""></div>
</div>
	<div id="menul">
	<div id="link">
<?php
	$sql1='SELECT * FROM tlink ORDER BY name';
	$result2=mysql_query($sql1);
		while ($row2=mysql_fetch_array($result2)){
			echo "<div class='linkli'><ul class='mn'><li><a href='".$row2["link"]."' target='_new' class='mn'>".$row2["name"]."</a></li></ul></div>";
		}
	echo "</div></div>";
}
?>