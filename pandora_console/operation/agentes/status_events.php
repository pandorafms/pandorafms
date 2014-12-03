<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
check_login();

if (!isset($id_agente)) {
	require ("general/noaccess.php");
	exit;
}

require_once ("include/functions_events.php");

ui_toggle(
	"<div id='event_list'>" .
		html_print_image('images/spinner.gif', true) .
	"</div>",
	__('Latest events for this agent'),
	__('Latest events for this agent'),
	false);

?>
<script type="text/javascript">
	$(document).ready(function() {
		var parameters = {};
		
		parameters["table_events"] = 1;
		parameters["id_agente"] = <?php echo $id_agente; ?>;
		parameters["page"] = "include/ajax/events";
		
		jQuery.ajax ({
			data: parameters,
			type: 'POST',
			url: "ajax.php",
			timeout: 10000,
			dataType: 'html',
			async: false,
			success: function (data) {
				$("#event_list").empty();
				$("#event_list").html(data);
			}
		});
	});
</script>
