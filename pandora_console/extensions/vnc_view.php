<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

function vnc_view() {
	$idAgent = (int)get_parameter('id_agente', 0);
	$ipAgent = db_get_value('direccion', 'tagente', 'id_agente', $idAgent);

	echo '<applet code="VncViewer.class" archive="extensions/vnc/VncViewer.jar" width="750" height="800">';
	echo '<param name="Host" value="'.$ipAgent.'">';
	echo '<param name="Port" value="5901">';
	echo '<param name="Scaling factor" value="75">';
	echo '</applet>';

/* 	<iframe width="95%" height="500px" src="http://<?php echo $ipAgent;?>:5801"></iframe> */
}

add_extension_opemode_tab_agent('vnc_view', __('VNC view'), 'images/computer.png', 'vnc_view');
?>
