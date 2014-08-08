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
		case 'choose_tabs_modules':
			$steps = array();
			$steps[] = array(
				'element'=> '#clippy',
				'intro' => __('Now you must go to modules, don\'t worry I teach you.')
				);
			$steps[] = array(
				'element'=> "img[alt='Modules']",
				'intro' => __('Please click in this tab.')
				);
			break;
		case 'create_module':
			$steps = array();
			$steps[] = array(
				'element'=> '#clippy',
				'intro' => __('Now you must create the module, don\'t worry I teach you.')
				);
			$steps[] = array(
				'element'=> "#moduletype",
				'intro' => __('Choose the network server module.')
				);
			$steps[] = array(
				'element'=> "input[name='updbutton']",
				'intro' => __('And click in this button.')
				);
			break;
		case 'create_module_second_step':
			$steps = array();
			$steps[] = array(
				'element'=> '#clippy',
				'intro' => __('We are going to fill the form.')
				);
			$steps[] = array(
				'element'=> "#network_component_group",
				'intro' => __('Please choose the Network Management.')
				);
			$steps[] = array(
				'element'=> "#network_component",
				'intro' => __('And choose the component with the name "Host Alive".')
				);
			$steps[] = array(
				'element'=> "input[name='name']",
				'intro' => __('You can change the name.')
				);
			$steps[] = array(
				'element'=> "input[name='ip_target']",
				'intro' => __('Check if this IP is the address of your machine.')
				);
			$steps[] = array(
				'element'=> "input[name='crtbutton']",
				'intro' => __('And only to finish it is clicking this button.')
				);
			break;
		case 'create_module_third_step':
			$steps = array();
			$steps[] = array(
				'element'=> '#clippy',
				'intro' => __('Now, your module is just created.<br/> And the status color is <b>blue</b>.<br/>This meaning of blue status is the module is not executed for first time.<br/>In the next seconds if there is not a problem, the status color will change to red or green.')
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
				case 'choose_tabs_modules':
					?>
					intro.setOptions({
						steps: steps,
						showBullets: false,
						showStepNumbers: false
					});
					clippy_set_help('create_module');
					intro.start();
					<?php
					break;
				case 'create_module':
					?>
					intro.setOptions({
						steps: steps,
						showBullets: false,
						showStepNumbers: false
					});
					clippy_set_help('create_module_second_step');
					intro.start();
					<?php
					break;
				case 'create_module_second_step':
					?>
					intro.setOptions({
						steps: steps,
						showBullets: false,
						showStepNumbers: false
					});
					clippy_set_help('create_module_third_step');
					intro.start();
					<?php
					break;
				case 'create_module_third_step':
					?>
					intro.setOptions({
						steps: steps,
						showBullets: false,
						showStepNumbers: false
					});
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