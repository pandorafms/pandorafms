<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function vnc_view() {
	$idAgent = (int)get_parameter('id_agente', 0);
	$ipAgent = get_db_value('direccion', 'tagente', 'id_agente', $idAgent);

	echo '<APPLET CODE="VncViewer.class" ARCHIVE="extensions/vnc/VncViewer.jar" WIDTH=750 HEIGHT=800>';
	echo '<param name="Host" value="'.$ipAgent.'">';
	echo '<param name="Port" value="5901">';
	echo '<PARAM NAME="Scaling factor" VALUE=75>';
	echo '</APPLET>';

/* 	<iframe width="95%" height="500px" src="http://<?php echo $ipAgent;?>:5801"></iframe> */
}

add_extension_opemode_tab_agent('vnc_view', __('VNC view'), 'images/computer.png', 'vnc_view');
?>
