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
	
	$steps = array();
	$steps[] = array(
		'element'=> '#clippy',
		'intro' => __('Could you help you?<br/><br/>I am Pandorin, the annoying clippy for Pandora. You could follow my advices for to make common and basic tasks in Pandora.')
		);
	$steps[] = array(
		'element'=> '#clippy',
		'intro' => __('What task do you want to do?') . '<br/><br/>' .
			'<ul style="text-align: left; margin-left: 3px; list-style-type: disc;">' .
				'<li>' .
					"<a href='javascript: clippy_go_link_show_help(\"index.php?sec=gagente&sec2=godmode/agentes/modificar_agente\", \"monitoring_server\");'>" . 
					//'<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&clippy=monitoring_server">' . 
						__('Monitoring a server Linux/Windows with a pandora agent') .
					'</a>' .
				'</li>' .
				'<li>' . __('Monitoring a switch with remote SNMP') . '</li>' .
				'<li>' . __('Monitoring a Windows server with remote WMI ') . '</li>' .
			'</ul>'
		);
	
	?>
	<script type="text/javascript">
		var steps = <?php echo json_encode($steps); ?>;
		var intro = null;
		
		$(document).ready(function() {
			intro = introJs();
			
			intro.setOptions({
				steps: steps,
				showBullets: false,
				showStepNumbers: false
			});
			<?php
			if ($config['logged']) {
			?>
			intro.start();
			<?php
			}
			?>
		});
	</script>
	<?php
}
?>