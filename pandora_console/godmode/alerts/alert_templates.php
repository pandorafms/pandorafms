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
require_once ('include/config.php');
require_once ('include/functions_alerts.php');

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}


if (is_ajax ()) {
	$get_template_tooltip = (bool) get_parameter ('get_template_tooltip');
	
	if ($get_template_tooltip) {
		$id_template = (int) get_parameter ('id_template');
		$template = get_alert_template ($id_template);
		if ($template === false)
			return;
		
		echo '<h3>'.$template['name'].'</h3>';
		echo '<strong>'.__('Type').': </strong>';
		echo get_alert_templates_type_name ($template['type']);
		
		echo '<br />';
		echo print_alert_template_example ($template['id'], true);
		
		echo '<br />';
		
		if ($template['description'] != '') {
			echo '<strong>'.__('Description').':</strong><br />';
			echo $template['description'];
			echo '<br />';
		}
		
		if ($template['monday'] && $template['tuesday']
			&& $template['wednesday'] && $template['thursday']
			&& $template['friday'] && $template['saturday']
			&& $template['sunday']) {
			
			/* Everyday */
			echo '<strong>'.__('Everyday').'</strong><br />';
		} else {
			$days = array ('monday' => __('Monday'),
				'tuesday' => __('Tuesday'),
				'wednesday' => __('Wednesday'),
				'thursday' => __('Thursday'),
				'friday' => __('Friday'),
				'saturday' => __('Saturday'),
				'sunday' => __('Sunday'));
			
			echo '<strong>'.__('Days').'</strong>: '.__('Every').' ';
			$actives = array ();
			foreach ($days as $day => $name) {
				if ($template[$day])
					array_push ($actives, $name);
			}
			
			$last = array_pop ($actives);
			if (count ($actives)) {
				echo implode (', ', $actives);
				echo ' '.__('and').' ';
			}
			echo $last;
			
		}
		echo '<br />';
		
		if ($template['time_from'] != $template['time_to']) {
			echo '<strong>'.__('From').'</strong> ';
			echo $template['time_from'];
			echo ' <strong>'.__('to').'</strong> ';
			echo $template['time_to'];
			echo '<br />';
		}
		
		
		return;
	}
	
	return;
}

echo '<h1>'.__('Alert templates').'</h1>';
$update_template = (bool) get_parameter ('update_template');
$delete_template = (bool) get_parameter ('delete_template');

if ($update_template) {
	$id = (int) get_parameter ('id');
	
	$recovery_notify = (bool) get_parameter ('recovery_notify');
	$field2_recovery = (bool) get_parameter ('field2_recovery');
	$field3_recovery = (bool) get_parameter ('field3_recovery');
	
	$result = update_alert_template ($id,
		array ('recovery_notify' => $recovery_notify,
			'field2_recovery' => $field2_recovery,
			'field3_recovery' => $field3_recovery));
	
	print_error_message ($result, __('Successfully updated'),
		__('Could not be updated'));
}

if ($delete_template) {
	$id = get_parameter ('id');
	// Templates below 4 are special and cannot be deleted
	if ($id < 4) {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to access Alert Management");
		require ("general/noaccess.php");
		exit;
	}
	
	$result = delete_alert_template ($id);
	
	print_error_message ($result, __('Successfully deleted'),
		__('Could not be deleted'));
}

$table->width = '90%';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Description');
$table->head[2] = __('Type');
$table->head[3] = __('Delete');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[2] = '10%';
$table->size[3] = '40px';
$table->align = array ();
$table->align[3] = 'center';

$templates = get_alert_templates (false);
if ($templates === false)
	$templates = array ();

foreach ($templates as $template) {
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&id='.$template['id'].'">'.
		$template['name'].'</a>';
	
	$data[1] = $template['description'];
	$data[2] = get_alert_templates_type_name ($template['type']);
	$data[3] = '<a href="index.php?sec=gagente&sec2=godmode/alerts/alert_templates&delete_template=1&id='.$template['id'].'"
		onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.
		'<img src="images/cross.png"></a>';
	
	array_push ($table->data, $data);
}

print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template">';
print_submit_button (__('Create'), 'create', false, 'class="sub next"');
print_input_hidden ('create_alert', 1);
echo '</form>';
echo '</div>';

?>
