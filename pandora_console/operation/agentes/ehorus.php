<?php
/**
 * Pandora FMS- http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Load global vars
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "AR") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access eHorus");
	require ("general/noaccess.php");
	return;
}

require_once($config['homedir'] . '/include/functions_ui.php');
require_once($config['homedir'] . '/include/functions_agents.php');

if (!$config['ehorus_enabled']) {
	return;
}

/* Get the parameters */
$agent_id = (int) get_parameter('id_agente');

if (empty($agent_id)) {
	ui_print_error_message(__('Missing agent id'));
	return;
}

$ehorus_agent_id = agents_get_agent_custom_field($agent_id, $config['ehorus_custom_field']);

if (empty($ehorus_agent_id)) {
	ui_print_error_message(__('Missing ehorus agent id'));
	return;
}

// Directory data
$hostname = $config['ehorus_hostname'];
$port = $config['ehorus_port'];
$user = $config['ehorus_user'];
$password = io_output_password($config['ehorus_pass']);
$curl_timeout = $config['ehorus_req_timeout'];

// Get the agent auth token
$token_path = '/agents/' . $ehorus_agent_id . '/token';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://' . $hostname . ':' . $port . $token_path);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $curl_timeout);
curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);

$result_token = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = false;
if ($result_token === false) {
	$error = curl_error($ch);
}
curl_close($ch);

if ($error !== false || $http_status !== 200) {
	if ($error !== false) {
		// echo $error;
		ui_print_error_message(__('There was an error retrieving an authorization token'));
	}
	else {
		ui_print_error_message($http_status . ' ' . $result_token);
	}
	return;
}

$response_auth = array();
try {
	$response_auth = json_decode($result_token, true);
}
catch (Exception $e) {
	ui_print_error_message(__('There was an error processing the response'));
}

// Get agent info
$agent_path = '/agents/' . $ehorus_agent_id;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://' . $hostname . ':' . $port . $agent_path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $curl_timeout);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: JWT ' . $response_auth['token']));

$result_agent = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = false;
if ($result_agent === false) {
	$error = curl_error($ch);
}
curl_close($ch);

if ($error !== false || $http_status !== 200) {
	if ($error !== false) {
		// echo $error;
		ui_print_error_message(__('There was an error retrieving the agent data'));
	}
	else {
		ui_print_error_message($http_status . ' ' . $result_agent);
	}
	return;
}

$agent_data = array();
try {
	$agent_data = json_decode($result_agent, true);
}
catch (Exception $e) {
	ui_print_error_message(__('There was an error processing the response'));
}

// Invalid permision
// return;

echo '<table id="ehorus-client-run-info" class="databox" style="width: 100%;"><tr>';
echo '<td>';
echo __('Remote management of this agent with eHorus');
echo '</td><td>';
echo '<input type="button" id="run-ehorus-client" class="sub next" value="' . __('Launch') . '" />';
echo '</td>';
echo '</tr></table>';

echo '<div id="ehorus-client"></div>';

ui_require_css_file('bootstrap.min', 'include/ehorus/css/');
ui_require_css_file('style', 'include/ehorus/css/');
ui_require_javascript_file('bundle.min', 'include/ehorus/');
?>

<script type="text/javascript">
	$(document).ready(function () {
		var runClient = function () {
			var agentID = '<?php echo $ehorus_agent_id; ?>';
			var protocol = 'wss';
			var hostname = '<?php echo $agent_data['serverAddress']; ?>';
			var port = <?php echo $agent_data['serverPort']; ?>;
			var token = '<?php echo $response_auth['token']; ?>';
			var isBusy = <?php echo json_encode($agent_data['isBusy']); ?>;
			var lastConnection = <?php echo json_encode($agent_data['lastConnection']); ?>;
			
			var eHorusProps = {
				url: {
					protocol,
					hostname,
					port,
					slashes: true,
					pathname: '',
					search: 'auth=' + token
				},
				agentID: agentID,
				agentLastContact: lastConnection,
				agentIsBusy: isBusy,
				header: false,
				section: 'terminal',
				handleDisconnect: function () {
					console.log('Disconnect callback');
				}
			}

			var eHorus = new EHorus(eHorusProps);
			eHorus.renderIn(document.getElementById('ehorus-client'));
		}
		$('input#run-ehorus-client').click(function () {
			$('table#ehorus-client-run-info').remove();
			runClient();
		});
	});
</script>