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
check_login ();
ui_require_css_file ('firts_task');

ui_print_info_message(
	array(
		'no_close'=>true,
		'message'=>  __('There are no visual console defined yet.')));
?>

<div class="new_task">
	<div class="image_task">
		<?php echo html_print_image('images/firts_task/icono_grande_visualconsole.png', true, array("title" => __('Visual Console')));?>
	</div>
	<div class="text_task">
		<h3> <?php echo __('Create Visual Console'); ?></h3>
		<p id="description_task"> <?php echo __("Pandora FMS allows you to create visual maps in which each user is able to create his own monitoring map.
			The new visual console editor is much more practical, although the old visual console editor had its advantages. 
			Within the new visual console, we've been successful in imitating the sensation and touch of a drawing application like GIMP. 
			We've also simplified the editor by dividing it into several subject-matter tabs named 'Data', 'Preview', 'Wizard', 'List of Elements' and 'Editor'.
			The elements the Pandora FMS Visual Map was designed to handle are 'static image', 'percentage bar', 'module graph' and 'simple value'. "); ?></p>
		<form action="index.php?sec=network&amp;sec2=godmode/reporting/visual_console_builder" method="post">
			<?php html_print_input_hidden ('edit_layout', 1); ?>
			<input type="submit" class="button_task" value="<?php echo __('Create Visual Console'); ?>" />
		</form>
	</div>
</div>
