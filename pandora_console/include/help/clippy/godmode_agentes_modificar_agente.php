<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Clippy
 */

function clippy_start_page() {
	global $config;
	
	$clippy = get_cookie('clippy', false);
	set_cookie('clippy', null);
	
	switch ($clippy) {
		case 'monitoring_server':
			$steps = array();
			$steps[] = array(
				'element'=> '#clippy',
				'intro' => __('I show how to monitoring a server.')
				);
			$steps[] = array(
				'element'=> 'input[name="search"]',
				'intro' => __('Please type a agent to save the modules for monitoring a server.')
				);
			$steps[] = array(
				'element'=> 'input[name="srcbutton"]',
				'intro' => __('Maybe if you typped correctly the name, you can see the agent.')
				);
			break;
		case 'choose_agent':
			$steps = array();
			$steps[] = array(
				'element'=> '#clippy',
				'intro' => __('Please choose the agent that you have searched.')
				);
			$steps[] = array(
				'element'=> '#agent_list',
				'intro' => __('Choose the agent, please click in the name.')
				);
			break;
	}
	
	
	
	?>
	<script type="text/javascript">
		var steps = <?php echo json_encode($steps); ?>;
		var intro = null;
		
		$(document).ready(function() {
			intro = introJs();
			
			
			<?php
			switch ($clippy) {
				case 'monitoring_server':
					?>
					intro.setOptions({
						steps: steps,
						showBullets: false,
						showStepNumbers: true
					});
					
					clippy_set_help('choose_agent');
					intro.start();
					<?php
					break;
				case 'choose_agent':
					?>
					intro.setOptions({
						steps: steps,
						showBullets: false,
						showStepNumbers: false
					});
					
					clippy_set_help('choose_tabs_modules');
					intro.start();
					<?php
					break;
			}
			?>
		});
	</script>
	<?php
}
?>