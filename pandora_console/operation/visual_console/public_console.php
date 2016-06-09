<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 20012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Real start

require_once ("../../include/config.php");

// Set root on homedir, as defined in setup
chdir ($config["homedir"]);

session_start ();
ob_start ();
/* Enterprise support */
if (file_exists (ENTERPRISE_DIR . "/load_enterprise.php")) {
	include_once (ENTERPRISE_DIR . "/load_enterprise.php");
}

if (file_exists (ENTERPRISE_DIR . "/include/functions_login.php")) {
	include_once (ENTERPRISE_DIR . "/include/functions_login.php");
}

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>';

global $vc_public_view;
$vc_public_view = true;
// This starts the page head. In the call back function,
// things from $page['head'] array will be processed into the head
ob_start ('ui_process_page_head');
// Enterprise main
enterprise_include ('index.php');

require ('include/functions_visual_map.php');

$hash = get_parameter ('hash');
$id_layout = (int) get_parameter ('id_layout');
$graph_javascript = (bool) get_parameter('graph_javascript');
$config["id_user"] = get_parameter ('id_user');

$myhash = md5($config["dbpass"].$id_layout. $config["id_user"]);

// Check input hash
if ($myhash != $hash) {
	exit;
}

$refr = (int) get_parameter ('refr', 0);
$layout = db_get_row ('tlayout', 'id', $id_layout);

if (! $layout) {
	db_pandora_audit("ACL Violation","Trying to access visual console without id layout");
	include ($config['homedir'] . "/general/noaccess.php");
	exit;
}

$id_group = $layout["id_group"];
$layout_name = $layout["name"];
$background = $layout["background"];
$bwidth = $layout["width"];
$bheight = $layout["height"];

if (!isset($config['pure']))
	$config['pure'] = 0;

$xhr = (bool) get_parameter('xhr');
if ($xhr) {
	$width = (int) get_parameter('width');
	if ($width <= 0) $width = null;
	$height = (int) get_parameter('height');
	if ($height <= 0) $height = null;
	
	ob_start();
	// Render map
	visual_map_print_visual_map($id_layout, true, true, $width, $height,
		'../../', true, $graph_javascript, true);
	return;
}
else {
	echo '<div id="vc-container"></div>';
}

// Floating menu - Start
echo '<div id="vc-controls">';

echo '<div id="menu_tab">';
echo '<ul class="mn">';

// QR code
echo '<li class="nomn">';
echo '<a href="javascript: show_dialog_qrcode();">';
echo '<img class="vc-qr" src="../../images/qrcode_icon.jpg"/>';
echo '</a>';
echo '</li>';

// Countdown
echo '<li class="nomn">';
echo '<div class="vc-refr">';
echo '<div class="vc-countdown"></div>';
echo '<div id="vc-refr-form">';
echo __('Refresh') . ':';
echo html_print_select(get_refresh_time_array(), 'refr', $refr, '', '', 0, true, false, false);
echo '</div>';
echo '</div>';
echo '</li>';

// Console name
echo '<li class="nomn">';
echo '<div class="vc-title">' . $layout_name . '</div>';
echo '</li>';

echo '</ul>';
echo '</div>';

echo '</div>';
// Floating menu - End

// QR code dialog
echo '<div style="display: none;" id="qrcode_container" title="' . __('QR code of the page') . '">';
echo '<div id="qrcode_container_image"></div>';
echo '</div>';

ui_require_jquery_file('countdown');
ui_require_javascript_file('wz_jsgraphics');
ui_require_javascript_file('pandora_visual_console');
?>

<script language="javascript" type="text/javascript">
	$(document).ready(function () {
		var refr = <?php echo (int) $refr; ?>;
		
		var startCountDown = function (duration, cb) {
			$('div.vc-countdown').countdown('destroy');
			if (!duration) return;
			var t = new Date();
			t.setTime(t.getTime() + duration * 1000);
			$('div.vc-countdown').countdown({
				until: t,
				format: 'MS',
				layout: '(%M%nn%M:%S%nn%S <?php echo __('Until refresh'); ?>) ',
				alwaysExpire: true,
				onExpiry: function () {
					$('div.vc-countdown').countdown('destroy');
					cb();
				}
			});
		}
		
		var fetchMap = function () {
			$.ajax({
				url: 'public_console.php',
				type: 'GET',
				dataType: 'html',
				data: {
					hash: '<?php echo $hash; ?>',
					id_layout: <?php echo $id_layout; ?>,
					graph_javascript: <?php echo (int) $graph_javascript; ?>,
					id_user: '<?php echo $config['id_user']; ?>',
					width: $(window).width(),
					height: $(window).height(),
					xhr: true
				}
			})
			.done(function (data, textStatus, xhr) {
				$('div#vc-container').html(data);
				startCountDown(refr, fetchMap);
			});
		}
		
		// Auto hide controls
		var controls = document.getElementById('vc-controls');
		autoHideElement(controls, 1000);
		
		$('select#refr').change(function (event) {
			refr = Number.parseInt(event.target.value, 10);
			startCountDown(refr, fetchMap);
		});
		
		// Start the map fetch
		fetchMap();
	});
</script>
