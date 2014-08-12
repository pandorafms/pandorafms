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

function clippy_start($sec2) {
	global $config;
	
	if ($sec2 === false) {
		$sec2 = 'homepage';
	}
	
	$sec2 = str_replace('/', '_', $sec2);
	
	if ($sec2 != 'homepage') {
		if (is_file("include/help/clippy/" . $sec2 . ".php")) {
			require("include/help/clippy/" . $sec2 . ".php");
			
			clippy_start_page();
		}
		
		//Add homepage for all pages for to show the "task sugestions"
		require("include/help/clippy/homepage.php");
		clippy_start_page_homepage();
	}
	else {
		require("include/help/clippy/homepage.php");
		clippy_start_page_homepage();
	}
}

function clippy_clean_help() {
	set_cookie('clippy', null);
}

function clippy_write_javascript_helps_steps($helps) {
	global $config;
	
	$clippy = get_cookie('clippy', false);
	set_cookie('clippy', null);
	
	
	//Get the help steps from a task
	$steps = $helps[$clippy]['steps'];
	if (empty($steps)) {
		//Get the first by default
		$temp = reset($helps);
		$steps = $temp['steps'];
	}
	
	$conf = $helps[$clippy]['conf'];
	if (empty($conf)) {
		//Get the first by default
		$temp = reset($helps);
		$conf = $temp['conf'];
	}
	
	$name_obj_tour = 'intro';
	if (!empty($conf['name_obj_tour'])) {
		$name_obj_tour = $conf['name_obj_tour'];
	}
	
	$autostart = true;
	if (!is_null($conf['autostart'])) {
		$autostart = $conf['autostart'];
	}
	
	$other_js = '';
	if (!empty($conf['other_js'])) {
		$other_js = $conf['other_js'];
	}
	
	?>
	<script type="text/javascript">
		var <?php echo $name_obj_tour; ?> = null;
		
		$(document).ready(function() {
			<?php echo $name_obj_tour; ?> = introJs();
			
			<?php echo $name_obj_tour; ?>.setOptions({
				steps: <?php echo json_encode($steps); ?>,
				showBullets: <?php echo json_encode($conf['showBullets']); ?>,
				showStepNumbers: <?php echo json_encode($conf['showStepNumbers']); ?>,
				nextLabel: "<?php echo __('Next &rarr;'); ?>",
				prevLabel: "<?php echo __('&larr; Back'); ?>",
				skipLabel: "<?php echo __('Skip'); ?>",
				doneLabel: "<?php echo __('Done'); ?>",
				exitOnOverlayClick: false,
				exitOnEsc: true, //false,
			})
			.onexit(function(value) {
					exit = confirm("<?php echo __("Do you want to exit the help tour?"); ?>");
					return exit;
				});
			
			<?php
			if (!empty($conf['next_help'])) {
			?>
				clippy_set_help('<?php echo $conf['next_help']; ?>');
			<?php
			}
			?>
			
			<?php
			if ($autostart) {
			?>
				<?php echo $name_obj_tour; ?>.start();
			<?php
			}
			?>
		});
		
		<?php echo $other_js; ?>
	</script>
	<?php
}
?>