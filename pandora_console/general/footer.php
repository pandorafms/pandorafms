<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006
?>

<div id="foot">
	<?php
	echo '<a target="_new" href="general/license/pandora_info_'.$language_code.'.html">Pandora ';
	echo $pandora_version." Build ";
	echo $build_version." "; 
	echo $lang_label["gpl_notice"];
	echo '</a><br>';
	if (isset($_SESSION['id_usuario'])) {
	echo $lang_label["gen_date"]." ".date("D F d, Y H:i:s",time())."<br>";
	}
	?>
	<i>Pandora is a <a target="_new" href="http://pandora.sourceforge.net">SourceForge registered project</a></i>
</div>