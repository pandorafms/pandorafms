<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'],$REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	exit;
}

$id_component_type = (int) get_parameter ('id_component_type');
if (isset ($id)) {
	$component = get_network_component ((int) $id);
	if ($component !== false) {
		$id_component_type = $component['id_modulo'];
		$name = $component["name"];
		$type = $component["type"];
		$description = $component["description"];
		$max = $component["max"];
		$min = $component["min"];
		$module_interval = $component["module_interval"];
		$tcp_port = $component["tcp_port"];
		$tcp_rcv = $component["tcp_rcv"];
		$tcp_send = $component["tcp_send"];
		$snmp_community = $component["snmp_community"];
		$snmp_oid = $component["snmp_oid"];
		$id_module_group = $component["id_module_group"];
		$id_group = $component["id_group"];
		$id_plugin = $component['id_plugin'];
		$plugin_user = $component["plugin_user"];
		$plugin_pass = $component["plugin_pass"];
		$plugin_parameter = $component["plugin_parameter"];
		$max_timeout = $component["max_timeout"];
		$min_warning = $component["min_warning"];
		$max_warning = $component["max_warning"];
		$max_critical = $component["max_critical"];
		$min_critical = $component["min_critical"];
		$ff_event = $component["min_ff_event"];
		$history_data = $component["history_data"];
	} elseif (isset ($new_component) && $new_component) {
		$name = "";
		$snmp_oid = "";
		$description = "";
		$id_group = 1;
		$oid = "";
		$max = "0";
		$min = "0";
		$module_interval = "0";
		$tcp_port = "";
		$tcp_rcv = "";
		$tcp_send = "";
		$snmp_community = "";
		$id_module_group = "";
		$id_group = "";
		$type = 0;
		$min_warning = 0;
		$max_warning = 0;
		$max_critical = 0;
		$min_critical = 0;
		$ff_event = 0;
		$history_data = true;
	}
}

if ($id_component_type == 6) {
	$categories = array (0, 1, 2);
	require ("godmode/modules/manage_network_components_form_common.php");
	require ("godmode/modules/manage_network_components_form_wmi.php");
} else if ($id_component_type == 4) {
	$categories = array (0, 1, 2);
	require ("godmode/modules/manage_network_components_form_common.php");
	require ("godmode/modules/manage_network_components_form_plugin.php");
} else if ($id_component_type == 2) {
	$categories = array (3, 4, 5);
	require ("godmode/modules/manage_network_components_form_common.php");
	require ("godmode/modules/manage_network_components_form_network.php");
} else {
	return;
}

echo '<form name="component" method="post">';

/* $table came from manage_network_components_form_common.php */
$table->colspan['description'][1] = 3;
$data = array ();
$data[0] = __('Description');
$data[1] = print_textarea ('description', 2, 65, $description, '', true);
push_table_row ($data, 'description');

print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_input_hidden ('id_component_type', $id_component_type);
if ($id) {
	print_input_hidden ('update_component', 1);
	print_input_hidden ('id', $id);
	print_submit_button (__('Update'), 'upd', false, 'class="sub upd"');
} else {
	print_input_hidden ('create_component', 1);
	print_submit_button (__('Create'), 'crt', false, 'class="sub next"');
}
echo '</div>';
echo '</form>';
?>
<script language="JavaScript" type="text/javascript">
<!--
function type_change () {
	// type 1-4 - Generic_xxxxxx
	if ((document.component.type.value > 0) && (document.component.type.value < 5)){
		document.component.snmp_oid.style.background="#ddd";
		document.component.snmp_oid.disabled=true;
		document.component.snmp_community.style.background="#ddd";
		document.component.snmp_community.disabled=true;
		document.component.tcp_send.style.background="#ddd";
		document.component.tcp_send.disabled=true;
		document.component.tcp_rcv.style.background="#ddd";
		document.component.tcp_rcv.disabled=true;
		document.component.tcp_port.style.background="#ddd";
		document.component.tcp_port.disabled=true;
	}
	// type 15-18- SNMP
	if ((document.component.type.value > 14) && (document.component.type.value < 19 )){
		document.component.snmp_oid.style.background="#fff";
		document.component.snmp_oid.style.disabled=false;
		document.component.snmp_community.style.background="#fff";
		document.component.snmp_community.disabled=false;
		document.component.snmp_oid.style.background="#fff";
		document.component.snmp_oid.disabled=false;
		document.component.tcp_send.style.background="#ddd";
		document.component.tcp_send.disabled=true;
		document.component.tcp_rcv.style.background="#ddd";
		document.component.tcp_rcv.disabled=true;
		document.component.tcp_port.style.background="#ddd";
		document.component.tcp_port.disabled=true;
	}
	// type 6-7 - ICMP
	if ((document.component.type.value == 6) || (document.component.type.value == 7)){
		document.component.snmp_oid.style.background="#ddd";
		document.component.snmp_oid.disabled=true;
		document.component.snmp_community.style.background="#ddd";
		document.component.snmp_community.disabled=true;
		document.component.snmp_oid.style.background="#ddd";
		document.component.snmp_oid.disabled=true;
		document.component.tcp_send.style.background="#ddd";
		document.component.tcp_send.disabled=true;
		document.component.tcp_rcv.style.background="#ddd";
		document.component.tcp_rcv.disabled=true;
		document.component.tcp_port.style.background="#ddd";
		document.component.tcp_port.disabled=true;
	}
	// type 8-11 - TCP
	if ((document.component.type.value > 7) && (document.component.type.value < 12)){
		document.component.snmp_oid.style.background="#ddd";
		document.component.snmp_oid.disabled=true;
		document.component.snmp_community.style.background="#ddd";
		document.component.snmp_community.disabled=true;	
		document.component.tcp_send.style.background="#fff";
		document.component.tcp_send.disabled=false;
		document.component.tcp_rcv.style.background="#fff";
		document.component.tcp_rcv.disabled=false;
		document.component.tcp_port.style.background="#fff";
		document.component.tcp_port.disabled=false;
	}
	// type 12 - UDP
	if (document.component.type.value == 12){
		document.component.snmp_oid.style.background="#ddd";
		document.component.snmp_oid.disabled=true;
		document.component.snmp_community.style.background="#ddd";
		document.component.snmp_community.disabled=true;
		document.component.tcp_send.style.background="#fff";
		document.component.tcp_send.disabled=false;
		document.component.tcp_rcv.style.background="#fff";
		document.component.tcp_rcv.disabled=false;
		document.component.tcp_port.style.background="#fff";
		document.component.tcp_port.disabled=false;
	}
}
<?php if ($id_component_type == 2) :?>
type_change ();
<?php endif; ?>
//-->
</script>
