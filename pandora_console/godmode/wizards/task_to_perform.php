<?php
/**
 * Extension to schedule tasks on Pandora FMS Console
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Host&Devices
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 PandoraFMS S.L.
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
$create_modules_dialog = get_parameter('create_modules_dialog', 0);
$create_connectivity_dialog = get_parameter('create_connectivity_dialog', 0);
$create_net_scan_dialog = get_parameter('create_net_scan_dialog', 0);
$create_alert_mail_dialog = get_parameter('create_alert_mail_dialog', 0);
$check_web = get_parameter('check_web', 0);
$check_connectivity = get_parameter('check_connectivity', 0);
$create_net_scan = get_parameter('create_net_scan', 0);
$create_mail_alert = get_parameter('create_mail_alert', 0);
?>
<div id="dialog_goliat" class="invisible">
    <form method="post" action="index.php?sec=wizard&sec2=godmode/wizards/task_to_perform">
    <?php
    echo html_print_input_hidden('check_web', 1);
    echo html_print_label_input_block(
        __('URL'),
        html_print_input_text(
            'url_goliat',
            '',
            '',
            false,
            255,
            true,
            false,
            true,
            '',
            'w100p'
        )
    );
    echo html_print_label_input_block(
        __('Text to search'),
        html_print_input_text(
            'text_to_search',
            '',
            '',
            false,
            255,
            true,
            false,
            false,
            '',
            'w100p'
        )
    );
    echo html_print_label_input_block(
        __('Modules name'),
        html_print_input_text(
            'module_name',
            '',
            '',
            false,
            255,
            true,
            false,
            false,
            '',
            'w100p'
        )
    );
    echo html_print_label_input_block(
        __('Module group'),
        html_print_select_from_sql(
            'SELECT * FROM tgrupo ORDER BY nombre',
            'id_group',
            '',
            '',
            '',
            false,
            true,
            false,
            true,
            false,
            'width: 100%;'
        )
    );

    echo html_print_submit_button(__('Create'), '', false, ['icon' => 'next', 'style' => 'margin-top:15px; float:right;']);
    ?>
    </form>
</div>
<div id="dialog_connectivity" class="invisible">
    <form method="post" action="index.php?sec=wizard&sec2=godmode/wizards/task_to_perform">
    <?php
    echo html_print_input_hidden('check_connectivity', 1);
    echo html_print_label_input_block(
        __('Ip target'),
        html_print_input_text(
            'ip_target',
            '',
            '',
            false,
            15,
            true,
            false,
            true,
            '',
            'w100p'
        )
    );
    echo html_print_label_input_block(
        __('Agent name'),
        html_print_input_text(
            'agent_name',
            '',
            '',
            false,
            255,
            true,
            false,
            false,
            '',
            'w100p'
        )
    );
    echo html_print_label_input_block(
        __('Module group'),
        html_print_select_from_sql(
            'SELECT * FROM tgrupo ORDER BY nombre',
            'id_group',
            '',
            '',
            '',
            false,
            true,
            false,
            true,
            false,
            'width: 100%;'
        )
    );

    echo html_print_submit_button(__('Create'), '', false, ['icon' => 'next', 'style' => 'margin-top:15px; float:right;']);
    ?>
    </form>
</div>
<div id="dialog_basic_net" class="invisible">
    <form method="post" action="index.php?sec=wizard&sec2=godmode/wizards/task_to_perform">
    <?php
    echo html_print_input_hidden('create_net_scan', 1);
    echo html_print_label_input_block(
        __('Ip target'),
        html_print_input_text(
            'ip_target',
            '192.168.10.0/24',
            '192.168.10.0/24',
            false,
            18,
            true,
            false,
            true,
            '',
            'w100p',
            '',
            'off',
            false,
            '',
            '',
            '',
            false,
            '',
            '192.168.10.0/24'
        )
    );

    echo html_print_submit_button(__('Create'), '', false, ['icon' => 'next', 'style' => 'margin-top:15px; float:right;']);
    ?>
    </form>
</div>
<div id="dialog_alert_mail" class="invisible">
    <form method="post" action="index.php?sec=wizard&sec2=godmode/wizards/task_to_perform">
    <?php
    echo html_print_input_hidden('create_mail_alert', 1);
    $params = [];
    $params['return'] = true;
    $params['show_helptip'] = true;
    $params['input_name'] = 'id_agent';
    $params['selectbox_id'] = 'id_agent_module';
    $params['javascript_is_function_select'] = true;
    $params['metaconsole_enabled'] = false;
    $params['use_hidden_input_idagent'] = true;
    $params['print_hidden_input_idagent'] = true;
    echo html_print_label_input_block(
        __('Agent'),
        ui_print_agent_autocomplete_input($params)
    );
    echo html_print_label_input_block(
        __('Module'),
        html_print_select(
            $modules,
            'id_agent_module',
            '',
            true,
            '',
            '',
            true,
            false,
            true,
            'w100p',
            false,
            'width: 100%;',
            false,
            false,
            false,
            '',
            false,
            false,
            true
        ).'<span id="latest_value" class="invisible">'.__('Latest value').':
        <span id="value">&nbsp;</span></span>
        <span id="module_loading" class="invisible">'.html_print_image('images/spinner.gif', true).'</span>'
    );

    $condition = alerts_get_alert_templates(['id IN (1,3)'], ['id', 'name']);

    echo html_print_label_input_block(
        __('Contition'),
        html_print_select(
            index_array($condition, 'id', 'name'),
            'id_condition',
            '',
            '',
            __('Select'),
            '',
            true,
            false,
            true,
            'w100p',
            false,
            'width: 100%;',
            false,
            false,
            false,
            '',
            false,
            false,
            true
        )
    );

    echo html_print_submit_button(__('Create'), '', false, ['icon' => 'next', 'style' => 'margin-top:15px; float:right;']);
    ?>
    </form>
</div>
<?php
// Begin.
global $config;

// Header.
ui_print_standard_header(
    __('Task to perform'),
    'images/op_snmp.png',
    false,
    false,
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Configuration'),
        ],
        [
            'link'  => '',
            'label' => __('Configuration wizard'),
        ],
    ]
);

$status_webserver = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_WEB], 'status')['status'];
$status_check_web = false;
if ($status_webserver === '1') {
    $status_check_web = true;
}

$status_newtwork = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_NETWORK], 'status')['status'];
$status_pluggin = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_PLUGIN], 'status')['status'];
$status_check_connectivity = false;
if ($status_newtwork === '1' && $status_pluggin === '1') {
    $status_check_connectivity = true;
}

ui_require_css_file('mini_diagnosis');
$table = new stdClass();
$table->width = '60%';
$table->class = 'filter-table-adv databox';
$table->size = [];
$table->data = [];
$table->size[0] = '30%';
$table->size[1] = '30%';
$table->data[0][0] = html_print_wizard_diagnosis(__('Wizard install agent'), 'wizard_install', __('Wizard install agent'), false, true);
$table->data[0][1] = html_print_wizard_diagnosis(__('Create check web'), 'configure_email', __('Create check web'), $status_check_web, true);
$table->data[1][0] = html_print_wizard_diagnosis(__('Create basic connectivity'), 'basic_connectivity', __('Create basic connectivity'), $status_check_connectivity, true);
$table->data[1][1] = html_print_wizard_diagnosis(__('Create basic net'), 'basic_net', __('Create basic net'), true, true);
$table->data[2][0] = html_print_wizard_diagnosis(__('Create Alert Mail'), 'alert_mail', __('Create Alert Mail'), true, true);
html_print_table($table);
?>

<script type="text/javascript">
    document.getElementById("wizard_install").setAttribute(
        'onclick',
        'deployAgent()'
    );

    document.getElementById("configure_email").setAttribute(
        'onclick',
        'openCreateModulesDialog()'
    );

    document.getElementById("basic_connectivity").setAttribute(
        'onclick',
        'openCreateConnectivityDialog()'
    );

    document.getElementById("basic_net").setAttribute(
        'onclick',
        'openCreateBasicNetDialog()'
    );

    document.getElementById("alert_mail").setAttribute(
        'onclick',
        'openCreateAlertMailDialog()'
    );

    function deployAgent() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&show_deploy_agent=1'); ?>';
    }

    function openCreateModulesDialog() {
        $('#dialog_goliat').dialog({
            title: '<?php echo __('Create goliat'); ?>',
            resizable: true,
            draggable: true,
            modal: true,
            close: false,
            height: 375,
            width: 480,
            overlay: {
                opacity: 0.5,
                background: "black"
            }
        })
        .show();
    }

    function openCreateConnectivityDialog() {
        $('#dialog_connectivity').dialog({
            title: '<?php echo __('Create basic connectivity'); ?>',
            resizable: true,
            draggable: true,
            modal: true,
            close: false,
            height: 350,
            width: 480,
            overlay: {
                opacity: 0.5,
                background: "black"
            }
        })
        .show();
    }

    function openCreateBasicNetDialog() {
        $('#dialog_basic_net').dialog({
            title: '<?php echo __('Create net scan'); ?>',
            resizable: true,
            draggable: true,
            modal: true,
            close: false,
            height: 200,
            width: 480,
            overlay: {
                opacity: 0.5,
                background: "black"
            }
        })
        .show();
    }

    function openCreateAlertMailDialog() {
        $('#dialog_alert_mail').dialog({
            title: '<?php echo __('Create alert mail'); ?>',
            resizable: true,
            draggable: true,
            modal: true,
            close: false,
            height: 350,
            width: 480,
            overlay: {
                opacity: 0.5,
                background: "black"
            }
        })
        .show();
    }

    $('#text-url_goliat').change(function(){
        if (is_valid_url($(this).val()) !== true){
            alert(" <?php echo __('The URL is not valid.'); ?> ");
            $(this).val("");
        }
    });

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

    function is_valid_url(url) {
        return /^(http(s)?:\/\/)?(www\.)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/.test(url);
    }
</script>

<?php
if ($create_modules_dialog) {
    ?>
    <script type="text/javascript">

        $('#dialog_goliat').dialog({
            title: '<?php echo __('Create goliat'); ?>',
            resizable: true,
            draggable: true,
            modal: true,
            close: false,
            height: 375,
            width: 480,
            overlay: {
                opacity: 0.5,
                background: "black"
            }
        })
        .show();
    </script>
    <?php
}

if ($check_web) {
    include_once 'include/functions_api.php';
    include_once 'include/functions_servers.php';

    $status_webserver = db_get_row_filter('tserver', ['server_type' => 9], 'status')['status'];
    if ($status_webserver === '1') {
        $name = array_keys(servers_get_names())[0];
        $id_group = get_parameter('id_group', 4);

        $array_other['data'] = [
            'Goliat',
            '',
            2,
            $id_group,
            0,
            30,
            30,
            9,
            $name,
            0,
            0,
            0,
            __('Agent goliat created on welcome'),
        ];

        $id_agent = api_set_new_agent(0, '', $array_other, '', true);
        if ($id_agent > 0) {
            $module_name = get_parameter('module_name', 'goliat_module');
            $text_to_search = get_parameter('text_to_search', '');
            $url_goliat = get_parameter('url_goliat', 'https://pandorafms.com/en/');
            $module_latency = create_module_latency_goliat($id_agent, $module_name, $id_group, $url_goliat, $text_to_search);
            $module_status = create_module_status_goliat($id_agent, $module_name, $id_group, $url_goliat, $text_to_search);
            if ($module_latency > 0 && $module_status > 0) {
                ui_print_success_message(__('Your check has been created, <a href='.ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agent).'>click here to view the data</a>. Please note that it may take a few seconds to see data if your server is busy'));
            }
        } else {
            ui_print_error_message(__('The Name is not valid for the modules.'));
        }
    } else {
        ui_print_error_message(__('Web server is not enabled.'));
    }
}


/**
 * Create_module_latency_goliat and return module id.
 *
 * @param mixed $id_agent      Id agent.
 * @param mixed $module_name   Module name.
 * @param mixed $id_group      Id group.
 * @param mixed $url_search    Url to search.
 * @param mixed $string_search Text to search.
 *
 * @return interger Module id.
 */
function create_module_latency_goliat($id_agent, $module_name, $id_group, $url_search, $string_search='')
{
    if ($string_search !== '') {
        $str_search = 'check_string '.$string_search.'';
    }

    include_once 'include/functions_modules.php';

    $array_values = [
        'id_tipo_modulo'        => '30',
        'descripcion'           => '',
        'max'                   => '0',
        'min'                   => '0',
        'snmp_oid'              => '',
        'snmp_community'        => 'public',
        'id_module_group'       => $id_group,
        'module_interval'       => '300',
        'module_ff_interval'    => '0',
        'ip_target'             => '',
        'tcp_port'              => '0',
        'tcp_rcv'               => '',
        'tcp_send'              => '',
        'id_export'             => '0',
        'plugin_user'           => '',
        'plugin_pass'           => '0',
        'plugin_parameter'      => 'task_begin
get '.$url_search.'
resource 1
'.$str_search.'
task_end',
        'id_plugin'             => '0',
        'post_process'          => '0',
        'prediction_module'     => '0',
        'max_timeout'           => '0',
        'max_retries'           => '0',
        'disabled'              => '',
        'id_modulo'             => '7',
        'custom_id'             => '',
        'history_data'          => '1',
        'dynamic_interval'      => '0',
        'dynamic_max'           => '0',
        'dynamic_min'           => '0',
        'dynamic_two_tailed'    => '0',
        'parent_module_id'      => '0',
        'min_warning'           => '0',
        'max_warning'           => '0',
        'str_warning'           => '',
        'min_critical'          => '0',
        'max_critical'          => '0',
        'str_critical'          => '',
        'custom_string_1'       => '',
        'custom_string_2'       => '',
        'custom_string_3'       => '',
        'custom_integer_1'      => '0',
        'custom_integer_2'      => '0',
        'min_ff_event'          => '0',
        'min_ff_event_normal'   => '0',
        'min_ff_event_warning'  => '0',
        'min_ff_event_critical' => '0',
        'ff_type'               => '0',
        'each_ff'               => '0',
        'ff_timeout'            => '0',
        'unit'                  => '',
        'macros'                => '',
        'quiet'                 => '0',
        'cps'                   => '0',
        'critical_instructions' => '',
        'warning_instructions'  => '',
        'unknown_instructions'  => '',
        'critical_inverse'      => '0',
        'warning_inverse'       => '0',
        'percentage_critical'   => '0',
        'percentage_warning'    => '0',
        'cron_interval'         => '* * * * *',
        'id_category'           => '0',
        'disabled_types_event'  => '{\"going_unknown\":0}',
        'module_macros'         => 'W10=',
        'warning_time'          => '0',
    ];
    return modules_create_agent_module($id_agent, $module_name.'_latency', $array_values);
}


/**
 * Create_module_status_goliat and return module id.
 *
 * @param mixed $id_agent      Id agent.
 * @param mixed $module_name   Module name.
 * @param mixed $id_group      Id group.
 * @param mixed $url_search    Url to search.
 * @param mixed $string_search Text to search.
 *
 * @return interger Module id.
 */
function create_module_status_goliat($id_agent, $module_name, $id_group, $url_search, $string_search='')
{
    if ($string_search !== '') {
        $str_search = 'check_string '.$string_search.' ';
    }

    include_once 'include/functions_modules.php';

    $array_values = [
        'id_tipo_modulo'        => '31',
        'descripcion'           => '',
        'max'                   => '0',
        'min'                   => '0',
        'snmp_oid'              => '',
        'snmp_community'        => 'public',
        'id_module_group'       => $id_group,
        'module_interval'       => '300',
        'module_ff_interval'    => '0',
        'ip_target'             => '',
        'tcp_port'              => '0',
        'tcp_rcv'               => '',
        'tcp_send'              => '',
        'id_export'             => '0',
        'plugin_user'           => '',
        'plugin_pass'           => '0',
        'plugin_parameter'      => 'task_begin
get '.$url_search.'
resource 1
'.$str_search.'
task_end',
        'id_plugin'             => '0',
        'post_process'          => '0',
        'prediction_module'     => '0',
        'max_timeout'           => '0',
        'max_retries'           => '0',
        'disabled'              => '',
        'id_modulo'             => '7',
        'custom_id'             => '',
        'history_data'          => '1',
        'dynamic_interval'      => '0',
        'dynamic_max'           => '0',
        'dynamic_min'           => '0',
        'dynamic_two_tailed'    => '0',
        'parent_module_id'      => '0',
        'min_warning'           => '0',
        'max_warning'           => '0',
        'str_warning'           => '',
        'min_critical'          => '0',
        'max_critical'          => '0',
        'str_critical'          => '',
        'custom_string_1'       => '',
        'custom_string_2'       => '',
        'custom_string_3'       => '',
        'custom_integer_1'      => '0',
        'custom_integer_2'      => '0',
        'min_ff_event'          => '0',
        'min_ff_event_normal'   => '0',
        'min_ff_event_warning'  => '0',
        'min_ff_event_critical' => '0',
        'ff_type'               => '0',
        'each_ff'               => '0',
        'ff_timeout'            => '0',
        'unit'                  => '',
        'macros'                => '',
        'quiet'                 => '0',
        'cps'                   => '0',
        'critical_instructions' => '',
        'warning_instructions'  => '',
        'unknown_instructions'  => '',
        'critical_inverse'      => '0',
        'warning_inverse'       => '0',
        'percentage_critical'   => '0',
        'percentage_warning'    => '0',
        'cron_interval'         => '* * * * *',
        'id_category'           => '0',
        'disabled_types_event'  => '{\"going_unknown\":0}',
        'module_macros'         => 'W10=',
        'warning_time'          => '0',
    ];
    return modules_create_agent_module($id_agent, $module_name.'_status', $array_values);
}


if ($create_connectivity_dialog) {
    ?>
    <script type="text/javascript">

        $('#dialog_connectivity').dialog({
            title: '<?php echo __('Create basic connectivity'); ?>',
            resizable: true,
            draggable: true,
            modal: true,
            close: false,
            height: 350,
            width: 480,
            overlay: {
                opacity: 0.5,
                background: "black"
            }
        })
        .show();
    </script>
    <?php
}


if ($check_connectivity) {
    include_once 'include/functions_api.php';
    include_once 'include/functions_servers.php';

    $status_newtwork = db_get_row_filter('tserver', ['server_type' => 1], 'status')['status'];
    $status_pluggin = db_get_row_filter('tserver', ['server_type' => 4], 'status')['status'];
    if ($status_newtwork === '1' && $status_pluggin === '1') {
        $name = array_keys(servers_get_names())[0];
        $id_group = get_parameter('id_group', 4);
        $agent_name = get_parameter('agent_name', __('Agent check connectivity'));

        $array_other['data'] = [
            $agent_name,
            '',
            2,
            $id_group,
            0,
            30,
            30,
            9,
            $name,
            0,
            0,
            0,
            __('Basic connectivity'),
        ];

        $id_agent = api_set_new_agent(0, '', $array_other, '', true);
        if ($id_agent > 0) {
            $ip_target = get_parameter('ip_target', '127.0.0.1');
            $basic_network = create_module_basic_network($id_agent, $id_group, $ip_target);
            $latency_network = create_module_latency_network($id_agent, $id_group, $ip_target);
            $packet_lost = create_module_packet_lost($id_agent, $id_group, $ip_target);
            if ($basic_network > 0 && $latency_network > 0 && $packet_lost > 0) {
                ui_print_success_message(__('Your check has been created, <a href='.ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agent).'>click here to view the data</a>. Please note that it may take a few seconds to see data if your server is busy'));
            }
        } else {
            ui_print_error_message(__('The Name is not valid for the modules.'));
        }
    } else {
        ui_print_error_message(__('Web server is not enabled.'));
    }
}


/**
 * Create module basic network and return module id.
 *
 * @param mixed $id_agent  Id agent.
 * @param mixed $id_group  Id group.
 * @param mixed $ip_target Ip target.
 *
 * @return interger Module id.
 */
function create_module_basic_network($id_agent, $id_group, $ip_target)
{
    include_once 'include/functions_modules.php';

    $array_values = [
        'id_tipo_modulo'        => '6',
        'descripcion'           => 'Basic network check (ping)',
        'max'                   => '0',
        'min'                   => '0',
        'snmp_oid'              => '',
        'snmp_community'        => 'public',
        'id_module_group'       => $id_group,
        'module_interval'       => '300',
        'module_ff_interval'    => '0',
        'ip_target'             => $ip_target,
        'tcp_port'              => '0',
        'tcp_rcv'               => '',
        'tcp_send'              => '',
        'id_export'             => '0',
        'plugin_user'           => '',
        'plugin_pass'           => '0',
        'plugin_parameter'      => '',
        'id_plugin'             => '0',
        'post_process'          => '0',
        'prediction_module'     => '0',
        'max_timeout'           => '0',
        'max_retries'           => '0',
        'disabled'              => '',
        'id_modulo'             => '2',
        'custom_id'             => '',
        'history_data'          => '1',
        'dynamic_interval'      => '0',
        'dynamic_max'           => '0',
        'dynamic_min'           => '0',
        'dynamic_two_tailed'    => '0',
        'parent_module_id'      => '0',
        'min_warning'           => '0',
        'max_warning'           => '0',
        'str_warning'           => '',
        'min_critical'          => '0',
        'max_critical'          => '0',
        'str_critical'          => '',
        'custom_string_1'       => '',
        'custom_string_2'       => '',
        'custom_string_3'       => '',
        'custom_integer_1'      => '0',
        'custom_integer_2'      => '0',
        'min_ff_event'          => '0',
        'min_ff_event_normal'   => '0',
        'min_ff_event_warning'  => '0',
        'min_ff_event_critical' => '0',
        'ff_type'               => '0',
        'each_ff'               => '0',
        'ff_timeout'            => '0',
        'unit'                  => '',
        'macros'                => '',
        'quiet'                 => '0',
        'cps'                   => '0',
        'critical_instructions' => '',
        'warning_instructions'  => '',
        'unknown_instructions'  => '',
        'critical_inverse'      => '0',
        'warning_inverse'       => '0',
        'percentage_critical'   => '0',
        'percentage_warning'    => '0',
        'cron_interval'         => '* * * * *',
        'id_category'           => '0',
        'disabled_types_event'  => '{\"going_unknown\":0}',
        'module_macros'         => 'W10=',
        'warning_time'          => '0',
    ];
    return modules_create_agent_module($id_agent, 'Basic_Network_Check', $array_values);
}


/**
 * Create module latency network and return module id.
 *
 * @param mixed $id_agent  Id agent.
 * @param mixed $id_group  Id group.
 * @param mixed $ip_target Ip target.
 *
 * @return interger Module id.
 */
function create_module_latency_network($id_agent, $id_group, $ip_target)
{
    include_once 'include/functions_modules.php';

    $array_values = [
        'id_tipo_modulo'        => '7',
        'descripcion'           => 'Basic network connectivity check to measure network latency in miliseconds',
        'max'                   => '0',
        'min'                   => '0',
        'snmp_oid'              => '',
        'snmp_community'        => 'public',
        'id_module_group'       => $id_group,
        'module_interval'       => '300',
        'module_ff_interval'    => '0',
        'ip_target'             => $ip_target,
        'tcp_port'              => '0',
        'tcp_rcv'               => '',
        'tcp_send'              => '',
        'id_export'             => '0',
        'plugin_user'           => '',
        'plugin_pass'           => '0',
        'plugin_parameter'      => '',
        'id_plugin'             => '0',
        'post_process'          => '0',
        'prediction_module'     => '0',
        'max_timeout'           => '0',
        'max_retries'           => '0',
        'disabled'              => '',
        'id_modulo'             => '2',
        'custom_id'             => '',
        'history_data'          => '1',
        'dynamic_interval'      => '0',
        'dynamic_max'           => '0',
        'dynamic_min'           => '0',
        'dynamic_two_tailed'    => '1',
        'parent_module_id'      => '0',
        'min_warning'           => '0',
        'max_warning'           => '0',
        'str_warning'           => '',
        'min_critical'          => '0',
        'max_critical'          => '0',
        'str_critical'          => '',
        'custom_string_1'       => '',
        'custom_string_2'       => '',
        'custom_string_3'       => '',
        'custom_integer_1'      => '0',
        'custom_integer_2'      => '0',
        'min_ff_event'          => '0',
        'min_ff_event_normal'   => '0',
        'min_ff_event_warning'  => '0',
        'min_ff_event_critical' => '0',
        'ff_type'               => '0',
        'each_ff'               => '0',
        'ff_timeout'            => '0',
        'unit'                  => '',
        'macros'                => '',
        'quiet'                 => '0',
        'cps'                   => '0',
        'critical_instructions' => '',
        'warning_instructions'  => '',
        'unknown_instructions'  => '',
        'critical_inverse'      => '0',
        'warning_inverse'       => '0',
        'percentage_critical'   => '0',
        'percentage_warning'    => '0',
        'cron_interval'         => '* * * * *',
        'id_category'           => '0',
        'disabled_types_event'  => '{\"going_unknown\":0}',
        'module_macros'         => 'W10=',
        'warning_time'          => '0',
    ];
    return modules_create_agent_module($id_agent, 'Basic_Network_Latency', $array_values);
}


/**
 * Create module packet lost and return module id.
 *
 * @param mixed $id_agent  Id agent.
 * @param mixed $id_group  Id group.
 * @param mixed $ip_target Ip target.
 *
 * @return interger Module id.
 */
function create_module_packet_lost($id_agent, $id_group, $ip_target)
{
    include_once 'include/functions_modules.php';

    $array_values = [
        'id_tipo_modulo'        => '1',
        'descripcion'           => 'Basic network connectivity check to measure packet loss in %',
        'max'                   => '0',
        'min'                   => '0',
        'snmp_oid'              => '',
        'snmp_community'        => 'public',
        'id_module_group'       => $id_group,
        'module_interval'       => '300',
        'module_ff_interval'    => '0',
        'ip_target'             => '',
        'tcp_port'              => '0',
        'tcp_rcv'               => '',
        'tcp_send'              => '',
        'id_export'             => '0',
        'plugin_user'           => '',
        'plugin_pass'           => '0',
        'plugin_parameter'      => '',
        'id_plugin'             => '9',
        'post_process'          => '0',
        'prediction_module'     => '0',
        'max_timeout'           => '0',
        'max_retries'           => '0',
        'disabled'              => '',
        'id_modulo'             => '4',
        'custom_id'             => '',
        'history_data'          => '1',
        'dynamic_interval'      => '0',
        'dynamic_max'           => '0',
        'dynamic_min'           => '0',
        'dynamic_two_tailed'    => '1',
        'parent_module_id'      => '0',
        'min_warning'           => '0',
        'max_warning'           => '0',
        'str_warning'           => '',
        'min_critical'          => '0',
        'max_critical'          => '0',
        'str_critical'          => '',
        'custom_string_1'       => '',
        'custom_string_2'       => '',
        'custom_string_3'       => '',
        'custom_integer_1'      => '0',
        'custom_integer_2'      => '0',
        'min_ff_event'          => '0',
        'min_ff_event_normal'   => '0',
        'min_ff_event_warning'  => '0',
        'min_ff_event_critical' => '0',
        'ff_type'               => '0',
        'each_ff'               => '0',
        'ff_timeout'            => '0',
        'unit'                  => '',
        'macros'                => '{"1":{"macro":"_field1_","desc":"Test time","help":"","value":"8","hide":""},"2":{"macro":"_field2_","desc":"Target IP","help":"","value":"'.$ip_target.'","hide":""}}',
        'quiet'                 => '0',
        'cps'                   => '0',
        'critical_instructions' => '',
        'warning_instructions'  => '',
        'unknown_instructions'  => '',
        'critical_inverse'      => '0',
        'warning_inverse'       => '0',
        'percentage_critical'   => '0',
        'percentage_warning'    => '0',
        'cron_interval'         => '* * * * *',
        'id_category'           => '0',
        'disabled_types_event'  => '{\"going_unknown\":0}',
        'module_macros'         => 'W10=',
        'warning_time'          => '0',
    ];
    return modules_create_agent_module($id_agent, 'Basic_Network_Packetloss', $array_values);
}


if ($create_net_scan_dialog) {
    ?>
    <script type="text/javascript">

        $('#dialog_basic_net').dialog({
            title: '<?php echo __('Create net scan'); ?>',
            resizable: true,
            draggable: true,
            modal: true,
            close: false,
            height: 200,
            width: 480,
            overlay: {
                opacity: 0.5,
                background: "black"
            }
        })
        .show();
    </script>
    <?php
}

if ($create_net_scan) {
    $ip_target = get_parameter('ip_target', '192.168.10.0/24');
    $id_net_scan = create_net_scan($ip_target);
    if ($id_net_scan > 0) {
        $id_recon_server = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_DISCOVERY], 'id_server')['id_server'];
        ui_print_success_message(__('Basic net created and scan in progress. <a href='.ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist&server_id='.$id_recon_server.'&force='.$id_net_scan).'>Click here to view the data</a>. Please note that it may take a few seconds to see data if your server is busy'));
    } else {
        ui_print_error_message(__('Basic net already exists. <a href='.ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist').'>Click here to view the data</a>'));
    }
}


/**
 * Create module packet lost and return module id.
 *
 * @param string $ip_target Ip and red mask.
 *
 * @return interger Module id.
 */
function create_net_scan($ip_target)
{
    global $config;
    include_once 'HostDevices.class.php';
    $HostDevices = new HostDevices(1);
    $id_recon_server = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_DISCOVERY], 'id_server')['id_server'];

    $_POST = [
        'page'                    => '1',
        'interval_manual_defined' => '1',
        'interval_select'         => '-1',
        'interval_text'           => '0',
        'interval'                => '0',
        'interval_units'          => '1',
        'taskname'                => __('Basic network'),
        'id_recon_server'         => $id_recon_server,
        'network'                 => $ip_target,
        'id_group'                => '8',
        'comment'                 => __('Created on welcome'),
    ];
    $task_created = $HostDevices->parseNetScan();
    if ($task_created === true) {
        $HostDevicesFinal = new HostDevices(2);
        $_POST = [
            'task'                      => $HostDevices->task['id_rt'],
            'page'                      => '2',
            'recon_ports'               => '',
            'auto_monitor'              => 'on',
            'id_network_profile'        => ['0' => '2'],
            'review_results'            => 'on',
            'review_limited'            => '0',
            'snmp_enabled'              => 'on',
            'snmp_version'              => '1',
            'snmp_skip_non_enabled_ifs' => 'on',
            'community'                 => '',
            'snmp_context'              => '',
            'snmp_auth_user'            => '',
            'snmp_security_level'       => 'authNoPriv',
            'snmp_auth_method'          => 'MD5',
            'snmp_auth_pass'            => '',
            'snmp_privacy_method'       => 'AES',
            'snmp_privacy_pass'         => '',
            'os_detect'                 => 'on',
            'resolve_names'             => 'on',
            'parent_detection'          => 'on',
            'parent_recursion'          => 'on',
            'vlan_enabled'              => 'on',
        ];

        $task_final_created = $HostDevicesFinal->parseNetScan();
        if ($task_final_created === true) {
            $net_scan_id = $HostDevices->task['id_rt'];
            unset($HostDevices, $HostDevicesFinal);
            return $net_scan_id;
        }
    } else {
        return 0;
    }
}


if ($create_alert_mail_dialog) {
    ?>
    <script type="text/javascript">

        $('#dialog_alert_mail').dialog({
            title: '<?php echo __('Create alert mail'); ?>',
            resizable: true,
            draggable: true,
            modal: true,
            close: false,
            height: 350,
            width: 480,
            overlay: {
                opacity: 0.5,
                background: "black"
            }
        })
        .show();
    </script>
    <?php
}


if ($create_mail_alert) {
    include_once 'include/functions_alerts.php';
    $id_action = db_get_row_filter('talert_actions', ['name' => 'Email to '.$config['id_user']], 'id')['id'];
    if (!$id_action) {
        $al_action = alerts_get_alert_action($id);
        $id_action = alerts_clone_alert_action(1, $al_action['id_group'], 'Email to '.$config['id_user']);
    }

    $id_alert_template = get_parameter('id_condition', 0);
    $id_agent_module = get_parameter('id_agent_module', 0);

    $exist = db_get_value_sql(
        sprintf(
            'SELECT COUNT(id)
            FROM talert_template_modules
            WHERE id_agent_module = %d
                AND id_alert_template = %d
                AND id_policy_alerts = 0
            ',
            $id_agent_module,
            $id_alert_template
        )
    );

    if ($exist > 0) {
        ui_print_error_message(__('Alert already exists. <a href='.ui_get_full_url('index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=list&pure=0').'>Click here to view the data</a>'));
    } else {
        $id = alerts_create_alert_agent_module($id_agent_module, $id_alert_template);
        if ($id !== false) {
            $values = [];
            $values['fires_min'] = (int) get_parameter('fires_min');
            $values['fires_max'] = (int) get_parameter('fires_max');
            $values['module_action_threshold'] = (int) 300;

            $alert_created = alerts_add_alert_agent_module_action($id, $id_action, $values);
        }
    }

    if ($alert_created === true) {
        ui_print_success_message(__('Congratulations, you have already created a simple alert. <a href='.ui_get_full_url('index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=list&pure=0').'>You can see it.</a> Pandora FMS alerts are very flexible, you can do many more things with them, we recommend you to read the <a href="https://pandorafms.com/manual/start?id=en/documentation/04_using/01_alerts">documentation</a> for more information. You can create advanced alerts from <a href='.ui_get_full_url('index.php?sec=galertas&sec2=godmode/alerts/alert_actions').'>here</a>.'));
    }
}