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

function clippy_write_javascript_helps_steps($helps,
	$force_first_step_by_default = true) {
	
	global $config;
	
	$clippy = get_cookie('clippy', false);
	set_cookie('clippy', null);
	
	
	//Get the help steps from a task
	$steps = $helps[$clippy]['steps'];
	if ($force_first_step_by_default) {
		if (empty($steps)) {
			//Get the first by default
			$temp = reset($helps);
			$steps = $temp['steps'];
		}
	}
	
	$conf = $helps[$clippy]['conf'];
	if ($force_first_step_by_default) {
		if (empty($conf)) {
			//Get the first by default
			$temp = reset($helps);
			$conf = $temp['conf'];
		}
	}
	
	if (!empty($steps)) {
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
}

function clippy_context_help($help = null) {
	$id = uniqid("id_");
	
	$return = '';
	
	require_once("include/help/clippy/" . $help . ".php");
	
	ob_start();
	$function = "clippy_" . $help;
	$function();
	$code = ob_get_clean();
	
	$code = str_replace('{clippy}', '#' . $id, $code); html_debug_print($code, true);
	$code = str_replace('{clippy_obj}', 'intro_' . $id, $code); html_debug_print($code, true);
	
	$return = $code . 
		'<div id="' . $id . '" style="display: inline;">' .
		'<a onclick="intro_' .  $id . '.start();" href="javascript: return false;" >' .
			html_print_image(
				"images/clippy_icon.png",
				true) .
		'</a>' .
		'</div>
		<script type="text/javascript">
		$(document).ready(function() {
			$("#' . $id . ' img").pulsate ();
		});
		</script>
		';
	
	
	
	return $return;
}
?>