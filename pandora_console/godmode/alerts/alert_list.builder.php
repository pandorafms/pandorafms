<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

// Login check
check_login();

if (! check_acl($config['id_user'], 0, 'LW')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_users.php';

$pure = get_parameter('pure', 0);

$table->id = 'add_alert_table';
$table->class = 'databox filters';
$table->width = '100%';
$table->head = [];
$table->data = [];
$table->size = [];
$table->size = [];
$table->style[0] = 'font-weight: bold;';
$table->style[1] = 'font-weight: bold;display: flex;align-items: baseline;';
$table->style[2] = 'font-weight: bold;';
$table->style[3] = 'font-weight: bold;';

// This is because if this view is reused after list alert view then
// styles in the previous view can affect this table.
$table->rowstyle[0] = '';
$table->rowstyle[1] = '';
$table->rowstyle[2] = '';
$table->rowstyle[3] = '';


// Add an agent selector
if (! $id_agente) {
    $table->data['agent'][0] = __('Agent');

    $params = [];
    $params['return'] = true;
    $params['show_helptip'] = true;
    $params['input_name'] = 'id_agent';
    $params['selectbox_id'] = 'id_agent_module';
    $params['javascript_is_function_select'] = true;
    $params['metaconsole_enabled'] = false;
    $params['use_hidden_input_idagent'] = true;
    $params['print_hidden_input_idagent'] = true;
    $table->data['agent'][1] = ui_print_agent_autocomplete_input($params);
}

$table->data[0][0] = __('Module');
$modules = [];

if ($id_agente) {
    $modules = agents_get_modules($id_agente, false, ['delete_pending' => 0]);
}

$table->data[0][1] = html_print_select(
    $modules,
    'id_agent_module',
    0,
    true,
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    ($id_agente == 0),
    'min-width: 250px;margin-right: 0.5em;'
);
$table->data[0][1] .= ' <span id="latest_value" class="invisible">'.__('Latest value').': ';
$table->data[0][1] .= '<span id="value">&nbsp;</span></span>';
$table->data[0][1] .= ' <span id="module_loading" class="invisible">';
$table->data[0][1] .= html_print_image('images/spinner.png', true).'</span>';

$table->data[1][0] = __('Actions');

$groups_user = users_get_groups($config['id_user']);
if (!empty($groups_user)) {
    $groups = implode(',', array_keys($groups_user));
    $sql = "SELECT id, name FROM talert_actions WHERE id_group IN ($groups)";
    $actions = db_get_all_rows_sql($sql);
}

$table->data[1][1] = html_print_select(
    index_array($actions, 'id', 'name'),
    'action_select',
    '',
    '',
    __('Default action'),
    '0',
    true,
    '',
    true,
    '',
    false,
    'min-width: 250px;'
);
$table->data[1][1] .= '<span id="advanced_action" class="advanced_actions invisible"><br>';
$table->data[1][1] .= __('Number of alerts match from').' ';
$table->data[1][1] .= html_print_input_text('fires_min', '', '', 4, 10, true);
$table->data[1][1] .= ' '.__('to').' ';
$table->data[1][1] .= html_print_input_text('fires_max', '', '', 4, 10, true);

$table->data[1][1] .= '</span>';
if (check_acl($config['id_user'], 0, 'LM')) {
    $table->data[1][1] .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_action&pure='.$pure.'">';
    $table->data[1][1] .= html_print_image('images/add.png', true);
    $table->data[1][1] .= '<span style="margin-left:0.5em;">'.__('Create Action').'</span>';
    $table->data[1][1] .= '</a>';
}

    $table->data[2][0] = __('Template');
    $own_info = get_user_info($config['id_user']);
if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
    $templates = alerts_get_alert_templates(false, ['id', 'name']);
} else {
    $usr_groups = users_get_groups($config['id_user'], 'LW', true);
    $filter_groups = '';
    $filter_groups = implode(',', array_keys($usr_groups));
    $templates = alerts_get_alert_templates(['id_group IN ('.$filter_groups.')'], ['id', 'name']);
}

    $table->data[2][1] = html_print_select(
        index_array($templates, 'id', 'name'),
        'template',
        '',
        '',
        __('Select'),
        0,
        true,
        false,
        true,
        '',
        false,
        'width: 250px;'
    );
    $table->data[2][1] .= ' <a class="template_details invisible" href="#">'.html_print_image('images/zoom.png', true, ['class' => 'img_help']).'</a>';
    if (check_acl($config['id_user'], 0, 'LM')) {
        $table->data[2][1] .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&pure='.$pure.'">';
        $table->data[2][1] .= html_print_image('images/add.png', true);
        $table->data[2][1] .= '<span style="margin-left:0.5em;">'.__('Create Template').'</span>';
        $table->data[2][1] .= '</a>';
    }

    $table->data[3][0] = __('Threshold');
    $table->data[3][1] = html_print_input_text('module_action_threshold', '0', '', 5, 7, true);
    $table->data[3][1] .= '<span style="margin-left:0.5em;">'.__('seconds').'</span>';

    if (!isset($step)) {
        echo '<form class="add_alert_form" method="post">';
        html_print_table($table);
    }

    echo '<div class="action-buttons" style="width: '.$table->width.'">';

    if (!isset($step)) {
        if ($id_cluster) {
            echo "<input onclick='window.location.replace(\"index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_view&id=".$id_cluster."\");' type=button style='float:right;margin-left:20px;' name='store' class='sub upd' value='".__('Finish and view cluster')."'>";
        }

        html_print_submit_button(__('Add alert'), 'add', false, 'class="sub wand"');
        html_print_input_hidden('create_alert', 1);
        echo '</div></form>';
    }

    ui_require_css_file('cluetip', 'include/styles/js/');
    ui_require_jquery_file('validate');
    ui_require_jquery_file('cluetip');
    ui_require_jquery_file('pandora.controls');
    ui_require_jquery_file('bgiframe');

    ?>
<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
<?php if (! $id_agente) : ?>
    $("#id_group").pandoraSelectGroupAgent ({
        callbackBefore: function () {
            $select = $("#id_agent_module").disable ();
            $select.siblings ("span#latest_value").hide ();
            $("option[value!=0]", $select).remove ();
            return true;
        }
    });
<?php endif; ?>

    // Rule.
    $.validator.addMethod(
        "valueNotEquals",
        function(value, element, arg) {
            return arg != value;
        },
        "Value must not equal arg."
    );

    // configure your validation
    $("form.add_alert_form").validate({
        rules: {
            id_agent_module: { valueNotEquals: "0" }
        },
        messages: {
            id_agent_module: { valueNotEquals: "Please select an item!" }
        }
    });
    $("select#template").change (function () {
        id = this.value;
        $a = $(this).siblings ("a.template_details");
        if (id == 0) {
            $a.hide ();
            return;
        }
        $a.unbind ()
            .attr ("href",<?php echo "'".ui_get_full_url(false, false, false, false)."'"; ?> + "ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template="+id)
            .show ()
            .cluetip ({
                arrows: true,
                attribute: 'href',
                cluetipClass: 'default'
            }).click (function () {
                return false;
            });
            
        $("#action_loading").show ();
    });
    
    $(".actions_container [name='action_select']").change(function () {
            if ($(this).val() != '0') {
                $('#advanced_action').show();
            }
            else {
                $('#advanced_action').hide();
            }
        }
    );
    
    $("#id_agent_module").change (function () {
        var $value = $(this).siblings ("span#latest_value").hide ();
        var $loading = $(this).siblings ("span#module_loading").show ();
        $("#value", $value).empty ();
        jQuery.post (<?php echo "'".ui_get_full_url(false, false, false, false)."'"; ?> + "ajax.php",
            {"page" : "operation/agentes/estado_agente",
            "get_agent_module_last_value" : 1,
            "id_agent_module" : this.value
            },
            function (data, status) {
                if (data === false) {
                    $("#value", $value).append ("<em><?php echo __('Unknown'); ?></em>");
                }
                else if (data == "") {
                    $("#value", $value).append ("<em><?php echo __('Empty'); ?></em>");
                }
                else {
                    $("#value", $value).append (data);
                }
                $loading.hide ();
                $value.show ();
            },
            "json"
        );
    });
});
/* ]]> */
</script>
