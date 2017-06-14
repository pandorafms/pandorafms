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
		events_table(0);
	});

	function events_table(all_events_24h){
		var parameters = {};
		parameters["table_events"] = 1;
		parameters["id_agente"] = <?php echo $id_agente; ?>;
		parameters["page"] = "include/ajax/events";
		parameters["all_events_24h"] = all_events_24h;
		
		jQuery.ajax ({
			data: parameters,
			type: 'POST',
			url: "ajax.php",
			dataType: 'html',
			success: function (data) {
				$("#event_list").empty();
				$("#event_list").html(data);
				$('#checkbox-all_events_24h').on('change',function(){
					if( $('#checkbox-all_events_24h').is(":checked") ){
						$('#checkbox-all_events_24h').val(1);
					}
					else{
						$('#checkbox-all_events_24h').val(0);
					}
					all_events_24h = $('#checkbox-all_events_24h').val();
		            events_table(all_events_24h);
		        });
			}
		});
	}
</script>
