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
?>
<?php ui_print_info_message ( array('no_close'=>true, 'message'=>  __('There are no transactions defined yet.') ) ); ?>

<div class="new_task">
	<div class="image_task">
		<?php echo html_print_image('images/firts_task/icono_grande_topology.png', true, array("title" => __('Transactions')));?>
	</div>
	<div class="text_task">
		<h3> <?php echo __('Create Transactions'); ?></h3>
		<p id="description_task"> <?php echo __("Text."); ?></p>
		<form action="index.php?sec=network&sec2=enterprise/operation/agentes/manage_transmap_creation&create_transaction=1" method="post">
			<input type="submit" class="button_task" value="<?php echo __('Create Transactions'); ?>" />
		</form>
	</div>
</div>