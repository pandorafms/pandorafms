<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
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
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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
global $statusProcessInDB;

use PandoraFMS\Agent;
use PandoraFMS\User;

check_login();

require_once $config['homedir'].'/include/functions_visual_map.php';
require_once $config['homedir'].'/include/functions_agents.php';
enterprise_include_once('include/functions_visual_map.php');

// Bypass the size limitation of posted inputs given by PHP config token 'max_input_vars'.
if (isset($_POST['serialized_form_inputs']) === true) {
    $posted_data_serialized = json_decode($_POST['serialized_form_inputs'], true);

    unset($_POST['serialized_form_inputs']);

    $posted_data_array = array_combine(
        array_column($posted_data_serialized, 'name'),
        array_column($posted_data_serialized, 'value')
    );

    // Merge data to $_POST superglobal.
    $_POST += $posted_data_array;
}

// Retrieve the visual console id.
set_unless_defined($idVisualConsole, 0);
// Set default.
$idVisualConsole = get_parameter('id_visual_console', $idVisualConsole);
if (empty($idVisualConsole) === true) {
    $idVisualConsole = get_parameter('id', 0);
}

if (is_metaconsole() === false) {
    $action_name_parameter = 'action';
} else {
    $action_name_parameter = 'action2';
}

$action = get_parameterBetweenListValues(
    $action_name_parameter,
    [
        'new',
        'save',
        'edit',
        'update',
        'delete',
        'multiple_delete',
    ],
    'new'
);
$activeTab = get_parameterBetweenListValues(
    'tab',
    [
        'data',
        'list_elements',
        'wizard',
        'wizard_services',
        'editor',
    ],
    'data'
);

// Visual console creation tab and actions.
if (empty($idVisualConsole)) {
    $visualConsole = null;

    // General ACL.
    $vconsole_write = check_acl($config['id_user'], 0, 'VW');
    $vconsole_manage = check_acl($config['id_user'], 0, 'VM');
} else {
    // Load the visual console data.
    $visualConsole = db_get_row_filter('tlayout', ['id' => $idVisualConsole]);
    // The visual console should exist.
    if (empty($visualConsole)) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access report builder'
        );
        include 'general/noaccess.php';
        return;
    }

    // The default group id is 0.
    set_unless_defined($visualConsole['id_group'], 0);

    // ACL for the existing visual console.
    $vconsole_write = check_acl_restricted_all($config['id_user'], $visualConsole['id_group'], 'VW');
    $vconsole_manage = check_acl_restricted_all($config['id_user'], $visualConsole['id_group'], 'VM');
}

// This section is only to manage the visual console
if (!$vconsole_write && !$vconsole_manage) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access report builder'
    );
    include 'general/noaccess.php';
    exit;
}

$pure = (int) get_parameter('pure', 0);
$refr = (int) get_parameter('refr', $config['vc_refr']);

$id_layout = 0;

// Save/Update data in DB
global $statusProcessInDB;
if (empty($statusProcessInDB)) {
    $statusProcessInDB = null;
}

switch ($activeTab) {
    case 'data':
        switch ($action) {
            case 'new':
                $idGroup = '';
                $background = '';
                $background_color = '';
                $width = '';
                $height = '';
                $visualConsoleName = '';
                $is_favourite = 0;
                $auto_adjust = 0;
            break;

            case 'update':
            case 'save':
                $idGroup = (int) get_parameter('id_group');
                $background = (string) get_parameter('background');
                $background_color = (string) get_parameter('background_color');
                $width = (int) get_parameter('width');
                $height = (int) get_parameter('height');
                $visualConsoleName = (string) get_parameter('name');
                $is_favourite  = (int) get_parameter('is_favourite_sent');
                $auto_adjust  = (int) get_parameter('auto_adjust_sent');

                // ACL for the new visual console
                // $vconsole_read_new = check_acl ($config['id_user'], $idGroup, "VR");
                $vconsole_write_new = check_acl_restricted_all($config['id_user'], $idGroup, 'VW');
                $vconsole_manage_new = check_acl_restricted_all($config['id_user'], $idGroup, 'VM');

                // The user should have permissions on the new group
                if (!$vconsole_write_new && !$vconsole_manage_new) {
                    db_pandora_audit(
                        AUDIT_LOG_ACL_VIOLATION,
                        'Trying to access report builder'
                    );
                    include 'general/noaccess.php';
                    exit;
                }

                $values = [
                    'name'             => $visualConsoleName,
                    'id_group'         => $idGroup,
                    'background'       => $background,
                    'background_color' => $background_color,
                    'width'            => $width,
                    'height'           => $height,
                    'is_favourite'     => $is_favourite,
                    'auto_adjust'      => $auto_adjust,
                ];

                $error = $_FILES['background_image']['error'];
                $upload_file = true;
                $uploadOK = true;
                switch ($error) {
                    case UPLOAD_ERR_OK:
                        $tmpName = $_FILES['background_image']['tmp_name'];
                        $pathname = $config['homedir'].'/images/console/background/';
                        $nameImage = str_replace(' ', '_', $_FILES['background_image']['name']);
                        $target_file = $pathname.basename($nameImage);
                        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                        $check = getimagesize($_FILES['background_image']['tmp_name']);
                        if ($check !== false) {
                            $uploadOK = 1;
                        } else {
                            $uploadOK = false;
                            $error_message = __("This file isn't image");
                            $statusProcessInDB = [
                                'flag'    => false,
                                'message' => ui_print_error_message(__("This file isn't image."), '', true),
                            ];
                        }

                        if (file_exists($target_file)) {
                            $uploadOK = false;
                            $error_message = __('File already are exists.');
                            $statusProcessInDB = [
                                'flag'    => false,
                                'message' => ui_print_error_message(__('File already are exists.'), '', true),
                            ];
                        }

                        if ($imageFileType != 'jpg' && $imageFileType != 'png' && $imageFileType != 'jpeg'
                            && $imageFileType != 'gif'
                        ) {
                            $uploadOK = false;
                            $error_message = __('The file have not image extension.');
                            $statusProcessInDB = [
                                'flag'    => false,
                                'message' => ui_print_error_message(__('The file have not image extension.'), '', true),
                            ];
                        }

                        if ($uploadOK == 1) {
                            if (move_uploaded_file($_FILES['background_image']['tmp_name'], $target_file)) {
                                $background = $nameImage;
                                $values['background'] = $background;
                                $error2 = chmod($target_file, 0644);
                                $uploadOK = $error2;
                            } else {
                                $uploadOK = false;
                                $error_message = __('Problems with move file to target.');
                                $statusProcessInDB = [
                                    'flag'    => false,
                                    'message' => ui_print_error_message(__('Problems with move file to target.'), '', true),
                                ];
                            }
                        }
                    break;

                    case UPLOAD_ERR_INI_SIZE:
                        $uploadOK = false;
                        $statusProcessInDB = [
                            'flag'    => false,
                            'message' => ui_print_error_message(__('Problems with move file to target.'), '', true),
                        ];
                    case UPLOAD_ERR_PARTIAL:
                        $uploadOK = false;
                        $statusProcessInDB = [
                            'flag'    => false,
                            'message' => ui_print_error_message(__('Problems with move file to target.'), '', true),
                        ];
                    break;

                    case UPLOAD_ERR_NO_FILE:
                        $upload_file = false;
                    break;
                }

                if ($upload_file && !$uploadOK) {
                    db_pandora_audit(
                        AUDIT_LOG_VISUAL_CONSOLE_MANAGEMENT,
                        $error_message
                    );
                    break;
                }

                // If the background is changed the size is reseted
                $background_now = $visualConsole['background'];

                $values['width'] = $width;
                $values['height'] = $height;
                switch ($action) {
                    case 'update':
                        $result = false;
                        if ($values['name'] != '' && $values['background']) {
                            $result = db_process_sql_update('tlayout', $values, ['id' => $idVisualConsole]);
                        }

                        if ($result !== false) {
                            ui_update_name_fav_element($idVisualConsole, 'Visual_Console', $values['name']);
                            db_pandora_audit(
                                AUDIT_LOG_VISUAL_CONSOLE_MANAGEMENT,
                                sprintf('Update visual console #%s', $idVisualConsole)
                            );
                            $action = 'edit';
                            $statusProcessInDB = [
                                'flag'    => true,
                                'message' => ui_print_success_message(__('Successfully update.'), '', true),
                            ];

                            // Return the updated visual console.
                            $visualConsole = db_get_row_filter(
                                'tlayout',
                                ['id' => $idVisualConsole]
                            );
                            // Update the ACL
                            // $vconsole_read = $vconsole_read_new;.
                            $vconsole_write = $vconsole_write_new;
                            $vconsole_manage = $vconsole_manage_new;
                        } else {
                            db_pandora_audit(
                                AUDIT_LOG_VISUAL_CONSOLE_MANAGEMENT,
                                sprintf('Fail update visual console #%s', $idVisualConsole)
                            );
                            $statusProcessInDB = [
                                'flag'    => false,
                                'message' => ui_print_error_message(__('Could not be update.'), '', true),
                            ];
                        }
                    break;

                    case 'save':
                        if ($values['name'] != '' && $values['background']) {
                            $idVisualConsole = db_process_sql_insert('tlayout', $values);
                        } else {
                            $idVisualConsole = false;
                        }

                        if ($idVisualConsole !== false) {
                            db_pandora_audit(
                                AUDIT_LOG_VISUAL_CONSOLE_MANAGEMENT,
                                sprintf('Create visual console #%s', $idVisualConsole)
                            );
                            $action = 'edit';
                            $statusProcessInDB = [
                                'flag'    => true,
                                'message' => ui_print_success_message(__('Successfully created.'), '', true),
                            ];

                            // Return the updated visual console.
                            $visualConsole = db_get_row_filter(
                                'tlayout',
                                ['id' => $idVisualConsole]
                            );
                            // Update the ACL
                            // $vconsole_read = $vconsole_read_new;.
                            $vconsole_write = $vconsole_write_new;
                            $vconsole_manage = $vconsole_manage_new;
                        } else {
                            db_pandora_audit(
                                AUDIT_LOG_VISUAL_CONSOLE_MANAGEMENT,
                                'Fail try to create visual console'
                            );
                            $statusProcessInDB = [
                                'flag'    => false,
                                'message' => ui_print_error_message(__('Could not be created.'), '', true),
                            ];
                        }
                    break;
                }
            break;

            case 'edit':
                $visualConsoleName = $visualConsole['name'];
                $idGroup = $visualConsole['id_group'];
                $background = $visualConsole['background'];
                $background_color = $visualConsole['background_color'];
                $width = $visualConsole['width'];
                $height = $visualConsole['height'];
                $is_favourite = $visualConsole['is_favourite'];
                $auto_adjust = $visualConsole['auto_adjust'];
            break;
        }
    break;

    case 'list_elements':
        switch ($action) {
            case 'multiple_delete':
                $delete_items_json = io_safe_output(
                    get_parameter(
                        'id_item_json',
                        json_encode([])
                    )
                );

                $delete_items = json_decode($delete_items_json, true);

                if (!empty($delete_items)) {
                    $result = (bool) db_process_sql_delete(
                        'tlayout_data',
                        [
                            'id_layout' => $idVisualConsole,
                            'id'        => $delete_items,
                        ]
                    );
                } else {
                    $result = false;
                }

                $statusProcessInDB = [
                    'flag'    => true,
                    'message' => ui_print_result_message(
                        $result,
                        __('Successfully multiple delete.'),
                        __('Unsuccessful multiple delete.'),
                        '',
                        true
                    ),
                ];
            break;

            case 'update':
                // Update background
                $background = get_parameter('background');
                $background_color = get_parameter('background_color');
                $width = get_parameter('width');
                $height = get_parameter('height');

                if ($width == 0 && $height == 0) {
                    $sizeBackground = getimagesize(
                        $config['homedir'].'/images/console/background/'.$background
                    );
                    $width = $sizeBackground[0];
                    $height = $sizeBackground[1];
                }

                db_process_sql_update(
                    'tlayout',
                    [
                        'background'       => $background,
                        'background_color' => $background_color,
                        'width'            => $width,
                        'height'           => $height,
                    ],
                    ['id' => $idVisualConsole]
                );

                // Return the updated visual console
                $visualConsole = db_get_row_filter(
                    'tlayout',
                    ['id' => $idVisualConsole]
                );

                // Update elements in visual map
                $idsElements = db_get_all_rows_filter(
                    'tlayout_data',
                    ['id_layout' => $idVisualConsole],
                    [
                        'id',
                        'type',
                    ]
                );

                if ($idsElements === false) {
                    $idsElements = [];
                }

                foreach ($idsElements as $idElement) {
                    $id = $idElement['id'];
                    $values = [];
                    $values['label'] = get_parameter('label_'.$id, '');
                    $values['image'] = get_parameter('image_'.$id, '');
                    $values['width'] = get_parameter('width_'.$id, 0);
                    $values['height'] = get_parameter('height_'.$id, 0);
                    $values['pos_x'] = get_parameter('left_'.$id, 0);
                    $values['pos_y'] = get_parameter('top_'.$id, 0);
                    switch ($idElement['type']) {
                        case NETWORK_LINK:
                        case LINE_ITEM:
                        continue 2;

                        break;

                        case SIMPLE_VALUE_MAX:
                        case SIMPLE_VALUE_MIN:
                        case SIMPLE_VALUE_AVG:
                            $values['period'] = get_parameter('period_'.$id, 0);
                        break;

                        case MODULE_GRAPH:
                            $values['period'] = get_parameter('period_'.$id, 0);
                            unset($values['image']);
                        break;

                        case GROUP_ITEM:
                            $values['id_group'] = get_parameter('group_'.$id, 0);
                        break;

                        case CIRCULAR_PROGRESS_BAR:
                        case CIRCULAR_INTERIOR_PROGRESS_BAR:
                        case PERCENTILE_BUBBLE:
                        case PERCENTILE_BAR:
                            unset($values['height']);
                        break;
                    }

                    $agentName = get_parameter('agent_'.$id, '');
                    if (defined('METACONSOLE')) {
                        $values['id_metaconsole'] = (int) get_parameter('id_server_id_'.$id, '');
                        $values['id_agent'] = (int) get_parameter('id_agent_'.$id, 0);
                    } else {
                        $agent_id = (int) get_parameter('id_agent_'.$id, 0);
                        $values['id_agent'] = $agent_id;
                    }

                    $values['id_agente_modulo'] = get_parameter('module_'.$id, 0);
                    $values['id_custom_graph'] = get_parameter('custom_graph_'.$id, 0);
                    $values['parent_item'] = get_parameter('parent_'.$id, 0);
                    $values['id_layout_linked'] = get_parameter('map_linked_'.$id, 0);

                    if (enterprise_installed()) {
                        enterprise_visual_map_update_action_from_list_elements($type, $values, $id);
                    }

                    db_process_sql_update('tlayout_data', $values, ['id' => $id]);
                }
            break;

            case 'delete':
                $id_element = get_parameter('id_element');
                $result = db_process_sql_delete('tlayout_data', ['id' => $id_element]);
                if ($result !== false) {
                    $statusProcessInDB = [
                        'flag'    => true,
                        'message' => ui_print_success_message(__('Successfully delete.'), '', true),
                    ];
                }
            break;
        }

        $visualConsoleName = $visualConsole['name'];
        $action = 'edit';
    break;

    case 'wizard':
        $visualConsoleName = $visualConsole['name'];
        $background = $visualConsole['background'];

        $fonts = get_parameter('fonts');
        $fontf = get_parameter('fontf');


        switch ($action) {
            case 'update':
                $id_agents = get_parameter('id_agents', []);
                $name_modules = get_parameter('module', []);

                $type = (int) get_parameter('type', STATIC_GRAPH);
                $image = get_parameter('image');
                $range = (int) get_parameter('range', 50);
                $width = (int) get_parameter('width', 0);
                $height = (int) get_parameter('height', 0);
                $period = (int) get_parameter('period', 0);
                $show_statistics = get_parameter('show_statistics', 0);
                $process_value = (int) get_parameter('process_value', 0);
                $percentileitem_width = (int) get_parameter('percentileitem_width', 0);
                $max_value = (int) get_parameter('max_value', 0);
                $type_percentile = get_parameter('type_percentile', 'percentile');
                $value_show = get_parameter('value_show', 'percent');
                $label_type = get_parameter('label_type', 'agent_module');
                $enable_link = get_parameter('enable_link', 'enable_link');
                $show_on_top = get_parameter('show_on_top', 0);

                // This var switch between creation of items, item_per_agent = 0 => item per module; item_per_agent <> 0  => item per agent
                $item_per_agent = get_parameter('item_per_agent', 0);
                $id_server = (int) get_parameter('servers', 0);

                $kind_relationship = (int) get_parameter(
                    'kind_relationship',
                    VISUAL_MAP_WIZARD_PARENTS_NONE
                );
                $item_in_the_map = (int) get_parameter('item_in_the_map', 0);

                $message = '';

                if (($width == 0) && ($height == 0) && ($type == MODULE_GRAPH)) {
                    $width = 400;
                    $height = 180;
                }

                // One item per agent
                if ($item_per_agent == 1) {
                    $id_agents_result = [];
                    foreach ($id_agents as $id_agent_key => $id_agent_id) {
                        if (defined('METACONSOLE')) {
                            $row = db_get_row_filter(
                                'tmetaconsole_agent',
                                ['id_tagente' => $id_agent_id]
                            );
                            $id_server = $row['id_tmetaconsole_setup'];
                            $id_agent_id = $row['id_tagente'];

                            $id_agents_result[] = [
                                'id_agent'  => $id_agent_id,
                                'id_server' => $id_server,
                            ];
                        } else {
                            $id_agents_result[] = $id_agent_id;
                        }
                    }

                    $message .= visual_map_process_wizard_add_agents(
                        $id_agents_result,
                        $image,
                        $idVisualConsole,
                        $range,
                        $width,
                        $height,
                        $period,
                        $process_value,
                        $percentileitem_width,
                        $max_value,
                        $type_percentile,
                        $value_show,
                        $label_type,
                        $type,
                        $enable_link,
                        $id_server,
                        $kind_relationship,
                        $item_in_the_map,
                        $fontf,
                        $fonts
                    );

                    $statusProcessInDB = [
                        'flag'    => true,
                        'message' => $message,
                    ];
                } else {
                    if (is_metaconsole() === true) {
                        $agents_ids = [];
                        $servers_ids = [];
                        foreach ($id_agents as $id_agent_id) {
                            $server_and_agent = explode('|', $id_agent_id);

                            $agents_ids[] = $server_and_agent[1];
                            $servers_ids[] = $server_and_agent[0];
                        }

                        $rows = db_get_all_rows_filter(
                            'tmetaconsole_agent',
                            [
                                'id_tagente'            => $agents_ids,
                                'id_tmetaconsole_setup' => $servers_ids,
                            ]
                        );

                        $agents = [];
                        foreach ($rows as $row) {
                            $agents[$row['id_tmetaconsole_setup']][] = $row['id_tagente'];
                        }
                    } else {
                        $agents[0] = $id_agents;
                    }

                    foreach ($agents as $id_server => $id_agents) {
                        // Any module.
                        if (empty($name_modules) === true || $name_modules[0] === '0') {
                            $message .= visual_map_process_wizard_add_agents(
                                $id_agents,
                                $image,
                                $idVisualConsole,
                                $range,
                                $width,
                                $height,
                                $period,
                                $process_value,
                                $percentileitem_width,
                                $max_value,
                                $type_percentile,
                                $value_show,
                                'agent',
                                $type,
                                $enable_link,
                                $id_server,
                                $kind_relationship,
                                $item_in_the_map,
                                $fontf,
                                $fonts
                            );
                        } else {
                            $id_modules = [];

                            if ($id_server != 0) {
                                foreach ($name_modules as $serial_data) {
                                    $modules_serial = explode(';', $serial_data);

                                    foreach ($modules_serial as $data_serialized) {
                                        $data = explode('|', $data_serialized);
                                        if ($id_server == $data[2]) {
                                            $id_modules[] = $data[0];
                                        }
                                    }
                                }
                            } else {
                                foreach ($name_modules as $mod) {
                                    foreach ($id_agents as $ag) {
                                        $agent = new Agent($ag);
                                        $id_module = $agent->searchModules(
                                            ['nombre' => $mod],
                                            1
                                        )->toArray()['id_agente_modulo'];

                                        if (empty($id_module) === true) {
                                            continue;
                                        }

                                        $id_modules[] = $id_module;
                                    }
                                }
                            }

                            $message .= visual_map_process_wizard_add_modules(
                                $id_modules,
                                $image,
                                $idVisualConsole,
                                $range,
                                $width,
                                $height,
                                $period,
                                $process_value,
                                $percentileitem_width,
                                $max_value,
                                $type_percentile,
                                $value_show,
                                $label_type,
                                $type,
                                $enable_link,
                                $id_server,
                                $kind_relationship,
                                $item_in_the_map,
                                $fontf,
                                $fonts
                            );
                        }
                    }

                    $statusProcessInDB = [
                        'flag'    => true,
                        'message' => $message,
                    ];
                }

                $action = 'edit';
            break;
        }
    break;

    case 'wizard_services':
        $visualConsoleName = $visualConsole['name'];
        switch ($action) {
            case 'update':
                enterprise_include_once('/include/functions_visual_map.php');

                $icon = (string) get_parameter('icon');
                $id_services = (array) get_parameter('services_selected');

                $result = enterprise_hook('enterprise_visual_map_process_services_wizard_add', [$id_services, $idVisualConsole, $icon]);
                if ($result != ENTERPRISE_NOT_HOOK) {
                    $statusProcessInDB = [
                        'flag'    => $result['status'],
                        'message' => $result['message'],
                    ];
                }

                $action = 'edit';
            break;
        }
    break;

    case 'editor':
        switch ($action) {
            case 'new':
            case 'update':
            case 'edit':
                $visualConsoleName = $visualConsole['name'];
                $action = 'edit';
            break;
        }
    break;
}

if (isset($config['vc_refr']) and $config['vc_refr'] != 0) {
    $view_refresh = $config['vc_refr'];
} else {
    $view_refresh = '300';
}

if (is_metaconsole() === false) {
    $url_base = 'index.php?sec=network&sec2=godmode/reporting/visual_console_builder&action=';
    $url_view = 'index.php?sec=network&sec2=operation/visual_console/render_view&id='.$idVisualConsole.'&refr='.$view_refresh;
} else {
    $url_base = 'index.php?operation=edit_visualmap&sec=screen&sec2=screens/screens&action=visualmap&pure='.$pure.'&action2=';
    $url_view = 'index.php?sec=screen&sec2=screens/screens&action=visualmap&pure=0&id='.$idVisualConsole.'&refr='.$view_refresh;
}

// Hash for auto-auth in public link.
$hash = User::generatePublicHash();

$buttons = [];
$buttons['consoles_list'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=network&sec2=godmode/reporting/map_builder&refr='.$refr.'">'.html_print_image('images/logs@svg.svg', true, ['title' => __('Visual consoles list'), 'class' => 'main_menu_icon invert_filter']).'</a>',
];
$buttons['public_link'] = [
    'active' => false,
    'text'   => '<a href="'.ui_get_full_url(
        'operation/visual_console/public_console.php?hash='.$hash.'&refr='.$refr.'&id_layout='.$idVisualConsole.'&id_user='.$config['id_user'],
        false,
        false,
        false
    ).'">'.html_print_image('images/item-icon.svg', true, ['title' => __('Show link to public Visual Console'), 'class' => 'main_menu_icon invert_filter']).'</a>',
];
$buttons['data'] = [
    'active' => false,
    'text'   => '<a href="'.$url_base.$action.'&tab=data&id_visual_console='.$idVisualConsole.'">'.html_print_image('images/bars-graph.svg', true, ['title' => __('Main data'), 'class' => 'main_menu_icon invert_filter']).'</a>',
];
$buttons['list_elements'] = [
    'active' => false,
    'text'   => '<a href="'.$url_base.$action.'&tab=list_elements&id_visual_console='.$idVisualConsole.'">'.html_print_image('images/edit_columns@svg.svg', true, ['title' => __('List elements'), 'class' => 'main_menu_icon invert_filter']).'</a>',
];

if (enterprise_installed()) {
    $buttons['wizard_services'] = [
        'active' => false,
        'text'   => '<a href="'.$url_base.$action.'&tab=wizard_services&id_visual_console='.$idVisualConsole.'">'.html_print_image('images/wand_services.png', true, ['title' => __('Services wizard'), 'class' => 'main_menu_icon invert_filter']).'</a>',
    ];
}

$buttons['wizard'] = [
    'active' => false,
    'text'   => '<a href="'.$url_base.$action.'&tab=wizard&id_visual_console='.$idVisualConsole.'">'.html_print_image('images/wizard@svg.svg', true, ['title' => __('Wizard'), 'class' => 'invert_filter']).'</a>',
];
if ($config['legacy_vc']) {
    $buttons['editor'] = [
        'active' => false,
        'text'   => '<a href="'.$url_base.$action.'&tab=editor&id_visual_console='.$idVisualConsole.'">'.html_print_image('images/builder@svg.svg', true, ['title' => __('Builder'), 'class' => 'invert_filter']).'</a>',
    ];
}

$buttons['view'] = [
    'active' => false,
    'text'   => '<a href="'.$url_view.'">'.html_print_image('images/enable.svg', true, ['title' => __('View'), 'class' => 'main_menu_icon invert_filter']).'</a>',
];

if (empty($idVisualConsole) === true) {
    $buttons = ['data' => $buttons['data']];
    // Show only the data tab
    // If it is a fail try, reset the values
    $action = 'new';
    $visualConsoleName = __('New visual console');
}

$buttons[$activeTab]['active'] = true;

$tab_builder = ($activeTab === 'editor') ? 'visual_console_editor_editor_tab' : '';
ui_print_standard_header(
    ($visualConsoleName ?? ''),
    'images/visual_console.png',
    false,
    $tab_builder,
    false,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Topology maps'),
        ],
        [
            'link'  => '',
            'label' => __('Visual console'),
        ],
    ]
);

if ($statusProcessInDB !== null) {
    echo $statusProcessInDB['message'];
}

// The source code for PAINT THE PAGE.
switch ($activeTab) {
    case 'wizard':
        include_once $config['homedir'].'/godmode/reporting/visual_console_builder.wizard.php';
    break;

    case 'wizard_services':
        if (enterprise_installed()) {
            enterprise_include('/godmode/reporting/visual_console_builder.wizard_services.php');
        }
    break;

    case 'data':
        include_once $config['homedir'].'/godmode/reporting/visual_console_builder.data.php';
    break;

    case 'list_elements':
        include_once $config['homedir'].'/godmode/reporting/visual_console_builder.elements.php';
    break;

    case 'editor':
        include_once $config['homedir'].'/godmode/reporting/visual_console_builder.editor.php';
    break;
}
