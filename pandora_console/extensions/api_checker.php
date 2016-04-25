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

function api_execute($url, $ip, $pandora_url, $apipass, $user, $password, $op, $op2, $id, $id2, $return_type, $other, $other_mode) {
	
	if (empty($url)) {
		$url = "http://" . $ip . $pandora_url . "/include/api.php";
		
		$url .= "?";
		$url .= "apipass=" . $apipass;
		$url .= "&user=" . $user;
		$url .= "&pass=" . $password;
		$url .= "&op=" . $op;
		$url .= "&op2=" . $op2;
		if ($id !== '') {
			$url .= "&id=" . $id;
		}
		if ($id2 !== '') {
			$url .= "&id2=" . $id2;
		}
		if ($return_type !== '') {
			$url .= "&return_type=" . $return_type;
		}
		if ($other !== '') {
			$url .= "&other_mode=" . $other_mode;
			$url .= "&other=" . $other;
		}
	}
	
	$curlObj = curl_init();
	curl_setopt($curlObj, CURLOPT_URL, $url);
	curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($curlObj);
	curl_close($curlObj);
	
	$return = array('url' => $url, 'result' => $result);
	
	return $return;
}

function extension_api_checker() {
	global $config;
	
	check_login ();
	
	if (! check_acl ($config['id_user'], 0, "PM")) {
		db_pandora_audit("ACL Violation",
			"Trying to access Profile Management");
		require ("general/noaccess.php");
		return;
	}
	
	$url = io_safe_output(get_parameter('url', ''));
	
	$ip = io_safe_output(get_parameter('ip', '127.0.0.1'));
	$pandora_url = io_safe_output(get_parameter('pandora_url', $config['homeurl_static']));
	$apipass = io_safe_output(get_parameter('apipass', ''));
	$user = io_safe_output(get_parameter('user', $config['id_user']));
	$password = io_safe_output(get_parameter('password', ''));
	
	$op = io_safe_output(get_parameter('op', 'get'));
	$op2 = io_safe_output(get_parameter('op2', 'test'));
	$id = io_safe_output(get_parameter('id', ''));
	$id2 = io_safe_output(get_parameter('id2', ''));
	$return_type = io_safe_output(get_parameter('return_type', ''));
	$other = io_safe_output(get_parameter('other', ''));
	$other_mode = io_safe_output(get_parameter('other_mode', 'url_encode_separator_|'));
	
	$api_execute = get_parameter('api_execute', 0);
	
	$return_call_api = '';
	if ($api_execute) {
		$return_call_api =
			api_execute($url, $ip, $pandora_url, $apipass, $user, $password,
				$op, $op2, urlencode($id), urlencode($id2),
				$return_type, urlencode($other), $other_mode);
	}
	
	ui_print_page_header (__("API checker"),
		"images/extensions.png", false, "", true, "");
	
	$table = null;
	$table->data = array();
	
	$row = array();
	$row[] = __("IP");
	$row[] = html_print_input_text('ip', $ip, '', 50, 255, true);
	$table->data[] = $row;
	
	$row = array();
	$row[] = __("Pandora Console URL");
	$row[] = html_print_input_text('pandora_url', $pandora_url, '', 50, 255, true);
	$table->data[] = $row;
	
	$row = array();
	$row[] = __("API Pass");
	$row[] = html_print_input_password('apipass', $apipass, '', 50, 255, true);
	$table->data[] = $row;
	
	$row = array();
	$row[] = __("User");
	$row[] = html_print_input_text('user', $user, '', 50, 255, true);
	$table->data[] = $row;
	
	$row = array();
	$row[] = __("Password");
	$row[] = html_print_input_password('password', $password, '', 50, 255, true);
	$table->data[] = $row;
	
	$table2 = null;
	$table2->data = array();
	
	$row = array();
	$row[] = __("Action (get or set)");
	$row[] = html_print_input_text('op', $op, '', 50, 255, true);
	$table2->data[] = $row;
	
	$row = array();
	$row[] = __("Operation");
	$row[] = html_print_input_text('op2', $op2, '', 50, 255, true);
	$table2->data[] = $row;
	
	$row = array();
	$row[] = __("ID");
	$row[] = html_print_input_text('id', $id, '', 50, 255, true);
	$table2->data[] = $row;
	
	$row = array();
	$row[] = __("ID 2");
	$row[] = html_print_input_text('id2', $id2, '', 50, 255, true);
	$table2->data[] = $row;
	
	$row = array();
	$row[] = __("Return Type");
	$row[] = html_print_input_text('return_type', $return_type, '', 50, 255, true);
	$table2->data[] = $row;
	
	$row = array();
	$row[] = __("Other");
	$row[] = html_print_input_text('other', $other, '', 50, 255, true);
	$table2->data[] = $row;
	
	$row = array();
	$row[] = __("Other Mode");
	$row[] = html_print_input_text('other_mode', $other_mode, '', 50, 255, true);
	$table2->data[] = $row;
	
	$table3 = null;
	$table3->data = array();
	
	$row = array();
	$row[] = __("Raw URL");
	$row[] = html_print_input_text('url', $url, '', 150, 2048, true);
	$table3->data[] = $row;
	
	echo "<form method='post'>";
	echo "<fieldset>";
	echo "<legend>" . __('Credentials') . "</legend>";
	html_print_table($table);
	echo "</fieldset>";
	
	echo "<fieldset>";
	echo "<legend>" . __('Call parameters') . "</legend>";
	html_print_table($table2);
	echo "</fieldset>";
	echo "<div style='text-align: right;'>";
	html_print_input_hidden('api_execute', 1);
	html_print_submit_button(__('Call'), 'submit', false, 'class="sub next"');
	echo "</div>";
	echo "</form>";
	
	
	echo "<form method='post'>";
	echo "<fieldset>";
	echo "<legend>" . __('Custom URL') . "</legend>";
	html_print_table($table3);
	echo "</fieldset>";
	
	echo "<div style='text-align: right;'>";
	html_print_input_hidden('api_execute', 1);
	html_print_submit_button(__('Call'), 'submit', false, 'class="sub next"');
	echo "</div>";
	echo "</form>";
	
	if ($api_execute) {
		echo "<fieldset>";
		echo "<legend>" . __('Result') . "</legend>";
		echo __('URL') . "<br />";
		html_print_input_password('url', $return_call_api['url'], '', 150, 255, false, true);
		echo "&nbsp;<a id='show_icon' title='" . __('Show URL') . "' href='javascript: show_url();'>";
		html_print_image("images/input_zoom.png");
		echo "</a>";
		echo "<br />";
		echo __('Result') . "<br />";
		html_print_textarea('result', 30, 20, $return_call_api['result'], 'readonly="readonly"');
		echo "</fieldset>";
	}
	?>
	<script>
	function show_url() {
		if ($("#password-url").attr('type') == 'password') {
			$("#password-url").attr('type', 'text');
			$("#show_icon").attr('title', '<?php echo __('Hide URL'); ?>');
		}
		else {
			$("#password-url").attr('type', 'password');
			$("#show_icon").attr('title', '<?php echo __('Show URL'); ?>');
		}
	}
	</script>
	<?php
}

extensions_add_godmode_function('extension_api_checker');
extensions_add_godmode_menu_option(__('API checker'), 'PM', 'gextensions', null, "v1r1");
?>