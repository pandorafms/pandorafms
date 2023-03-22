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

$gis_w = check_acl($config['id_user'], 0, 'MW', false, true, true);
$gis_m = check_acl($config['id_user'], 0, 'MM');
$access = ($gis_w == true) ? 'MW' : (($gis_m == true) ? 'MM' : 'MW');

if (!$gis_w && !$gis_m) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access map builder'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_gis.php';

$idMap = (int) get_parameter('map_id', 0);
$action = get_parameter('action', 'new_map');

$gis_map_group = db_get_value('group_id', 'tgis_map', 'id_tgis_map', $idMap);

if ($idMap > 0 && !check_acl_restricted_all($config['id_user'], $gis_map_group, 'MW') && !check_acl_restricted_all($config['id_user'], $gis_map_group, 'MW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access map builder'
    );
    include 'general/noaccess.php';
    return;
}

$sec2 = get_parameter_get('sec2');
$sec2 = safe_url_extraclean($sec2);

$sec = get_parameter_get('sec');
$sec = safe_url_extraclean($sec);

// Layers.
$layer_ids = get_parameter('layer_ids', []);
$layers = get_parameter('layers', []);
$layer_list = [];

foreach ($layer_ids as $layer_id) {
    if (empty($layers[$layer_id]) || empty($layers[$layer_id]['name'])) {
        continue;
    }

    $trimmed_name = trim($layers[$layer_id]['name']);
    if (empty($trimmed_name)) {
        continue;
    }

    $layer_list[] = [
        'id'               => (strpos($layer_id, 'new_') === false) ? (int) $layer_id : null,
        'layer_name'       => $trimmed_name,
        'layer_visible'    => ((int) $layers[$layer_id]['visible'] === 1),
        'layer_group'      => (int) $layers[$layer_id]['agents_from_group'],
        'layer_agent_list' => $layers[$layer_id]['agents'],
        'layer_group_list' => $layers[$layer_id]['groups'],
    ];
}

$next_action = 'new_map';

$buttons['gis_maps_list'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=godgismaps&sec2=operation/gis_maps/gis_map">'.html_print_image(
        'images/list.png',
        true,
        [
            'title' => __('GIS Maps list'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>',
];
if ($idMap) {
    $buttons['view_gis'] = [
        'active' => false,
        'text'   => '<a href="index.php?sec=gismaps&sec2=operation/gis_maps/render_view&map_id='.$idMap.'">'.html_print_image(
            'images/op_gis.png',
            true,
            [
                'title' => __('View GIS'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>',
    ];
}

// Header.
ui_print_standard_header(
    __('GIS Maps builder'),
    'images/gm_gis.png',
    false,
    'configure_gis_map_edit',
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

switch ($action) {
    case 'save_new':
        $map_name = get_parameter('map_name');
        $map_initial_longitude = get_parameter('map_initial_longitude');
        $map_initial_latitude = get_parameter('map_initial_latitude');
        $map_initial_altitude = get_parameter('map_initial_altitude');
        $map_zoom_level = get_parameter('map_zoom_level');
        $map_background = '';
        $map_default_longitude = get_parameter('map_default_longitude');
        $map_default_latitude = get_parameter('map_default_latitude');
        $map_default_altitude = get_parameter('map_default_altitude');
        $map_group_id = get_parameter('map_group_id');
        $map_levels_zoom = get_parameter('map_levels_zoom', 16);

        $map_connection_list_temp = explode(',', get_parameter('map_connection_list'));
        $listConnectionTemp = db_get_all_rows_sql('SELECT id_tmap_connection, conection_name, group_id FROM tgis_map_connection');


        foreach ($map_connection_list_temp as $index => $value) {
            $cleanValue = trim($value);
            if ($cleanValue == '') {
                unset($map_connection_list_temp[$index]);
            }
        }

        $map_connection_default = get_parameter('map_connection_default');

        $map_connection_list = [];
        foreach ($listConnectionTemp as $idMapConnection) {
            $default = 0;
            if ($map_connection_default == $idMapConnection['id_tmap_connection']) {
                $default = 1;
            }

            $map_connection_list[] = [
                'id_conection' => $idMapConnection['id_tmap_connection'],
                'default'      => $default,
            ];
        }

        $invalidFields = gis_validate_map_data(
            $map_name,
            $map_zoom_level,
            $map_initial_longitude,
            $map_initial_latitude,
            $map_initial_altitude,
            $map_default_longitude,
            $map_default_latitude,
            $map_default_altitude,
            $map_connection_list,
            $map_levels_zoom
        );

        if (empty($invalidFields)) {
            $idMap = gis_save_map(
                $map_name,
                $map_initial_longitude,
                $map_initial_latitude,
                $map_initial_altitude,
                $map_zoom_level,
                $map_background,
                $map_default_longitude,
                $map_default_latitude,
                $map_default_altitude,
                $map_group_id,
                $map_connection_list,
                $layer_list
            );
            if ($idMap) {
                $mapCreatedOk = true;
                $next_action = 'update_saved';
            } else {
                $next_action = 'save_new';
                $mapCreatedOk = false;
            }
        } else {
            $next_action = 'save_new';
            $mapCreatedOk = false;
        }

        ui_print_result_message(
            $mapCreatedOk,
            __('Map successfully created'),
            __('Map could not be created')
        );
    break;

    case 'new_map':
        $next_action = 'save_new';

        $map_name = '';
        $map_initial_longitude = '';
        $map_initial_latitude = '';
        $map_initial_altitude = '';
        $map_zoom_level = '';
        $map_background = '';
        $map_default_longitude = '';
        $map_default_latitude = '';
        $map_default_altitude = '';
        $map_group_id = '';
        $map_connection_list = [];
        $layer_list = [];
        $map_levels_zoom = 16;
    break;

    case 'edit_map':
        $next_action = 'update_saved';
    break;

    case 'update_saved':
        $map_name = get_parameter('map_name');
        $map_initial_longitude = get_parameter('map_initial_longitude');
        $map_initial_latitude = get_parameter('map_initial_latitude');
        $map_initial_altitude = get_parameter('map_initial_altitude');
        $map_zoom_level = get_parameter('map_zoom_level');
        $map_background = '';
        $map_default_longitude = get_parameter('map_default_longitude');
        $map_default_latitude = get_parameter('map_default_latitude');
        $map_default_altitude = get_parameter('map_default_altitude');
        $map_group_id = get_parameter('map_group_id');
        $map_levels_zoom = get_parameter('map_levels_zoom', 16);

        $map_connection_list_temp = explode(',', get_parameter('map_connection_list'));

        $listConnectionTemp = db_get_all_rows_sql('SELECT id_tmap_connection, conection_name, group_id FROM tgis_map_connection');

        foreach ($map_connection_list_temp as $index => $value) {
            $cleanValue = trim($value);
            if ($cleanValue == '') {
                unset($map_connection_list_temp[$index]);
            }
        }

        $map_connection_default = get_parameter('map_connection_default');

        $map_connection_list = [];
        foreach ($listConnectionTemp as $idMapConnection) {
            $default = 0;
            if ($map_connection_default == $idMapConnection['id_tmap_connection']) {
                $default = 1;
            }

            $map_connection_list[] = [
                'id_conection' => $idMapConnection['id_tmap_connection'],
                'default'      => $default,
            ];
        }

        $invalidFields = gis_validate_map_data(
            $map_name,
            $map_zoom_level,
            $map_initial_longitude,
            $map_initial_latitude,
            $map_initial_altitude,
            $map_default_longitude,
            $map_default_latitude,
            $map_default_altitude,
            $map_connection_list,
            $map_levels_zoom
        );

        if (empty($invalidFields) === true) {
            gis_update_map(
                $idMap,
                $map_name,
                $map_initial_longitude,
                $map_initial_latitude,
                $map_initial_altitude,
                $map_zoom_level,
                $map_background,
                $map_default_longitude,
                $map_default_latitude,
                $map_default_altitude,
                $map_group_id,
                $map_connection_list,
                $layer_list
            );
            $mapCreatedOk = true;
        } else {
            $next_action = 'update_saved';
            $mapCreatedOk = false;
        }

        ui_print_result_message(
            $mapCreatedOk,
            __('Map successfully update'),
            __('Map could not be updated')
        );

        $next_action = 'update_saved';
        html_print_input_hidden('map_id', $idMap);
    break;

    default:
        // Default.
    break;
}

?>

<script type="text/javascript">

var connectionMaps = [];

function isInt(x) {
    var y=parseInt(x);
    if (isNaN(y)) return false;
    return x==y && x.toString()==y.toString();
}

function deleteConnectionMap(idConnectionMap) {
    for (var index in connectionMaps) {
        
        //int because in the object array there are method as string
        if (isInt(index)) {
            if (connectionMaps[index] == idConnectionMap) {
                connectionMaps.splice(index, 1);
            }
        }
    }
    
    checked = $("#radiobtn0001", $("#map_connection_" + idConnectionMap)).attr('checked');
    $("#map_connection_" + idConnectionMap).remove();
    
    if (checked) {
        //Checked first, but not is index = 0 maybe.
        
        for (var index in connectionMaps) {
            
            //int because in the object array there are method as string
            if (isInt(index)) {
                $("#radiobtn0001", $("#map_connection_" + connectionMaps[index])).attr('checked', 'checked');
                break;
            }
        }
    }
}

function setFieldsRequestAjax(id_conexion) {
    if (confirm('<?php echo __('Do you want to use the default data from the connection?'); ?>')) {
        jQuery.ajax ({
            data: "page=operation/gis_maps/ajax&opt=get_data_conexion&id_conection=" + idConnectionMap,
            type: "GET",
            dataType: 'json',
            url: "ajax.php",
            success: function (data) {
                if (data.correct) {
                    $("input[name=map_initial_longitude]").val(data.content.initial_longitude);
                    $("input[name=map_initial_latitude]").val(data.content.initial_latitude);
                    $("input[name=map_initial_altitude]").val(data.content.initial_altitude);
                    $("input[name=map_default_longitude]").val(data.content.default_longitude);
                    $("input[name=map_default_latitude]").val(data.content.default_latitude);
                    $("input[name=map_default_altitude]").val(data.content.default_altitude);
                    $("input[name=map_zoom_level]").val(data.content.default_zoom_level);
                    $("input[name=map_levels_zoom]").val(data.content.num_zoom_levels);
                }
            }
        });
    }
}

function changeDefaultConection(id) {
    setFieldsRequestAjax(id);
}

function addConnectionMap() {
    idConnectionMap = $("#map_connection :selected").val();
    connectionMapName = $("#map_connection :selected").text();
    
    //Test if before just added
    for (var index in connectionMaps) {
        if (isInt(index)) {
            if (connectionMaps[index] == idConnectionMap) {
                alert("<?php echo __('The connection'); ?> " + connectionMapName + " <?php echo __('just added previously.'); ?>");

                return;
            }
        }
    }
    
    tableRows = $("#chunk_map_connection").clone();
    tableRows.attr('id','map_connection_' + idConnectionMap);
    $("input[name=map_connection_default]",tableRows).val(idConnectionMap);
    
    if (connectionMaps.length == 0) {
        //The first is checked
        $("#radiobtn0001", tableRows).attr('checked', 'checked');
        
        //Set the fields with conexion data (in ajax)
        setFieldsRequestAjax(idConnectionMap);
    }
    
    connectionMaps.push(idConnectionMap);
    
    $("#text-map_connection_name", tableRows).val(connectionMapName);
    $("#text-map_connection_name", tableRows).attr('name', 'map_connection_name_' + idConnectionMap);
    $("#delete_row", tableRows).attr('href', "javascript: deleteConnectionMap(" + idConnectionMap + ")");
    
    $("#map_connection").append(tableRows);
}

</script>

<?php
$url = 'index.php?sec='.$sec.'&sec2='.$sec2.'&map_id='.$idMap.'&action='.$next_action;
echo '<form action="'.$url.'" id="form_setup" method="post">';

// Load the data in edit or reload in update.
switch ($action) {
    case 'edit_map':
    case 'update_saved':
        $mapData = gis_get_map_data($idMap);

        $map_name = $mapData['map']['map_name'];
        $map_group_id = $mapData['map']['group_id'];
        $map_zoom_level = $mapData['map']['zoom_level'];
        $map_background = $mapData['map']['map_background'];
        $map_initial_longitude = $mapData['map']['initial_longitude'];
        $map_initial_latitude = $mapData['map']['initial_latitude'];
        $map_initial_altitude = $mapData['map']['initial_altitude'];
        $map_default_longitude = $mapData['map']['default_longitude'];
        $map_default_latitude = $mapData['map']['default_latitude'];
        $map_default_altitude = $mapData['map']['default_altitude'];

        $map_connection_list = $mapData['connections'];
        $map_levels_zoom = gis_get_num_zoom_levels_connection_default($map_connection_list);

        $layer_list = !empty($mapData['layers']) ? $mapData['layers'] : [];
    break;

    default:
        // Default.
    break;
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';

$table->data = [];

$table->data[0][0] = __('Map Name');
$table->data[0][1] = html_print_input_text('map_name', $map_name, '', 30, 60, true);
$table->rowspan[0][2] = 9;

$iconError = '';
if (isset($invalidFields['map_connection_list'])) {
    if ($invalidFields['map_connection_list']) {
        $iconError = html_print_image('images/dot_red.png', true);
    }
}

$listConnectionTemp = db_get_all_rows_sql('SELECT id_tmap_connection, conection_name, group_id FROM tgis_map_connection');
$listConnection = [];
foreach ($listConnectionTemp as $connectionTemp) {
    if (check_acl($config['id_user'], $connectionTemp['group_id'], 'MW') || check_acl($config['id_user'], $connectionTemp['group_id'], 'MM')) {
        $listConnection[$connectionTemp['id_tmap_connection']] = $connectionTemp['conection_name'];
    }
}

$table->data[1][0] = __('Add Map connection').$iconError;
$table->data[1][1] = "<table  class='no-class' border='0' id='map_connection'>
	<tr>
        <td>".html_print_select($listConnection, 'map_connection_list', '', '', '', '0', true)."
		</td>
		<td >
			<a href='javascript: addConnectionMap();'>".html_print_image(
            'images/add.png',
            true,
            ['class' => 'invert_filter main_menu_icon']
)."</a>
			<input type='hidden' name='map_connection_list' value='' id='map_connection_list' />
			<input type='hidden' name='layer_list' value='' id='layer_list' />
		</td>
	</tr> ".gis_add_conection_maps_in_form($map_connection_list).'
</table>';
$own_info = get_user_info($config['id_user']);

$return_all_group = false;

if (users_can_manage_group_all('MM') === true) {
    $return_all_group = true;
}

$table->data[2][0] = __('Group');
$table->data[2][1] = html_print_select_groups(
    false,
    'AR',
    $return_all_group,
    'map_group_id',
    $map_group_id,
    '',
    '',
    '',
    true,
    false,
    true,
    '',
    false,
    false,
    false,
    false,
    'id_grupo',
    false,
    false,
    false,
    '250px'
);

$table->data[3][0] = __('Default zoom');
$table->data[3][1] = html_print_input_text('map_zoom_level', $map_zoom_level, '', 2, 4, true).html_print_input_hidden(
    'map_levels_zoom',
    $map_levels_zoom,
    true
);

$table->data[4][0] = __('Center Latitude').':';
$table->data[4][1] = html_print_input_text('map_initial_latitude', $map_initial_latitude, '', 8, 8, true);

$table->data[5][0] = __('Center Longitude').':';
$table->data[5][1] = html_print_input_text('map_initial_longitude', $map_initial_longitude, '', 8, 8, true);

$table->data[6][0] = __('Center Altitude').':';
$table->data[6][1] = html_print_input_text('map_initial_altitude', $map_initial_altitude, '', 8, 8, true);

$table->data[7][0] = __('Default Latitude').':';
$table->data[7][1] = html_print_input_text('map_default_latitude', $map_default_latitude, '', 8, 8, true);

$table->data[8][0] = __('Default Longitude').':';
$table->data[8][1] = html_print_input_text('map_default_longitude', $map_default_longitude, '', 8, 8, true);

$table->data[9][0] = __('Default Altitude').':';
$table->data[9][1] = html_print_input_text('map_default_altitude', $map_default_altitude, '', 8, 8, true);

html_print_table($table);

$user_groups = users_get_groups($config['user'], 'AR', false);

echo '<h3>'.__('Layers').'</h3>';

$table->width = '100%';
$table->class = 'databox filters';
$table->valign = [];
$table->valign[0] = 'top';
$table->valign[1] = 'top';
$table->data = [];

$table->data[0][0] = '<h4>'.__('List of layers').'</h4>';
$table->data[0][1] = '<div class="right">'.html_print_button(__('New layer'), 'new_layer', false, 'newLayer();', 'class="sub add "', true).'</div>';

$table->data[1][0] = '<table class="databox" border="0" cellpadding="4" cellspacing="4" id="list_layers"></table>';
$table->data[1][1] = '<div id="form_layer" class="invisible">
		<table id="form_layer_table" class="" border="0" cellpadding="4" cellspacing="4">
			<tr>
				<td>'.__('Layer name').':</td>
				<td>'.html_print_input_text('layer_name_form', '', '', 20, 40, true).'</td>
				<td>'.__('Visible').':</td>
				<td>'.html_print_checkbox('layer_visible_form', 1, true, true).'</td>
			</tr>
			<tr>
				<td>'.__('Show agents from group').':</td>
                <td colspan="3">'.html_print_select($user_groups, 'layer_group_form', '-1', '', __('none'), '-1', true).'</td>
			</tr>
			<tr>
				<td colspan="4"><hr /></td>
			</tr>
			<tr>
				<td>'.__('Agent').':</td>
				<td colspan="3">';



$table->data[1][1] .= html_print_button(__('Add agent'), 'add_agent', true, '', ['mode' => 'secondary', 'icon' => 'next'], true);

$params = [];
$params['return'] = true;
$params['show_helptip'] = true;
$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_id'] = 'hidden-agent_id';
$params['hidden_input_idagent_name'] = 'agent_id';
$params['input_name'] = 'agent_alias';
$params['value'] = '';
$params['javascript_function_action_after_select'] = 'active_button_add_agent';
$params['javascript_is_function_select'] = true;
$params['disabled_javascript_on_blur_function'] = false;

$table->data[1][1] .= ui_print_agent_autocomplete_input($params);



$table->data[1][1] .= '</td>
			</tr>
			<tr>
				<td colspan="4">
					<h4>'.__('List of Agents to be shown in the layer').'</h4>
					<table class="databox" border="0" cellpadding="4" cellspacing="4" id="list_agents">
					</table>
				</td>
			</tr>';

// Group items.
$group_select = html_print_select_groups($config['id_user'], 'AR', false, 'layer_group_id', '', '', '', 0, true);
$params = [];
$params['return'] = true;
$params['show_helptip'] = true;
$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_id'] = 'hidden-agent_id_for_data';
$params['hidden_input_idagent_name'] = 'agent_id_for_data';
$params['input_name'] = 'agent_alias_for_data';
$params['value'] = '';
$params['javascript_function_action_after_select'] = 'toggleAddGroupBtn';
$params['selectbox_group'] = 'layer_group_id';
$params['javascript_is_function_select'] = true;

// Filter by group.
$params['disabled_javascript_on_blur_function'] = false;
$agent_for_group_input = ui_print_agent_autocomplete_input($params);
$add_group_btn = html_print_button(__('Add'), 'add_group', true, '', ['mode' => 'secondary', 'icon' => 'next'], true);

$table->data[1][1] .= '<tr><td colspan="4"><hr /></td></tr>
			<tr>
				<td>'.__('Group').':</td>
				<td colspan="3">'.$group_select.'</td>
			</tr>
			<tr>
				<td>'.__('Use the data of this agent').':</td>
				<td colspan="3">'.$agent_for_group_input.'</td>
			</tr>
			<tr>
				<td colspan="4" align="right">'.$add_group_btn.'</td>
			</tr>
			<tr>
				<td colspan="4">
					<h4>'.__('List of groups to be shown in the layer').'</h4>
					<table class="databox" border="0" cellpadding="4" cellspacing="4" id="list_groups">
					</table>
				</td>
			</tr>';

$table->data[1][1] .= '<tr>
				<td align="right" colspan="4">'.html_print_button(__('Save Layer'), 'save_layer', false, 'javascript:saveNewLayer();', 'class="sub wand"', true).'
					'.html_print_input_hidden('current_edit_layer_id', '', true).'
				</td>
			</tr>
		</table>
	</div>';

html_print_table($table);

switch ($action) {
    case 'save_new':
    case 'edit_map':
    case 'update_saved':
        if (empty($invalidFields) === true) {
            $action_button = html_print_submit_button(_('Save map'), 'save_button', false, ['mode' => 'primary', 'icon' => 'next'], true);
        } else {
            $action_button = html_print_submit_button(_('Update map'), 'update_button', false, ['mode' => 'primary', 'icon' => 'next'], true);
        }
    break;

    case 'new_map':
        $action_button = html_print_submit_button(_('Save map'), 'save_button', false, ['mode' => 'primary', 'icon' => 'next'], true);
    break;

    default:
        // Default.
    break;
}

html_print_action_buttons(
    $action_button,
    ['type' => 'form_action']
);

echo '</form>';


// -------------------------INI CHUNKS---------------------------------------
?>

<table style="visibility: hidden;">
    <tbody id="chunk_map_connection">
        <tr class="row_0">
            <td><?php html_print_input_text('map_connection_name', $map_name, '', 20, 40, false, true); ?></td>
            <td><?php html_print_radio_button_extended('map_connection_default', '', '', true, false, 'changeDefaultConection(this.value)', ''); ?></td>
            <td><a id="delete_row" href="none">
            <?php
            html_print_image(
                'images/delete.svg',
                false,
                [
                    'alt'   => '',
                    'class' => 'invert_filter main_menu_icon',
                ]
            );
            ?>
                </a></td>
        </tr>
    </tbody>
</table>

<?php
// -------------------------END CHUNKS---------------------------------------
ui_require_css_file('cluetip', 'include/styles/js/');
ui_require_jquery_file('cluetip');
ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('json');
?>
<script type="text/javascript">

function active_button_add_agent() {
    $("#button-add_agent").prop("disabled", false);
}

function addAgentClick (event) {
    var $layerFormAgentIdInput = $("#hidden-agent_id");
    var $layerFormAgentAliasInput = $("#text-agent_alias");
    
    var agentId = Number.parseInt($layerFormAgentIdInput.val());
    var agentAlias = $layerFormAgentAliasInput.val();
    var layerId = $("input#hidden-current_edit_layer_id").val();
    
    if (Number.isNaN(agentId) || agentId === 0 || agentAlias.length === 0) return;
    
    addAgentRow(layerId, agentId, agentAlias);
    
    // Clear agent inputs
    $layerFormAgentIdInput.val("");
    $layerFormAgentAliasInput.val("");

    $("#button-add_agent").prop("disabled", true);
}

function toggleAddGroupBtn () {
    var groupId = Number.parseInt($("select#layer_group_id").val());
    var existGroupId = $("table#list_groups tr.groups_list_item[data-group-id='" + groupId + "']").length > 0;
    var agentId = Number.parseInt($("input#hidden-agent_id_for_data").val());
    var agentAlias = $("input#text-agent_alias_for_data").val();

    var enabled = (
        !existGroupId
        && !Number.isNaN(groupId)
        && groupId > 0
        && !Number.isNaN(agentId)
        && agentId > 0
        && agentAlias.length > 0
    );
    
    $("#button-add_group").prop("disabled", !enabled);
}

function addGroupClick (event) {
    var $layerFormGroupIdInput = $("select#layer_group_id");
    var $layerFormAgentIdInput = $("input#hidden-agent_id_for_data");
    var $layerFormAgentAliasInput = $("input#text-agent_alias_for_data");
    
    var layerId = $("input#hidden-current_edit_layer_id").val();
    var groupId = Number.parseInt($layerFormGroupIdInput.val());
    var groupName = $layerFormGroupIdInput.find(":selected").text();
    var agentId = Number.parseInt($layerFormAgentIdInput.val());
    var agentAlias = $layerFormAgentAliasInput.val();

    var valid = (
        !Number.isNaN(groupId)
        && groupId > 0
        && groupName.length > 0
        && !Number.isNaN(agentId)
        && agentId > 0
        && agentAlias.length > 0
    );
    
    if (!valid) return;
    
    addGroupRow(layerId, groupId, groupName, agentId, agentAlias);
    
    // Clear inputs
    // $layerFormGroupIdInput.val(0);
    $layerFormAgentIdInput.val("");
    $layerFormAgentAliasInput.val("");

    $("#button-add_group").prop("disabled", true);
}

function moveLayerRowUpOnClick (event) {
    var $row = $(event.currentTarget).parent().parent();
    $row.insertBefore($row.prev());
}

function moveLayerRowDownOnClick (event) {
    var $row = $(event.currentTarget).parent().parent();
    $row.insertAfter($row.next());
}

function removeLayerRowOnClick (event) {
    var $layerRow = $(event.currentTarget).parent().parent();
    var layerRowId = $layerRow.find("input.layer_id").val();
    var layerEditorId = $("input#hidden-current_edit_layer_id").val();
    if (layerRowId == layerEditorId) hideLayerEditor();
    // Remove row
    $(event.currentTarget).parent().parent().remove();
}

function hideLayerEditor () {
    // Clean editor
    cleanLayerEditor();
    // Hide editor
    $("div#form_layer").hide();
}

function showLayerEditor (layerId) {
    var $layerSaveBtn = $("input#button-save_layer");

    // Clean editor
    cleanLayerEditor();
    
    if (layerId) {
        // Hide save layer button
        $layerSaveBtn.hide();
        // Hightlight selected row
        hightlightRow(layerId);
        // Get layer data
        var data = getLayerData(layerId);
        // Fill editor with data
        setLayerEditorData(data);
        // Bind editor events
        bindLayerEditorEvents(layerId);
    } else {
        // Show save layer button
        $layerSaveBtn.show();
        // Remove the hightlight
        hightlightRow();
    }

    // Show editor (if hidden)
    $("div#form_layer").show();
}

function getLayerData (layerId) {
    var $layerRow = $("tr#layer_row_" + layerId);
    var layerName = $layerRow.find("input.layer_name").val();
    var layerVisible = $layerRow.find("input.layer_visible").val() == 1;
    var layerAgentsFromGroup = $layerRow.find("input.layer_agents_from_group").val();
    var layerAgents = $layerRow.find("input.layer_agent_alias").map(function () {
        return {
            "id": $(this).data("agent-id"),
            "alias": $(this).val()
        };
    }).get();
    var layerGroups = $layerRow.find("input.layer_group_id").map(function () {
        var groupId = $(this).val();
        var groupName = $(this).siblings("input.layer_group_name[data-group-id='" + groupId + "']").val();
        var agentId = $(this).siblings("input.layer_agent_id_for_data[data-group-id='" + groupId + "']").val();
        var agentAlias = $(this).siblings("input.layer_agent_alias_for_data[data-group-id='" + groupId + "']").val();
        
        return {
            "id": groupId,
            "name": groupName,
            "agentId": agentId,
            "agentAlias": agentAlias
        };
    }).get();

    return {
        id: layerId,
        name: layerName,
        visible: layerVisible,
        agentsFromGroup: layerAgentsFromGroup,
        agents: layerAgents,
        groups: layerGroups
    }
}

function setLayerEditorData (data) {
    if (data == null) data = {};
    // Set defaults
    data = {
        id: data.id || 0,
        name: data.name || "",
        visible: data.visible != null ? !!data.visible : true,
        agentsFromGroup: data.agentsFromGroup || -1,
        agents: data.agents || [],
        groups: data.groups || []
    }

    var $layerFormIdInput = $("input#hidden-current_edit_layer_id");
    var $layerFormNameInput = $("input#text-layer_name_form");
    var $layerFormVisibleCheckbox = $("input#checkbox-layer_visible_form");
    var $layerFormAgentsFromGroupSelect = $("#layer_group_form");
    var $layerFormAgentInput = $("input#text-agent_alias");
    var $layerFormAgentButton = $("button#button-add_agent");
    var $layerFormAgentsListItems = $("tr.agents_list_item");
    var $layerFormGroupsListItems = $("tr.groups_list_item");

    $layerFormIdInput.val(data.id);
    $layerFormNameInput.val(data.name);
    $layerFormVisibleCheckbox.prop("checked", data.visible);
    $(`#layer_group_form option[value=${data.agentsFromGroup}]`).attr('selected', 'selected');
    $(`#layer_group_form`).trigger('change');
    $layerFormAgentInput.val("");
    $layerFormAgentButton.prop("disabled", true);
    $layerFormAgentsListItems.remove();
    $layerFormGroupsListItems.remove();

    var $tableAgents = $("table#list_agents");
    data.agents.forEach(function (agent) {
        addAgentRow(data.id, agent.id, agent.alias);
    });

    var $tableGroups = $("table#list_groups");
    data.groups.forEach(function (group) {
        addGroupRow(data.id, group.id, group.name, group.agentId, group.agentAlias);
    });
}

function newLayer () {
    showLayerEditor(null);
}

function saveNewLayer () {
    var $layerFormNameInput = $("input#text-layer_name_form");
    var $layerFormVisibleCheckbox = $("input#checkbox-layer_visible_form");
    var $layerFormAgentsFromGroupSelect = $("select#layer_group_form");
    var $layerFormAgentsListItems = $("tr.agents_list_item > td > span.agent_alias");
    var $layerFormGroupsListItems = $("tr.groups_list_item");
    var newLayerId = "new_" + ($("tr.layer_row").length + 1);

    addLayerRow(newLayerId, {
        id: newLayerId,
        name: $layerFormNameInput.val(),
        visible: $layerFormVisibleCheckbox.prop("checked"),
        agentsFromGroup: $layerFormAgentsFromGroupSelect.val(),
        agents: $layerFormAgentsListItems.map(function () {
            return {
                "id": $(this).data("agent-id"),
                "alias": $(this).text()
            };
        }).get(),
        groups: $layerFormGroupsListItems.map(function () {
            return {
                "id": $(this).data("group-id"),
                "name": $(this).data("group-name"),
                "agentId": $(this).data("agent-id"),
                "agentAlias": $(this).data("agent-alias")
            };
        }).get()
    });
}

function cleanLayerEditor () {
    // Clear editor events
    unbindLayerEditorEvents();
    // Add default data to the editor
    setLayerEditorData();
}

function bindLayerEditorEvents (layerId) {
    var $layerFormNameInput = $("input#text-layer_name_form");
    var $layerFormVisibleCheckbox = $("input#checkbox-layer_visible_form");
    var $layerFormAgentsFromGroupSelect = $("select#layer_group_form");

    var $layerRow = $("tr#layer_row_" + layerId);

    if ($layerRow.length === 0) return;

    $layerFormNameInput.bind("change", function (event) {
        var name = event.currentTarget.value;
        $layerRow.find("span.layer_name").html(name);
        $layerRow.find("input.layer_name").val(name);
    });
    $layerFormVisibleCheckbox.bind("click", function (event) {
        var visible = $(event.currentTarget).prop("checked");
        $layerRow.find("input.layer_visible").val(visible ? 1 : 0);
    });
    $layerFormAgentsFromGroupSelect.bind("change", function (event) {
        var group = event.currentTarget.value;
        $layerRow.find("input.layer_agents_from_group").val(group);
    });
}

function unbindLayerEditorEvents () {
    var $layerFormNameInput = $("input#text-layer_name_form");
    var $layerFormVisibleCheckbox = $("input#checkbox-layer_visible_form");
    var $layerFormAgentsFromGroupSelect = $("select#layer_group_form");

    $layerFormNameInput.unbind("change");
    $layerFormVisibleCheckbox.unbind("click");
    $layerFormAgentsFromGroupSelect.val('-1');
}

function getAgentRow (layerId, agentId, agentAlias) {
    var $row = $("<tr class=\"agents_list_item\" />");
    var $nameCol = $("<td />");
    var $deleteCol = $("<td />");

    var $agentAlias = $("<span class=\"agent_alias\" data-agent-id=\"" + agentId + "\">" + agentAlias + "</span>");
    var $removeBtn = $('<a class="delete_row" href="javascript:" <?php echo html_print_image('images/delete.svg', false, ['class' => 'invert_filter main_menu_icon']); ?> </a>');

    $removeBtn.click(function (event) {
        var $layerRow = $("tr#layer_row_" + layerId);

        if ($layerRow.length > 0) {
            $layerRow.find("input.layer_agent_id[data-agent-id='" + agentId + "']").remove();
            $layerRow.find("input.layer_agent_alias[data-agent-id='" + agentId + "']").remove();
        }

        var $agentListItemRow = $(event.currentTarget).parent().parent();
        $agentListItemRow.remove();
    });

    $nameCol.append($agentAlias);
    $deleteCol.append($removeBtn);

    $row.append($nameCol).append($deleteCol);

    return $row;
}

function addAgentRow (layerId, agentId, agentAlias) {
    if (agentId == null || agentId == 0 || agentAlias.length === 0) return;

    var $layerRow = $("tr#layer_row_" + layerId);
    if ($layerRow && $layerRow.find("input.layer_agent_id[value='" + agentId + "']").length === 0) {
        $layerRow
            .find("td:first-child")
                .append(getLayerAgentIdInput(layerId, agentId))
                .append(getLayerAgentAliasInput(layerId, agentId, agentAlias));
    }

    $("table#list_agents").append(getAgentRow(layerId, agentId, agentAlias));
}

function getLayerAgentIdInput (layerId, agentId) {
    return $("<input class=\"layer_agent_id\" type=\"hidden\" data-agent-id=\"" + agentId + "\" name=\"layers[" + layerId + "][agents][" + agentId + "][id]\" value=\"" + agentId + "\">");
}

function getLayerAgentAliasInput (layerId, agentId, agentAlias) {
    return $("<input class=\"layer_agent_alias\" type=\"hidden\" data-agent-id=\"" + agentId + "\" name=\"layers[" + layerId + "][agents][" + agentId + "][alias]\" value=\"" + agentAlias + "\">");
}

function getGroupRow (layerId, groupId, groupName, agentId, agentAlias) {
    var $row = $("<tr class=\"groups_list_item\" data-group-id=\"" + groupId + "\" data-group-name=\"" + groupName + "\" data-agent-id=\"" + agentId + "\" data-agent-alias=\"" + agentAlias + "\" />");
    var $nameCol = $("<td />");
    var $deleteCol = $("<td />");

    var $groupName = $("<span class=\"group_desc\">"
        + groupName
        + " ("
        + "<?php echo __('Using data from'); ?> "
        + "<i>" + agentAlias + "</i>"
        + ")"
        + "</span>");
    var $removeBtn = $('<a class="delete_row" href="javascript:;"><?php echo html_print_image('images/delete.svg', true, ['class' => 'invert_filter main_menu_icon']); ?></a>');

    $removeBtn.click(function (event) {
        var $layerRow = $("tr#layer_row_" + layerId);

        if ($layerRow.length > 0) {
            $layerRow.find("input.layer_group_id[data-group-id='" + groupId + "']").remove();
            $layerRow.find("input.layer_group_name[data-group-id='" + groupId + "']").remove();
            $layerRow.find("input.layer_agent_id_for_data[data-group-id='" + groupId + "']").remove();
            $layerRow.find("input.layer_agent_alias_for_data[data-group-id='" + groupId + "']").remove();
        }

        var $groupListItemRow = $(event.currentTarget).parent().parent();
        $groupListItemRow.remove();
    });

    $nameCol.append($groupName);
    $deleteCol.append($removeBtn);

    $row.append($nameCol).append($deleteCol);

    return $row;
}

function addGroupRow (layerId, groupId, groupName, agentId, agentAlias) {
    if (
        groupId == null ||
        groupId == 0 ||
        groupName.length === 0 ||
        agentId == null ||
        agentId == 0 ||
        agentAlias.length === 0
    ) return;

    var $layerRow = $("tr#layer_row_" + layerId);
    if ($layerRow && $layerRow.find("input.layer_group_id[value='" + groupId + "']").length === 0) {
        $layerRow
            .find("td:first-child")
                .append(getLayerGroupIdInput(layerId, groupId))
                .append(getLayerGroupNameInput(layerId, groupId, groupName))
                .append(getLayerAgentIdForDataInput(layerId, groupId, agentId))
                .append(getLayerAgentAliasForDataInput(layerId, groupId, agentAlias));
    }

    $("table#list_groups").append(getGroupRow(layerId, groupId, groupName, agentId, agentAlias));
}

function getLayerGroupIdInput (layerId, groupId) {
    return $("<input class=\"layer_group_id\" type=\"hidden\" data-group-id=\"" + groupId + "\" name=\"layers[" + layerId + "][groups][" + groupId + "][id]\" value=\"" + groupId + "\">");
}

function getLayerGroupNameInput (layerId, groupId, groupName) {
    return $("<input class=\"layer_group_name\" type=\"hidden\" data-group-id=\"" + groupId + "\" name=\"layers[" + layerId + "][groups][" + groupId + "][name]\" value=\"" + groupName + "\">");
}

function getLayerAgentIdForDataInput (layerId, groupId, agentId) {
    return $("<input class=\"layer_agent_id_for_data\" type=\"hidden\" data-group-id=\"" + groupId + "\" name=\"layers[" + layerId + "][groups][" + groupId + "][agent_id]\" value=\"" + agentId + "\">");
}

function getLayerAgentAliasForDataInput (layerId, groupId, agentAlias) {
    return $("<input class=\"layer_agent_alias_for_data\" type=\"hidden\" data-group-id=\"" + groupId + "\" name=\"layers[" + layerId + "][groups][" + groupId + "][agent_alias]\" value=\"" + agentAlias + "\">");
}

function getLayerRow (layerId, layerData) {
    var $row = $("<tr id=\"layer_row_" + layerId + "\" class=\"layer_row\" />");
    var $nameCol = $("<td />");
    var $sortCol = $("<td />");
    var $editCol = $("<td />");
    var $deleteCol = $("<td />");

    var $layerIdInput = $("<input class=\"layer_id\" type=\"hidden\" name=\"layer_ids[]\" value=\"" + layerId + "\">");
    var $layerNameInput = $("<input class=\"layer_name\" type=\"hidden\" name=\"layers[" + layerId + "][name]\" value=\"" + layerData.name + "\">");
    var $layerVisibleInput = $("<input class=\"layer_visible\" type=\"hidden\" name=\"layers[" + layerId + "][visible]\" value=\"" + (layerData.visible ? 1 : 0) + "\">");
    var $layerAgentsFromGroupInput = $("<input class=\"layer_agents_from_group\" type=\"hidden\" name=\"layers[" + layerId + "][agents_from_group]\" value=\"" + layerData.agentsFromGroup + "\">");

    var $layerName = $("<span class=\"layer_name\">" + layerData.name + "</span>");
    var $sortUpBtn = $("<a class=\"up_arrow\" href=\"javascript:;\" />");
    var $sortDownBtn = $("<a class=\"down_arrow\" href=\"javascript:;\" />");
    var $editBtn = $('<a class="edit_layer" href="javascript:;"><?php echo html_print_image('images/edit.svg', true, ['class' => 'invert_filter main_menu_icon']); ?></a>');
    var $removeBtn = $('<a class="delete_row" href="javascript:;"><?php echo html_print_image('images/delete.svg', true, ['class' => 'invert_filter main_menu_icon']); ?></a>');

    $sortUpBtn.click(moveLayerRowUpOnClick);
    $sortDownBtn.click(moveLayerRowDownOnClick);
    $editBtn.click(function () { showLayerEditor(layerId); });
    $removeBtn.click(removeLayerRowOnClick);

    $nameCol
        .append($layerName)
        .append($layerIdInput)
        .append($layerNameInput)
        .append($layerVisibleInput)
        .append($layerAgentsFromGroupInput);
    
    if (layerData.agents && layerData.agents.length > 0) {
        layerData.agents.forEach(function (agent) {
            $nameCol.append(getLayerAgentIdInput(layerId, agent.id));
            $nameCol.append(getLayerAgentAliasInput(layerId, agent.id, agent.alias));
        });
    }

    if (layerData.groups && layerData.groups.length > 0) {
        layerData.groups.forEach(function (group) {
            $nameCol.append(getLayerGroupIdInput(layerId, group.id));
            $nameCol.append(getLayerGroupNameInput(layerId, group.id, group.name));
            $nameCol.append(getLayerAgentIdForDataInput(layerId, group.id, group.agentId));
            $nameCol.append(getLayerAgentAliasForDataInput(layerId, group.id, group.agentAlias));
        });
    }

    $sortCol
        .append($sortUpBtn)
        .append($sortDownBtn);
    $editCol
        .append($editBtn);
    $deleteCol
        .append($removeBtn);

    $row
        .append($nameCol)
        .append($sortCol)
        .append($editCol)
        .append($deleteCol);

    return $row;
}

function addLayerRow (layerId, layerData) {
    $("table#list_layers").append(getLayerRow(layerId, layerData));
    showLayerEditor(layerId);
}

function hightlightRow (layerId) {
    var highlightColor = "#E9F3D2";
    $("tr.layer_row").css("background", "");
    $("tr#layer_row_" + layerId).css("background", highlightColor);
}

function existInvalidLayerNames () {
    var exist = false;
    $("table#list_layers input.layer_name").each(function () {
        if ($(this).val().trim().length === 0) {
            exist = true;
            return false; // Break jQuery object each
        }
    });
    
    return exist;
}

function onFormSubmit (event) {
    // Validate layer names
    if (existInvalidLayerNames()) {
        event.preventDefault();
        event.stopPropagation();
        alert("<?php echo __('Empty layer names are not supported'); ?>");
        return false;
    }
    // Save connection list
    $('#map_connection_list').val(connectionMaps.toString());
}

function onLayerGroupIdChange (event) {
    // Clear agent inputs
    $("input#hidden-agent_id_for_data").val(0);
    $("input#text-agent_alias_for_data").val("");
    toggleAddGroupBtn();
}

// Bind events
$("form#form_setup").submit(onFormSubmit);
$("button#button-add_agent").click(addAgentClick);
$("select#layer_group_id").change(onLayerGroupIdChange);
$("button#button-add_group").click(addGroupClick);

// Populate layer list
var layers = <?php echo json_encode($layer_list); ?>;
layers.forEach(function (layer) {
    $("table#list_layers").append(
        getLayerRow(layer["id"], {
            name: layer["layer_name"],
            visible: Number.parseInt(layer["layer_visible"]),
            agentsFromGroup: layer["layer_group"],
            agents: layer["layer_agent_list"],
            groups: (layer["layer_group_list"] || []).map(function (group) {
                group.agentId = group["agent_id"];
                group.agentAlias = group["agent_alias"];
                return group;
            })
        })
    );
});

</script>
