<?php
/**
 * Interfaces view.
 *
 * @category   Monitoring
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2021 Artica Soluciones Tecnologicas
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
global $config;

check_login();

if (check_acl($config['id_user'], 0, 'AR') === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent Management'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'interface_view.functions.php';
require_once $config['homedir'].'/include/functions_agents.php';

$recursion = get_parameter_switch('recursion', false);
if ($recursion === false) {
    $recursion = get_parameter('recursion', false);
}

$selected_agents = get_parameter('selected_agents', []);
$selected_interfaces = get_parameter('selected_interfaces', []);
$refr = (int) get_parameter('refr', 0);
$offset = (int) get_parameter('offset', 0);
$sort_field = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$autosearch = false;
$sec = (string) get_parameter('sec', 'view');
$agent_id = (int) get_parameter('id_agente', 0);

if ($sec === 'view') {
    ui_print_standard_header(
        __('Interface view').$subpage,
        '',
        false,
        '',
        true,
        [],
        [
            [
                'link'  => '',
                'label' => __('Monitoring'),
            ],
            [
                'link'  => '',
                'label' => __('Views'),
            ],
        ]
    );
}

$agent_filter = ['id_agente' => $agent_id];

// Autosearch if search parameters are different from those by default.
if (empty($selected_agents) === false || empty($selected_interfaces) === false
    || $sort_field !== '' || $sort !== 'none' || $sec === 'estado'
) {
    $autosearch = true;
}

print_filters($sec);

$result = false;

if ($autosearch === true) {
    if ($sec === 'estado') {
        $result = agents_get_network_interfaces(false, $agent_filter);
    } else {
        $result = agents_get_network_interfaces($selected_agents);
    }

    if ($result === false || empty($result) === true) {
        $result = [];
    } else {
        $pagination = ui_pagination(
            count($selected_interfaces),
            false,
            $offset,
            0,
            true,
            'offset',
            false
        );

        html_print_action_buttons(
            '',
            [ 'right_content' => $pagination ]
        );
    }
}

print_table(
    $result,
    $selected_agents,
    $selected_interfaces,
    $sort_field,
    $sort,
    $offset,
    $sec
);

?>
<script type="text/javascript">

$(document).ready(function() {
    var group_id = $("#group_id").val();
    load_agents_selector(group_id);

    var sec = "<?php echo $sec; ?>";
    var agent_id = "<?php echo $agent_id; ?>";

    if (sec === 'estado' && agent_id > 0) {
        load_agent_interfaces_selector([agent_id]);
    }
    $("#selected_agents").filterByText($("#text-filter_agents"));
});


$('#moduletype').click(function() {
    jQuery.get (
        "ajax.php",
        {
            "page": "general/subselect_data_module",
            "module":$('#moduletype').val()
        },
        function (data, status) {
            $("#datatypetittle").show ();
            $("#datatypebox").hide ()
            .empty ()
            .append (data)
            .show ();
        },
        "html"
    );

    return false;
});


function toggle_full_value(id) {
    text = $('#hidden_value_module_' + id).html();
    old_text = $("#value_module_text_" + id).html();
    
    $("#hidden_value_module_" + id).html(old_text);
    
    $("#value_module_text_" + id).html(text);
}

function load_agents_selector(group) {
    
    jQuery.post ("ajax.php",
        {
            "page" : "operation/agentes/ver_agente",
            "get_agents_group_json" : 1,
            "get_agents_also_interfaces" : 1,
            "id_group" : group,
            "privilege" : "AW",
            "keys_prefix" : "_",
            "recursion" : $('#checkbox-recursion').is(':checked')
        },
        function (data, status) {
            $("#selected_agents").html('');
            jQuery.each (data, function (id, value) {
                id = id.substring(1);
                
                option = $("<option></option>")
                    .attr ("value", value["id_agente"])
                    .html (value["alias"]);
                $("#id_agents").append (option);
                $("#selected_agents").append (option);
            });

            var selected_agents = "<?php echo implode(',', $selected_agents); ?>";

            $.each(selected_agents.split(","), function(i,e) {
                $("#selected_agents option[value='" + e + "']").prop(
                    "selected",
                    true
                );
            });

            load_agent_interfaces_selector($("#selected_agents").val());
        },
        "json"
    );
}

$("#group_id").change(function() {
    load_agents_selector(this.value);
});

$("#checkbox-recursion").change (function () {
    jQuery.post ("ajax.php",
        {"page" : "operation/agentes/ver_agente",
            "get_agents_group_json" : 1,
            "get_agents_also_interfaces" : 1,
            "id_group" :     $("#group_id").val(),
            "privilege" : "AW",
            "keys_prefix" : "_",
            "recursion" : $('#checkbox-recursion').is(':checked')
        },
        function (data, status) {
            $("#selected_agents").html('');
            $("#module").html('');
            jQuery.each (data, function (id, value) {
                id = id.substring(1);
                option = $("<option></option>")
                    .attr ("value", value["id_agente"])
                    .html (value["alias"]);
                $("#id_agents").append (option);
                $("#selected_agents").append (option);
            });
        },
        "json"
    );
});

$("#selected_agents").click (function() {
    var selected_agents = $(this).val();

    load_agent_interfaces_selector(selected_agents);
});

function load_agent_interfaces_selector(selected_agents) {
    $("#selected_interfaces").empty();
    jQuery.post ("ajax.php",
        {
            "page" : "include/ajax/agent",
            "get_agents_interfaces" : 1,
            "id_agents" : selected_agents
        },
        function (data, status) {
            $("#module").html('');

            if (data) {
                Object.values(data).forEach(function(obj) {
                    for (const [key, value] of Object.entries(obj.interfaces)) {
                        option = $("<option></option>")
                        .attr ("value", value.status_module_id)
                        .html (key + ' (' + obj.agent_alias + ')');
                    $("#selected_interfaces").append(option);
                    }
                });
            }

            var selected_interfaces =
                "<?php echo implode(',', $selected_interfaces); ?>";

            $.each(selected_interfaces.split(","), function(i,e) {
                $("#selected_interfaces option[value='" + e + "']").prop(
                    "selected",
                    true
                );
            });

        },
        "json"
    );
}

</script>
