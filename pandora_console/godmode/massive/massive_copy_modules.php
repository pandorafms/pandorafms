<?php
/**
 * View for copy modules in Massive Operations
 *
 * @category   Configuration
 * @package    Pandora FMS
 * @subpackage Massive Operations
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.
check_login();

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent Config Management Admin section'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_agents.php';
require_once 'include/functions_alerts.php';
require_once 'include/functions_modules.php';
require_once 'include/functions_users.php';

$source_id_group = (int) get_parameter('source_id_group');
$source_id_agent = (int) get_parameter('source_id_agent');
$destiny_id_group = (int) get_parameter('destiny_id_group');
$destiny_id_agents = (array) get_parameter('destiny_id_agent', []);
$source_recursion = get_parameter('source_recursion');
$destiny_recursion = get_parameter('destiny_recursion');

$do_operation = (bool) get_parameter('do_operation');



if ($do_operation) {
    $result = agents_process_manage_config(
        $source_id_agent,
        $destiny_id_agents
    );

    $info = [
        'Source agent'    => $source_id_agent,
        'Destinity agent' => implode(',', $destiny_id_agents),
    ];
    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Copy modules',
            false,
            false,
            json_encode($info)
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Fail to try copy modules',
            false,
            false,
            json_encode($info)
        );
    }
}

$groups = users_get_groups();

$table = new stdClass();
$table->class = 'databox filters';
$table->width = '100%';
$table->data = [];
$table->style = [];
$table->style[0] = 'font-weight: bold;';
$table->style[2] = 'font-weight: bold';
$table->style[4] = 'font-weight: bold';
$table->style[6] = 'font-weight: bold';

// Source selection
$table->id = 'source_table';
$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(
    false,
    'AW',
    true,
    'source_id_group',
    $source_id_group,
    false,
    '',
    '',
    true
);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox('source_recursion', 1, $source_recursion, true, false);
$status_list = [];
$status_list[AGENT_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_STATUS_WARNING] = __('Warning');
$status_list[AGENT_STATUS_CRITICAL] = __('Critical');
$status_list[AGENT_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_STATUS_NOT_INIT] = __('Not init');
$table->data[0][4] = __('Status');
$table->data[0][5] = html_print_select(
    $status_list,
    'status_agents_source',
    'selected',
    '',
    __('All'),
    AGENT_STATUS_ALL,
    true
);
$table->data[0][6] = __('Agent');
$table->data[0][6] .= ' <span id="source_agent_loading" class="invisible">';
$table->data[0][6] .= html_print_image('images/spinner.png', true);
$table->data[0][6] .= '</span>';
// $table->data[0][7] = html_print_select (agents_get_group_agents ($source_id_group, false, "none"),
// 'source_id_agent', $source_id_agent, false, __('Select'), 0, true);
$agents = ( $source_id_group ? agents_get_group_agents($source_id_group, false, 'none') : agents_get_group_agents(array_keys(users_get_groups($config['id_user'], 'AW', false))) );
$table->data[0][7] = html_print_select($agents, 'source_id_agent', $source_id_agent, false, __('Select'), 0, true);

echo '<form '.'action="index.php?'.'sec=gmassive&'.'sec2=godmode/massive/massive_operations&'.'option=copy_modules" '.'id="manage_config_form" '.'method="post">';

echo '<fieldset id="fieldset_source">';
echo '<legend>';
echo '<span>'.__('Source');
echo '</legend>';
html_print_table($table);
echo '</fieldset>';

// Target selection
$table->id = 'target_table';
$table->class = 'databox filters';
$table->data = [];

$modules = [];
if ($source_id_agent) {
    $modules = agents_get_modules($source_id_agent, 'nombre');
}

$agent_alerts = [];
if ($source_id_agent) {
    $agent_alerts = agents_get_alerts_simple($source_id_agent);
}

$alerts = [];
foreach ($agent_alerts as $alert) {
    $name = alerts_get_alert_template_name($alert['id_alert_template']);
    $name .= ' (<em>'.$modules[$alert['id_agent_module']].'</em>)';
    $alerts[$alert['id']] = $name;
}

$tags = tags_get_user_tags();
$table->data['tags'][0] = __('Tags');
$table->data['tags'][1] = html_print_select(
    $tags,
    'tags[]',
    $tags_name,
    false,
    __('Any'),
    -1,
    true,
    true,
    true
);

$table->data['operations'][0] = __('Operations');
$table->data['operations'][1] = '<span class="with_modules'.(empty($modules) ? ' invisible' : '').'">';
$table->data['operations'][1] .= html_print_checkbox('copy_modules', 1, true, true);
$table->data['operations'][1] .= html_print_label(__('Copy modules'), 'checkbox-copy_modules', true);
$table->data['operations'][1] .= '</span><br />';

$table->data['operations'][1] .= '<span class="with_alerts'.(empty($alerts) ? ' invisible' : '').'">';
$table->data['operations'][1] .= html_print_checkbox('copy_alerts', 1, true, true);
$table->data['operations'][1] .= html_print_label(__('Copy alerts'), 'checkbox-copy_alerts', true);
$table->data['operations'][1] .= '</span>';

$table->data['form_modules_filter'][0] = __('Filter Modules');
$table->data['form_modules_filter'][1] = html_print_input_text('filter_modules', '', '', 20, 255, true);

$table->data[1][0] = __('Modules');
$table->data[1][1] = '<span class="with_modules'.(empty($modules) ? ' invisible' : '').'">';
$table->data[1][1] .= html_print_select(
    $modules,
    'target_modules[]',
    0,
    false,
    '',
    '',
    true,
    true
);
$table->data[1][1] .= '</span>';
$table->data[1][1] .= '<span class="without_modules'.(! empty($modules) ? ' invisible' : '').'">';
$table->data[1][1] .= '<em>'.__('No modules for this agent').'</em>';
$table->data[1][1] .= '</span>';

$table->data[2][0] = __('Alerts');
$table->data[2][1] = '<span class="with_alerts'.(empty($alerts) ? ' invisible' : '').'">';
$table->data[2][1] .= html_print_select(
    $alerts,
    'target_alerts[]',
    0,
    false,
    '',
    '',
    true,
    true
);
$table->data[2][1] .= '</span>';
$table->data[2][1] .= '<span class="without_alerts'.(! empty($modules) ? ' invisible' : '').'">';
$table->data[2][1] .= '<em>'.__('No alerts for this agent').'</em>';
$table->data[2][1] .= '</span>';

echo '<div id="modules_loading" class="loading invisible">';
html_print_image('images/spinner.png');
echo __('Loading').'&hellip;';
echo '</div>';

echo '<fieldset id="fieldset_targets"'.($source_id_agent ? '' : ' class="invisible"').'>';
echo '<legend><span>'.__('Targets').'</span></legend>';
html_print_table($table);
echo '</fieldset>';


// Destiny selection
$table->id = 'destiny_table';
$table->class = 'databox filters';
$table->data = [];
$table->size[0] = '20%';
$table->size[1] = '30%';
$table->size[2] = '20%';
$table->size[3] = '30%';

$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups(
    false,
    'AW',
    true,
    'destiny_id_group',
    $destiny_id_group,
    false,
    '',
    '',
    true
);
$table->data[0][2] = __('Group recursion');
$table->data[0][3] = html_print_checkbox(
    'destiny_recursion',
    1,
    $destiny_recursion,
    true,
    false
);

$status_list = [];
$status_list[AGENT_STATUS_NORMAL] = __('Normal');
$status_list[AGENT_STATUS_WARNING] = __('Warning');
$status_list[AGENT_STATUS_CRITICAL] = __('Critical');
$status_list[AGENT_STATUS_UNKNOWN] = __('Unknown');
$status_list[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$status_list[AGENT_STATUS_NOT_INIT] = __('Not init');
$table->data[1][0] = __('Status');
$table->data[1][1] = html_print_select(
    $status_list,
    'status_agents_destiny',
    'selected',
    '',
    __('All'),
    AGENT_STATUS_ALL,
    true
);

$table->data['form_agents_filter'][0] = __('Filter Agents');
$table->data['form_agents_filter'][1] = html_print_input_text('filter_agents', '', '', 20, 255, true);

$table->data[2][0] = __('Agent');
$table->data[2][0] .= '<span id="destiny_agent_loading" class="invisible">';
$table->data[2][0] .= html_print_image('images/spinner.png', true);
$table->data[2][0] .= '</span>';

$agents = [];
if ($source_id_agent) {
    $agents = ( $destiny_id_group ? agents_get_group_agents($destiny_id_group, false, 'none') : agents_get_group_agents(array_keys(users_get_groups($config['id_user'], 'AW', false))) );
    unset($agents[$source_id_agent]);
}

$table->data[2][1] = html_print_select($agents, 'destiny_id_agent[]', 0, false, '', '', true, true);

echo '<fieldset id="fieldset_destiny"'.($source_id_agent ? '' : ' class="invisible"').'>';
echo '<legend><span>'.__('To agent(s)').'</span></legend>';
html_print_table($table);
echo '</fieldset>';

attachActionButton('do_operation', 'copy', $table->width, false, $SelectAction);

echo '</form>';

echo '<h3 class="error invisible" id="message">&nbsp;</h3>';
// Load JS files.
ui_require_javascript_file('pandora_modules');
ui_require_jquery_file('form');
ui_require_jquery_file('pandora.controls');
?>

<script type="text/javascript">
/* <![CDATA[ */
var module_alerts;
var limit_parameters_massive = <?php echo $config['limit_parameters_massive']; ?>;


$(document).ready (function () {
    var source_recursion;
    $("#checkbox-source_recursion").click(function () {
        source_recursion = this.checked ? 1 : 0;
        $("#source_id_group").trigger("change");
    });
    
    $("#source_id_group").pandoraSelectGroupAgent ({
        agentSelect: "select#source_id_agent",
        privilege: "AW",
        recursion: function() {
            return source_recursion
        },
        status_agents: function () {
            return $("#status_agents_source").val();
        },
        loading: "#source_agent_loading"
    });
    
    $("#status_agents_source").change(function() {
        $("#source_id_group").trigger("change");
    });
    
    var destiny_recursion;
    $("#checkbox-destiny_recursion").click(function () {
        destiny_recursion = this.checked ? 1 : 0;
        $("#destiny_id_group").trigger("change");
    });
    
    $("#destiny_id_group").pandoraSelectGroupAgent ({
        agentSelect: "select#destiny_id_agent",
        privilege: "AW",
        recursion: function() {return destiny_recursion},
        status_agents: function () {
            return $("#status_agents_destiny").val();
        },
        loading: "#destiny_agent_loading",
        callbackPost: function (id, value, option) {
            /* Hide source agent */
            var selected_agent = $("#source_id_agent").val();
            $("#destiny_id_agent option[value='" + selected_agent + "']").remove();
        },
        callbackAfter:function() {
            //Filter agents. Call the function when the select is fully loaded.
            var textNoData = "<?php echo __('None'); ?>";
            filterByText($('#destiny_id_agent'), $("#text-filter_agents"), textNoData);
        }
    });
    
    $("#tags").change(function() {
        $("#source_id_agent").trigger("change");
    });

    $("#status_agents_destiny").change(function() {
        $("#destiny_id_group").trigger("change");
    });
    
    $("#source_id_agent").change (function () {
        var id_agent = $("#source_id_agent").val();
        if (id_agent == 0) {
            $("#submit-go").attr("disabled", "disabled");
            
            $("span.without_modules, span.without_alerts").hide();
            $("span.without_modules").hide();
            $("span.with_modules").hide();
            $("span.without_alerts").hide();
            $("span.with_alerts").hide();
            $("#fieldset_destiny, #target_table-operations").hide();
            $("#fieldset_targets").hide();
            
            return;
        }
        
        var params = {
            "page" : "operation/agentes/ver_agente",
            "get_agent_modules_json" : 1,
            "get_id_and_name" : 1,
            "disabled" : 0,
            "id_agent" : id_agent,
            "safe_name": 1,
        };

        var tags_to_search = $('#tags').val();
        if (tags_to_search != null) {
            if (tags_to_search[0] != -1) {
                params['tags'] = tags_to_search;
            }
        }

        $("#submit-go").attr("disabled", false);
        
        $("#modules_loading").show ();
        $("#target_modules option, #target_alerts option").remove ();
        $("#target_modules, #target_alerts").disable ();
        $("#destiny_id_agent option").show ();
        $("#destiny_id_agent option[value="+id_agent+"]").hide ();
        var no_modules;
        var no_alerts;
        /* Get modules */
        jQuery.post ("ajax.php",
            params,
            function (data, status) {
                if (data.length == 0) {
                    no_modules = true;
                }
                else {
                    jQuery.each (data, function (i, val) {
                        option = $("<option></option>")
                            .attr ("value", val["id_agente_modulo"])
                            .append (val["safe_name"]);
                        $("#target_modules").append (option);
                    });
                    
                    no_modules = false;
                }
                
                /* Get alerts */
                jQuery.post ("ajax.php",
                    {"page" : "include/ajax/alert_list.ajax",
                    "get_agent_alerts_simple" : 1,
                    "id_agent" : id_agent
                    },
                    function (data, status) {
                        module_alerts = Array ();
                        if (! data) {
                            no_alerts = true;
                        }
                        else {
                            jQuery.each (data, function (i, val) {
                                module_name = $("<em></em>").append (val["module_name"]);
                                option = $("<option></option>")
                                    .attr ("value", val["id"])
                                    .append (val["template"]["name"])
                                    .append (" (")
                                    .append (module_name)
                                    .append (")");
                                $("#target_alerts").append (option);
                                module_alerts[val["id"]] = val["id_agent_module"];
                            });
                            no_alerts = false;
                        }
                        $("#modules_loading").hide ();
                        
                        if (no_modules && no_alerts) {
                            /* Nothing to export from selected agent */
                            $("#fieldset_destiny").hide ();
                            
                            $("span.without_modules, span.without_alerts").show ();
                            $("span.with_modules, span.with_alerts, #target_table-operations, #target_table-form_modules_filter").hide ();
                        }
                        else {
                            if (no_modules) {
                                $("span.without_modules").show ();
                                $("span.with_modules").hide ();
                                $("#checkbox-copy_modules").uncheck ();
                                $("#target_table-form_modules_filter").hide ();
                            }
                            else {
                                $("span.without_modules").hide ();
                                $("span.with_modules").show ();
                                $("#checkbox-copy_modules").check ();
                            }
                            
                            if (no_alerts) {
                                $("span.without_alerts").show ();
                                $("span.with_alerts").hide ();
                                $("#checkbox-copy_alerts").uncheck ();
                            }
                            else {
                                $("span.without_alerts").hide ();
                                $("span.with_alerts").show ();
                                $("#checkbox-copy_alerts").check ();
                            }
                            $("#fieldset_destiny, #target_table-operations, #target_table-form_modules_filter").show ();
                        }
                        $("#fieldset_targets").show ();
                        $("#target_modules, #target_alerts").enable ();
                        //Filter modules. Call the function when the select is fully loaded.
                        var textNoData = "<?php echo __('None'); ?>";
                        filterByText($('#target_modules'), $("#text-filter_modules"), textNoData);
                    },
                    "json"
                );
                // Refresh selectable agents to delete the selected one
                $("#destiny_id_group").trigger("change");
            },
            "json"
        );
    });
    
    $("#target_alerts").change (function () {
        jQuery.each ($(this).fieldValue (), function () {
            if (module_alerts[this] != undefined)
                $("#target_modules option[value="+module_alerts[this]+"]")
                    .prop("selected", true);
        });
    });
    
    $("#manage_config_form").submit (function () {
        /* var get_parameters_count = window.location.href.slice(
            window.location.href.indexOf('?') + 1).split('&').length;
        var post_parameters_count = $("#manage_config_form").serializeArray().length;
        
        var count_parameters =
            get_parameters_count + post_parameters_count;
        
        if (count_parameters > limit_parameters_massive) {
            alert("
            <?php
            // echo __('Unsucessful sending the data, please contact with your administrator or make with less elements.');
            ?>
            ");
            return false;
        } */
        
        
        
        
        $("h3:not([id=message])").remove ();
        
        if ($("#source_id_agent").attr ("value") == 0) {
            $("#message").showMessage ("<?php echo __('No source agent to copy'); ?>");
            return false;
        }
        
        copy_modules = $("#checkbox-copy_modules");
        copy_alerts = $("#checkbox-copy_alerts");
        
        if (! $(copy_modules).attr ("checked") && ! $(copy_alerts).attr ("checked")) {
            $("#message").showMessage ("<?php echo __('No operation selected'); ?>");
            return false;
        }
        
        if ($(copy_modules).attr ("checked") && $("#target_modules").fieldValue ().length == 0) {
            $("#message").showMessage ("<?php echo __('No modules have been selected'); ?>");
            return false;
        }
        
        if ($("#destiny_id_agent").fieldValue ().length == 0) {
            $("#message").showMessage ("<?php echo __('No destiny agent(s) to copy'); ?>");
            return false;
        }
        
        $("#message").hide ();
        
        return true;
    });
});
/* ]]> */
</script>
