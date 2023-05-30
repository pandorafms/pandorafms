<?php
/**
 * Network map.
 *
 * @category   View
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
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

// Check user credentials.
check_login();
// error_reporting(E_ALL);
// ini_set("display_errors", 1);
// General ACL for the network maps.
$networkmaps_read   = (bool) check_acl($config['id_user'], 0, 'MR');
$networkmaps_write  = (bool) check_acl($config['id_user'], 0, 'MW');
$networkmaps_manage = (bool) check_acl($config['id_user'], 0, 'MM');

if ($networkmaps_read === false && $networkmaps_write === false && $networkmaps_manage === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access networkmap'
    );
    include $config['homedir'].'/general/noaccess.php';
    return;
}

require_once 'include/functions_networkmap.php';

$new_networkmap          = (bool) get_parameter('new_networkmap', false);
$save_networkmap         = (bool) get_parameter('save_networkmap', false);
$save_empty_networkmap   = (bool) get_parameter('save_empty_networkmap', false);
$update_empty_networkmap = (bool) get_parameter('update_empty_networkmap', false);
$update_networkmap       = (bool) get_parameter('update_networkmap', false);
$copy_networkmap         = (bool) get_parameter('copy_networkmap', false);
$delete                  = (bool) get_parameter('delete', false);
$tab                     = (string) get_parameter('tab', 'list');
$new_empty_networkmap    = (bool) get_parameter('new_empty_networkmap', false);

if ($new_empty_networkmap === true) {
    if ($networkmaps_write === true || $networkmaps_manage === true) {
        include_once 'godmode/agentes/pandora_networkmap_empty.editor.php';
        return;
    }
}

if ($save_empty_networkmap === true) {
    $id_group     = (int) get_parameter('id_group', 0);
    $id_group_map = (int) get_parameter('id_group_map', 0);

    // ACL for the network map.
    $networkmap_write  = (bool) check_acl_restricted_all($config['id_user'], $id_group_map, 'MW');
    $networkmap_manage = (bool) check_acl_restricted_all($config['id_user'], $id_group_map, 'MM');

    if ($networkmap_write === false && $networkmap_manage === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access networkmap'
        );
        include 'general/noaccess.php';
        return;
    }

    $name = (string) get_parameter('name', '');

    // Default size values.
    $width = $config['networkmap_max_width'];
    $height = $config['networkmap_max_width'];

    $method = (string) get_parameter('method', 'fdp');

    $dont_show_subgroups = (int) get_parameter_checkbox(
        'dont_show_subgroups',
        0
    );
    $node_radius = (int) get_parameter('node_radius', 40);
    $description = get_parameter('description', '');

    $values = [];
    $values['name'] = $name;
    $values['id_group'] = $id_group;
    $values['source_period'] = 60;
    $values['width'] = $width;
    $values['height'] = $height;
    $values['id_user'] = $config['id_user'];
    $values['description'] = $description;
    $values['source'] = 0;
    $values['source_data'] = $id_group;
    $values['id_group_map'] = $id_group_map;


    if (!$networkmap_write && !$networkmap_manage) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access networkmap'
        );
        include 'general/noaccess.php';
        return;
    }

    $filter = [];
    $filter['dont_show_subgroups'] = $dont_show_subgroups;
    $filter['node_radius'] = $node_radius;
    $filter['empty_map'] = 1;
    $values['filter'] = json_encode($filter);

    $result = false;
    if (!empty($name)) {
        $result = db_process_sql_insert(
            'tmap',
            $values
        );
    }

    $result_txt = ui_print_result_message(
        $result,
        __('Succesfully created'),
        __('Could not be created'),
        '',
        true
    );

    $id = $result;
    define('_id_', $id);

    if ($result !== false) {
        $tab = 'view';
        header(
            'Location: '.ui_get_full_url(
                'index.php?sec=network&sec2=operation/agentes/pandora_networkmap&tab='.$tab.'&id_networkmap='.$id
            )
        );
    }
} else if ($update_empty_networkmap) {
    $id_group = (int) get_parameter('id_group', 0);
    $id_group_map = (int) get_parameter('id_group_map', 0);


    // ACL for the new network map
    $networkmap_write_new = (bool) check_acl_restricted_all($config['id_user'], $id_group_map, 'MW');
    $networkmap_manage_new = (bool) check_acl_restricted_all($config['id_user'], $id_group_map, 'MM');

    if (!$networkmap_write && !$networkmap_manage) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access networkmap'
        );
        include 'general/noaccess.php';
        return;
    }

    $name = (string) get_parameter('name', '');

    $recon_task_id = (int) get_parameter(
        'recon_task_id',
        0
    );

    $source = (string) get_parameter('source', 'group');

    $values = [];
    $values['name'] = $name;
    $values['id_group'] = implode(',', $id_group);

    $values['generation_method'] = 4;

    $description = get_parameter('description', '');
    $values['description'] = $description;

    $dont_show_subgroups = (int) get_parameter_checkbox(
        'dont_show_subgroups',
        0
    );
    $node_radius = (int) get_parameter('node_radius', 40);
    $row = db_get_row('tmap', 'id', $id);
    $filter = json_decode($row['filter'], true);
    $filter['dont_show_subgroups'] = $dont_show_subgroups;
    $filter['node_radius'] = $node_radius;

    $values['filter'] = json_encode($filter);

    $result = false;
    if (empty($name) === false) {
        $result = db_process_sql_update(
            'tmap',
            $values,
            ['id' => $id]
        );
    }

    $result_txt = ui_print_result_message(
        $result,
        __('Succesfully updated'),
        __('Could not be updated'),
        '',
        true
    );
}

// The networkmap doesn't exist yet
if ($new_networkmap || $save_networkmap) {
    $result_txt = '';
    if ($new_networkmap) {
        if ($networkmaps_write || $networkmaps_manage) {
            include 'pandora_networkmap.editor.php';
            return;
        } else {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access networkmap'
            );
            include 'general/noaccess.php';
            return;
        }
    }

    if ($save_networkmap) {
        $id_group = get_parameter('id_group', 0);
        $id_group_map = (int) get_parameter('id_group_map', 0);


        // ACL for the network map.
        $networkmap_write  = (bool) check_acl_restricted_all($config['id_user'], $id_group_map, 'MW');
        $networkmap_manage = (bool) check_acl_restricted_all($config['id_user'], $id_group_map, 'MM');

        if ($networkmap_write === false && $networkmap_manage === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access networkmap'
            );
            include 'general/noaccess.php';
            return;
        }

        $name = (string) get_parameter('name');

        // Default size values.
        $width = $config['networkmap_max_width'];
        $height = $config['networkmap_max_width'];

        $method = (string) get_parameter('method', 'fdp');

        $recon_task_id = (int) get_parameter(
            'recon_task_id',
            0
        );
        $ip_mask = get_parameter(
            'ip_mask',
            ''
        );
        $source = (string) get_parameter('source', 'group');
        $dont_show_subgroups = (int) get_parameter_checkbox(
            'dont_show_subgroups',
            0
        );
        $node_radius = (int) get_parameter('node_radius', 40);
        $description = get_parameter('description', '');

        $offset_x = get_parameter('pos_x', 0);
        $offset_y = get_parameter('pos_y', 0);
        $scale_z = get_parameter('scale_z', 0.5);

        $node_sep = get_parameter('node_sep', '0.25');
        $rank_sep = get_parameter('rank_sep', ($method === 'twopi') ? '1.0' : '0.5');

        $mindist = get_parameter('mindist', '1.0');
        $kval = get_parameter('kval', '0.3');

        $refresh_time = get_parameter('refresh_time', '300');

        $values = [];
        $values['name'] = $name;
        $values['id_group'] = implode(',', $id_group);
        $values['source_period'] = 60;
        $values['width'] = $width;
        $values['height'] = $height;
        $values['id_user'] = $config['id_user'];
        $values['description'] = $description;
        $values['id_group_map'] = $id_group_map;
        $values['refresh_time'] = $refresh_time;

        switch ($method) {
            case 'twopi':
                $values['generation_method'] = LAYOUT_RADIAL;
            break;

            case 'dot':
                $values['generation_method'] = LAYOUT_FLAT;
            break;

            case 'circo':
                $values['generation_method'] = LAYOUT_CIRCULAR;
            break;

            case 'neato':
                $values['generation_method'] = LAYOUT_SPRING1;
            break;

            case 'fdp':
                $values['generation_method'] = LAYOUT_SPRING2;
            break;

            case 'radial_dinamic':
                $values['generation_method'] = LAYOUT_RADIAL_DYNAMIC;
            break;

            default:
                $values['generation_method'] = LAYOUT_RADIAL;
            break;
        }

        if ($source == 'group') {
            $values['source'] = 0;
            $values['source_data'] = implode(',', $id_group);
        } else if ($source == 'recon_task') {
            $values['source'] = 1;
            $values['source_data'] = $recon_task_id;
        } else if ($source == 'ip_mask') {
            $values['source'] = 2;
            $values['source_data'] = $ip_mask;
        }

        if ($networkmap_write === false && $networkmap_manage === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access networkmap'
            );
            include 'general/noaccess.php';
            return;
        }

        $filter = [];
        $filter['dont_show_subgroups'] = $dont_show_subgroups;
        $filter['node_radius'] = $node_radius;
        $filter['x_offs'] = $offset_x;
        $filter['y_offs'] = $offset_y;
        $filter['z_dash'] = $scale_z;
        $filter['node_sep'] = $node_sep;
        $filter['rank_sep'] = $rank_sep;
        $filter['mindist'] = $mindist;
        $filter['kval'] = $kval;

        $values['filter'] = json_encode($filter);

        $result = false;
        if (!empty($name)) {
            $result = db_process_sql_insert(
                'tmap',
                $values
            );
        }

        $result_txt = ui_print_result_message(
            $result,
            __('Succesfully created'),
            __('Could not be created'),
            '',
            true
        );

        $id = $result;
        define('_id_', $id);

        if ($result !== false) {
            $tab = 'view';
            if ($values['generation_method'] == LAYOUT_RADIAL_DYNAMIC) {
                $tab = 'r_dinamic';
                define('_activeTab_', 'radial_dynamic');
                $url = 'index.php?sec=network&sec2=operation/agentes/networkmap.dinamic&activeTab=radial_dynamic';
                header(
                    'Location: '.ui_get_full_url(
                        $url.'&id_networkmap='.$id
                    )
                );
            } else {
                $url = 'index.php?sec=network&sec2=operation/agentes/pandora_networkmap';
                header(
                    'Location: '.ui_get_full_url(
                        $url.'&tab='.$tab.'&id_networkmap='.$id
                    )
                );
            }
        }
    }
}
// The networkmap exists
else if ($update_networkmap || $copy_networkmap || $delete) {
    $id = (int) get_parameter('id_networkmap', 0);

    // Networkmap id required
    if (empty($id)) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access networkmap'
        );
        include 'general/noaccess.php';
        return;
    }

    // ACL for the network map.
    $id_group_map_old = db_get_value('id_group_map', 'tmap', 'id', $id);

    if ($id_group_map_old === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to accessnode graph builder'
        );
        include 'general/noaccess.php';
        return;
    }

    $networkmap_write  = (bool) check_acl_restricted_all($config['id_user'], $id_group_map_old, 'MW');
    $networkmap_manage = (bool) check_acl_restricted_all($config['id_user'], $id_group_map_old, 'MM');

    if ($networkmap_write === false && $networkmap_manage === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access networkmap'
        );
        include 'general/noaccess.php';
        return;
    }

    if ($update_networkmap) {
        $id_group = get_parameter('id_group', 0);
        // Get id of old group source to check changes.
        $id_group_old = db_get_value('id_group', 'tmap', 'id', $id);


        // ACL for the new network map.
        $id_group_map          = (int) get_parameter('id_group_map', 0);
        $networkmap_write_new  = (bool) check_acl_restricted_all($config['id_user'], $id_group_map, 'MW');
        $networkmap_manage_new = (bool) check_acl_restricted_all($config['id_user'], $id_group_map, 'MM');

        if ($networkmap_write === false && $networkmap_manage === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access networkmap'
            );
            include 'general/noaccess.php';
            return;
        }

        $name = (string) get_parameter('name', '');

        $recon_task_id = (int) get_parameter(
            'recon_task_id',
            0
        );

        $source = (string) get_parameter('source', 'group');

        $offset_x = get_parameter('pos_x', 0);
        $offset_y = get_parameter('pos_y', 0);
        $scale_z = get_parameter('scale_z', 0.5);

        $refresh_time = get_parameter('refresh_time', '300');

        $values = [];
        $values['name'] = $name;
        $values['id_group'] = implode(',', $id_group);
        $values['id_group_map'] = $id_group_map;

        $description = get_parameter('description', '');
        $values['description'] = $description;

        $values['refresh_time'] = $refresh_time;

        $dont_show_subgroups = (int) get_parameter('dont_show_subgroups', 0);
        $node_radius = (int) get_parameter('node_radius', 40);
        $row = db_get_row('tmap', 'id', $id);
        $filter = json_decode($row['filter'], true);
        $filter['dont_show_subgroups'] = $dont_show_subgroups;
        $filter['node_radius'] = $node_radius;
        $filter['x_offs'] = $offset_x;
        $filter['y_offs'] = $offset_y;
        $filter['z_dash'] = $scale_z;

        $values['filter'] = json_encode($filter);

        $result = false;
        if (empty($name) === false) {
            $result = db_process_sql_update(
                'tmap',
                $values,
                ['id' => $id]
            );
            ui_update_name_fav_element($id, 'Network_map', $name);
        }

        $result_txt = ui_print_result_message(
            $result,
            __('Succesfully updated'),
            __('Could not be updated'),
            '',
            true
        );

        if ($result) {
            // If change the group, the map must be regenerated
            if ($id_group != $id_group_old) {
                networkmap_delete_nodes($id);
                // Delete relations.
                networkmap_delete_relations($id);
            }

            $networkmap_write = $networkmap_write_new;
            $networkmap_manage = $networkmap_manage_new;
        }
    }

    if ($copy_networkmap) {
        $id = (int) get_parameter('id_networkmap', 0);

        $result = duplicate_networkmap($id);
        $result_txt = ui_print_result_message(
            $result,
            __('Succesfully duplicate'),
            __('Could not be duplicated'),
            '',
            true
        );
    }

    if ($delete) {
        $id = (int) get_parameter('id_networkmap', 0);

        $result = networkmap_delete_networkmap($id);

        // Delete network map from fav menu.
        db_process_sql_delete(
            'tfavmenu_user',
            [
                'id_element' => $id,
                'section'    => 'Network_map',
                'id_user'    => $config['id_user'],
            ]
        );
        $result_txt = ui_print_result_message(
            $result,
            __('Succesfully deleted'),
            __('Could not be deleted'),
            '',
            true
        );
    }
}

switch ($tab) {
    case 'r_dinamic':
        include 'networkmap.dinamic.php';
    break;

    case 'edit':
        include 'pandora_networkmap.editor.php';
    break;

    case 'view':
        include 'pandora_networkmap.view.php';
    break;

    case 'list':
        $old_networkmaps_enterprise = [];
        $old_networkmaps_open = [];

        $old_networkmaps_enterprise = db_get_all_rows_sql('SELECT * FROM tnetworkmap_enterprise');
        if ($old_networkmaps_enterprise === false) {
            $old_networkmaps_enterprise = [];
        }

        $old_networkmaps_open = db_get_all_rows_sql('SELECT * FROM tnetwork_map');

        $ent_maps_to_migrate = [];
        foreach ($old_networkmaps_enterprise as $old_map_ent) {
            $old_map_options = json_decode($old_map_ent['options'], true);

            if (isset($old_map_options['migrated']) === false) {
                $ent_maps_to_migrate[] = $old_map_ent['id'];
            }
        }

        $open_maps_to_migrate = [];
        if (isset($old_networkmaps_open) === true && is_array($old_networkmaps_open) === true) {
            foreach ($old_networkmaps_open as $old_map_open) {
                $text_filter = $old_map_open['text_filter'];
                if ($text_filter != 'migrated') {
                    $open_maps_to_migrate[] = $old_map_open['id_networkmap'];
                }
            }
        }

        if (empty($ent_maps_to_migrate) === false || empty($open_maps_to_migrate) === false) {
            ?>
            <div id="migration_dialog" class="center">
                <p class="center"><strong>Networkmaps are not migrated, wait while migration is processed...</strong></p>
                <br>
                <img class="vertical_middle" src="<?php echo 'images/spinner.gif'; ?>"> 
            </div>
            <script>
                $("#migration_dialog").dialog({
                                            close: function() {document.location.href = document.location.href;}
                                        });
                
                var old_maps_ent = "<?php echo implode(',', $ent_maps_to_migrate); ?>";
                var old_maps_open = "<?php echo implode(',', $open_maps_to_migrate); ?>";
                
                if (old_maps_ent == "") {
                    old_maps_ent = 0;
                }
                if (old_maps_open == "") {
                    old_maps_open = 0;
                }
                
                var params = [];
                params.push("process_migration=1");
                params.push("old_maps_ent=" + old_maps_ent);
                params.push("old_maps_open=" + old_maps_open);
                params.push("page=operation/agentes/pandora_networkmap.view");
                jQuery.ajax ({
                    data: params.join ("&"),
                    dataType: 'json',
                    type: 'POST',
                    url: action="ajax.php",
                    success: function (data) {
                        var html_message = "";
                        if (data['ent'] && data['open']) {
                            html_message = "<p><strong>Complete migrations without errors</strong></p>"
                            $("#migration_dialog").html(html_message);
                        }
                        else if (data['ent']) {
                            html_message = "<p><strong>Complete migrations with open maps errors</strong></p>"
                            $("#migration_dialog").html(html_message);
                        }
                        else if (data['open']) {
                            html_message = "<p><strong>Complete migrations with enterprise maps errors</strong></p>"
                            $("#migration_dialog").html(html_message);
                        }
                        else {
                            html_message = "<p><strong>Complete migrations with errors</strong></p>"
                            $("#migration_dialog").html(html_message);
                        }
                    }
                });
                
            </script>
            <?php
        }

        // Header.
        ui_print_standard_header(
            __('List of network maps'),
            'images/op_network.png',
            false,
            '',
            false,
            [],
            [
                [
                    'link'  => '',
                    'label' => __('Topology maps'),
                ],
                [
                    'link'  => '',
                    'label' => __('Networkmap'),
                ],
            ]
        );

        echo $result_txt;

        $table = new stdClass();
        $table->width = '100%';
        $table->class = 'info_table';
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->headstyle['actions'] = 'text-align: right;';

        $table->style = [];
        $table->style['name'] = '';
        $table->style['nodes'] = 'text-align: left;';
        $table->style['groups'] = 'text-align: left;';
        if ($networkmaps_write === true || $networkmaps_manage === true) {
            $table->style['actions'] = 'text-align: right;';
        }

        $table->size = [];
        $table->size['name'] = '40%';
        $table->size['nodes'] = '15%';
        $table->size['groups'] = '400px';
        if ($networkmaps_write === true || $networkmaps_manage === true) {
            $table->size['actions'] = '10%';
        }

        $table->head = [];
        $table->head['name'] = __('Name');
        $table->head['nodes'] = __('Nodes');

        $table->head['groups'] = __('Groups');
        if ($networkmaps_write === true || $networkmaps_manage === true) {
            $table->head['actions'] = __('Actions');
        }

        $id_groups = array_keys(users_get_groups());

        // Prepare pagination.
        $offset = (int) get_parameter('offset');
        $limit = $config['block_size'];
        $count_maps = db_get_value_filter(
            'count(*)',
            'tmap',
            ['id_group' => $id_groups]
        );

        $network_maps = db_get_all_rows_filter(
            'tmap',
            [
                'id_group_map' => $id_groups,
                'limit'        => $limit,
                'offset'       => $offset,
            ]
        );

        if ($network_maps !== false) {
            $table->data = [];

            foreach ($network_maps as $network_map) {
                // ACL for the network map.
                $networkmap_read   = (bool) check_acl_restricted_all($config['id_user'], $network_map['id_group_map'], 'MR');
                $networkmap_write  = (bool) check_acl_restricted_all($config['id_user'], $network_map['id_group_map'], 'MW');
                $networkmap_manage = (bool) check_acl_restricted_all($config['id_user'], $network_map['id_group_map'], 'MM');

                $data = [];
                if ($network_map['generation_method'] == 6) {
                    $data['name'] = '<a href="index.php?'.'sec=network&'.'sec2=operation/agentes/networkmap.dinamic&'.'activeTab=radial_dynamic&'.'id_networkmap='.$network_map['id'].'">'.$network_map['name'].'</a>';
                } else {
                    $data['name'] = '<a href="index.php?'.'sec=network&'.'sec2=operation/agentes/pandora_networkmap&'.'tab=view&'.'id_networkmap='.$network_map['id'].'">'.$network_map['name'].'</a>';
                }

                if ($network_map['id_group'] > 0) {
                    $nodes = db_get_all_rows_sql(
                        'SELECT style 
                        FROM titem 
                        WHERE id_map = '.$network_map['id'].' AND deleted = 0 AND type <> 2'
                    );
                    $count = 0;
                    foreach ($nodes as $node) {
                        $node_style = json_decode($node['style'], true);
                        if ($node_style['id_group'] == $network_map['id_group']) {
                            $count++;
                        }
                    }
                } else {
                    $count = db_get_value_sql(
                        'SELECT COUNT(*)
						FROM titem
						WHERE id_map = '.$network_map['id'].' AND deleted = 0 AND type <> 2'
                    );
                }

                if (empty($count)) {
                    $count = 0;
                }

                if (($count == 0) && ($network_map['source'] != 'empty')) {
                    if ($network_map['generated']) {
                        $data['nodes'] = __('Empty map');
                    } else if ($network_map['generation_method'] == LAYOUT_RADIAL_DYNAMIC) {
                        $data['nodes'] = __('Dynamic');
                    } else {
                        $data['nodes'] = __('Pending to generate');
                    }
                } else {
                    $data['nodes'] = $count;
                }

                $data['groups'] = ui_print_group_icon($network_map['id_group_map'], true);

                $data['actions'] = '';

                if ($networkmap_write || $networkmap_manage) {
                    $tableActionButtons = [];

                    $tableActionButtons[] = html_print_anchor(
                        [
                            'title'   => __('Copy'),
                            'href'    => 'index.php?sec=network&sec2=operation/agentes/pandora_networkmap&amp;copy_networkmap=1&id_networkmap='.$network_map['id'],
                            'content' => html_print_image('images/copy.svg', true, ['class' => 'main_menu_icon invert_filter']),
                        ],
                        true
                    );

                    $tableActionButtons[] = html_print_anchor(
                        [
                            'title'   => __('Edit'),
                            'href'    => 'index.php?sec=network&sec2=operation/agentes/pandora_networkmap&tab=edit&edit_networkmap=1&id_networkmap='.$network_map['id'],
                            'content' => html_print_image('images/edit.svg', true, ['class' => 'main_menu_icon invert_filter']),
                        ],
                        true
                    );

                    $tableActionButtons[] = html_print_anchor(
                        [
                            'title'   => __('Delete'),
                            'href'    => 'index.php?sec=network&sec2=operation/agentes/pandora_networkmap&delete=1&id_networkmap='.$network_map['id'],
                            'content' => html_print_image('images/delete.svg', true, ['class' => 'main_menu_icon invert_filter']),
                        ],
                        true
                    );

                    $data['actions'] = html_print_div(
                        [
                            'class'   => 'table_action_buttons',
                            'content' => implode('', $tableActionButtons),
                        ],
                        true
                    );
                }

                $table->data[] = $data;
            }

            html_print_table($table);
            $tablePagination = ui_pagination($count_maps, false, $offset, $limit, true, 'offset', false);
        } else {
            ui_print_info_message(['no_close' => true, 'message' => __('There are no maps defined.') ]);
        }

        if ($networkmaps_write || $networkmaps_manage) {
            echo '<form id="new_networkmap" method="post" action="index.php?sec=network&amp;sec2=operation/agentes/pandora_networkmap">';
            html_print_input_hidden('new_networkmap', 1);
            echo '</form>';

            echo '<form id="empty_networkmap" method="post" action="index.php?sec=network&amp;sec2=operation/agentes/pandora_networkmap">';
            html_print_input_hidden('new_empty_networkmap', 1);
            echo '</form>';
        }

        html_print_action_buttons(
            html_print_submit_button(__('Create network map'), 'crt', false, [ 'icon' => 'next', 'form' => 'new_networkmap' ], true).html_print_submit_button(__('Create empty network map'), 'crt', false, [ 'icon' => 'next', 'form' => 'empty_networkmap' ], true),
            [ 'right_content' => $tablePagination ],
        );

    break;
}
