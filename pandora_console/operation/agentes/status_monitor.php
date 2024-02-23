<?php
/**
 * Monitor Status View.
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Monitoring.
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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
use PandoraFMS\Enterprise\Metaconsole\Node;
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'AR')
    && ! check_acl($config['id_user'], 0, 'AW')
    && ! check_acl($config['id_user'], 0, 'AM')
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent Management'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';
enterprise_include_once('include/functions_metaconsole.php');

$isFunctionPolicies = enterprise_include_once('include/functions_policies.php');

$buttons = [];
$subpage = '';
if (is_metaconsole() === false) {
    $section = (string) get_parameter('section', 'view');

    $buttons['fields'] = [
        'active'    => false,
        'text'      => '<a href="index.php?sec=view&sec2=operation/agentes/status_monitor&section=fields">'.html_print_image(
            'images/edit_columns@svg.svg',
            true,
            [
                'title' => __('Custom fields'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>',
        'operation' => true,
    ];

    $buttons['view'] = [
        'active'    => false,
        'text'      => '<a href="index.php?sec=view&sec2=operation/agentes/status_monitor">'.html_print_image(
            'images/logs@svg.svg',
            true,
            [
                'title' => __('View'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>',
        'operation' => true,
    ];

    switch ($section) {
        case 'fields':
            $buttons['fields']['active'] = true;
            $subpage = ' &raquo; '.__('Custom fields');
        break;

        default:
            $buttons['view']['active'] = true;
        break;
    }
}

if (is_metaconsole() === false) {
    if ($section == 'fields') {
        include_once $config['homedir'].'/godmode/agentes/status_monitor_custom_fields.php';
        exit();
    }
} else {
    $section = (string) get_parameter('sec', 'estado');
}

$recursion = get_parameter_switch('recursion', false);

if ($recursion === false) {
    $recursion = get_parameter('recursion', false);
}

$ag_freestring = (string) get_parameter('ag_freestring');
$moduletype = (string) get_parameter('moduletype');
$datatype = (string) get_parameter('datatype');
$ag_modulename = (string) get_parameter('ag_modulename');
$refr = (int) get_parameter('refr', 0);
$offset = (int) get_parameter('offset', 0);
$status = (int) get_parameter('status', 4);
$modulegroup = (int) get_parameter('modulegroup', -1);
$tag_filter = get_parameter('tag_filter', [0]);
$min_hours_status = (string) get_parameter('min_hours_status', '');
// Sort functionality.
$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
// When the previous page was a visualmap and show only one module.
$id_module = (int) get_parameter('id_module', 0);
$ag_custom_fields = (array) get_parameter('ag_custom_fields', []);
$module_option = (int) get_parameter('module_option', 1);

$not_condition = (string) get_parameter('not_condition', '');

$is_none = 'All';
if ($not_condition !== '') {
    $is_none = 'None';
    $not_condition = 'NOT';
}

// If option not_condition is enabled, the conditions of the queries are reversed.
$condition_query = '=';
if ($not_condition !== '') {
    $condition_query = '!=';
}

$autosearch = false;

// It is validated if it receives parameters different from those it has by default.
if ($ag_freestring !== '' || $moduletype !== '' || $datatype !== ''
    || $ag_modulename !== '' || $refr !== 0 || $offset !== 0 || $status !== 4
    || $modulegroup !== -1 || (bool) array_filter($tag_filter) !== false || $sortField !== ''
    || $sort !== 'none' || $id_module !== 0 || $module_option !== 1
    || $min_hours_status !== ''
) {
    $autosearch = true;
}

// The execution has not been done manually.
$userRequest = (bool) get_parameter('uptbutton');
if ($userRequest === true) {
    $autosearch = true;
}

if (is_metaconsole() === false) {
    $ag_group = (int) get_parameter('ag_group', 0);
} else {
    $ag_group  = get_parameter('ag_group', 0);
    $ag_group_metaconsole = $ag_group;
}

$ag_custom_fields_params = '';
if (!empty($ag_custom_fields)) {
    foreach ($ag_custom_fields as $id => $value) {
        if (!empty($value)) {
            $ag_custom_fields_params .= '&ag_custom_fields['.$id.']='.$value;
        }
    }
}

if ($id_module) {
    $status = -1;
    $ag_modulename = modules_get_agentmodule_name($id_module);
    $ag_freestring = modules_get_agentmodule_agent_alias($id_module);
}

// Get Groups and profiles from user.
$user_groups = implode(',', array_keys(users_get_groups(false, 'AR', false)));

// Begin Build SQL sentences.
$sql_from = ' FROM tagente_modulo 
	INNER JOIN tagente 
		ON tagente_modulo.id_agente = tagente.id_agente 
	LEFT JOIN tagent_secondary_group tasg
	 	ON tagente_modulo.id_agente = tasg.id_agent
	INNER JOIN tagente_estado
		ON tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
	INNER JOIN tmodule
		ON tmodule.id_module = tagente_modulo.id_modulo
	INNER JOIN ttipo_modulo
		ON tagente_modulo.id_tipo_modulo = ttipo_modulo.id_tipo
	LEFT JOIN ttag_module
		ON tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo';

$sql_conditions = ' WHERE tagente.disabled = 0';

if (is_numeric($ag_group)) {
    $id_ag_group = 0;
} else {
    $id_ag_group = db_get_value('id_grupo', 'tgrupo', 'nombre', $ag_group);
}

$load_filter_id = (int) get_parameter('filter_id', 0);

if ($load_filter_id > 0) {
    $user_groups_fl = users_get_groups(
        $config['id_user'],
        'AR',
        users_can_manage_group_all('AR'),
        true
    );

    $sql = sprintf(
        'SELECT id_filter, id_name
        FROM tmonitor_filter
        WHERE id_filter = %d AND id_group_filter IN (%s)',
        $load_filter_id,
        implode(',', array_keys($user_groups_fl))
    );

    $loaded_filter = db_get_row_sql($sql);
}

if (isset($loaded_filter['id_filter']) === false) {
    $loaded_filter['id_filter'] = 0;
}

if ($loaded_filter['id_filter'] > 0) {
    $query_filter['id_filter'] = $load_filter_id;
    $filter = db_get_row_filter('tmonitor_filter', $query_filter, false);
    if ($filter !== false) {
        $ag_group = $filter['ag_group'];
        $recursion = $filter['recursion'];
        $status = $filter['status'];
        $modulegroup = $filter['modulegroup'];
        $ag_modulename = $filter['ag_modulename'];
        $ag_freestring = $filter['ag_freestring'];
        $tag_filter = $filter['tag_filter'];
        $moduletype = $filter['moduletype'];
        $module_option = $filter['module_option'];
        $min_hours_status = $filter['min_hours_status'];
        $datatype = $filter['datatype'];
        $not_condition = $filter['not_condition'];
        $ag_custom_fields = $filter['ag_custom_fields'];

        if ($not_condition === 'false') {
            $not_condition = '';
        }

        if ($not_condition !== '') {
            $is_none = 'None';
            $not_condition = 'NOT';
        }

        if ($not_condition !== '') {
            $condition_query = '!=';
        }

        if (is_array($tag_filter) === false) {
            $tag_filter = json_decode($tag_filter, true);
        }

        if ($tag_filter === '') {
            $tag_filter = [0 => 0];
        }

        if (is_array($ag_custom_fields) === false) {
            $ag_custom_fields = json_decode(io_safe_output($ag_custom_fields), true);
        }
    }

    // Fav menu.
    $fav_menu = [
        'id_element' => $loaded_filter['id_filter'],
        'url'        => 'operation/agentes/status_monitor&pure=&load_filter=1&filter_id='.$loaded_filter['id_filter'],
        'label'      => $loaded_filter['id_name'],
        'section'    => 'Modules',
    ];
}

if (is_metaconsole() === false) {
    $section = (string) get_parameter('section', 'view');

    $buttons['fields'] = [
        'active'    => false,
        'text'      => '<a href="index.php?sec=view&sec2=operation/agentes/status_monitor&section=fields">'.html_print_image(
            'images/edit_columns@svg.svg',
            true,
            [
                'title' => __('Custom fields'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>',
        'operation' => true,
    ];

    $buttons['view'] = [
        'active'    => false,
        'text'      => '<a href="index.php?sec=view&sec2=operation/agentes/status_monitor">'.html_print_image(
            'images/logs@svg.svg',
            true,
            [
                'title' => __('View'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>',
        'operation' => true,
    ];

    switch ($section) {
        case 'fields':
            $buttons['fields']['active'] = true;
            $subpage = ' &raquo; '.__('Custom fields');
        break;

        default:
            $buttons['view']['active'] = true;
        break;
    }
}

// Header.
ui_print_standard_header(
    __('Monitor detail').$subpage,
    'images/agent.png',
    false,
    '',
    true,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Monitoring'),
        ],
        [
            'link'  => '',
            'label' => __('Views'),
        ],
    ],
    (empty($fav_menu) === true) ? [] : $fav_menu
);


$all_groups = [];

// Agent group selector.
if (is_metaconsole() === false) {
    if ($ag_group > 0 && check_acl($config['id_user'], $ag_group, 'AR')) {
        if ($recursion) {
            $all_groups = groups_get_children_ids($ag_group, true);

            // User has explicit permission on group 1 ?
            $sql_conditions_group = sprintf(
                ' AND (tagente.id_grupo IN (%s) OR tasg.id_group IN (%s)) ',
                implode(',', $all_groups),
                implode(',', $all_groups)
            );
        } else {
            $sql_conditions_group = sprintf(
                ' AND (tagente.id_grupo '.$condition_query.' %d OR tasg.id_group '.$condition_query.' %d)',
                $ag_group,
                $ag_group
            );
        }
    } else if ($user_groups != '') {
        // User has explicit permission on group 1 ?
        $sql_conditions_group = ' AND (
			tagente.id_grupo IN ('.$user_groups.')
			OR tasg.id_group IN ('.$user_groups.')
		)';
    }
} else {
    if (((int) $ag_group !== 0) && (check_acl($config['id_user'], $id_ag_group, 'AR'))) {
        if ($recursion) {
            $all_groups = groups_get_children_ids($ag_group, true);

            // User has explicit permission on group 1 ?
            $sql_conditions_group = sprintf(
                ' AND (tagente.id_grupo IN (%s) OR tasg.id_group IN (%s)) ',
                implode(',', $all_groups),
                implode(',', $all_groups)
            );
        } else {
            $sql_conditions_group = sprintf(
                ' AND (tagente.id_grupo '.$not_condition.' IN (%s) OR tasg.id_group '.$not_condition.' IN (%s))',
                $ag_group,
                $ag_group
            );
        }
    } else if ($user_groups != '') {
        // User has explicit permission on group 1 ?
        $sql_conditions_group = ' AND (
			tagente.id_grupo IN ('.$user_groups.')
			OR tasg.id_group IN ('.$user_groups.')
		)';
    }
}

// Module group.
if (is_metaconsole() === true) {
    if ($modulegroup != '-1') {
        $sql_conditions .= sprintf(' AND tagente_modulo.id_module_group '.$not_condition.' IN (%s)', $modulegroup);
    }
} else if ($modulegroup > -1) {
    $sql_conditions .= sprintf(' AND tagente_modulo.id_module_group '.$condition_query.' \'%d\'', $modulegroup);
}

// Module name selector.
if ($ag_modulename != '') {
    $sql_conditions .= " AND tagente_modulo.nombre $not_condition LIKE '%".$ag_modulename."%'";
}

if ($id_module) {
    $sql_conditions .= sprintf(' AND tagente_modulo.id_agente_modulo = \'%d\'', $id_module);
}

if ($module_option !== 0) {
    if ($module_option == 1) {
        // Only enabled.
        $sql_conditions .= sprintf(' AND tagente_modulo.disabled '.$condition_query.' 0');
    } else if ($module_option == 2) {
        // Only disabled.
        $sql_conditions .= sprintf(' AND tagente_modulo.disabled '.$condition_query.' 1');
    }
}

if (empty($datatype) === false) {
    $sql_conditions .= sprintf(' AND ttipo_modulo.id_tipo  '.$condition_query.' '.$datatype);
}

if ($moduletype != '') {
    $sql_conditions .= sprintf(' AND tagente_modulo.id_modulo '.$condition_query.' '.$moduletype.'');
}

// Freestring selector.
if ($ag_freestring != '') {
    $sql_conditions .= ' AND EXISTS (
        SELECT 1
        FROM tagente
        WHERE tagente.id_agente = tagente_modulo.id_agente
        AND (tagente.nombre '.$not_condition.' LIKE \'%%'.$ag_freestring.'%%\'
        OR tagente.alias '.$not_condition.' LIKE \'%%'.$ag_freestring.'%%\'
        OR tagente_modulo.nombre '.$not_condition.' LIKE \'%%'.$ag_freestring.'%%\'
        OR tagente_modulo.descripcion '.$not_condition.' LIKE \'%%'.$ag_freestring.'%%\')
    )';
}

// Status selector.
if ($status == AGENT_MODULE_STATUS_NORMAL) {
    // Normal.
    $sql_conditions .= ' AND tagente_estado.estado '.$condition_query.' 0 
	AND (utimestamp > 0 OR (tagente_modulo.id_tipo_modulo IN(21,22,23,100))) ';
} else if ($status == AGENT_MODULE_STATUS_CRITICAL_BAD) {
    // Critical.
    $sql_conditions .= ' AND tagente_estado.estado '.$condition_query.' 1 AND utimestamp > 0';
} else if ($status == AGENT_MODULE_STATUS_WARNING) {
    // Warning.
    $sql_conditions .= ' AND tagente_estado.estado '.$condition_query.' 2 AND utimestamp > 0';
} else if ($status == AGENT_MODULE_STATUS_NOT_NORMAL) {
    // Not normal.
    $sql_conditions .= ' AND tagente_estado.estado <> 0';
} else if ($status == AGENT_MODULE_STATUS_UNKNOWN) {
    // Unknown.
    $sql_conditions .= ' AND tagente_estado.estado '.$condition_query.' 3 AND tagente_estado.utimestamp <> 0';
} else if ($status == AGENT_MODULE_STATUS_NOT_INIT) {
    // Not init.
    $sql_conditions .= ' AND tagente_estado.utimestamp '.$condition_query.' 0
		AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100)';
}

$min_hours_condition = '<';
if ($not_condition !== '') {
    $min_hours_condition = '>';
}

if (!empty($min_hours_status)) {
    $date = new DateTime(null, new DateTimeZone($config['timezone']));
    $current_timestamp = $date->getTimestamp();
    $max_time = ($current_timestamp - ((int) $min_hours_status * 3600));
    $sql_conditions .= sprintf(' AND tagente_estado.last_status_change '.$min_hours_condition.' %d', $max_time);
}

// Filter by agent custom fields.
$sql_conditions_custom_fields = '';
if (!empty($ag_custom_fields)) {
    $cf_filter = [];
    foreach ($ag_custom_fields as $field_id => $value) {
        if (!empty($value)) {
            $cf_filter[] = '(tagent_custom_data.id_field '.$condition_query.' '.$field_id.' AND tagent_custom_data.description '.$not_condition.' LIKE \'%'.$value.'%\')';
        }
    }

    if (!empty($cf_filter)) {
        $sql_conditions_custom_fields = ' AND tagente.id_agente '.$not_condition.' IN (
				SELECT tagent_custom_data.id_agent
				FROM tagent_custom_data
				WHERE '.implode(' AND ', $cf_filter).')';
    }
}

$all_tags = in_array(0, $tag_filter);

// Filter by tag.
if ($all_tags === false) {
    $sql_conditions .= ' AND tagente_modulo.id_agente_modulo IN (
        SELECT ttag_module.id_agente_modulo
        FROM ttag_module
        WHERE 1=1';

    if ($all_tags === false) {
        $sql_conditions .= ' AND ttag_module.id_tag '.$not_condition.' IN ('.implode(',', $tag_filter).'))';
    }
} else if ($not_condition === 'NOT') {
    // Match nothing if not condition has been selected along with all tags selected (none).
    $sql_conditions .= ' AND 0=0';
}



// Apply the module ACL with tags.
$sql_conditions_tags = '';

if (!users_is_admin()) {
    $sql_conditions_tags = tags_get_acl_tags(
        $config['id_user'],
        ($recursion) ? array_flip($all_groups) : $ag_group,
        'AR',
        'module_condition',
        'AND',
        'tagente_modulo',
        true,
        [],
        false
    );

    if (is_numeric($sql_conditions_tags)) {
        $sql_conditions_tags = ' AND 1 = 0';
    }
}

// Two modes of filter. All the filters and only ACLs filter.
$sql_conditions_all = $sql_conditions.$sql_conditions_group.$sql_conditions_tags.$sql_conditions_custom_fields;

// Get count to paginate.
if (!defined('METACONSOLE')) {
    $count = db_get_sql('SELECT COUNT(DISTINCT tagente_modulo.id_agente_modulo)'.$sql_from.$sql_conditions_all);
}

// Get limit_sql depend of the metaconsole or standard mode.
if (is_metaconsole() === true) {
    // Offset will be used to get the subset of modules.
    $inferior_limit = $offset;
    $superior_limit = ($config['block_size'] + $offset);
    // Offset reset to get all elements.
    $offset = 0;
    if (!isset($config['meta_num_elements'])) {
        $config['meta_num_elements'] = 100;
    }

    $limit_sql = $config['meta_num_elements'];
} else {
    $limit_sql = $config['block_size'];
}

$fields = [];
$fields[AGENT_MODULE_STATUS_NORMAL] = __('Normal');
$fields[AGENT_MODULE_STATUS_WARNING] = __('Warning');
$fields[AGENT_MODULE_STATUS_CRITICAL_BAD] = __('Critical');
$fields[AGENT_MODULE_STATUS_UNKNOWN] = __('Unknown');
$fields[AGENT_MODULE_STATUS_NOT_NORMAL] = __('Not normal');
// Default.
$fields[AGENT_MODULE_STATUS_NOT_INIT] = __('Not init');

$rows_select = [];
$rows_select[0] = __('Not assigned');
if (is_metaconsole() === false) {
    $rows = db_get_all_rows_sql(
        'SELECT *
		FROM tmodule_group ORDER BY name'
    );
    $rows = io_safe_output($rows);
    if (empty($rows) === false) {
        foreach ($rows as $module_group) {
            $rows_select[$module_group['id_mg']] = $module_group['name'];
        }
    }
} else {
    $rows_select = modules_get_modulegroups();
}

$tags = [];
$tags = tags_get_user_tags();
if (empty($tags) === true) {
    $tagsElement = __('No tags');
} else {
    $tagsElement = html_print_select(
        $tags,
        'tag_filter[]',
        $tag_filter,
        '',
        __('All'),
        0,
        true,
        true,
        true,
        '',
        false,
        'width: 100%;'
    );
    $tagsElement .= ui_print_input_placeholder(
        __('Only it is show tags in use.'),
        true
    );
}



$network_available = db_get_sql(
    'SELECT count(*)
    FROM tserver
    WHERE server_type = 1'
);
// POSTGRESQL AND ORACLE COMPATIBLE.
$wmi_available = db_get_sql(
    'SELECT count(*)
    FROM tserver
    WHERE server_type = 6'
);
// POSTGRESQL AND ORACLE COMPATIBLE.
$plugin_available = db_get_sql(
    'SELECT count(*)
    FROM tserver
    WHERE server_type = 4'
);
// POSTGRESQL AND ORACLE COMPATIBLE.
$prediction_available = db_get_sql(
    'SELECT count(*)
    FROM tserver
    WHERE server_type = 5'
);
// POSTGRESQL AND ORACLE COMPATIBLE.
$wux_available = db_get_sql(
    'SELECT count(*)
    FROM tserver
    WHERE server_type = 17'
);
// POSTGRESQL AND ORACLE COMPATIBLE.
// Development mode to use all servers.
if ($develop_bypass) {
    $network_available = 1;
    $wmi_available = 1;
    $plugin_available = 1;
    $prediction_available = 1;
}

$typemodules = [];
$typemodules[1] = __('Data server module');
if ($network_available || is_metaconsole() === true) {
    $typemodules[2] = __('Network server module');
}

if ($plugin_available || is_metaconsole() === true) {
    $typemodules[4] = __('Plugin server module');
}

if ($wmi_available || is_metaconsole() === true) {
    $typemodules[6] = __('WMI server module');
}

if ($prediction_available || is_metaconsole() === true) {
    $typemodules[5] = __('Prediction server module');
}

if (enterprise_installed()) {
    $typemodules[7] = __('Web server module');
    if ($wux_available || is_metaconsole() === true) {
          $typemodules[8] = __('Wux server module');
    }
}

$monitor_options = [
    0 => __('All'),
    1 => __('Only enabled'),
    2 => __('Only disabled'),
];

$min_hours_val = empty($min_hours_status) ? '' : (int) $min_hours_status;

switch ($moduletype) {
    case 1:
        $sqlModuleType = sprintf(
            'SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria '.$not_condition.' IN (6,7,8,0,1,2,-1) order by descripcion '
        );
    break;

    case 2:
        $sqlModuleType = sprintf(
            'SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria '.$not_condition.' between 3 and 5 '
        );
    break;

    case 4:
        $sqlModuleType = sprintf(
            'SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria '.$not_condition.' between 0 and 2 '
        );
    break;

    case 6:
        $sqlModuleType = sprintf(
            'SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria '.$not_condition.' between 0 and 2 '
        );
    break;

    case 7:
        $sqlModuleType = sprintf(
            'SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria '.$condition_query.' 9'
        );
    break;

    case 5:
        $sqlModuleType = sprintf(
            'SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria '.$condition_query.' 0'
        );
    break;

    case 8:
        $sqlModuleType = sprintf(
            'SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE nombre '.$condition_query.' \'web_analysis\''
        );
    break;

    case '':
    default:
        $sqlModuleType = sprintf(
            'SELECT id_tipo, descripcion
					FROM ttipo_modulo'
        );
    break;
}

if ($not_condition !== '') {
    $check_not_condition = true;
} else {
    $check_not_condition = '';
}

$custom_fields = db_get_all_fields_in_table('tagent_custom_fields');
if ($custom_fields === false) {
    $custom_fields = [];
}

$div_custom_fields = '<div class="flex-row">';
foreach ($custom_fields as $custom_field) {
    $custom_field_value = '';
    if (empty($ag_custom_fields) === false) {
        $custom_field_value = $ag_custom_fields[$custom_field['id_field']];
        if (empty($custom_field_value) === true) {
            $custom_field_value = '';
        }
    }

    $div_custom_fields .= '<div class="div-col">';

    $div_custom_fields .= '<div class="div-span">';
    $div_custom_fields .= '<span >'.$custom_field['name'].'</span>';
    $div_custom_fields .= '</div>';

    $div_custom_fields .= '<div class="div-input">';
    $div_custom_fields .= html_print_input_text(
        'ag_custom_fields['.$custom_field['id_field'].']',
        $custom_field_value,
        '',
        0,
        300,
        true,
        false,
        false,
        '',
        'div-input'
    );
    $div_custom_fields .= '</div>';

    $div_custom_fields .= '</div>';
}

$div_custom_fields .= '</div>';


// End Build SQL sentences.
//
// Start Build Search Form.
//
$table = new stdClass();
$tableFilter = new StdClass();
$tableFilter->width = '100%';
$tableFilter->size = [];
$tableFilter->size[0] = '33%';
$tableFilter->size[1] = '33%';
$tableFilter->size[2] = '33%';
$tableFilter->id = 'main_status_monitor_filter';
$tableFilter->class = 'filter-table-adv';
// Captions for first line.
$tableFilter->data['first_line'][0] = html_print_label_input_block(
    __('Group'),
    html_print_select_groups(
        $config['id_user'],
        'AR',
        true,
        'ag_group',
        $ag_group,
        '',
        '',
        '0',
        true,
        false,
        false,
        '',
        false,
        '',
        false,
        false,
        'id_grupo',
        false,
        false,
        false,
        false,
        false,
        false,
        $not_condition
    )
);
$tableFilter->data['first_line'][0] .= html_print_label_input_block(
    __('Recursion'),
    html_print_checkbox_switch(
        'recursion',
        1,
        ($recursion === true || $recursion === 'true' || $recursion === '1') ? 'checked' : false,
        true
    ),
    [
        'div_class'   => 'add-input-reverse',
        'label_class' => 'label-thin',
    ]
);

$tableFilter->data['first_line'][1] = html_print_label_input_block(
    __('Module group'),
    html_print_select(
        $rows_select,
        'modulegroup',
        $modulegroup,
        '',
        __($is_none),
        -1,
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$tableFilter->rowspan['first_line'][2] = 3;
$tableFilter->data['first_line'][2] = html_print_label_input_block(
    __('Tags'),
    $tagsElement
);

// Inputs for second line.
$tableFilter->data['second_line'][0] = html_print_label_input_block(
    __('Monitor status'),
    html_print_select(
        $fields,
        'status',
        $status,
        '',
        __($is_none),
        -1,
        true,
        false,
        true,
        '',
        false,
        'width: 100%'
    )
);

$tableFilter->data['second_line'][1] = html_print_label_input_block(
    __('Module name'),
    html_print_autocomplete_modules(
        'ag_modulename',
        $ag_modulename,
        false,
        true,
        '',
        [],
        true,
        0,
        '30',
        true
    )
);

$tableFilter->data['third_line'][0] = html_print_label_input_block(
    __('Search'),
    html_print_input_text(
        'ag_freestring',
        $ag_freestring,
        '',
        40,
        30,
        true
    )
);

// Advanced filter.
$tableAdvancedFilter = new StdClass();
$tableAdvancedFilter->width = '100%';
$tableAdvancedFilter->class = 'filters';
$tableAdvancedFilter->size = [];
$tableAdvancedFilter->size[0] = '33%';
$tableAdvancedFilter->size[1] = '33%';
$tableAdvancedFilter->size[2] = '33%';
$tableAdvancedFilter->data['advancedField_1'][0] = html_print_label_input_block(
    __('Server type'),
    html_print_select(
        $typemodules,
        'moduletype',
        $moduletype,
        '',
        __($is_none),
        '',
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$tableAdvancedFilter->data['advancedField_1'][1] = html_print_label_input_block(
    __('Show monitors...'),
    html_print_select(
        $monitor_options,
        'module_option',
        $module_option,
        '',
        '',
        '',
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$tableAdvancedFilter->data['advancedField_1'][2] = html_print_label_input_block(
    __('Min. hours in current status'),
    html_print_input_text('min_hours_status', $min_hours_val, '', 12, 20, true)
);

$tableAdvancedFilter->data['advancedField_2'][0] = html_print_label_input_block(
    __('Data type'),
    html_print_select_from_sql($sqlModuleType, 'datatype', $datatype, '', __('All'), 0, true)
);

$tableAdvancedFilter->data['advancedField_2'][1] = html_print_label_input_block(
    __('Not condition'),
    html_print_div(
        [
            'class'   => 'mrgn_5px mrgn_lft_0px mrgn_right_0px flex wrap',
            'content' => html_print_input(
                [
                    'type'    => 'switch',
                    'name'    => 'not_condition',
                    'return'  => false,
                    'checked' => ($check_not_condition === true || $check_not_condition === 'true' || $check_not_condition === '1') ? 'checked' : false,
                    'value'   => 'NOT',
                    'id'      => 'not_condition_switch',
                    'onclick' => 'changeNotConditionStatus(this)',
                ]
            ).ui_print_input_placeholder(
                __('If you check this option, those elements that do NOT meet any of the requirements will be shown'),
                true
            ),
        ],
        true
    )
);

$tableAdvancedFilter->colspan[2][0] = 3;
$tableAdvancedFilter->data[2][0] = ui_toggle(
    $div_custom_fields,
    __('Agent custom fields'),
    '',
    '',
    true,
    true,
    '',
    'white-box-content'
);

$tableFilter->colspan[3][0] = 3;
$tableFilter->data[3][0] = ui_toggle(
    html_print_table(
        $tableAdvancedFilter,
        true
    ),
    '<span class="">'.__('Advanced options').'</span>',
    '',
    '',
    true,
    true,
    '',
    'white-box-content'
);

$filters = '<form method="post" action="index.php?sec='.$section.'&sec2=operation/agentes/status_monitor&refr='.$refr.'&ag_group='.$ag_group.'&ag_freestring='.$ag_freestring.'&module_option='.$module_option.'&ag_modulename='.$ag_modulename.'&moduletype='.$moduletype.'&datatype='.$datatype.'&status='.$status.'&sort_field='.$sortField.'&sort='.$sort.'&pure='.$config['pure'].$ag_custom_fields_params.'">';
$filters .= html_print_table($tableFilter, true);
$buttons = html_print_submit_button(
    __('Filter'),
    'uptbutton',
    false,
    [
        'icon' => 'search',
        'mode' => 'mini',
    ],
    true
);

$buttons .= html_print_button(
    __('Load filter'),
    'load-filter',
    false,
    '',
    [
        'icon'  => 'wand',
        'mode'  => 'mini secondary',
        'class' => 'float-left margin-right-2 sub config',
    ],
    true
);
if (check_acl($config['id_user'], 0, 'AW')) {
    $buttons .= html_print_button(
        __('Manage filter'),
        'save-filter',
        false,
        '',
        [
            'icon'  => 'wand',
            'mode'  => 'mini secondary',
            'class' => 'float-left margin-right-2 sub wand',
        ],
        true
    );
}

$filters .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => $buttons,
    ],
    true
);

$filters .= '</form>';
ui_toggle(
    $filters,
    '<span class="subsection_header_title">'.__('Filters').'</span>',
    'filter_form',
    '',
    true,
    false,
    '',
    'white-box-content',
    'box-flat white_table_graph fixed_filter_bar'
);

unset($table);
// End Build Search Form.
//
// Sort functionality.
$selected = true;
$selectAgentNameUp = false;
$selectAgentNameDown = false;
$selectDataTypeUp = false;
$selectDataTypeDown = false;
$selectTypeUp = false;
$selectTypeDown = false;
$selectModuleNameUp = false;
$selectModuleNameDown = false;
$selectIntervalUp = false;
$selectIntervalDown = false;
$selectStatusUp = false;
$selectStatusDown = false;
$selectStatusChangeUp = false;
$selectStatusChangeDown = false;
$selectDataUp = false;
$selectDataDown = false;
$selectTimestampUp = false;
$selectTimestampDown = false;
$order = null;

switch ($sortField) {
    case 'agent_alias':
        $fieldForSorting = 'agent_alias';
        switch ($sort) {
            case 'up':
                $selectAgentNameUp = $selected;
                $order = [
                    'field' => 'tagente.alias',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectAgentNameDown = $selected;
                $order = [
                    'field' => 'tagente.alias',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'type':
        $fieldForSorting = 'module_type';
        switch ($sort) {
            case 'up':
                $selectDataTypeUp = $selected;
                $order = [
                    'field' => 'tagente_modulo.id_tipo_modulo',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectDataTypeDown = $selected;
                $order = [
                    'field' => 'tagente_modulo.id_tipo_modulo',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'moduletype':
        $fieldForSorting = 'module_type';
        switch ($sort) {
            case 'up':
                $selectTypeUp = $selected;
                $order = [
                    'field' => 'tagente_modulo.id_modulo',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectTypeDown = $selected;
                $order = [
                    'field' => 'tagente_modulo.id_modulo',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'module_name':
        $fieldForSorting = 'module_name';
        switch ($sort) {
            case 'up':
                $selectModuleNameUp = $selected;
                $order = [
                    'field' => 'tagente_modulo.nombre',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectModuleNameDown = $selected;
                $order = [
                    'field' => 'tagente_modulo.nombre',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'interval':
        $fieldForSorting = 'module_interval';
        switch ($sort) {
            case 'up':
                $selectIntervalUp = $selected;
                $order = [
                    'field' => 'tagente_modulo.module_interval',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectIntervalDown = $selected;
                $order = [
                    'field' => 'tagente_modulo.module_interval',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'status':
        $fieldForSorting = 'estado';
        switch ($sort) {
            case 'up':
                $selectStatusUp = $selected;
                $order = [
                    'field' => 'tagente_estado.estado',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectStatusDown = $selected;
                $order = [
                    'field' => 'tagente_estado.estado',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'last_status_change':
        $fieldForSorting = 'last_status_change';
        switch ($sort) {
            case 'up':
                $selectStatusChangeUp = $selected;
                $order = [
                    'field' => 'tagente_estado.last_status_change',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectStatusChangeDown = $selected;
                $order = [
                    'field' => 'tagente_estado.last_status_change',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'timestamp':
        $fieldForSorting = 'utimestamp';
        switch ($sort) {
            case 'up':
                $selectTimestampUp = $selected;
                $order = [
                    'field' => 'tagente_estado.utimestamp',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectTimestampDown = $selected;
                $order = [
                    'field' => 'tagente_estado.utimestamp',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'data':
        $fieldForSorting = 'datos';
        switch ($sort) {
            case 'up':
                $selectDataUp = $selected;
                $order = [
                    'field' => 'tagente_estado.datos',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectDataDown = $selected;
                $order = [
                    'field' => 'tagente_estado.datos',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    default:
        $fieldForSorting = 'agent_alias';
        $selectAgentNameUp = $selected;
        $selectAgentNameDown = false;
        $selectDataTypeUp = false;
        $selectDataTypeDown = false;
        $selectTypeUp = false;
        $selectTypeDown = false;
        $selectModuleNameUp = false;
        $selectModuleNameDown = false;
        $selectIntervalUp = false;
        $selectIntervalDown = false;
        $selectStatusUp = false;
        $selectStatusDown = false;
        $selectStatusChangeUp = false;
        $selectStatusChangeDown = false;
        $selectDataUp = false;
        $selectDataDown = false;
        $selectTimestampUp = false;
        $selectTimestampDown = false;
        $order = [
            'field' => 'tagente.alias',
            'order' => 'ASC',
        ];
    break;
}

$sql = 'SELECT
    (SELECT GROUP_CONCAT(ttag.name SEPARATOR \',\')
		FROM ttag
		WHERE ttag.id_tag IN (
			SELECT ttag_module.id_tag
			FROM ttag_module
			WHERE ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo
        )
    ) AS tags,
	tagente_modulo.id_agente_modulo,
	tagente_modulo.id_modulo,
	tagente.intervalo AS agent_interval,
	tagente.alias AS agent_alias,
	tagente.nombre AS agent_name,
	tagente_modulo.nombre AS module_name,
	tagente_modulo.history_data,
	tagente.id_grupo AS id_group,
	tagente.id_agente AS id_agent,
	tagente_modulo.id_tipo_modulo AS module_type,
	tagente_modulo.module_interval,
	tagente_estado.datos,
	tagente_estado.estado,
    tagente_estado.last_status_change,
	tagente_modulo.min_warning,
	tagente_modulo.max_warning,
	tagente_modulo.str_warning,
	tagente_modulo.unit,
	tagente_modulo.min_critical,
	tagente_modulo.max_critical,
	tagente_modulo.str_critical,
	tagente_modulo.extended_info,
	tagente_modulo.critical_inverse,
	tagente_modulo.warning_inverse,
	tagente_estado.utimestamp AS utimestamp'.$sql_from.$sql_conditions_all.'
	GROUP BY tagente_modulo.id_agente_modulo
	ORDER BY '.$order['field'].' '.$order['order'].'
	LIMIT '.$limit_sql.' OFFSET '.$offset;

// We do not show the modules until the user searches with the filter.
if ($autosearch) {
    if (is_metaconsole() === false) {
        $result = db_get_all_rows_sql($sql);

        if ($result === false) {
            $result = [];
        } else {
            $tablePagination = ui_pagination($count, false, $offset, 0, true, 'offset', false);
        }
    } else {
        // For each server defined and not disabled.
        $servers = db_get_all_rows_sql(
            'SELECT *
		FROM tmetaconsole_setup
		WHERE disabled = 0'
        );
        if ($servers === false) {
            $servers = [];
        }

        $result = [];
        $count_modules = 0;
        foreach ($servers as $server) {
            try {
                $node = new Node((int) $server['id']);
                $node->connect();

                $result_server = db_get_all_rows_sql($sql);

                if (empty($result_server) === false) {
                    // Create HASH login info.
                    $pwd = $server['auth_token'];
                    $auth_serialized = json_decode($pwd, true);

                    if (is_array($auth_serialized)) {
                        $pwd = $auth_serialized['auth_token'];
                        $api_password = $auth_serialized['api_password'];
                        $console_user = $auth_serialized['console_user'];
                        $console_password = $auth_serialized['console_password'];
                    }

                    $user = $config['id_user'];
                    $user_rot13 = str_rot13($config['id_user']);
                    $hashdata = $user.$pwd;
                    $hashdata = md5($hashdata);

                    foreach ($result_server as $result_element_key => $result_element_value) {
                        $result_server[$result_element_key]['server_id'] = $server['id'];
                        $result_server[$result_element_key]['server_name'] = $server['server_name'];
                        $result_server[$result_element_key]['server_url'] = $server['server_url'].'/';
                        $result_server[$result_element_key]['hashdata'] = $hashdata;
                        $result_server[$result_element_key]['user'] = $config['id_user'];
                        $result_server[$result_element_key]['groups_in_server'] = agents_get_all_groups_agent(
                            $result_element_value['id_agent'],
                            $result_element_value['id_group']
                        );

                        $count_modules++;
                    }

                    $result = array_merge($result, $result_server);
                }

                usort($result, arrayOutputSorting($sort, $fieldForSorting));
            } catch (\Exception $e) {
                $node->disconnect();
                return;
            } finally {
                $node->disconnect();
            }
        }

        if ($count_modules > $config['block_size']) {
            $show_count = false;
            if (is_metaconsole() === true) {
                $show_count = true;
            }

            $tablePagination = ui_pagination($count_modules, false, $offset, 0, true, 'offset', $show_count);
        }

        // Get number of elements of the pagination.
        $result = ui_meta_get_subset_array($result, $inferior_limit, $superior_limit);
    }
}

// Urls to sort the table.
$url_agent_name = 'index.php?sec='.$section.'&sec2=operation/agentes/status_monitor';
$url_type = 'index.php?sec='.$section.'&sec2=operation/agentes/status_monitor';
$url_module_name = 'index.php?sec='.$section.'&sec2=operation/agentes/status_monitor';
$url_server_type = 'index.php?sec='.$section.'&sec2=operation/agentes/status_monitor';
$url_interval = 'index.php?sec='.$section.'&sec2=operation/agentes/status_monitor';
$url_status = 'index.php?sec='.$section.'&sec2=operation/agentes/status_monitor';
$url_status_change = 'index.php?sec='.$section.'&sec2=operation/agentes/status_monitor';
$url_data = 'index.php?sec='.$section.'&sec2=operation/agentes/status_monitor';
$url_timestamp_up = 'index.php?sec='.$section.'&sec2=operation/agentes/status_monitor';
$url_timestamp_down = 'index.php?sec='.$section.'&sec2=operation/agentes/status_monitor';

$url_agent_name .= '&refr='.$refr.'&datatype='.$datatype.'&moduletype='.$moduletype.'&modulegroup='.$modulegroup.'&offset='.$offset.'&ag_group='.$ag_group.'&ag_freestring='.$ag_freestring.'&ag_modulename='.$ag_modulename.'&status='.$status.$ag_custom_fields_params;
$url_type .= '&datatype='.$datatype.'&moduletype='.$moduletype.'&refr='.$refr.'&modulegroup='.$modulegroup.'&offset='.$offset.'&ag_group='.$ag_group.'&ag_freestring='.$ag_freestring.'&ag_modulename='.$ag_modulename.'&status='.$status.$ag_custom_fields_params;
$url_module_name .= '&datatype='.$datatype.'&moduletype='.$moduletype.'&refr='.$refr.'&modulegroup='.$modulegroup.'&offset='.$offset.'&ag_group='.$ag_group.'&ag_freestring='.$ag_freestring.'&ag_modulename='.$ag_modulename.'&status='.$status.$ag_custom_fields_params;
$url_server_type .= '&datatype='.$datatype.'&moduletype='.$moduletype.'&refr='.$refr.'&modulegroup='.$modulegroup.'&offset='.$offset.'&ag_group='.$ag_group.'&ag_freestring='.$ag_freestring.'&ag_modulename='.$ag_modulename.'&status='.$status.$ag_custom_fields_params;
$url_interval .= '&datatype='.$datatype.'&moduletype='.$moduletype.'&refr='.$refr.'&modulegroup='.$modulegroup.'&offset='.$offset.'&ag_group='.$ag_group.'&ag_freestring='.$ag_freestring.'&ag_modulename='.$ag_modulename.'&status='.$status.$ag_custom_fields_params;
$url_status .= '&datatype='.$datatype.'&moduletype='.$moduletype.'&refr='.$refr.'&modulegroup='.$modulegroup.'&offset='.$offset.'&ag_group='.$ag_group.'&ag_freestring='.$ag_freestring.'&ag_modulename='.$ag_modulename.'&status='.$status.$ag_custom_fields_params;
$url_status_change .= '&datatype='.$datatype.'&moduletype='.$moduletype.'&refr='.$refr.'&modulegroup='.$modulegroup.'&offset='.$offset.'&ag_group='.$ag_group.'&ag_freestring='.$ag_freestring.'&ag_modulename='.$ag_modulename.'&status='.$status.$ag_custom_fields_params;
$url_data .= '&datatype='.$datatype.'&moduletype='.$moduletype.'&refr='.$refr.'&modulegroup='.$modulegroup.'&offset='.$offset.'&ag_group='.$ag_group.'&ag_freestring='.$ag_freestring.'&ag_modulename='.$ag_modulename.'&status='.$status.$ag_custom_fields_params;
$url_timestamp_up .= '&datatype='.$datatype.'&moduletype='.$moduletype.'&refr='.$refr.'&offset='.$offset.'&ag_group='.$ag_group.'&ag_freestring='.$ag_freestring.'&ag_modulename='.$ag_modulename.'&status='.$status.$ag_custom_fields_params;
$url_timestamp_down .= '&datatype='.$datatype.'&moduletype='.$moduletype.'&refr='.$refr.'&modulegroup='.$modulegroup.'&offset='.$offset.'&ag_group='.$ag_group.'&ag_freestring='.$ag_freestring.'&ag_modulename='.$ag_modulename.'&status='.$status.$ag_custom_fields_params;

// Holy god...
$url_agent_name .= '&recursion='.$recursion;
$url_type .= '&recursion='.$recursion;
$url_module_name .= '&recursion='.$recursion;
$url_server_type .= '&recursion='.$recursion;
$url_interval .= '&recursion='.$recursion;
$url_status .= '&recursion='.$recursion;
$url_status_change .= '&recursion='.$recursion;
$url_data .= '&recursion='.$recursion;
$url_timestamp_up .= '&recursion='.$recursion;
$url_timestamp_down .= '&recursion='.$recursion;

$url_agent_name .= '&sort_field=agent_alias&sort=';
$url_type .= '&sort_field=type&sort=';
$url_module_name .= '&sort_field=module_name&sort=';
$url_server_type .= '&sort_field=moduletype&sort=';
$url_interval .= '&sort_field=interval&sort=';
$url_status .= '&sort_field=status&sort=';
$url_status_change .= '&sort_field=last_status_change&sort=';
$url_data .= '&sort_field=data&sort=';
$url_timestamp_up .= '&sort_field=timestamp&sort=up';
$url_timestamp_down .= '&sort_field=timestamp&sort=down';

// Start Build List Result.
if (empty($result) === false) {
    if (is_metaconsole() === true) {
        html_print_action_buttons(
            '',
            [
                'type'          => 'form_action',
                'right_content' => $tablePagination,
            ]
        );
    }

    $table = new StdClass();
    $table->cellpadding = 0;
    $table->cellspacing = 0;
    $table->styleTable = 'margin: 0 10px; width: -webkit-fill-available; width: -moz-available';
    $table->class = 'info_table tactical_table';
    $table->id = 'monitors_view';
    $table->head = [];
    $table->data = [];
    $table->size = [];
    $table->align = [];

    $show_fields = explode(',', $config['status_monitor_fields']);

    if (in_array('policy', $show_fields)) {
        if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
            $table->head[0] = '<span title=\''.__('Policy').'\'>'.__('P.').'</span>';
        }
    }

    if (in_array('agent', $show_fields) || is_metaconsole()) {
        $table->head[1] = '<span>'.__('Agent').'</span>';
        $table->head[1] .= ui_get_sorting_arrows($url_agent_name.'up', $url_agent_name.'down', $selectAgentNameUp, $selectAgentNameDown);
    }

    if (in_array('data_type', $show_fields) || is_metaconsole()) {
        $table->head[2] = '<span>'.__('Data Type').'</span>';
        $table->head[2] .= ui_get_sorting_arrows($url_type.'up', $url_type.'down', $selectDataTypeUp, $selectDataTypeDown);
        $table->headstyle[2] = 'text-align: center';
        $table->align[2] = 'center';
    }

    if (in_array('module_name', $show_fields) || is_metaconsole()) {
        $table->head[3] = '<span>'.__('Module name').'</span>';
        $table->head[3] .= ui_get_sorting_arrows($url_module_name.'up', $url_module_name.'down', $selectModuleNameUp, $selectModuleNameDown);
    }

    if (in_array('server_type', $show_fields) || is_metaconsole()) {
        $table->head[4] = '<span>'.__('Server type').'</span>';
        $table->head[4] .= ui_get_sorting_arrows($url_server_type.'up', $url_server_type.'down', $selectTypeUp, $selectTypeDown);
        $table->headstyle[4] = 'text-align: center';
        $table->align[4] = 'center';
    }

    if (in_array('interval', $show_fields) || is_metaconsole()) {
        $table->head[5] = '<span>'.__('Interval').'</span>';
        $table->head[5] .= ui_get_sorting_arrows($url_interval.'up', $url_interval.'down', $selectIntervalUp, $selectIntervalDown);
        $table->align[5] = 'left';
    }

    if (in_array('status', $show_fields) || is_metaconsole()) {
        $table->head[6] = '<span>'.__('Status').'</span>';
        $table->head[6] .= ui_get_sorting_arrows($url_status.'up', $url_status.'down', $selectStatusUp, $selectStatusDown);
        $table->align[6] = 'left';
    }

    if (in_array('last_status_change', $show_fields)) {
        $table->head[7] = '<span>'.__('Last status change').'</span>';
        $table->head[7] .= ui_get_sorting_arrows($url_status_change.'up', $url_status_change.'down', $selectStatusChangeUp, $selectStatusChangeDown);
        $table->headstyle[7] = 'text-align: center';
        $table->align[7] = 'center';
    }

    if (in_array('graph', $show_fields) || is_metaconsole()) {
        $table->head[8] = '<span>'.__('Graph').'</span>';
        $table->headstyle[8] = 'text-align: center';
        $table->align[8] = 'center';
    }

    if (in_array('warn', $show_fields) || is_metaconsole()) {
        $table->head[9] = '<span>'.__('W/C').'</span>';
        $table->align[9] = 'left';
    }

    if (in_array('data', $show_fields) || is_metaconsole()) {
        $table->head[10] = '<span>'.__('Data').'</span>';
        $table->align[10] = 'left';
        if (is_metaconsole()) {
            $table->head[10] .= ui_get_sorting_arrows($url_data.'up', $url_data.'down', $selectDataUp, $selectDataDown);
        }
    }

    if (in_array('timestamp', $show_fields) || is_metaconsole()) {
        $table->head[11] = '<span>'.__('Timestamp').'</span>';
        $table->head[11] .= ui_get_sorting_arrows($url_timestamp_up, $url_timestamp_down, $selectTimestampUp, $selectTimestampDown);
        $table->align[11] = 'left';
    }

    if (check_acl($config['id_user'], 0, 'AR')) {
        $actions_list = true;
        $table->head[12] = __('Actions');
        $table->align[12] = 'left';
    }

    $id_type_web_content_string = db_get_value(
        'id_tipo',
        'ttipo_modulo',
        'nombre',
        'web_content_string'
    );

    $inc_id = 0;

    foreach ($result as $row) {
        // Avoid unset, null and false value.
        if (empty($row['server_name']) === true) {
            $row['server_name'] = '';
        }

        $is_web_content_string = (bool) db_get_value_filter(
            'id_agente_modulo',
            'tagente_modulo',
            [
                'id_agente_modulo' => $row['id_agente_modulo'],
                'id_tipo_modulo'   => $id_type_web_content_string,
            ]
        );

        // Fixed the goliat sends the strings from web.
        // Without HTML entities.
        if ($is_web_content_string) {
            $row['datos'] = io_safe_input($row['datos']);
        }

        // Fixed the data from Selenium Plugin.
        if ($row['datos'] != strip_tags($row['datos'])) {
            $row['datos'] = io_safe_input($row['datos']);
        }

        $data = [];

        if (in_array('policy', $show_fields) || is_metaconsole()) {
            if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
                if (is_metaconsole()) {
                    $node = metaconsole_get_connection_by_id($row['server_id']);
                    if (metaconsole_load_external_db($node) !== NOERR) {
                        // Restore the default connection.
                        metaconsole_restore_db();
                        $errors++;
                        break;
                    }
                }

                $policyInfo = policies_info_module_policy($row['id_agente_modulo']);

                if ($policyInfo === false) {
                    $data[0] = '';
                } else {
                    $linked = policies_is_module_linked($row['id_agente_modulo']);

                    $adopt = false;
                    if (policies_is_module_adopt($row['id_agente_modulo'])) {
                        $adopt = true;
                    }

                    if ($linked) {
                        if ($adopt) {
                            $img = 'images/policies_brick.png';
                            $title = __('(Adopt) ').$policyInfo['name_policy'];
                        } else {
                            $img = 'images/policies_mc.png';
                            $title = $policyInfo['name_policy'];
                        }
                    } else {
                        if ($adopt) {
                            $img = 'images/policies_not_brick.png';
                            $title = __('(Unlinked) (Adopt) ').$policyInfo['name_policy'];
                        } else {
                            $img = 'images/unlinkpolicy.png';
                            $title = __('(Unlinked) ').$policyInfo['name_policy'];
                        }
                    }

                    if (is_metaconsole()) {
                        $data[0] = '<a href="?sec=gmodules&sec2=advanced/policymanager&id='.$policyInfo['id_policy'].'">'.html_print_image($img, true, ['title' => $title]).'</a>';
                    } else {
                        $data[0] = '<a href="?sec=gmodules&sec2=enterprise/godmode/policies/policies&id='.$policyInfo['id_policy'].'">'.html_print_image($img, true, ['title' => $title]).'</a>';
                    }
                }

                if (is_metaconsole()) {
                    metaconsole_restore_db();
                }
            }
        }

        if (in_array('agent', $show_fields) || is_metaconsole()) {
            $agent_alias = !empty($row['agent_alias']) ? $row['agent_alias'] : $row['agent_name'];

            // TODO: Calculate hash access before to use it more simply like other sections. I.E. Events view
            if (is_metaconsole() === true) {
                echo "<form id='agent-redirection-".$inc_id."' method='POST' target='_blank' action='".$row['server_url'].'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$row['id_agent']."'>";
                html_print_input_hidden(
                    'loginhash',
                    'auto',
                    false
                );
                html_print_input_hidden(
                    'loginhash_data',
                    $row['hashdata'],
                    false
                );
                html_print_input_hidden(
                    'loginhash_user',
                    str_rot13($row['user']),
                    false
                );
                echo '</form>';
                $agent_link = "<a target='_blank' href='".$row['server_url'].'index.php?sec=estado&sec2=operation/agentes/ver_agente&loginhash=auto&loginhash_data='.$row['hashdata'].'&loginhash_user='.str_rot13($row['user']).'&id_agente='.$row['id_agent']."'>";

                $agent_alias = ui_print_truncate_text(
                    $agent_alias,
                    'agent_small',
                    false,
                    true,
                    true,
                    '[&hellip;]',
                    'font-size:7.5pt;'
                );
                if (can_user_access_node()) {
                    $data[1] = $agent_link.'<b>'.$agent_alias.'</b></a>';
                } else {
                    $data[1] = $agent_alias;
                }
            } else {
                $data[1] = '<strong><a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$row['id_agent'].'">';
                $data[1] .= ui_print_truncate_text($agent_alias, 'agent_medium', false, true, false, '[&hellip;]', 'font-size:7.5pt;');
                $data[1] .= '</a></strong>';
            }
        }

        if (in_array('data_type', $show_fields) || is_metaconsole()) {
            $data[2] = html_print_image('images/'.modules_show_icon_type($row['module_type']), true, ['class' => 'invert_filter main_menu_icon']);
            $agent_groups = is_metaconsole() ? $row['groups_in_server'] : agents_get_all_groups_agent($row['id_agent'], $row['id_group']);
            if (check_acl_one_of_groups($config['id_user'], $agent_groups, 'AW')) {
                $show_edit_icon = true;
                if (is_metaconsole() === true) {
                    if (!can_user_access_node()) {
                        $show_edit_icon = false;
                    }

                    $url_edit_module = $row['server_url'].'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$row['id_agent'].'&'.'tab=module&'.'id_agent_module='.$row['id_agente_modulo'].'&'.'edit_module=1'.'&loginhash=auto&loginhash_data='.$row['hashdata'].'&loginhash_user='.str_rot13($row['user']);
                } else {
                    $url_edit_module = 'index.php?'.'sec=gagente&'.'sec2=godmode/agentes/configurar_agente&'.'id_agente='.$row['id_agent'].'&'.'tab=module&'.'id_agent_module='.$row['id_agente_modulo'].'&'.'edit_module=1';
                }
            }
        }

        if (in_array('module_name', $show_fields) === true || is_metaconsole() === true) {
            $data[3] = html_print_anchor(
                [
                    'target'  => '_blank',
                    'href'    => ($url_edit_module ?? '#'),
                    'content' => ui_print_truncate_text($row['module_name'], 'module_small', false, true, true),
                ],
                true
            );

            if (empty($row['extended_info']) === false) {
                $data[3] .= ui_print_help_tip($row['extended_info'], true, '/images/default_list.png');
            }

            if (empty($row['tags']) === false) {
                $data[3] .= html_print_image(
                    '/images/tag@svg.svg',
                    true,
                    [
                        'title' => $row['tags'],
                        'class' => 'invert_filter main_menu_icon',
                    ]
                );
            }
        }

        if (in_array('server_type', $show_fields) === true || is_metaconsole() === true) {
            $data[4] = ui_print_servertype_icon((int) $row['id_modulo']);
        }


        if (in_array('interval', $show_fields) === true || is_metaconsole() === true) {
            $data[5] = ((int) $row['module_interval'] === 0) ? human_time_description_raw($row['agent_interval']) : human_time_description_raw($row['module_interval']);
        }

        if (in_array('status', $show_fields) || is_metaconsole()) {
            $data[6] = '<div class="status_rounded_rectangles">';
            if ($row['utimestamp'] === 0 && (($row['module_type'] < 21
                || $row['module_type'] > 23) && $row['module_type'] != 100)
            ) {
                $data[6] .= ui_print_status_image(
                    STATUS_MODULE_NO_DATA,
                    __('NOT INIT'),
                    true
                );
            } else if ($row['estado'] == 0) {
                if (is_numeric($row['datos'])) {
                    $data[6] .= ui_print_status_image(
                        STATUS_MODULE_OK,
                        __('NORMAL').': '.remove_right_zeros(number_format($row['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])),
                        true
                    );
                } else {
                    $data[6] .= ui_print_status_image(
                        STATUS_MODULE_OK,
                        __('NORMAL').': '.htmlspecialchars($row['datos']),
                        true
                    );
                }
            } else if ($row['estado'] == 1) {
                if (is_numeric($row['datos'])) {
                    $data[6] .= ui_print_status_image(
                        STATUS_MODULE_CRITICAL,
                        __('CRITICAL').': '.remove_right_zeros(
                            number_format(
                                $row['datos'],
                                $config['graph_precision'],
                                $config['decimal_separator'],
                                $config['thousand_separator']
                            )
                        ),
                        true
                    );
                } else {
                    $data[6] .= ui_print_status_image(
                        STATUS_MODULE_CRITICAL,
                        __('CRITICAL').': '.htmlspecialchars($row['datos']),
                        true
                    );
                }
            } else if ($row['estado'] == 2) {
                if (is_numeric($row['datos'])) {
                    $data[6] .= ui_print_status_image(
                        STATUS_MODULE_WARNING,
                        __('WARNING').': '.remove_right_zeros(
                            number_format(
                                $row['datos'],
                                $config['graph_precision'],
                                $config['decimal_separator'],
                                $config['thousand_separator']
                            )
                        ),
                        true
                    );
                } else {
                    $data[6] .= ui_print_status_image(
                        STATUS_MODULE_WARNING,
                        __('WARNING').': '.htmlspecialchars($row['datos']),
                        true
                    );
                }
            } else if ($row['estado'] == 3) {
                if (is_numeric($row['datos'])) {
                    $data[6] .= ui_print_status_image(
                        STATUS_MODULE_UNKNOWN,
                        __('UNKNOWN').': '.remove_right_zeros(
                            number_format(
                                $row['datos'],
                                $config['graph_precision'],
                                $config['decimal_separator'],
                                $config['thousand_separator']
                            )
                        ),
                        true
                    );
                } else {
                    $data[6] .= ui_print_status_image(
                        STATUS_MODULE_UNKNOWN,
                        __('UNKNOWN').': '.htmlspecialchars($row['datos']),
                        true
                    );
                }
            } else if ($row['estado'] == 4) {
                if (is_numeric($row['datos'])) {
                    $data[6] .= ui_print_status_image(
                        STATUS_MODULE_NO_DATA,
                        __('NO DATA').': '.remove_right_zeros(
                            number_format(
                                $row['datos'],
                                $config['graph_precision'],
                                $config['decimal_separator'],
                                $config['thousand_separator']
                            )
                        ),
                        true
                    );
                } else {
                    $data[6] .= ui_print_status_image(
                        STATUS_MODULE_NO_DATA,
                        __('NO DATA').': '.htmlspecialchars($row['datos']),
                        true
                    );
                }
            } else {
                $last_status = modules_get_agentmodule_last_status(
                    $row['id_agente_modulo']
                );

                switch ($last_status) {
                    case 0:
                        if (is_numeric($row['datos'])) {
                            $data[6] .= ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('NORMAL').': '.remove_right_zeros(number_format($row['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])),
                                true
                            );
                        } else {
                            $data[6] .= ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('NORMAL').': '.htmlspecialchars($row['datos']),
                                true
                            );
                        }
                    break;

                    case 1:
                        if (is_numeric($row['datos'])) {
                            $data[6] .= ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('CRITICAL').': '.remove_right_zeros(number_format($row['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])),
                                true
                            );
                        } else {
                            $data[6] .= ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('CRITICAL').': '.htmlspecialchars($row['datos']),
                                true
                            );
                        }
                    break;

                    case 2:
                        if (is_numeric($row['datos'])) {
                            $data[6] .= ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('WARNING').': '.remove_right_zeros(number_format($row['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])),
                                true
                            );
                        } else {
                            $data[6] .= ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('WARNING').': '.htmlspecialchars($row['datos']),
                                true
                            );
                        }
                    break;
                }
            }

            $data[6] .= '</div>';
        }

        if (in_array('last_status_change', $show_fields) || is_metaconsole()) {
            $data[7] = ($row['last_status_change'] > 0) ? human_time_comparation($row['last_status_change']) : __('N/A');
        }

        if (in_array('graph', $show_fields) || is_metaconsole()) {
            $data[8] = '';

            $acl_graphs = false;

            // Avoid the check on the metaconsole. Too slow to show/hide an icon depending on the permissions.
            if (!is_metaconsole()) {
                $agent_groups = agents_get_all_groups_agent($row['id_agent'], $row['id_group']);
                $acl_graphs = check_acl_one_of_groups($config['id_user'], $agent_groups, 'RR');
            } else {
                $acl_graphs = true;
            }

            if ($row['history_data'] == 1 && $acl_graphs) {
                $tresholds = true;
                if (empty((float) $module['min_warning']) === true
                    && empty((float) $module['max_warning']) === true
                    && empty($module['warning_inverse']) === true
                    && empty((float) $module['min_critical']) === true
                    && empty((float) $module['max_critical']) === true
                    && empty($module['critical_inverse']) === true
                ) {
                    $tresholds = false;
                }

                $graph_type = return_graphtype($row['module_type']);

                $url = ui_get_full_url('operation/agentes/stat_win.php', false, false, false);
                $handle = dechex(crc32($row['id_agente_modulo'].$row['module_name']));
                $win_handle = 'day_'.$handle;

                $graph_params = [
                    'type'    => $graph_type,
                    'period'  => SECONDS_1DAY,
                    'id'      => $row['id_agente_modulo'],
                    'refresh' => SECONDS_10MINUTES,
                ];

                if ($tresholds === true || $graph_type === 'boolean') {
                    $graph_params['histogram'] = 1;
                }

                if (is_metaconsole() === true && isset($row['server_id']) === true) {
                    // Set the server id.
                    $graph_params['server'] = $row['server_id'];
                }

                $graph_params_str = http_build_query($graph_params);

                $link = 'winopeng_var(\''.$url.'?'.$graph_params_str.'\',\''.$win_handle.'\', 800, 480)';

                $graphIconsContent = [];
                $graphIconsContent[] = get_module_realtime_link_graph($row);

                if ($tresholds === true || $graph_type === 'boolean') {
                    $graphIconsContent[] = html_print_anchor(
                        [
                            'href'    => 'javascript:'.$link,
                            'content' => html_print_image(
                                'images/event-history.svg',
                                true,
                                [
                                    'border' => '0',
                                    'alt'    => '',
                                    'class'  => 'invert_filter main_menu_icon',
                                ]
                            ),
                        ],
                        true
                    );
                }

                if (is_snapshot_data($row['datos']) === false) {
                    if ($tresholds === true || $graph_type === 'boolean') {
                        unset($graph_params['histogram']);
                    }

                    $graph_params_str = http_build_query($graph_params);

                    $link = 'winopeng_var(\''.$url.'?'.$graph_params_str.'\',\''.$win_handle.'\', 800, 480)';
                    $graphIconsContent[] = html_print_anchor(
                        [
                            'href'    => 'javascript:'.$link,
                            'content' => html_print_image('images/module-graph.svg', true, ['border' => '0', 'alt' => '', 'class' => 'invert_filter main_menu_icon']),
                        ],
                        true
                    );
                }

                $graphIconsContent[] = html_print_anchor(
                    [
                        'href'    => 'javascript: show_module_detail_dialog('.$row['id_agente_modulo'].', '.$row['id_agent'].', \''.$row['server_name'].'\', 0, '.SECONDS_1DAY.', \''.$row['module_name'].'\')',
                        'content' => html_print_image(
                            'images/simple-value.svg',
                            true,
                            [
                                'border' => '0',
                                'alt'    => '',
                                'class'  => 'invert_filter main_menu_icon',
                            ]
                        ),
                    ],
                    true
                );

                $graphIconsContent[] = '<span id=\'hidden_name_module_'.$row['id_agente_modulo'].'\'
								class=\'invisible\'>'.$row['module_name'].'</span>';

                $data[8] = html_print_div(
                    [
                        'class'   => 'table_action_buttons',
                        'content' => implode('', $graphIconsContent),
                    ],
                    true
                );
            }
        }

        if (in_array('warn', $show_fields) || is_metaconsole()) {
            $data[9] = ui_print_module_warn_value(
                $row['max_warning'],
                $row['min_warning'],
                $row['str_warning'],
                $row['max_critical'],
                $row['min_critical'],
                $row['str_critical'],
                $row['warning_inverse'],
                $row['critical_inverse']
            );

            if (is_numeric($row['datos']) && !modules_is_string_type($row['module_type'])) {
                if ($config['render_proc']) {
                    switch ($row['module_type']) {
                        case 2:
                        case 6:
                        case 9:
                        case 18:
                        case 21:
                        case 31:
                            if ($row['datos'] >= 1) {
                                $salida = $config['render_proc_ok'];
                            } else {
                                $salida = $config['render_proc_fail'];
                            }
                        break;

                        default:
                            switch ($row['module_type']) {
                                case 15:
                                    $value = db_get_value('snmp_oid', 'tagente_modulo', 'id_agente_modulo', $row['id_agente_modulo']);
                                    if ($value == '.1.3.6.1.2.1.1.3.0' || $value == '.1.3.6.1.2.1.25.1.1.0') {
                                        $salida = human_milliseconds_to_string($row['datos']);
                                    } else {
                                        $salida = remove_right_zeros(number_format($row['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator']));
                                    }
                                break;

                                default:
                                    $salida = remove_right_zeros(number_format($row['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator']));
                                break;
                            }
                        break;
                    }
                } else {
                    switch ($row['module_type']) {
                        case 15:
                            $value = db_get_value('snmp_oid', 'tagente_modulo', 'id_agente_modulo', $row['id_agente_modulo']);
                            if ($value == '.1.3.6.1.2.1.1.3.0' || $value == '.1.3.6.1.2.1.25.1.1.0') {
                                $salida = human_milliseconds_to_string($row['datos']);
                            } else {
                                $salida = remove_right_zeros(number_format($row['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator']));
                            }
                        break;

                        default:
                            $salida = remove_right_zeros(number_format($row['datos'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator']));
                        break;
                    }
                }

                // Show units ONLY in numeric data types.
                if (isset($row['unit'])) {
                    $data_macro = modules_get_unit_macro($row['datos'], $row['unit']);
                    if ($data_macro) {
                        $salida = $data_macro;
                    } else {
                        $salida .= '&nbsp;'.'<i>'.io_safe_output($row['unit']).'</i>';
                        if (strlen($salida) > $config['agent_size_text_small']) {
                            $salida = ui_print_truncate_text($salida, 'agent_small', true, true, false, '[&hellip;]', 'font-size:7.5pt;');
                            // Clean tag <i>.
                            $text_aux = explode('<a', $salida);
                            $match = preg_replace('/(&lt;i&gt;|&lt;\/i&gt;|&lt;i|&lt;\/i|i&gt;|\/i&gt;|&lt;|&gt;)/', '', $text_aux[0]);
                            $salida = $match.'<a'.$text_aux[1];
                        } else {
                            $salida = ui_print_truncate_text($salida, 'agent_small', true, true, false, '[&hellip;]', 'font-size:7.5pt;');
                        }
                    }
                }
            } else {
                // Fixed the goliat sends the strings from web.
                // Without HTML entities.
                if ($is_web_content_string) {
                    $module_value = $row['datos'];
                } else {
                    $module_value = io_safe_output($row['datos']);
                }

                $is_snapshot = is_snapshot_data($module_value);
                $is_large_image = is_text_to_black_string($module_value);

                if (($config['command_snapshot']) && ($is_snapshot || $is_large_image)) {
                    $link = ui_get_snapshot_link(
                        [
                            'id_module'   => $row['id_agente_modulo'],
                            'interval'    => $row['current_interval'],
                            'module_name' => $row['module_name'],
                            'id_node'     => $row['server_id'],
                        ]
                    );
                    $salida = ui_get_snapshot_image($link, $is_snapshot).'&nbsp;&nbsp;';
                } else {
                    $sub_string = substr(io_safe_output($row['datos']), 0, 12);
                    if ($module_value == $sub_string) {
                        if ((empty($module_value) === true || $module_value == 0) && !$sub_string) {
                            $salida = 0;
                        } else {
                            $data_macro = modules_get_unit_macro($row['datos'], $row['unit']);
                            if ($data_macro) {
                                $salida = $data_macro;
                            } else {
                                $salida = $row['datos'];
                            }
                        }
                    } else {
                        // Fixed the goliat sends the strings from web.
                        // Without HTML entities.
                        if ($is_web_content_string) {
                            $sub_string = substr($row['datos'], 0, 12);
                        } else {
                            // Fixed the data from Selenium Plugin.
                            if ($module_value != strip_tags($module_value)) {
                                $module_value = io_safe_input($module_value);
                                $sub_string = substr($row['datos'], 0, 12);
                            } else {
                                $sub_string = substr(io_safe_output($row['datos']), 0, 12);
                            }
                        }

                        if ($module_value == $sub_string) {
                            $salida = $module_value;
                        } else {
                            $salida = '<span '."id='hidden_value_module_".$row['id_agente_modulo']."'
								class='invisible'>".$module_value.'</span>'.'<span '."id='value_module_".$row['id_agente_modulo']."'
								title='".$module_value."' "."class='nowrap'>".'<span id="value_module_text_'.$row['id_agente_modulo'].'">'.$sub_string.'</span> '."<a href='javascript: toggle_full_value(".$row['id_agente_modulo'].")'>".html_print_image('images/rosette.png', true).'</a></span>';
                        }
                    }
                }
            }
        }

        if (in_array('data', $show_fields) || is_metaconsole()) {
            $data[10] = $salida;
        }

        if (in_array('timestamp', $show_fields) || is_metaconsole()) {
            if ($row['module_interval'] > 0) {
                $interval = $row['module_interval'];
            } else {
                $interval = $row['agent_interval'];
            }

            if ($row['estado'] == 3) {
                $option = [
                    'html_attr' => 'class="redb"',
                    'style'     => 'font-size:7pt;',
                ];
            } else {
                $option = ['style' => 'font-size:7pt;'];
            }

            $data[11] = ui_print_timestamp($row['utimestamp'], true, $option);
        }

        if (check_acl_one_of_groups($config['id_user'], $agent_groups, 'AW')) {
            $table->cellclass[][2] = 'action_buttons';

            if (is_metaconsole() === true) {
                echo "<form id='agent-edit-redirection-".$inc_id."' target='_blank' method='POST' action='".$row['server_url']."index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&edit_module=1'>";
                html_print_input_hidden(
                    'id_agente',
                    $row['id_agent'],
                    false
                );
                html_print_input_hidden(
                    'id_agent_module',
                    $row['id_agente_modulo'],
                    false
                );
                html_print_input_hidden(
                    'loginhash',
                    'auto',
                    false
                );
                html_print_input_hidden(
                    'loginhash_data',
                    $row['hashdata'],
                    false
                );
                html_print_input_hidden(
                    'loginhash_user',
                    str_rot13($row['user']),
                    false
                );

                echo '</form>';

                $url_edit_module = $row['server_url'];
                $url_edit_module .= 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&';
                $url_edit_module .= '&id_agente='.$row['id_agent'];
                $url_edit_module .= '&tab=module&id_agent_module='.$row['id_agente_modulo'].'&edit_module=1';
                $url_edit_module .= '&loginhash=auto&loginhash_data='.$row['hashdata'];
                $url_edit_module .= '&loginhash_user='.str_rot13($row['user']);

                $agent_link = "<a href='".$url_edit_module."'>";

                $agent_alias = ui_print_truncate_text(
                    $agent_alias,
                    'agent_small',
                    false,
                    true,
                    true,
                    '[&hellip;]',
                    'font-size:7.5pt;'
                );

                $data[12] .= $agent_link.html_print_image(
                    'images/edit.svg',
                    true,
                    [
                        'alt'    => '0',
                        'border' => '',
                        'title'  => __('Edit'),
                        'class'  => 'main_menu_icon invert_filter',
                    ]
                ).'</a>';
            } else {
                $url_edit_module = $row['server_url'];
                $url_edit_module .= 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&';
                $url_edit_module .= '&id_agente='.$row['id_agent'];
                $url_edit_module .= '&tab=module&id_agent_module='.$row['id_agente_modulo'].'&edit_module=1';
                $data[12] .= '<a href="'.$url_edit_module.'">'.html_print_image(
                    'images/edit.svg',
                    true,
                    [
                        'alt'    => '0',
                        'border' => '',
                        'title'  => __('Edit'),
                        'class'  => 'main_menu_icon invert_filter',
                    ]
                ).'</a>';
            }

            // Delete.
            if (is_metaconsole() === false) {
                $url_delete_module = $row['server_url'].'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente';
                $url_delete_module .= '&id_agente='.$row['id_agent'].'&delete_module='.$row['id_agente_modulo'];

                $onclick = 'onclick="javascript: if (!confirm(\''.__('Are you sure to delete?').'\')) return false;';
                $data[12] .= '<a href="'.$url_delete_module.'" '.$onclick.'" target="_blank">'.html_print_image(
                    'images/delete.svg',
                    true,
                    [
                        'alt'    => '0',
                        'border' => '',
                        'title'  => __('Delete'),
                        'class'  => 'main_menu_icon invert_filter',
                    ]
                ).'</a>';
            }
        }

        $inc_id++;

        array_push($table->data, $data);
    }

    if (!defined('METACONSOLE')) {
        echo '<div class="total_pages">'.sprintf(__('Total items: %s'), $count).'</div>';
    }

    html_print_table($table);

    if ($count_modules > $config['block_size']) {
        $show_count = false;
        if (is_metaconsole() === true) {
            $show_count = true;
        }

        $tablePagination = ui_pagination($count_modules, false, $offset, 0, true, 'offset', $show_count);
    }
} else {
    ui_print_info_message(['no_close' => true, 'message' => __('Please apply a filter to display the data')]);
}

if (isset($tablePagination) === false) {
    $tablePagination = '';
}

if (is_metaconsole() !== true) {
    html_print_action_buttons(
        '',
        [
            'type'          => 'form_action',
            'right_content' => $tablePagination,
        ]
    );
}

// End Build List Result.
echo "<div id='monitor_details_window'></div>";

// Load filter div for dialog.
echo '<div id="load-modal-filter" style="display:none"></div>';
echo '<div id="save-modal-filter" style="display:none"></div>';

ui_require_javascript_file('pandora_modules');

?>
<script type="text/javascript">

var loading = 0;

/* Filter management */
$('#button-load-filter').click(function (event) {
   // event.preventDefault();

    if($('#load-filter-select').length) {
        $('#load-filter-select').dialog();
    } else {
        if (loading == 0) {
            loading = 1
            $.ajax({
                method: 'POST',
                url: '<?php echo ui_get_full_url('ajax.php'); ?>',
                data: {
                    page: 'include/ajax/module',
                    load_filter_modal: 1
                },
                success: function (data){
                    $('#load-modal-filter')
                        .empty()
                        .html(data);

                    loading = 0;
                }
            });
        }
    }
});

$('#button-save-filter').click(function (){
   // event.preventDefault();
    if($('#save-filter-select').length) {
        $('#save-filter-select').dialog();
    } else {
        if (loading == 0) {
            loading = 1
            $.ajax({
                method: 'POST',
                url: '<?php echo ui_get_full_url('ajax.php'); ?>',
                data: {
                    page: 'include/ajax/module',
                    save_filter_modal: 1,
                    current_filter: $('#latest_filter_id').val()
                },
                success: function (data){
                    $('#save-modal-filter')
                    .empty()
                    .html(data);
                    loading = 0;
                }
            });
        }
    }
});

if(!document.getElementById('not_condition_switch').checked){
    document.getElementById("select2-ag_group-container").innerHTML = "None";

}

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

// Show the modal window of an module.
function show_module_detail_dialog(module_id, id_agent, server_name, offset, period, module_name) {
    if (period == -1) {
        if ($("#period").length == 1) {
            period = $('#period').val();
        }
        else {
            period = <?php echo SECONDS_1DAY; ?>;
        }
    }

    
    if ($('input[name=selection_mode]:checked').val()) {

        var selection_mode = $('input[name=selection_mode]:checked').val();
        var date_from = $('#text-date_from').val();
        var time_from = $('#text-time_from').val();
        var date_to = $('#text-date_to').val();
        var time_to = $('#text-time_to').val();
        
        var extra_parameters = '&selection_mode=' + selection_mode + '&date_from=' + date_from + '&date_to=' + date_to + '&time_from=' + time_from + '&time_to=' + time_to;
    
    }
    title = <?php echo '"'.__('Module: ').'"'; ?>;
    $.ajax({
        type: "POST",
        url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        data: "page=include/ajax/module&get_module_detail=1&server_name="+server_name+"&id_agent="+id_agent+"&id_module=" + module_id+"&offset="+offset+"&period="+period+extra_parameters,
        dataType: "html",
        success: function(data) {
            $("#monitor_details_window").hide ()
                .empty ()
                .append (data)
                .dialog ({
                    resizable: true,
                    draggable: true,
                    modal: true,
                    title: title + module_name,
                    overlay: {
                        opacity: 0.5,
                        background: "black"
                    },
                    width: 700,
                    height: 500
                })
                .show ();
            
            refresh_pagination_callback (module_id, id_agent, server_name,module_name);
        }
    });
}
    
function refresh_pagination_callback (module_id, id_agent, server_name,module_name) {
    
    $(".binary_dialog").click( function() {
        var classes = $(this).attr('class');
        classes = classes.split(' ');
        var offset_class = classes[2];
        offset_class = offset_class.split('_');
        var offset = offset_class[1];
        
        var period = $('#period').val();
        
        show_module_detail_dialog(module_id, id_agent, server_name, offset, period,module_name);
        
        return false;
    });
}


function changeNotConditionStatus() {

    let chkbox =document.getElementById('not_condition_switch');
    if(chkbox.checked) {

        $('select[name=datatypebox] > option:first-child').val('None');
        $('#datatypebox option:first').text('None');
        $('select[name=status] > option:first-child').text('None');
        $('select[name=moduletype] > option:first-child').text('None');
        $('select[name=modulegroup] > option:first-child').text('None');
        $('select[name=tag_filter] > option:first-child').text('None');

        $("#status").select2().val(["None"]).trigger("change");
        $("#moduletype").select2().val(["None"]).trigger("change");
        $("#modulegroup").select2().val(["None"]).trigger("change");
        $("#tag_filter").select2().val(["None"]).trigger("change");

        document.getElementById("select2-status-container").innerHTML = "None";
        document.getElementById("select2-moduletype-container").innerHTML = "None";
        document.getElementById("select2-ag_group-container").innerHTML = "None";
        document.getElementById("select2-modulegroup-container").innerHTML = "None";

    }else {
        $('select[name=datatypebox] > option:first-child').val('All');
        $('#datatypebox option:first').text('All');
        $('select[name=status] > option:first-child').text('All');
        $('select[name=moduletype] > option:first-child').text('All');
        $('select[name=modulegroup] > option:first-child').text('All');
        $('select[name=tag_filter] > option:first-child').text('All');

        $('#datatypebox option:first').text('All');

        $("#status").select2().val(["All"]).trigger("change");
        $("#moduletype").select2().val(["All"]).trigger("change");
        $("#modulegroup").select2().val(["All"]).trigger("change");
        $("#tag_filter").select2().val(["All"]).trigger("change");


        document.getElementById("select2-status-container").innerHTML = "All";
        document.getElementById("select2-moduletype-container").innerHTML = "All";
        document.getElementById("select2-ag_group-container").innerHTML = "All";
        document.getElementById("select2-modulegroup-container").innerHTML = "All";
    }

}


let chkbox =document.getElementById('not_condition_switch');
let value_swtich = "<?php echo $not_condition; ?>";
if( value_swtich != "") {
    chkbox.checked = true;
}else {
    chkbox.checked = false;
}
</script>