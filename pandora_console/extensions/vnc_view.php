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

	echo "<iframe src='http://$ipAgent:5800' width='100%' height=550>";
	echo "</iframe>";
}

$id_agente = get_parameter ("id_agente");
	
// This extension is usefull only if the agent has associated IP
$address = agents_get_address($id_agente);

if(!empty($address) || empty($id_agente)) {
	extensions_add_opemode_tab_agent('vnc_view', __('VNC view'), 'images/vnc.png', 'vnc_view', "v1r1");
}
?>
