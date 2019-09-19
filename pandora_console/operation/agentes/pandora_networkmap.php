<?php
// ______                 __                     _______ _______ _______
// |   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
// |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
// |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2010 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================
// Load global variables
global $config;

// Check user credentials
check_login();

// General ACL for the network maps
$networkmaps_read = check_acl($config['id_user'], 0, 'MR');
$networkmaps_write = check_acl($config['id_user'], 0, 'MW');
$networkmaps_manage = check_acl($config['id_user'], 0, 'MM');

if (!$networkmaps_read && !$networkmaps_write && !$networkmaps_manage) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access networkmap'
    );
    include $config['homedir'].'/general/noaccess.php';
    return;
}

require_once 'include/functions_networkmap.php';

$new_networkmap = (bool) get_parameter('new_networkmap', false);
$save_networkmap = (bool) get_parameter('save_networkmap', false);
$save_empty_networkmap = (bool) get_parameter('save_empty_networkmap', false);
$update_empty_networkmap = (bool) get_parameter('save_empty_networkmap', false);
$update_networkmap = (bool) get_parameter('update_networkmap', false);
$copy_networkmap = (bool) get_parameter('copy_networkmap', false);
$delete = (bool) get_parameter('delete', false);
$tab = (string) get_parameter('tab', 'list');
$new_empty_networkmap = get_parameter('new_empty_networkmap', false);

if (enterprise_installed()) {
    if ($new_empty_networkmap) {
        if ($networkmaps_write || $networkmaps_manage) {
            enterprise_include(
                'godmode/agentes/pandora_networkmap_empty.editor.php'
            );
            return;
        }
    }

    if ($save_empty_networkmap) {
        $id_group = (int) get_parameter('id_group', 0);

        // ACL for the network map
        // $networkmap_read = check_acl ($config['id_user'], $id_group, "MR");
        $networkmap_write = check_acl($config['id_user'], $id_group, 'MW');
        $networkmap_manage = check_acl($config['id_user'], $id_group, 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access networkmap'
            );
            include 'general/noaccess.php';
            return;
        }

        $name = (string) get_parameter('name', '');

        // Default size values
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


        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                'ACL Violation',
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

        // ACL for the new network map
        $networkmap_write_new = check_acl($config['id_user'], $id_group, 'MW');
        $networkmap_manage_new = check_acl($config['id_user'], $id_group, 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                'ACL Violation',
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
        $values['id_group'] = $id_group;

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
        if (!empty($name)) {
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
                'ACL Violation',
                'Trying to access networkmap'
            );
            include 'general/noaccess.php';
            return;
        }
    }

    if ($save_networkmap) {
        $id_group = (int) get_parameter('id_group', 0);

        // ACL for the network map
        // $networkmap_read = check_acl ($config['id_user'], $id_group, "MR");
        $networkmap_write = check_acl($config['id_user'], $id_group, 'MW');
        $networkmap_manage = check_acl($config['id_user'], $id_group, 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access networkmap'
            );
            include 'general/noaccess.php';
            return;
        }

        $name = (string) get_parameter('name', '');

        // Default size values
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
        if ($method == 'twopi') {
            $rank_sep = get_parameter('rank_sep', '1.0');
        } else {
            $rank_sep = get_parameter('rank_sep', '0.5');
        }

        $mindist = get_parameter('mindist', '1.0');
        $kval = get_parameter('kval', '0.3');

        $values = [];
        $values['name'] = $name;
        $values['id_group'] = $id_group;
        $values['source_period'] = 60;
        $values['width'] = $width;
        $values['height'] = $height;
        $values['id_user'] = $config['id_user'];
        $values['description'] = $description;

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
            $values['source_data'] = $id_group;
        } else if ($source == 'recon_task') {
            $values['source'] = 1;
            $values['source_data'] = $recon_task_id;
        } else if ($source == 'ip_mask') {
            $values['source'] = 2;
            $values['source_data'] = $ip_mask;
        }

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                'ACL Violation',
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
            'ACL Violation',
            'Trying to access networkmap'
        );
        include 'general/noaccess.php';
        return;
    }

    $id_group_old = db_get_value('id_group', 'tmap', 'id', $id);
    if ($id_group_old === false) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to accessnode graph builder'
        );
        include 'general/noaccess.php';
        return;
    }

    // ACL for the network map
    $networkmap_write = check_acl($config['id_user'], $id_group_old, 'MW');
    $networkmap_manage = check_acl($config['id_user'], $id_group_old, 'MM');

    if (!$networkmap_write && !$networkmap_manage) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access networkmap'
        );
        include 'general/noaccess.php';
        return;
    }

    if ($update_networkmap) {
        $id_group = (int) get_parameter('id_group', 0);

        // ACL for the new network map
        $networkmap_write_new = check_acl($config['id_user'], $id_group, 'MW');
        $networkmap_manage_new = check_acl($config['id_user'], $id_group, 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                'ACL Violation',
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

        $values = [];
        $values['name'] = $name;
        $values['id_group'] = $id_group;

        $description = get_parameter('description', '');
        $values['description'] = $description;

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
        if (!empty($name)) {
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

        if ($result) {
            // If change the group, the map must be regenerated
            if ($id_group != $id_group_old) {
                networkmap_delete_nodes($id);
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

        if (enterprise_installed()) {
            $old_networkmaps_enterprise = db_get_all_rows_sql('SELECT * FROM tnetworkmap_enterprise');
            if ($old_networkmaps_enterprise === false) {
                $old_networkmaps_enterprise = [];
            }
        }

        $old_networkmaps_open = db_get_all_rows_sql('SELECT * FROM tnetwork_map');

        $ent_maps_to_migrate = [];
        foreach ($old_networkmaps_enterprise as $old_map_ent) {
            $old_map_options = json_decode($old_map_ent['options'], true);

            if (!isset($old_map_options['migrated'])) {
                $ent_maps_to_migrate[] = $old_map_ent['id'];
            }
        }

        $open_maps_to_migrate = [];
        if (isset($old_networkmaps_open) && is_array($old_networkmaps_open)) {
            foreach ($old_networkmaps_open as $old_map_open) {
                $text_filter = $old_map_open['text_filter'];
                if ($text_filter != 'migrated') {
                    $open_maps_to_migrate[] = $old_map_open['id_networkmap'];
                }
            }
        }

        if (!empty($ent_maps_to_migrate) || !empty($open_maps_to_migrate)) {
            ?>
            <div id="migration_dialog" style="text-align: center;">
                <p style="text-align: center;"><strong>Networkmaps are not migrated, wait while migration is processed...</strong></p>
                <br>
                <img style="vertical-align: middle;" src="images/spinner.gif"> 
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

        ui_print_page_header(
            __('Networkmap'),
            'images/op_network.png',
            false,
            'network_map_enterprise_list',
            false
        );

        echo $result_txt;

        $table = new stdClass();
        $table->width = '100%';
        $table->class = 'info_table';
        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->headstyle['copy'] = 'text-align: center;';
        $table->headstyle['edit'] = 'text-align: center;';

        $table->style = [];
        $table->style['name'] = '';
        if (enterprise_installed()) {
            $table->style['nodes'] = 'text-align: center;';
        }

        $table->style['groups'] = 'text-align: left;';
        if ($networkmaps_write || $networkmaps_manage) {
            $table->style['copy'] = 'text-align: center;';
            $table->style['edit'] = 'text-align: center;';
            $table->style['delete'] = 'text-align: center;';
        }

        $table->size = [];
        $table->size['name'] = '60%';
        if (enterprise_installed()) {
            $table->size['nodes'] = '30px';
        }

        $table->size['groups'] = '400px';
        if ($networkmaps_write || $networkmaps_manage) {
            $table->size['copy'] = '30px';
            $table->size['edit'] = '30px';
            $table->size['delete'] = '30px';
        }

        $table->head = [];
        $table->head['name'] = __('Name');
        if (enterprise_installed()) {
            $table->head['nodes'] = __('Nodes');
        }

        $table->head['groups'] = __('Groups');
        if ($networkmaps_write || $networkmaps_manage) {
            $table->head['copy'] = __('Copy');
            $table->head['edit'] = __('Edit');
            $table->head['delete'] = __('Delete');
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
                'id_group' => $id_groups,
                'limit'    => $limit,
                'offset'   => $offset,
            ]
        );

        if ($network_maps !== false) {
            $table->data = [];

            foreach ($network_maps as $network_map) {
                // ACL for the network map
                $networkmap_read = check_acl($config['id_user'], $network_map['id_group'], 'MR');
                $networkmap_write = check_acl($config['id_user'], $network_map['id_group'], 'MW');
                $networkmap_manage = check_acl($config['id_user'], $network_map['id_group'], 'MM');

                if (!$networkmap_read && !$networkmap_write && !$networkmap_manage) {
                    db_pandora_audit(
                        'ACL Violation',
                        'Trying to access networkmap enterprise'
                    );
                    include 'general/noaccess.php';
                    return;
                }

                $data = [];
                if ($network_map['generation_method'] == 6) {
                    $data['name'] = '<a href="index.php?'.'sec=network&'.'sec2=operation/agentes/networkmap.dinamic&'.'activeTab=radial_dynamic&'.'id_networkmap='.$network_map['id'].'">'.$network_map['name'].'</a>';
                } else {
                    $data['name'] = '<a href="index.php?'.'sec=network&'.'sec2=operation/agentes/pandora_networkmap&'.'tab=view&'.'id_networkmap='.$network_map['id'].'">'.$network_map['name'].'</a>';
                }

                if ($network_map['id_group'] > 0) {
                    $nodes = db_get_all_rows_sql('SELECT style FROM titem WHERE id_map = '.$network_map['id'].' AND deleted = 0');
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
						WHERE id_map = '.$network_map['id'].' AND deleted = 0 AND type = 0'
                    );
                }

                if (empty($count)) {
                    $count = 0;
                }

                if (enterprise_installed()) {
                    if (($count == 0) && ($network_map['source'] != 'empty')) {
                        if ($network_map['generated']) {
                            $data['nodes'] = __('Empty map');
                        } else if ($network_map['generation_method'] == LAYOUT_RADIAL_DYNAMIC) {
                            $data['nodes'] = __('Dynamic');
                        } else {
                            $data['nodes'] = __('Pending to generate');
                        }
                    } else {
                        $data['nodes'] = ($network_map['id_group'] == 0) ? ($count - 1) : $count;
                        // PandoraFMS node is not an agent
                    }
                }

                $data['groups'] = ui_print_group_icon($network_map['id_group'], true);

                if ($networkmap_write || $networkmap_manage) {
                    $table->cellclass[] = [
                        'copy'   => 'action_buttons',
                        'edit'   => 'action_buttons',
                        'delete' => 'action_buttons',
                    ];
                    $data['copy'] = '<a href="index.php?'.'sec=network&'.'sec2=operation/agentes/pandora_networkmap&amp;'.'copy_networkmap=1&'.'id_networkmap='.$network_map['id'].'" alt="'.__('Copy').'">'.html_print_image('images/copy.png', true).'</a>';
                    $data['edit'] = '<a href="index.php?'.'sec=network&'.'sec2=operation/agentes/pandora_networkmap&'.'tab=edit&'.'edit_networkmap=1&'.'id_networkmap='.$network_map['id'].'" alt="'.__('Config').'">'.html_print_image('images/config.png', true).'</a>';
                    $data['delete'] = '<a href="index.php?'.'sec=network&'.'sec2=operation/agentes/pandora_networkmap&'.'delete=1&'.'id_networkmap='.$network_map['id'].'" alt="'.__('Delete').'" onclick="javascript: if (!confirm(\''.__('Are you sure?').'\')) return false;">'.html_print_image('images/cross.png', true).'</a>';
                }

                $table->data[] = $data;
            }

            ui_pagination($count_maps, false, $offset);
            html_print_table($table);
            ui_pagination($count_maps, false, 0, 0, false, 'offset', true, 'pagination-bottom');
        } else {
            ui_print_info_message(['no_close' => true, 'message' => __('There are no maps defined.') ]);
        }

        if ($networkmaps_write || $networkmaps_manage) {
            echo "<div style='width: ".$table->width."; margin-top: 5px;'>";
            echo '<form method="post" action="index.php?sec=network&amp;sec2=operation/agentes/pandora_networkmap">';
            html_print_input_hidden('new_networkmap', 1);
            html_print_submit_button(__('Create network map'), 'crt', false, 'class="sub next" style="float: right;"');
            echo '</form>';
            echo '</div>';

            if (enterprise_installed()) {
                echo "<div style='width: ".$table->width."; margin-top: 5px;'>";
                echo '<form method="post" action="index.php?sec=network&amp;sec2=operation/agentes/pandora_networkmap">';
                html_print_input_hidden('new_empty_networkmap', 1);
                html_print_submit_button(__('Create empty network map'), 'crt', false, 'class="sub next" style="float: right; margin-right:20px;"');
                echo '</form>';
                echo '</div>';
            }
        }
    break;
}
