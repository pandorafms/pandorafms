<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Alert Management'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_users.php';

$pure = get_parameter('pure', 0);

$table = new stdClass();
$table->id = 'add_alert_table';
$table->class = 'databox filters filter-table-adv';
$table->width = '100%';
$table->head = [];
$table->data = [];
$table->size = [];
$table->style[0] = 'width: 50%';
$table->style[1] = 'width: 50%';

// Add an agent selector
if (! $id_agente) {
    $params = [];
    $params['return'] = true;
    $params['show_helptip'] = true;
    $params['input_name'] = 'id_agent';
    $params['selectbox_id'] = 'id_agent_module';
    $params['javascript_is_function_select'] = true;
    $params['metaconsole_enabled'] = false;
    $params['use_hidden_input_idagent'] = true;
    $params['print_hidden_input_idagent'] = true;
    $table->data[0][0] = html_print_label_input_block(
        __('Agent'),
        ui_print_agent_autocomplete_input($params)
    );
}

$modules = [];

if ($id_agente) {
    $modules = agents_get_modules($id_agente, false, ['delete_pending' => 0]);
}

$table->data[0][1] = html_print_label_input_block(
    __('Module'),
    html_print_select(
        $modules,
        'id_agent_module',
        0,
        true,
        __('Select'),
        0,
        true,
        false,
        true,
        'w100p',
        ($id_agente == 0),
        'width: 100%;'
    ).'<span id="latest_value" class="invisible">'.__('Latest value').': 
    <span id="value">&nbsp;</span></span>
    <span id="module_loading" class="invisible">'.html_print_image('images/spinner.gif', true).'</span>'
);

$groups_user = users_get_groups($config['id_user']);
if (!empty($groups_user)) {
    $groups = implode(',', array_keys($groups_user));

    if ($config['integria_enabled'] == 0) {
        $integria_command = 'Integria&#x20;IMS&#x20;Ticket';
        $sql = sprintf('SELECT taa.id, taa.name FROM talert_actions taa INNER JOIN talert_commands tac ON taa.id_alert_command = tac.id WHERE tac.name <> "%s" AND taa.id_group IN (%s)', $integria_command, $groups);
    } else {
        $sql = "SELECT id, name FROM talert_actions WHERE id_group IN ($groups)";
    }

    $actions = db_get_all_rows_sql($sql);
}

if ((bool) check_acl($config['id_user'], 0, 'LM') === true) {
    $create_action = html_print_button(
        __('Create Action'),
        '',
        false,
        'window.location.assign("index.php?sec=galertas&sec2=godmode/alerts/configure_alert_action&pure='.$pure.'")',
        [ 'mode' => 'link' ],
        true
    );
}

$table->data[1][0] = html_print_label_input_block(
    __('Actions'),
    html_print_select(
        index_array($actions, 'id', 'name'),
        'action_select',
        '',
        '',
        __('Default action'),
        '0',
        true,
        false,
        true,
        'w100p',
        false,
        'width: 100%;'
    ).'<span id="advanced_action" class="advanced_actions invisible"><br>'.__('Number of alerts match from').' '.html_print_input_text('fires_min', '', '', 4, 10, true).' '.__('to').' '.html_print_input_text('fires_max', '', '', 4, 10, true).'</span><div class="flex_justify_end">'.$create_action.'</div>'
);

$own_info = get_user_info($config['id_user']);
if ($own_info['is_admin']) {
    $templates = alerts_get_alert_templates(false, ['id', 'name']);
} else {
    $usr_groups = users_get_groups($config['id_user'], 'LW', true);
    $filter_groups = '';
    $filter_groups = implode(',', array_keys($usr_groups));
    $templates = alerts_get_alert_templates(['id_group IN ('.$filter_groups.')'], ['id', 'name']);
}

if ((bool) check_acl($config['id_user'], 0, 'LM') === true) {
    $create_template = html_print_button(
        __('Create Template'),
        '',
        false,
        'window.location.assign("index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&pure='.$pure.'")',
        [ 'mode' => 'link' ],
        true
    );
}

$table->data[1][1] = html_print_label_input_block(
    __('Template'),
    html_print_select(
        index_array($templates, 'id', 'name'),
        'template',
        '',
        '',
        __('Select'),
        0,
        true,
        false,
        true,
        'w100p',
        false,
        'width: 100%;'
    ).' <a class="template_details invisible" href="#">'.html_print_image('images/zoom.png', true, ['class' => 'img_help']).'</a><div class="flex_justify_end">'.$create_template.'</div>'
);

$table->data[2][0] = html_print_label_input_block(
    __('Threshold').ui_print_help_tip(__('It takes precedence over the action\'s threshold configuration.'), true),
    html_print_extended_select_for_time(
        'module_action_threshold',
        '0',
        '',
        '',
        '',
        false,
        true,
        false,
        true,
        'w100p',
        false,
        false,
        '',
        false,
        true
    )
);

if (isset($step) === false) {
    echo '<form id="form_alerts" class="add_alert_form max_floating_element_size" method="post">';
    html_print_table($table);
}

if (isset($step) === false) {
    $output = '';

    if ($id_cluster) {
        $actionButtons .= html_print_button(
            __('Finish and view cluster'),
            'store',
            false,
            'window.location.replace(\"index.php?sec=reporting&sec2=enterprise/godmode/reporting/cluster_view&id=".$id_cluster."\");',
            [
                'icon' => 'update',
                'mode' => 'secondary',
            ],
            true
        );
    }

    $actionButtons .= html_print_submit_button(
        __('Add alert'),
        'add',
        false,
        [ 'icon' => 'wand' ],
        true
    );

    if ($_GET['sec2'] === 'operation/cluster/cluster') {
        html_print_div(
            [
                'content' => html_print_submit_button(
                    __('Add alert'),
                    'add',
                    false,
                    [
                        'icon' => 'wand',
                        'form' => 'form_alerts',
                        'mode' => 'secondary',
                    ],
                    true
                ),
                'style'   => 'display:none',
                'id'      => 'add_alert_div',
            ]
        );
    }

    html_print_action_buttons($actionButtons, ['right_content' => $pagination]);

    html_print_input_hidden('create_alert', 1);
    echo '</form>';
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
