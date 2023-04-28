<?php
// ______                 __                     _______ _______ _______
// |   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
// |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
// |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2021 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================
// Load global variables
global $config;

// Check user credentials
check_login();

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access agent manager'
    );
    include $config['homedir'].'/general/noaccess.php';
    return;
}

global $direccion_agente, $id_agente, $id_os;

// include_once ($config['homedir'].'/'.ENTERPRISE_DIR.'/include/functions_policies.php');
require_once $config['homedir'].'/include/functions_ui.php';

// Initialize data
$add_inventory_module = (boolean) get_parameter('add_inventory_module');
$update_inventory_module = (boolean) get_parameter('update_inventory_module');
$delete_inventory_module = (int) get_parameter('delete_inventory_module');
$load_inventory_module = (int) get_parameter('load_inventory_module');
$force_inventory_module = (int) get_parameter('force_inventory_module');
$id_agent_module_inventory = (int) get_parameter('id_agent_module_inventory');
$id_module_inventory = (int) get_parameter('id_module_inventory');
$target = (string) get_parameter('target', '');
$username = (string) get_parameter('username');
$password = io_input_password((string) get_parameter('password'));
$interval = (int) get_parameter('interval');

$custom_fields = array_map(
    function ($field) {
        $field['secure'] = (bool) $field['secure'];
        if ($field['secure']) {
            $field['value'] = io_input_password(io_safe_output($field['value']));
        }

        return $field;
    },
    get_parameter('custom_fields', [])
);

$custom_fields_enabled = (bool) get_parameter('custom_fields_enabled');

// Add inventory module to agent
if ($add_inventory_module) {
    $if_exists = db_get_value_filter(
        'id_agent_module_inventory',
        'tagent_module_inventory',
        [
            'id_agente'           => $id_agente,
            'id_module_inventory' => $id_module_inventory,
        ]
    );

    if (!$if_exists) {
        $values = [
            'id_agente'           => $id_agente,
            'id_module_inventory' => $id_module_inventory,
            'target'              => $target,
            'interval'            => $interval,
            'username'            => $username,
            'password'            => $password,
            'custom_fields'       => $custom_fields_enabled && !empty($custom_fields) ? base64_encode(json_encode(io_safe_output($custom_fields), JSON_UNESCAPED_UNICODE)) : '',
        ];

        $result = db_process_sql_insert('tagent_module_inventory', $values);

        if ($result) {
            ui_print_success_message(__('Successfully added inventory module'));
        } else {
            ui_print_error_message(__('Error adding inventory module'));
        }
    } else {
        ui_print_error_message(__('The inventory of the module already exists'));
    }

    // Remove inventory module from agent
} else if ($delete_inventory_module) {
    $result = db_process_sql_delete(
        'tagent_module_inventory',
        ['id_agent_module_inventory' => $delete_inventory_module]
    );

    if ($result) {
        ui_print_success_message(__('Successfully deleted inventory module'));
    } else {
        ui_print_error_message(__('Error deleting inventory module'));
    }

    // Update inventory module
} else if ($force_inventory_module) {
    $result = db_process_sql_update('tagent_module_inventory', ['flag' => 1], ['id_agent_module_inventory' => $force_inventory_module]);

    if ($result) {
        ui_print_success_message(__('Successfully forced inventory module'));
    } else {
        ui_print_error_message(__('Error forcing inventory module'));
    }

    // Update inventory module
} else if ($update_inventory_module) {
    $values = [
        'target'        => $target,
        'interval'      => $interval,
        'username'      => $username,
        'password'      => $password,
        'custom_fields' => $custom_fields_enabled && !empty($custom_fields) ? base64_encode(json_encode(io_safe_output($custom_fields, true), JSON_UNESCAPED_UNICODE)) : '',
    ];

    $result = db_process_sql_update('tagent_module_inventory', $values, ['id_agent_module_inventory' => $id_agent_module_inventory, 'id_agente' => $id_agente]);

    if ($result) {
        ui_print_success_message(__('Successfully updated inventory module'));
    } else {
        ui_print_error_message(__('Error updating inventory module'));
    }
}

// Load inventory module data for updating
if ($load_inventory_module) {
    $sql = 'SELECT * FROM tagent_module_inventory WHERE id_module_inventory = '.$load_inventory_module;
    $row = db_get_row_sql($sql);

    if (!empty($row)) {
        $id_agent_module_inventory = $row['id_agent_module_inventory'];
        $id_module_inventory = $row['id_module_inventory'];
        $target = $row['target'];
        $interval = $row['interval'];
        $username = $row['username'];
        $password = io_output_password($row['password']);
        $custom_fields = [];

        if (!empty($row['custom_fields'])) {
            try {
                $custom_fields = array_map(
                    function ($field) {
                        if ($field['secure']) {
                            $field['value'] = io_output_password($field['value']);
                        }

                        return $field;
                    },
                    json_decode(base64_decode($row['custom_fields']), true)
                );
                $custom_fields_enabled = true;
            } catch (Exception $e) {
            }
        }
    } else {
        ui_print_error_message(__('Inventory module error'));
        include 'general/footer.php';

        return;
    }
} else {
    $target = $direccion_agente;
    $interval = (string) SECONDS_1HOUR;
    $username = '';
    $password = '';
    $custom_fields_enabled = false;
    $custom_fields = [];
}

// Inventory module configuration
$form_buttons = '';
if ($load_inventory_module) {
    $form_buttons .= html_print_input_hidden('id_agent_module_inventory', $id_agent_module_inventory, true);
    $form_buttons .= html_print_submit_button(
        __('Update'),
        'update_inventory_module',
        false,
        ['icon' => 'wand'],
        true
    );
} else {
    $form_buttons .= html_print_submit_button(
        __('Add'),
        'add_inventory_module',
        false,
        ['icon' => 'wand'],
        true
    );
}

echo ui_get_inventory_module_add_form(
    'index.php?sec=estado&sec2=godmode/agentes/configurar_agente&tab=inventory&id_agente='.$id_agente,
    html_print_action_buttons($form_buttons, [], true),
    $load_inventory_module,
    $id_os,
    $target,
    $interval,
    $username,
    $password,
    $custom_fields_enabled,
    $custom_fields
);

// Inventory module list
$sql = sprintf(
    'SELECT *
	FROM tmodule_inventory, tagent_module_inventory
	WHERE tagent_module_inventory.id_agente = %d
		AND tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
	ORDER BY name',
    $id_agente
);
$result = db_process_sql($sql);
if (db_get_num_rows($sql) == 0) {
    echo '&nbsp;</td></tr><tr><td>';
} else {
    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'databox info_table max_floating_element_size';
    $table->data = [];
    $table->head = [];
    $table->styleTable = '';
    $table->head[0] = "<span title='".__('Policy')."'>".__('P.').'</span>';
    $table->head[1] = __('Name');
    $table->head[2] = __('Description');
    $table->head[3] = __('Target');
    $table->head[4] = __('Interval');
    $table->head[5] = __('Actions');
    $table->align = [];
    $table->align[5] = 'left';
    $i = 0;
    foreach ($result as $row) {
        $table->cellclass[$i++][5] = 'table_action_buttons';
        $data = [];

        $sql = sprintf('SELECT id_policy FROM tpolicy_modules_inventory WHERE id = %d', $row['id_policy_module_inventory']);
        $id_policy = db_get_value_sql($sql);

        if ($id_policy) {
            $policy = policies_get_policy($id_policy);
            $data[0] = '<a href="index.php?sec=gmodules&sec2='.ENTERPRISE_DIR.'/godmode/policies/policies&id='.$id_policy.'">';
            $data[0] .= html_print_image('images/policy@svg.svg', true, ['border' => '0', 'title' => $policy['name'], 'class' => 'main_menu_icon invert_filter']);
            $data[0] .= '</a>';
        } else {
            $data[0] = '';
        }

        $data[1] = '<a href="index.php?sec=estado&sec2=godmode/agentes/configurar_agente&tab=inventory&id_agente='.$id_agente.'&load_inventory_module='.$row['id_module_inventory'].'">'.$row['name'].'</a>';
        $data[2] = $row['description'];
        $data[3] = $row['target'];
        $data[4] = human_time_description_raw($row['interval']);
        // Delete module
        $data[5] = '<a href="index.php?sec=estado&sec2=godmode/agentes/configurar_agente&tab=inventory&id_agente='.$id_agente.'&delete_inventory_module='.$row['id_agent_module_inventory'].'" onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">';
        $data[5] .= html_print_image('images/delete.svg', true, ['border' => '0', 'title' => __('Delete'), 'class' => 'main_menu_icon invert_filter']);
        $data[5] .= '</b></a>&nbsp;&nbsp;';
        // Update module
        $data[5] .= '<a href="index.php?sec=estado&sec2=godmode/agentes/configurar_agente&tab=inventory&id_agente='.$id_agente.'&load_inventory_module='.$row['id_module_inventory'].'">';
        $data[5] .= html_print_image('images/edit.svg', true, ['border' => '0', 'title' => __('Update'), 'class' => 'main_menu_icon invert_filter']);
        $data[5] .= '</b></a>&nbsp;&nbsp;';
        // Force refresh module
        $data[5] .= '<a href="index.php?sec=estado&sec2=godmode/agentes/configurar_agente&tab=inventory&id_agente='.$id_agente.'&force_inventory_module='.$row['id_agent_module_inventory'].'">';
        $data[5] .= html_print_image('images/force@svg.svg', true, ['border' => '0', 'title' => __('Force'), 'class' => 'main_menu_icon invert_filter']).'</b></a>';
        array_push($table->data, $data);
    }

    html_print_table($table);
}
