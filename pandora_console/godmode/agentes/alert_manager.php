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

if (!isset ($id_agente)) {
	die ("Not Authorized");
}

echo "<h2>".__('Agent configuration')." &gt; ".__('Alerts')."</h2>";

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
	print_error_message ($id, __('Successfully created'),
		__('Could not be created'));
	if ($id !== false) {
		$id_alert_action = (int) get_parameter ('action');
		$fires_min = (int) get_parameter ('fires_min');
		$fires_max = (int) get_parameter ('fires_max');
		$values = array ();
		if ($fires_min != -1)
			$values['fires_min'] = $fires_min;
		if ($fires_max != -1)
			$values['fires_max'] = $fires_max;
		
		add_alert_agent_module_action ($id, $id_alert_action, $values);
	}
}

if ($delete_alert) {
	$id_alert_agent_module = (int) get_parameter ('id_alert');
	
	$result = delete_alert_agent_module ($id_alert_agent_module);
	print_error_message ($id, __('Successfully deleted'),
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
	print_error_message ($id, __('Successfully added'),
		__('Could not be added'));
}

if ($delete_action) {
	$id_action = (int) get_parameter ('id_action');
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = delete_alert_agent_module_action ($id_alert, $id_action);
	print_error_message ($id, __('Successfully deleted'),
		__('Could not be deleted'));
}

if ($enable_alert) {
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = set_alerts_agent_module_disable ($id_alert, false);
	print_error_message ($id, __('Successfully enabled'),
		__('Could not be enabled'));
}

if ($disable_alert) {
	$id_alert = (int) get_parameter ('id_alert');
	
	$result = set_alerts_agent_module_disable ($id_alert, true);
	print_error_message ($id, __('Successfully disabled'),
		__('Could not be disabled'));
}

$modules = get_agent_modules ($id_agente,
	array ('id_tipo_modulo', 'nombre', 'id_agente'));

echo "<h3>".__('Alerts defined')."</h3>";

$table->class = 'databox_color modules';
$table->cellspacing = '0';
$table->width = '90%';
$table->data = array ();
$table->rowstyle = array ();
$table->style = array ();
$table->style[1] = 'vertical-align: top';

$table_alerts->class = 'listing';
$table_alerts->width = '100%';
$table_alerts->size = array ();
$table_alerts->size[0] = '50%';
$table_alerts->size[1] = '50%';
$table_alerts->style = array ();
$table_alerts->style[0] = 'vertical-align: top';
$table_alerts->style[1] = 'vertical-align: top';

foreach ($modules as $id_agent_module => $module) {
	$last_data = get_agent_module_last_value ($id_agent_module);
	if ($last_data === false)
		$last_data = '<em>'.__('N/A').'</em>';
	
	$table->data[0][0] = '<span><strong>Module</strong>: '.$module['nombre'].'</span>';
	$table->data[0][0] .= '<div class="actions left" style="visibility: hidden;">';
	$table->data[0][0] .= '<span class="module_values" style="float: right;">';
	$table->data[0][0] .= '<em>'.__('Latest value').'</em>: ';
	if ($last_data == '')
		$table->data[0][0] .= '<em>'.__('Empty').'</em>';
	elseif (is_numeric ($last_data))
		$table->data[0][0] .= format_numeric ($last_data);
	else
		$table->data[0][0] .= $last_data;
	
	$table->data[0][0] .= '</span>';
	$table->data[0][0] .= '</div>';
	$table->data[0][0] .= '<div class="actions right" style="visibility: hidden;">';
	$table->data[0][0] .= '<span class="add">';
	$table->data[0][0] .= '<a href="#" class="add_alert" id="module-'.$id_agent_module.'">';
	$table->data[0][0] .= __('Add alert');
	$table->data[0][0] .= '</a>';
	$table->data[0][0] .= '</span>';
	$table->data[0][0] .= '</div>';
	
	
	/* Alerts in module list */
	$table_alerts->id = 'alerts-'.$id_agent_module;
	$table_alerts->data = array ();
	
	$alerts = get_alerts_agent_module ($id_agent_module, true);
	if ($alerts === false) {
		$alerts = array ();
		$table->data[1][0] = '';
		$table->rowstyle[1] = 'display: none';
	} else {
		$table->data[1][0] = '<h4 class="left" style="clear: left">';
		$table->data[1][0] .= __('Alerts assigned');
		$table->data[1][0] .= '</h4>';
		$table->rowstyle[1] = '';
	}
	
	foreach ($alerts as $alert) {
		$alert_data = array ();
		
		$alert_actions = get_alert_agent_module_actions ($alert['id']);
		
		$alert_data[0] = get_alert_template_name ($alert['id_alert_template']);
		
		if (empty ($alert_actions)) {
			$alert_data[0] .= '<form style="display: inline" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$id_agente.'" method="post" class="delete_link">';
			$alert_data[0] .= print_input_image ('delete', 'images/cross.png', 1, '', true);
			$alert_data[0] .= print_input_hidden ('delete_alert', 1, true);
			$alert_data[0] .= print_input_hidden ('id_alert', $alert['id'], true);
			$alert_data[0] .= '</form>';
		} else {
			$alert_data[0] .= '<form style="display: inline" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$id_agente.'" method="post">';
			if ($alert['disabled']) {
				$alert_data[0] .= print_input_image ('enable', 'images/lightbulb_off.png', 1, '', true);
				$alert_data[0] .= print_input_hidden ('enable_alert', 1, true);
			} else {
				$alert_data[0] .= print_input_image ('disable', 'images/lightbulb.png', 1, '', true);
				$alert_data[0] .= print_input_hidden ('disable_alert', 1, true);
			}
			$alert_data[0] .= print_input_hidden ('id_alert', $alert['id'], true);
			$alert_data[0] .= '</form>';
		}
		
		$alert_data[0] .= '<span class="actions" style="visibility: hidden">';
		$alert_data[0] .= '<a href="ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$alert['id_alert_template'].'"
			class="template_details">';
		$alert_data[0] .= print_image ("images/zoom.png", true,
			array ("id" => 'template-details-'.$alert['id'],
				"class" => "left img_help")
			);
		$alert_data[0] .= '</a>';
		$alert_data[0] .= '</span>';
		
		$alert_data[1] = '<ul style="float: left; margin-bottom: 10px">';
		foreach ($alert_actions as $action) {
			$alert_data[1] .= '<li><div>';
			$alert_data[1] .= '<span class="left">';
			$alert_data[1] .= $action['name'].' ';
			$alert_data[1] .= '<em>(';
			if ($action['fires_min'] == $action['fires_max']) {
				if ($action['fires_min'] == 0)
					$alert_data[1] .= __('Always');
				else
					$alert_data[1] .= __('On').' '.$action['fires_min'];
			} else {
				if ($action['fires_min'] == 0)
					$alert_data[1] .= __('Until').' '.$action['fires_max'];
				else
					$alert_data[1] .= __('From').' '.$action['fires_min'].
						' '.__('to').' '.$action['fires_max'];
			}
			$url = '&delete_action=1&id_alert='.$alert['id'].'&id_action='.$action['id'];
			$alert_data[1] .= ')</em>';
			$alert_data[1] .= '</span>';
			$alert_data[1] .= ' <span class="actions" style="visibility: hidden">';
			$alert_data[1] .= '<span class="delete">';
			$alert_data[1] .= '<form action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$id_agente.'" method="post" class="delete_link">';
			$alert_data[1] .= print_input_image ('delete', 'images/cross.png', 1, '', true);
			$alert_data[1] .= print_input_hidden ('delete_action', 1, true);
			$alert_data[1] .= print_input_hidden ('id_alert', $alert['id'], true);
			$alert_data[1] .= print_input_hidden ('id_action', $action['id'], true);
			$alert_data[1] .= '</form>';
			$alert_data[1] .= '</span>';
			$alert_data[1] .= '</span>';
			$alert_data[1] .= '</div></li>';
		}
		$alert_data[1] .= '</ul>';
		
		$alert_data[1] .= '<div class="actions left invisible" style="clear: left">';
		$alert_data[1] .= '<a class="add_action" id="add-action-'.$alert['id'].'" href="#">';
		$alert_data[1] .= __('Add action');
		$alert_data[1] .= '</a>';
		$alert_data[1] .= '</div>';
		
		$table_alerts->data['alert-'.$alert['id']] = $alert_data;
	}
	
	$table->data[1][0] .= print_table ($table_alerts, true);
	
	print_table ($table);
	$table->data = array ();
}

/* This hidden value is used in Javascript. It's a workaraound for IE because
   it doesn't allow input elements creation. */
print_input_hidden ('add_action', 1);
print_input_hidden ('id_alert_module', 0);

echo '<form class="add_alert_form invisible" method="post"
	action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.
	$id_agente.'">';
echo '<div style="float:left">';
print_label (__('Template'), 'template');
$templates = get_alert_templates ();
if (empty ($templates))
	$templates = array ();
print_select ($templates, 'template', '', '', __('None'), 0);
echo '</div><div style="margin-left: 270px">';
print_label (__('Action'), 'action');
$actions = get_alert_actions ();
if (empty ($actions))
	$actions = array ();
print_select ($actions, 'action', '', '', __('None'), 0);
echo '<br />';
echo '<span><a href="#" class="show_advanced_actions">'.__('Advanced options').' &raquo; </a></span>';
echo '<span class="advanced_actions invisible">';
echo __('From').' ';
print_input_text ('fires_min', -1, '', 4, 10);
echo ' '.__('to').' ';
print_input_text ('fires_max', -1, '', 4, 10);
echo ' '.__('matches of the alert');
echo pandora_help("alert-matches", true);
echo '</span></div>';
echo '<div style="float: right; margin-left: 30px;"><br />';
print_submit_button (__('Add'), 'add', false, 'class="sub next"');
print_input_hidden ('id_agent_module', 0);
print_input_hidden ('create_alert', 1);
echo '</div></form>';
$config['jquery'][] = 'cluetip';
$config['css'][] = 'cluetip';
?>
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("table.modules tr").hover (
		function () {
			$(".actions", this).css ("visibility", "");
		},
		function () {
			$(".actions", this).css ("visibility", "hidden");
		}
	);
	
	$("a.add_alert").click (function () {
		place = $(this).parents ("tbody").children ("tr:last").children ("td");
		if ($("form.add_alert_form", place).length > 0) {
			return false;
		}
		id = this.id.split ("-").pop ();
		form = $("form.add_alert_form:last").clone (true);
		$("input#hidden-id_agent_module", form).attr ("value", id);
		$(place).append (form);
		$(form).show ();
		$(this).parents ("tbody").children ("tr:last").show ();
		return false;
	});
	
	$(".add_alert_form").submit (function () {
		if ($("#template", this).attr ("value") == 0) {
			return false;
		}
		
		return true;
	});
	
	$("a.show_advanced_actions").click (function () {
		/* It can be done in two different site, so it must use two different selectors */
		actions = $(this).parents ("form").children ("span.advanced_actions");
		if (actions.length == 0)
			actions = $(this).parents ("div").children ("span.advanced_actions")
		$("#text-fires_min", actions).attr ("value", 0);
		$("#text-fires_max", actions).attr ("value", 0);
		$(actions).show ();
		$(this).remove ();
		return false;
	});
	
	$(".actions a.add_action").click (function () {
		id = this.id.split ("-").pop ();
		
		/* Remove new alert form (if shown) to clean a bit the UI */
		$(this).parents ("td:last").children ("form.add_alert_form")
			.remove ();
		
		/* Replace link with a combo with the actions and a form */
		a = $("a.show_advanced_actions:first").clone (true);
		advanced = $("span.advanced_actions:first").clone (true).hide ();
		select = $("select#action:first").clone ();
		button = $('<input type="image" class="sub next" value="'+"<?php echo __('Add');?>"+'"></input>');
		divbutton = $("<div></div>").css ("float", "right").html (button);
		input1 = $("input#hidden-add_action");
		input2 = $("input#hidden-id_alert_module").clone ().attr ("value", id);
		form = $('<form method="post"></form>')
			.append (select)
			.append ("<br></br>")
			.append (a)
			.append (advanced)
			.append (divbutton)
			.append (input1)
			.append (input2);
		
		$(this).parents (".actions:first").replaceWith (form);
		
		return false;
	});
	
	$("a.template_details").cluetip ({
		arrows: true,
		attribute: 'href',
		cluetipClass: 'default'
	}).click (function () {
		return false;
	});;
	
	$("select[name=template]").change (function () {
		if (this.value == 0) {
			$(this).parents ("div:first").children ("a").remove ();
			return;
		}
		
		details = $("a.template_details:first").clone (true)
			.attr ("href",
				"ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template=" + this.value);
		$(this).after (details);
	});
	$("form.delete_link").submit (function () {
		if (! confirm ("<?php echo __('Are you sure?')?>"))
			return false;
		return true;
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
});
/* ]]> */
</script>
