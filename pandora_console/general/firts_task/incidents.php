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

global $config;
global $incident_w;
global $incident_m;
check_login ();
ui_require_css_file ('firts_task');
?>
<?php 

ui_print_info_message ( array('no_close'=>true, 'message'=>  __('There are no incidents defined yet.') ) );

if ($incident_w || $incident_m) {
?>

<div class="new_task">
	<div class="image_task">
		<?php echo html_print_image('images/firts_task/icono_grande_incidencia.png', true, array("title" => __('Incidents')));?>
	</div>
	<div class="text_task">
		<h3> <?php echo __('Create Incidents'); ?></h3>
		<p id="description_task"> <?php echo __("Besides receiving and processing data to monitor systems or applications, 
			you're also required to monitor possible incidents which might take place on these systems within the system monitoring process.
			For it, the Pandora FMS team has designed an incident manager within which any user is able to open incidents, 
			explaining what's happened on the network and to update them with comments and files any time in case there is a need to do so.
			This system allows the users to work as a team, along with different roles and work-flow systems which allows an incident to be 
			moved from one group to another, and that members from different groups and different people could work on the same incident, sharing information and files.
		"); ?></p>
		<form action="index.php?sec=workspace&amp;sec2=operation/incidents/incident_detail&amp;insert_form=1" method="post">
			<input type="submit" class="button_task" value="<?php echo __('Create Incidents'); ?>" />
		</form>
	</div>
</div>
<?php } ?>
