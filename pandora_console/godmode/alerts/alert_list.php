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

check_login ();

if (is_ajax ()) {
	$get_agent_alerts_simple = (bool) get_parameter ('get_agent_alerts_simple');
	$disable_alert = (bool) get_parameter ('disable_alert');
	$enable_alert = (bool) get_parameter ('enable_alert');
	
	if ($get_agent_alerts_simple) {
		$id_agent = (int) get_parameter ('id_agent');
		if ($id_agent <= 0) {
			echo json_encode (false);
			return;
		}
		$id_group = get_agent_group ($id_agent);
		
		if (! give_acl ($config['id_user'], $id_group, "AR")) {
			audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
				"Trying to access Alert Management");
			echo json_encode (false);
			return;
		}
		
		require_once ('include/functions_agents.php');
		require_once ('include/functions_alerts.php');
		
		$alerts = get_agent_alerts_simple ($id_agent);
		if (empty ($alerts)) {
			echo json_encode (false);
			return;
		}
		
		$retval = array ();
		foreach ($alerts as $alert) {
			$alert['template'] = get_alert_template ($alert['id_alert_template']);
			$alert['module_name'] = get_agentmodule_name ($alert['id_agent_module']);
			$alert['agent_name'] = get_agentmodule_agent_name ($alert['id_agent_module']);
			$retval[$alert['id']] = $alert;
		}
		
		echo json_encode ($retval);
		return;
	}
	
	if ($enable_alert) {
		$id_alert = (int) get_parameter ('id_alert');
	
		$result = set_alerts_agent_module_disable ($id_alert, false);
		if ($result)
			echo __('Successfully enabled');
		else
			echo __('Could not be enabled');
		return;
	}

	if ($disable_alert) {
		$id_alert = (int) get_parameter ('id_alert');
	
		$result = set_alerts_agent_module_disable ($id_alert, true);
		if ($result)
			echo __('Successfully disabled');
		else
			echo __('Could not be disabled');
		return;
	}
	return;
}

$id_group = 0;
/* Check if this page is included from a agent edition */
if (isset ($id_agente)) {
	$id_group = get_agent_group ($id_agente);
} else {
	$id_agente = 0;
}

if (! give_acl ($config['id_user'], $id_group, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');

$create_alert = (bool) get_parameter ('create_alert');
$add_action = (bool) get_parameter ('add_action');
$delete_action = (bool) get_parameter ('delete_action');
$delete_alert = (bool) get_parameter ('delete_alert');
$disable_alert = (bool) get_parameter ('disable_alert');
$enable_alert = (bool) get_parameter ('enable_alert');

if ($create_alert) {
	$id_alert_template = (int) get_parameter ('template');
	$id_agent_module = (int) get_parameter ('id_agent_module');
	
	$id = create_alert_agent_module ($id_agent_module, $id_alert_template);
	print_result_message ($id,
		__('Successfully created'),
		__('Could not be created'));
	if ($id !== false) {
		$actions = (array) get_parameter ('actions');
		$fires_min = (array) get_parameter ('fires_min');
		$fires_max = (array) get_parameter ('fires_max');
		
		foreach ($actions as $id_action) {
			$values = array ();
			if (isset ($fires_min[$id_action]))
				$values['fires_min'] = @max ($fires_min[$id_action], 0);
			if (($fires_max != -1) && (isset ($fires_min[$id_action]))) 
				$values['fires_max'] = @max ($fires_max[$id_action], 0);
			add_alert_agent_module_action ($id, $id_action, $values);
		}
	}
}

if ($delete_alert) {
	$id_alert_agent_module = (int) get_parameter ('id_alert');
	
	$result = delete_alert_agent_module ($id_alert_agent_module);
	print_result_message ($id,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

if ($add_action) {
	$id_action = (int) get_parameter ('action');
	$id_alert_module = (int) get_parameter ('id_alert_module');
	$fires_min = (int) get_parameter ('fires_min');
	$fires_max = (int) get_parameter ('fires_max');
	$values = array ();
	if ($fires_min != -1)
		$values['fires_min'] = $fires_min;
	if ($fires_max != -1)
		$values['fires_max'] = $fires_max;
	
	$result = add_alert_agent_module_action ($id_alert_module, $id_action, $values);
	print_result_message ($id,
		__('Successfully added'),
		__('Could not be added'));
}

if ($delete_action) {
	$id_action = (int) get_parameter ('id_action');
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = delete_alert_agent_module_action ($id_action);
	print_result_message ($id,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

if ($enable_alert) {
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = set_alerts_agent_module_disable ($id_alert, false);
	print_result_message ($result,
		__('Successfully enabled'),
		__('Could not be enabled'));
}

if ($disable_alert) {
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = set_alerts_agent_module_disable ($id_alert, true);
	print_result_message ($result,
		__('Successfully disabled'),
		__('Could not be disabled'));
}

if ($id_agente) {
	echo '<h1>'.__('Agent configuration').' &gt; '.__('Alerts').'</h1>';
	$agents = array ($id_agente => get_agent_name ($id_agente));
} else {
	echo '<h1>'.__('Alerts').'</h1>';
	$groups = get_user_groups ();
	$agents = get_group_agents (array_keys ($groups), false, "none");
}


if ($id_agente) {
	$simple_alerts = get_agent_alerts_simple (array_keys ($agents));
} else {
	$sql = sprintf ('SELECT COUNT(*) FROM talert_template_modules
		WHERE id_agent_module IN (SELECT id_agente_modulo
			FROM tagente_modulo WHERE id_agente IN (%s))',
		implode (',', array_keys ($agents)));
	$total = get_db_sql ($sql);
	
	pagination ($total, 'index.php?sec=gagente&sec2=godmode/alerts/alert_list');
	$simple_alerts = get_agent_alerts_simple (array_keys ($agents), '',
		array ('offset' => (int) get_parameter ('offset'),
			'limit' => $config['block_size']));
}

$table->class = 'alert_list';
$table->width = '90%';
$table->size = array ();
$table->head = array ();
$table->head[0] = '';
if (! $id_agente) {
	$table->style = array ();
	$table->style[1] = 'font-weight: bold';
	$table->head[1] = __('Agent');
	$table->size[0] = '20px';
	$table->size[1] = '15%';
	$table->size[2] = '20%';
	$table->size[3] = '15%';
	$table->size[4] = '50%';
} else {
	/* Different sizes or the layout screws up */
	$table->size[0] = '20px';
	$table->size[2] = '30%';
	$table->size[3] = '20%';
	$table->size[4] = '50%';
}
$table->head[2] = __('Module');
$table->head[3] = __('Template');
$table->head[4] = __('Actions');
$table->head[5] = '';
$table->data = array ();

foreach ($simple_alerts as $alert) {
	$data = array ();
	
	$data[0] = '<form class="disable_alert_form" method="post" style="display: inline;">';
	if ($alert['disabled']) {
		$data[0] .= print_input_image ('enable', 'images/lightbulb_off.png', 1, '', true);
		$data[0] .= print_input_hidden ('enable_alert', 1, true);
	} else {
		$data[0] .= print_input_image ('disable', 'images/lightbulb.png', 1, '', true);
		$data[0] .= print_input_hidden ('disable_alert', 1, true);
	}
	$data[0] .= print_input_hidden ('id_alert', $alert['id'], true);
	$data[0] .= '</form>';
	
	if (! $id_agente) {
		$id_agent = get_agentmodule_agent ($alert['id_agent_module']);
		$data[1] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente='.$id_agent.'">';
		$data[1] .= get_agent_name ($id_agent);
		$data[1] .= '</a>';
	}
	$data[2] = get_agentmodule_name ($alert['id_agent_module']);
	$data[3] = ' <a class="template_details"
		href="ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$alert['id_alert_template'].'">
		<img id="template-details-'.$alert['id_alert_template'].'" class="img_help" src="images/zoom.png"/></a> ';
	$data[3] .= get_alert_template_name ($alert['id_alert_template']);
	
	$actions = get_alert_agent_module_actions ($alert['id']);
	$data[4] = '<ul class="action_list">';
	foreach ($actions as $action_id => $action) {
		$data[4] .= '<li><div>';
		$data[4] .= '<span class="action_name">';
		$data[4] .= $action['name'];
		$data[4] .= ' <em>(';
		if ($action['fires_min'] == $action['fires_max']) {
			if ($action['fires_min'] == 0)
				$data[4] .= __('Always');
			else
				$data[4] .= __('On').' '.$action['fires_min'];
		} else {
			if ($action['fires_min'] == 0)
				$data[4] .= __('Until').' '.$action['fires_max'];
			else
				$data[4] .= __('From').' '.$action['fires_min'].
					' '.__('to').' '.$action['fires_max'];
		}
		
		$data[4] .= ')</em>';
		$data[4] .= '</span>';
		$data[4] .= ' <span class="delete" style="clear:right">';
		$data[4] .= '<form method="post" class="delete_link">';
		$data[4] .= print_input_image ('delete', 'images/cross.png', 1, '', true);
		$data[4] .= print_input_hidden ('delete_action', 1, true);
		$data[4] .= print_input_hidden ('id_alert', $alert['id'], true);
		$data[4] .= print_input_hidden ('id_action', $action_id, true);
		$data[4] .= '</form>';
		$data[4] .= '</span>';
		$data[4] .= '</div></li>';
	}
	$data[4] .= '</ul>';
	
	$data[4] .= '<a class="add_action" id="add-action-'.$alert['id'].'" href="#">';
	$data[4] .= print_image ('images/add.png', true);
	$data[4] .= ' '.__('Add action');
	$data[4] .= '</a>';
	
	$data[5] = '<form class="delete_alert_form" method="post" style="display: inline;">';
	
	$data[5] .= print_input_image ('delete', 'images/cross.png', 1, '', true);
	$data[5] .= print_input_hidden ('delete_alert', 1, true);
	$data[5] .= print_input_hidden ('id_alert', $alert['id'], true);
	$data[5] .= '</form>';
	array_push ($table->data, $data);
}

print_table ($table);

echo '<h3>'.__('Add alert').'</h3>';

$table->id = 'add_alert_table';
$table->class = 'databox';
$table->head = array ();
$table->data = array ();
$table->size = array ();
$table->size = array ();
$table->size[0] = '10%';
$table->size[1] = '90%';
$table->style[0] = 'font-weight: bold; vertical-align: top;';

/* Add an agent selector */
if (! $id_agente) {
	$table->data['group'][0] = __('Group');
	$table->data['group'][1] = print_select ($groups, 'id_group', $id_group,
		false, '', '', true);
	
	$table->data['agent'][0] = __('Agent');
	$table->data['agent'][1] = print_select (array_keys (get_group_agents (array_keys ($groups), false, "none")),
		'id_agent', 0, false, __('Select'), 0, true);
	$table->data['agent'][1] .= ' <span id="agent_loading" class="invisible">';
	$table->data['agent'][1] .= '<img src="images/spinner.gif" />';
	$table->data['agent'][1] .= '</span>';
}

$table->data[0][0] = __('Module');
$modules = array ();
if ($id_agente)
	$modules = get_agent_modules ($id_agente);
$table->data[0][1] = print_select ($modules, 'id_agent_module', 0, true,
	__('Select'), 0, true, false, true, '', ($id_agente == 0));
$table->data[0][1] .= ' <span id="latest_value" class="invisible">'.__('Latest value').': ';
$table->data[0][1] .= '<span id="value">&nbsp;</span></span>';
$table->data[0][1] .= ' <span id="module_loading" class="invisible">';
$table->data[0][1] .= '<img src="images/spinner.gif" /></span>';

$table->data[1][0] = __('Template');
$templates = get_alert_templates ();
if (empty ($templates))
	$templates = array ();
$table->data[1][1] = print_select ($templates, 'template', '', '', __('Select'),
	0, true);
$table->data[1][1] .= ' <a class="template_details invisible" href="#">
	<img class="img_help" src="images/zoom.png" /></a>';

$table->data[2][0] = __('Actions');
$actions = get_alert_actions ();
if (empty ($actions))
	$actions = array ();
$table->data[2][1] = '<div class="actions_container">';
foreach ($actions as $action_id => $action_name) {
	$id = 'actions['.$action_id.']';
	$table->data[2][1] .= print_checkbox ($id, $action_id, false, true);
	$table->data[2][1] .= print_label ($action_name, 'checkbox-'.$id, true);
	$table->data[2][1] .= ' <span id="advanced_'.$action_id.'" class="advanced_actions invisible">';
	$table->data[2][1] .=  __('From').' ';
	$table->data[2][1] .= print_input_text ('fires_min['.$action_id.']', -1, '', 4, 10, true);
	$table->data[2][1] .=  ' '.__('to').' ';
	$table->data[2][1] .= print_input_text ('fires_max['.$action_id.']', -1, '', 4, 10, true);
	$table->data[2][1] .= ' '.__('matches of the alert');
	$table->data[2][1] .= '</span>';
	$table->data[2][1] .= '<br />';
}
$table->data[2][1] .= '</div>';

echo '<form class="add_alert_form" method="post">';

print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Add'), 'add', false, 'class="sub next"');
print_input_hidden ('create_alert', 1);
echo '</div></form>';

echo '<form id="add_action_form" method="post" class="invisible">';
print_input_hidden ('add_action', 1);
print_input_hidden ('id_alert_module', 0);
print_select ($actions, 'action', '', '', __('None'), 0);
echo '<br />';
echo '<span><a href="#" class="show_advanced_actions">'.__('Advanced options').' &raquo; </a></span>';
echo '<span class="advanced_actions invisible">';
echo __('From').' ';
print_input_text ('fires_min', -1, '', 4, 10);
echo ' '.__('to').' ';
print_input_text ('fires_max', -1, '', 4, 10);
echo ' '.__('matches of the alert');
echo print_help_icon ("alert-matches", true);
echo '</span>';
echo '<div class="right">';
print_submit_button (__('Add'), 'add_action', false, 'class="sub next"');
echo '</div>';
echo '</form>';

require_css_file ('cluetip');
require_jquery_file ('cluetip');
require_jquery_file ('pandora.controls');
?>
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
<?php if (! $id_agente) : ?>
	$("#id_group").pandoraSelectGroup ({
		callbackBefore: function () {
			$select = $("#id_agent_module").disable ();
			$select.siblings ("span#latest_value").hide ();
			$("option[value!=0]", $select).remove ();
		},
		callbackAfter: function () {
		}
	});
	
	$("#id_agent").pandoraSelectAgentModule ();
<?php endif; ?>
	$("a.template_details").cluetip ({
		arrows: true,
		attribute: 'href',
		cluetipClass: 'default'
	}).click (function () {
		return false;
	});
	
	$("input[name=disable]").attr ("title", "<?php echo __('Disable')?>")
		.hover (function () {
				$(this).attr ("src", "images/lightbulb_off.png");
			},
			function () {
				$(this).attr ("src", "images/lightbulb.png");
			}
		);
	$("input[name=enable]").attr ("title", "<?php echo __('Enable')?>")
		.hover (function () {
				$(this).attr ("src", "images/lightbulb.png");
			},
			function () {
				$(this).attr ("src", "images/lightbulb_off.png");
			}
		);
	$("form.disable_alert_form").submit (function () {
		return true;
	});
	
	
	$("a.add_action").click (function () {
		id = this.id.split ("-").pop ();
		
		/* Replace link with a combo with the actions and a form */
		$form = $('form#add_action_form:last').clone (true).show ();
		$("input#hidden-id_alert_module", $form).attr ("value", id);
		$(this).replaceWith ($form);
		return false;
	});
	
	$("form.delete_link, form.delete_alert_form").submit (function () {
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;
		return true;
	});
	
	$("a.show_advanced_actions").click (function () {
		/* It can be done in two different sites, so it must use two different selectors */
		actions = $(this).parents ("form").children ("span.advanced_actions");
		if (actions.length == 0)
			actions = $(this).parents ("div").children ("span.advanced_actions")
		$("#text-fires_min", actions).attr ("value", 0);
		$("#text-fires_max", actions).attr ("value", 0);
		$(actions).show ();
		$(this).remove ();
		return false;
	});
	
	$("select#template").change (function () {
		id = this.value;
		$a = $(this).siblings ("a");
		if (id == 0) {
			$a.hide ();
			return;
		}
		$a.unbind ()
			.attr ("href", "ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template="+id)
			.show ()
			.cluetip ({
				arrows: true,
				attribute: 'href',
				cluetipClass: 'default'
			}).click (function () {
				return false;
			});
	});
	
	$("#id_agent_module").change (function () {
		var $value = $(this).siblings ("span#latest_value").hide ();
		var $loading = $(this).siblings ("span#module_loading").show ();
		$("#value", $value).empty ();
		jQuery.post ("ajax.php",
			{"page" : "operation/agentes/estado_agente",
			"get_agent_module_last_value" : 1,
			"id_agent_module" : this.value
			},
			function (data, status) {
				if (data === false) {
					$("#value", $value).append ("<em><?php echo __('Unknown') ?></em>");
				} else if (data == "") {
					$("#value", $value).append ("<em><?php echo __('Empty') ?></em>");
				} else {
					$("#value", $value).append (data);
				}
				$loading.hide ();
				$value.show ();
			},
			"json"
		);
	});
	
	$("form.add_alert_form :checkbox[name^=actions]").change (function () {
		$advanced = $(this).siblings ("span#advanced_"+this.value);
		$("input", $advanced).attr ("value", 0);
		$advanced.toggle ();
	});
});
/* ]]> */
</script>
