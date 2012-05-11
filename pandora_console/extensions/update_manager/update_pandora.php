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

require_once('lib/functions.php');

if (is_ajax ()) {
	global $config;
	
	check_login ();
	
	if (! check_acl ($config["id_user"], 0, "PM")) {
		db_pandora_audit("ACL Violation",
			"Trying to access event viewer");
		require ("general/noaccess.php");
		return;
	}
	
	require_once('lib/functions.ajax.php');
	
	$get_packages_online = (bool) get_parameter('get_packages_online');
	$download_package = (bool) get_parameter('download_package');
	$check_download_package = (bool) get_parameter('check_download_package');
	$install_package = (bool) get_parameter('install_package');
	$check_install_package = (bool) get_parameter('check_install_package');
	
	if ($get_packages_online)
		update_pandora_get_packages_online_ajax();
	if ($download_package)
		update_pandora_download_package();
	if ($check_download_package)
		update_pandora_check_download_package();
	if ($install_package)
		update_pandora_install_package();
	if ($check_install_package)
		update_pandora_check_install_package();
	
	return;
}

function update_pandora_administration($settings, $user_key) {
	global $config;
	global $conf_update_pandora;
	
	check_login ();
	
	if (! check_acl ($config["id_user"], 0, "PM")) {
		db_pandora_audit("ACL Violation",
			"Trying to access event viewer");
		require ("general/noaccess.php");
		return;
	}
	
	if (!update_pandora_check_installation()) {
		ui_print_error_message(__('First execution of Update Pandora'));
		update_pandora_installation();
	}
	
	$delete_package = (bool)get_parameter('delete_package');
	if ($delete_package) {
		$package = get_parameter('package');
		
		$dir = $config['attachment_store'] .  '/update_pandora/';
		
		$result = unlink($dir . $package);
	}
	
	$conf_update_pandora = update_pandora_get_conf();
	
	if (!empty($conf_update_pandora['last_installed'])){
		echo '<h4>';
		echo __('Your Pandora FMS open source package installed is') .
			' ' . $conf_update_pandora['last_installed'];
		echo "</h4>";
	}
	else {
		echo '<h4>';
		echo __('Your Pandora FMS does not have any update installed yet');
		echo "</h4>";
	}
	
	echo "<br><br>";
	
	ui_print_info_message(
		'<p>' .
			__('This is a automatilly update Pandora Console only. Be careful if you have changed any php file of console, please make a backup this modified files php. Because the update action ovewrite all php files in Pandora console.') .
		'</p>' .
		'<p>' .
		__('Update Manager sends anonymous information about Pandora FMS usage (number of agents and modules running). To disable it, just delete extension or remove remote server address from Update Manager plugin setup.') .
		'</p>'
		);
	
	echo "<h4>". __('Online') . '</h4>';
	
	echo '<table id="online_packages" class="databox" width="95%" cellspacing="4" cellpadding="4" border="0" style="">' .
			'<tbody>
				<tr id="online_packages-0" class="spinner_row" style="">
					<td id="online_packages-0-0" style=" text-align:left; width:80%;">' .
						__('Get list online Package') . " " . html_print_image('images/spinner.gif', true) . 
					'</td>
					<td id="online_packages-0-1" style=" text-align:center; width:50px;"></td>
				</tr>
			</tbody>' .
		'</table>';
	
	?>
	<div id="dialog_download" title="<?php echo __('Process packge'); ?>"
		style="display:none;">
		<div style="position:absolute; top:10%; text-align: center; left:0%; right:0%; width:600px;">
			<?php
			echo '<h4 id="title_downloading_update_pandora">' . __('Downloading <span class="package_name">package</span> in progress') . " ";
			html_print_image('images/spinner.gif');
			echo '</h4>';
			echo '<h4 style="display: none;" id="title_downloaded_update_pandora">' . __('Downloaded <span class="package_name">package</span>') . '</h2>';
			echo '<h4 style="display: none;" id="title_installing_update_pandora">' . __('Installing <span class="package_name">package</span> in progress') . " ";
			html_print_image('images/spinner.gif');
			echo '</h4>';
			echo '<h4 style="display: none;" id="title_installed_update_pandora">' . __('Installed <span class="package_name">package</span> in progress') . '</h2>';
			echo '<h4 style="display: none;" id="title_error_update_pandora">' . __('Fail download <span class="package_name">package</span>') . '</h2>';
			echo '<br /><br />';
			echo "<div id='progress_bar_img'>";
				echo progress_bar(0, 300, 20, 0 . '%', 1, false, "#00ff00");
			echo "</div>";
			
			echo "<div style='padding-top: 10px; display: none;' id='info_text'>
					<b>Size:</b> 666/666 kbytes <b>Speed:</b> 666 bytes/second
				</div>";
			
			?>
			<div id="button_close_download_disabled" style="position: absolute; top:280px; right:43%;">
				<?php
				html_print_submit_button(__("Close"), 'hide_download_disabled_dialog', true, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only" style="width:100px;"');
				?>  
			</div>
			<div id="button_close_download" style="display: none; position: absolute; top:280px; right:43%;">
				<?php
				html_print_submit_button(__("Close"), 'hide_download_dialog', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only" style="width:100px;"');
				?>  
			</div>
		 </div> 
	</div>
	<?php

	echo '<h4>' . __('Downloaded Packages') . '</h4>';	
	$tableMain = null;
	$tableMain->width = '95%';
	$tableMain->data = array();
	
	$list_downloaded_packages = update_pandora_get_list_downloaded_packages('administration');
	if (empty($list_downloaded_packages))
		$list_downloaded_packages = array();
	$table = null;
	$table->width = '100%';
	$table->size = array('50%', '25%', '25%');
	$table->align = array('left', 'center');
	$table->data = array();
	foreach ($list_downloaded_packages as $package) {
		$actions = '';
		if (!isset($package['empty'])) {
			if (!$package['current']) {
				$actions =  html_print_button(__('Install'),
					'install_' . uniqid(), false,
					'ajax_start_install_package(\'' . $package['name'] . '\');',
					'class="sub next" style="width: 40%;"', true);
			}
			else {
				$actions = html_print_button(__('Reinstall'),
					'reinstall_' . uniqid(), false,
					'ajax_start_install_package(\'' . $package['name'] . '\');',
					'class="sub upd" style="width: 40%;"', true);
			}
			$actions .= ' ' . html_print_button(__('Delete'),
				'delete' . uniqid(), false,
				'delete_package(\'' . $package['name'] . '\');',
				'class="sub delete" style="width: 40%;"', true);
		}
		$table->data[] = array($package['name'], $package['time'], $actions);
	}
	$tableMain->data[1][0] = html_print_table($table, true);
	
	html_print_table($tableMain);
	
	ui_require_css_file ('dialog');
	ui_require_jquery_file ('ui.core');
	ui_require_jquery_file ('ui.dialog');
	
	update_pandora_print_javascript_admin();
}
?>
