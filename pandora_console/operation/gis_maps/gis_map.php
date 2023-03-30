<?php
/**
 * Pandora FMS- http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Login check
check_login();

// Load global vars
global $config;

require_once 'include/functions_gis.php';

$buttons['gis_maps_list'] = [
    'active' => true,
    'text'   => '<a href="index.php?sec=godgismaps&sec2=operation/gis_maps/gis_map">'.html_print_image(
        'images/logs@svg.svg',
        true,
        [
            'title' => __('GIS Maps list'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

// Header.
ui_print_standard_header(
    __('List of GIS maps'),
    'images/op_gis.png',
    false,
    '',
    false,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Topology maps'),
        ],
        [
            'link'  => '',
            'label' => __('GIS Maps'),
        ],
    ]
);

$own_info = get_user_info($config['id_user']);
if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'MM')) {
    $display_default_column = true;
} else {
    $display_default_column = false;
}

$edit_gis_maps = false;
if (check_acl($config['id_user'], 0, 'MW') || check_acl($config['id_user'], 0, 'MM')) {
    $edit_gis_maps = true;
}



if (is_ajax()) {
    $action = get_parameter('action');
    $id_map = get_parameter('id_map');

    // Set to not default the actual default map
    $returnOperationDB = db_process_sql_update('tgis_map', ['default_map' => 0], ['default_map' => 1]);

    // Set default the new default map
    $returnOperationDB = db_process_sql_update('tgis_map', ['default_map' => 1], ['id_tgis_map' => $id_map]);

    if ($returnOperationDB === false) {
        $data['correct'] = false;
    } else {
        $data['correct'] = true;
    }

    echo json_encode($data);

    return;
}

$action = get_parameter('action');
switch ($action) {
    case 'delete_map':
        $idMap = get_parameter('map_id');
        $result = gis_delete_map($idMap);

        ui_print_result_message(
            $result,
            __('Successfully deleted'),
            __('Could not be deleted')
        );

    break;
}


$maps = gis_get_maps();


$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table';

$table->head = [];
$table->head['name'] = __('Name');
$table->head['group'] = __('Group');
if ($edit_gis_maps) {
    if ($display_default_column) {
        $table->head['default'] = __('Default');
    }

    $table->head['op'] = '<span title="Operations">'.__('Op.').'</span>';
}

$table->headstyle = [];
$table->headstyle['name'] = 'text-align: left;';
$table->headstyle['group'] = 'text-align: center;';
if ($edit_gis_maps) {
    if ($display_default_column) {
        $table->headstyle['default'] = 'text-align: center;';
    }

    $table->headstyle['op'] = 'text-align: center;';
}

$table->size = [];
$table->size['name'] = '80%';
$table->size['group'] = '30';
if ($edit_gis_maps) {
    if ($display_default_column) {
        $table->size['default'] = '30';
    }

    $table->size['op'] = '60';
}

$table->align = [];
$table->align['name'] = 'left';
$table->align['group'] = 'center';
if ($edit_gis_maps) {
    if ($display_default_column) {
        $table->align['default'] = 'center';
    }

    $table->align['op'] = 'center';
}

$table->data = [];

$rowPair = true;
$iterator = 0;

if ($maps !== false) {
    foreach ($maps as $map) {
        if (!check_acl($config['id_user'], $map['group_id'], 'MR')
            && !check_acl($config['id_user'], $map['group_id'], 'MW')
            && !check_acl($config['id_user'], $map['group_id'], 'MM')
        ) {
            continue;
        }

        if ($rowPair) {
            $table->rowclass[$iterator] = 'rowPair';
        } else {
            $table->rowclass[$iterator] = 'rowOdd';
        }

        $rowPair = !$rowPair;
        $iterator++;

        $data = [];

        $data['name'] = '<a href="index.php?sec=gismaps&amp;sec2=operation/gis_maps/render_view&amp;map_id='.$map['id_tgis_map'].'">'.$map['map_name'].'</a> ';
        $data['group'] = ui_print_group_icon($map['group_id'], true);

        $data['default'] = '';
        $data['op'] = '';

        if (check_acl_restricted_all($config['id_user'], $map['group_id'], 'MW')
            || check_acl_restricted_all($config['id_user'], $map['group_id'], 'MM')
        ) {
            if (check_acl_restricted_all($config['id_user'], 0, 'MM')) {
                $checked = false;
                if ($map['default_map']) {
                    $checked = true;
                    $defaultMapId = $map['id_tgis_map'];
                }

                $data['default'] = html_print_radio_button_extended('default_map', $map['id_tgis_map'], '', $checked, false, 'setDefault('.$map['id_tgis_map'].');', '', true);
            }

            $table->cellclass[]['op'] = 'table_action_buttons';
            $data['op'] = '<a href="index.php?sec=godgismaps&amp;sec2=godmode/gis_maps/configure_gis_map&map_id='.$map['id_tgis_map'].'&amp;action=edit_map">'.html_print_image('images/edit.svg', true, ['title' => __('Edit')]).'</a>'.'<a href="index.php?sec=godgismaps&amp;sec2=operation/gis_maps/gis_map&amp;map_id='.$map['id_tgis_map'].'&amp;action=delete_map" onclick="return confirmDelete();">'.html_print_image('images/delete.svg', true, ['title' => __('Delete'), 'class' => 'invert_filter main_menu_icon']).'</a>';
        }

        array_push($table->data, $data);
    }
}

if (!empty($table->data)) {
    html_print_table($table);
} else {
    echo '<div class="nf">'.__('No maps found').'</div>';
}

if ($edit_gis_maps) {
    echo '<form action="index.php?sec=godgismaps&amp;sec2=godmode/gis_maps/configure_gis_map" method="post">';
    html_print_input_hidden('action', 'new_map');
    $ActionButton = html_print_submit_button(
        __('Create'),
        'create',
        false,
        [
            'class' => 'sub ok submitButton',
            'icon'  => 'next',
        ],
        true
    );
    echo '<div class="action-buttons">'.html_print_action_buttons($ActionButton, ['type' => 'form_action'], true).'</div>';
    echo '</form>';
}

unset($table);
?>

<script type="text/javascript">
    var defaultMapId = "<?php echo $defaultMapId; ?>";
    
    function confirmDelete() {
        if (confirm('<?php echo __('Caution: Do you want delete the map?'); ?>'))
            return true;
        
        return false;
    }
    
    function setDefault(id_tgis_map) {
        if (confirm('<?php echo __('Do you want to set default the map?'); ?>')) {
            jQuery.ajax ({
                data: "page=operation/gis_maps/gis_map&action=set_default&id_map="  + id_tgis_map,
                type: "POST",
                dataType: 'json',
                url: "ajax.php",
                success: function (data) {
                    if (data.correct == 0) {
                        alert("<?php echo __('There was error on setup the default map.'); ?>");
                    }
                }
            });
        }
        else {
            jQuery.each($("input[name=default_map]"), function() {
                if ($(this).val() == defaultMapId) {
                    $(this).attr("checked", "checked");
                }
                else {
                    $(this).removeAttr("checked");
                }
            });
        }
    }
</script>
