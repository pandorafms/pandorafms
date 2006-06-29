<?PHP 
// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnológicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
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