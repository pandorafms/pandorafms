<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	return;
}

if (! $id) {
	$snmp_community = "public";
}

echo "<h2>".__('Module management')." &raquo; ";
echo __('Module component management')."</h2>";
echo "<h3>".__('Network component')."</h3>";

$data = array ();
$data[0] = _('Port');
$data[1] = print_input_text ('tcp_port', $tcp_port, '', 5, 20, true);
$data[2] = __('SNMP community');
$data[3] = print_input_text ('snmp_community', $snmp_community, '', 15, 60, true);

push_table_row ($data, 'snmp_port');

$data = array ();
$data[0] = __('SNMP OID');
$data[1] = print_input_text ('snmp_oid', $snmp_oid, '', 30, 120, true);
$table->colspan['snmp_2'][1] = 3;

push_table_row ($data, 'snmp_2');

/* Advanced stuff */
$data = array ();
$data[0] = __('TCP send').' '.print_help_icon ("tcp_send", true);
$data[1] = print_textarea ('tcp_send', 2, 65, $tcp_send, '', true);
$table->colspan['tcp_send'][1] = 3;

push_table_row ($data, 'tcp_send');

$data[0] = __('TCP receive');
$data[1] = print_textarea ('tcp_rcv', 2, 65, $tcp_rcv, '', true);
$table->colspan['tcp_receive'][1] = 3;

push_table_row ($data, 'tcp_receive');

return;
?>

<td class="datos2"><?php echo __('TCP port') ?></td>
<td class="datos2">
<input type="text" name="tcp_port" size="5" value="<?php echo $tcp_port ?>">
</td></tr>
<tr><td class="datos"><?php echo __('SNMP OID') ?><?php print_help_icon("snmpoid"); ?></td>
<td class="datos">
<input type="text" name="snmp_oid" size="25" value="<?php echo $snmp_oid ?>">
</td>
<td class="datos"><?php echo __('SNMP community') ?></td>
<td class="datos">
<input type="text" name="snmp_community" size="25" value="<?php echo $snmp_community ?>">
</td></tr>
<tr><td class="datos2t"><?php echo __('TCP send') ?></td>
<td class="datos2">
<textarea name="tcp_send" cols="20" rows="2"><?php echo $tcp_send ?></textarea>
</td>
<td class="datos2t"><?php echo __('TCP receive') ?></td>
<td class="datos2">
<textarea name="tcp_rcv" cols="20" rows="2"><?php echo $tcp_rcv ?></textarea>
</td></tr>
<tr><td class="datos"><?php echo __('Minimum Data') ?></td>
<td class="datos">
<input type="text" name="modulo_min" size="5" value="<?php echo $modulo_min ?>">
</td>
<td class="datos"><?php echo __('Maximum Data') ?></td>
<td class="datos">
<input type="text" name="modulo_max" size="5" value="<?php echo $modulo_max ?>">
</td></tr>
<?PHP

echo '<tr><td class="datos2t">'.__('Comments');
echo '<td class="datos2" colspan=3>';
echo '<textarea name="descripcion" cols=70 rows=2>';
echo $description;
echo "</textarea>";
echo "</td></tr>";
echo "</table>";

// Module type, hidden
print_input_hidden ('id_modulo', $id_component_type);

// Update/Add buttons
echo '<div class="action-buttons" style="width: 95%">';
if ($id) {
	print_input_hidden ('update_component', 1);
	print_input_hidden ('id', $id);
	print_submit_button (__('Update'), 'crt', false, 'class="sub upd"');
} else {
	print_input_hidden ('create_component', 1);
	print_submit_button (__('Create'), 'crt', false, 'class="sub next"');
}
echo '</div>';
echo '</form>';

?>
