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
// Load global vars
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Network Profile Management'
    );
    include 'general/noaccess.php';
    return;
}

// Header
/*
    Hello there! :)

    We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(

    You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.

*/

ui_print_page_header(
    __('Module management').' &raquo; '.__('Module template management'),
    'images/gm_modules.png',
    false,
    '',
    true,
    '',
    true,
    'modulemodal'
);
/*
    // At this moment, this message is not necessary
    ui_print_info_message(
    __(
        'This section is maintained only for legacy use. Please, keep in mind use %s for manage template blocks.',
        '<a href="'.ui_get_full_url('index.php?logged=1&sec=gmodules&sec2=godmode/modules/manage_block_templates').'">Module Blocks</a>'
    )
    );
*/
require_once 'include/functions_network_profiles.php';

$delete_profile = (bool) get_parameter('delete_profile');
$export_profile = (bool) get_parameter('export_profile');
$multiple_delete = (bool) get_parameter('multiple_delete', 0);

if ($delete_profile) {
    // if delete
    $id = (int) get_parameter('delete_profile');

    $result = network_profiles_delete_network_profile($id);

    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Delete module template #'.$id
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Fail try to delete module template #'.$id
        );
    }

    ui_print_result_message(
        $result,
        __('Template successfully deleted'),
        __('Error deleting template')
    );
}

if ($multiple_delete) {
    $ids = (array) get_parameter('delete_multiple', []);

    foreach ($ids as $id) {
        $result = network_profiles_delete_network_profile($id);

        if ($result === false) {
            break;
        }
    }

    $str_ids = implode(',', $ids);
    if ($result) {
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Multiple delete module template: '.$str_ids
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MODULE_MANAGEMENT,
            'Fail try to delete module template: '.$str_ids
        );
    }

    ui_print_result_message(
        $result,
        __('Successfully multiple deleted'),
        __('Not deleted. Error deleting multiple data')
    );
}

if ($export_profile) {
    $id = (int) get_parameter('export_profile');
    $profile_info = network_profiles_get_network_profile($id);

    if (empty($profile_info)) {
        ui_print_error_message(__('This template does not exist'));
        return;
    }

    // It's important to keep the structure and order in the same way for backwards compatibility.
    switch ($config['dbtype']) {
        case 'mysql':
            $sql = sprintf(
                '
				SELECT components.name, components.description, components.type, components.max, components.min, components.module_interval, 
					components.tcp_port, components.tcp_send, components.tcp_rcv, components.snmp_community, components.snmp_oid, 
					components.id_module_group, components.id_modulo, components.plugin_user, components.plugin_pass, components.plugin_parameter,
					components.max_timeout, components.max_retries, components.history_data, components.min_warning, components.max_warning, components.str_warning, components.min_critical, 
					components.max_critical, components.str_critical, components.min_ff_event, components.dynamic_interval, components.dynamic_max, components.dynamic_min, components.dynamic_two_tailed, comp_group.name AS group_name, components.critical_instructions, components.warning_instructions, components.unknown_instructions
				FROM `tnetwork_component` AS components, tnetwork_profile_component AS tpc, tnetwork_component_group AS comp_group
				WHERE tpc.id_nc = components.id_nc
					AND components.id_group = comp_group.id_sg
					AND tpc.id_np = %d',
                $id
            );
        break;

        case 'postgresql':
            $sql = sprintf(
                '
				SELECT components.name, components.description, components.type, components.max, components.min, components.module_interval, 
					components.tcp_port, components.tcp_send, components.tcp_rcv, components.snmp_community, components.snmp_oid, 
					components.id_module_group, components.id_modulo, components.plugin_user, components.plugin_pass, components.plugin_parameter,
					components.max_timeout, components.max_retries, components.history_data, components.min_warning, components.max_warning, components.str_warning, components.min_critical, 
					components.max_critical, components.str_critical, components.min_ff_event, comp_group.name AS group_name, components.critical_instructions, components.warning_instructions, components.unknown_instructions
				FROM "tnetwork_component" AS components, tnetwork_profile_component AS tpc, tnetwork_component_group AS comp_group
				WHERE tpc.id_nc = components.id_nc
					AND components.id_group = comp_group.id_sg
					AND tpc.id_np = %d',
                $id
            );
        break;

        case 'oracle':
            $sql = sprintf(
                '
				SELECT components.name, components.description, components.type, components.max, components.min, components.module_interval, 
					components.tcp_port, components.tcp_send, components.tcp_rcv, components.snmp_community, components.snmp_oid, 
					components.id_module_group, components.id_modulo, components.plugin_user, components.plugin_pass, components.plugin_parameter,
					components.max_timeout, components.max_retries, components.history_data, components.min_warning, components.max_warning, components.str_warning, components.min_critical, 
					components.max_critical, components.str_critical, components.min_ff_event, comp_group.name AS group_name, components.critical_instructions, components.warning_instructions, components.unknown_instructions
				FROM tnetwork_component AS components, tnetwork_profile_component AS tpc, tnetwork_component_group AS comp_group
				WHERE tpc.id_nc = components.id_nc
					AND components.id_group = comp_group.id_sg
					AND tpc.id_np = %d',
                $id
            );
        break;
    }

    $components = db_get_all_rows_sql($sql);

    $row_names = [];
    $inv_names = [];
    // Find the names of the rows that we are getting and throw away the duplicate numeric keys
    foreach ($components[0] as $row_name => $detail) {
        if (is_numeric($row_name)) {
            $inv_names[] = $row_name;
        } else {
            $row_names[] = $row_name;
        }
    }

    // Set cookie for download control.
    setDownloadCookieToken();
    // Send headers to tell the browser we're sending a file
    header('Content-type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.preg_replace('/\s/', '_', $profile_info['name']).'.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Clean up output buffering
    while (@ob_end_clean()) {
    }

    // Then print the first line (row names)
    echo '"'.implode('","', $row_names).'"';
    echo "\n";

    // Then print the rest of the data. Encapsulate in quotes in case we have comma's in any of the descriptions
    foreach ($components as $row) {
        foreach ($inv_names as $bad_key) {
            unset($row[$bad_key]);
        }

        echo '"'.implode('","', $row).'"';
        echo "\n";
    }

    // We're done here. The original page will still be there
    exit;
}

// Prepare pagination.
$offset = (int) get_parameter('offset');
$limit = $config['block_size'];
$count_network_templates = db_get_value('count(*)', 'tnetwork_profile');

$result = db_get_all_rows_filter(
    'tnetwork_profile',
    [
        'order'  => 'name',
        'limit'  => $limit,
        'offset' => $offset,
    ]
);

if ($result === false) {
    $result = [];
}

$table->cellpadding = 0;
$table->cellspacing = 0;
$table->width = '100%';
$table->class = 'info_table';

$table->head = [];
$table->head[0] = html_print_checkbox('all_delete', 0, false, true, false);
;
$table->head[1] = __('Name');
$table->head[2] = __('Description');
$table->head[3] = '<span class="mrgn_right_7p">'.__('Action').'</span>';
$table->size = [];
$table->size[0] = '20px';
$table->size[2] = '65%';
$table->size[3] = '15%';

$table->align = [];
$table->align[3] = 'left';

$table->data = [];

foreach ($result as $row) {
    $data = [];
    $data[0] = html_print_checkbox_extended('delete_multiple[]', $row['id_np'], false, false, '', 'class="check_delete"', true);
    $data[1] = '<a href="index.php?sec=gmodules&amp;sec2=godmode/modules/manage_network_templates_form&amp;id_np='.$row['id_np'].'">'.io_safe_output($row['name']).'</a>';
    $data[2] = ui_print_truncate_text(io_safe_output($row['description']), 'description', true, true, true, '[&hellip;]');
    $table->cellclass[][3] = 'table_action_buttons';
    $data[3] = html_print_input_image(
        'delete_profile',
        'images/delete.svg',
        $row['id_np'],
        '',
        true,
        [
            'onclick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;',
            'class'   => 'invert_filter',
        ]
    );
    $data[3] .= html_print_input_image(
        'export_profile',
        'images/csv.png',
        $row['id_np'],
        '',
        true,
        [
            'title' => 'Export to CSV',
            'class' => 'invert_filter',
        ]
    );
    $data[3] = '<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates'.'&delete_profile=1&delete_profile='.$row['id_np'].'" '.'onclick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.html_print_image('images/delete.svg', true, ['title' => __('Delete'), 'class' => 'invert_filter']).'</a>';
    $data[3] .= '<a onclick="blockResubmit($(this))" href="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates'.'&export_profile='.$row['id_np'].'">'.html_print_image('images/csv.png', true, ['title' => __('Export to CSV'), 'class' => 'invert_filter']).'</a>';

    array_push($table->data, $data);
}

if (!empty($table->data)) {
    echo '<form method="post" action="index.php?sec=gmodules&amp;sec2=godmode/modules/manage_network_templates">';
    html_print_input_hidden('multiple_delete', 1);
    ui_pagination($count_network_templates, false, $offset);
    html_print_table($table);
    ui_pagination($count_network_templates, false, $offset, 0, false, 'offset', true, '');
    echo "<div class='pdd_l_5px right'>";
    html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
    echo '</div>';
    echo '</form>';
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('There are no defined network profiles') ]);
}

echo '<form method="post" action="index.php?sec=gmodules&amp;sec2=godmode/modules/manage_network_templates_form">';
echo '<div class="right">';
html_print_submit_button(__('Create'), 'crt', '', 'class="sub next"');
echo '</div></form>';

?>
<script type="text/javascript">

    $( document ).ready(function() {

        $('[id^=checkbox-delete_multiple]').change(function(){
            if($(this).parent().parent().hasClass('checkselected')){
                $(this).parent().parent().removeClass('checkselected');
            }
            else{
                $(this).parent().parent().addClass('checkselected');    
            }
        });

        // If you click on the input type checkbox with id checkbox-all_delete
        $('[id^=checkbox-all_delete]').change(function(){    
            // If selected (if the checked property equals true)
            if ($("#checkbox-all_delete").prop("checked")) {
                $('[id^=checkbox-delete_multiple]').parent().parent().addClass('checkselected');
                // Select each input that has the class .check_delete
                $(".check_delete").prop("checked", true);
            }
            else{
                $('[id^=checkbox-delete_multiple]').parent().parent().removeClass('checkselected');
                // deselect every input that has the class .check_delete
                $(".check_delete").prop("checked", false);
            }    
        });

    });

</script>
