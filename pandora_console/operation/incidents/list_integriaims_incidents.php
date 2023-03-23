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

require_once 'include/functions_integriaims.php';

check_login();

// Header tabs.
$onheader = integriaims_tabs('list_tab');
ui_print_standard_header(
    __('Integria IMS Tickets'),
    '',
    false,
    'integria_tab',
    false,
    $onheader,
    [
        [
            'link'  => '',
            'label' => __('Issues'),
        ],
        [
            'link'  => '',
            'label' => __('Integria IMS Tickets'),
        ],
    ]
);

// Check if Integria integration enabled.
if ($config['integria_enabled'] == 0) {
    ui_print_error_message(__('In order to access ticket management system, integration with Integria IMS must be enabled and properly configured'));
    return;
}

// Check connection to Integria IMS API.
$has_connection = integria_api_call(null, null, null, null, 'get_login', []);

if ($has_connection === false) {
    ui_print_error_message(__('Integria IMS API is not reachable'));
    return;
}

// Styles.
ui_require_css_file('integriaims');

// Get parameters for filters.
$incident_text = (string) get_parameter('incident_text', '');
$incident_status = (int) get_parameter('incident_status', 0);
$incident_group = (int) get_parameter('incident_group', 1);
$incident_owner = (string) get_parameter('incident_owner', '');
$incident_creator = (string) get_parameter('incident_creator', '');
$incident_priority = (int) get_parameter('incident_priority', -1);
$incident_resolution = (string) get_parameter('incident_resolution', '');
$created_from = (string) get_parameter('created_from', '');
$created_to = (string) get_parameter('created_to', '');

$offset = (int) get_parameter('offset');

$delete_incident = get_parameter('delete_incident');

// Sorting.
$sort_field = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');

$selected = true;
$select_incident_id_up = false;
$select_incident_id_down = false;
$select_title_up = false;
$select_title_down = false;
$select_group_company_up = false;
$select_group_company_down = false;
$select_status_resolution_up = false;
$select_status_resolution_down = false;
$select_priority_up = false;
$select_priority_down = false;
$select_creator_up = false;
$select_creator_down = false;
$select_owner_up = false;
$select_owner_down = false;

$order[] = [
    'field' => 'incident_id',
    'order' => 'ASC',
];

switch ($sort_field) {
    case 'incident_id':
        switch ($sort) {
            case 'up':
                $select_incident_id_up = $selected;
                $order = [
                    'field' => 0,
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $select_incident_id_down = $selected;
                $order = [
                    'field' => 0,
                    'order' => 'DESC',
                ];
            break;

            default:
                // Nothing to do.
            break;
        }
    break;

    case 'title':
        switch ($sort) {
            case 'up':
                $select_title_up = $selected;
                $order = [
                    'field' => 3,
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $select_title_down = $selected;
                $order = [
                    'field' => 3,
                    'order' => 'DESC',
                ];
            break;

            default:
                // Nothing to do.
            break;
        }
    break;

    case 'group_company':
        switch ($sort) {
            case 'up':
                $select_group_company_up = $selected;
                $order = [
                    'field' => 'group_company',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $select_group_company_down = $selected;
                $order = [
                    'field' => 'group_company',
                    'order' => 'DESC',
                ];
            break;

            default:
                // Nothing to do.
            break;
        }
    break;

    case 'status_resolution':
        switch ($sort) {
            case 'up':
                $select_status_resolution_up = $selected;
                $order = [
                    'field' => 'status_resolution',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $select_status_resolution_down = $selected;
                $order = [
                    'field' => 'status_resolution',
                    'order' => 'DESC',
                ];
            break;

            default:
                // Nothing to do.
            break;
        }
    break;

    case 'priority':
        switch ($sort) {
            case 'up':
                $select_priority_up = $selected;
                $order = [
                    'field' => 7,
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $select_priority_down = $selected;
                $order = [
                    'field' => 7,
                    'order' => 'DESC',
                ];
            break;

            default:
                // Nothing to do.
            break;
        }
    break;

    case 'creator':
        switch ($sort) {
            case 'up':
                $select_creator_up = $selected;
                $order = [
                    'field' => 10,
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $select_creator_down = $selected;
                $order = [
                    'field' => 10,
                    'order' => 'DESC',
                ];
            break;

            default:
                // Nothing to do.
            break;
        }
    break;

    case 'owner':
        switch ($sort) {
            case 'up':
                $select_owner_up = $selected;
                $order = [
                    'field' => 5,
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $select_owner_down = $selected;
                $order = [
                    'field' => 5,
                    'order' => 'DESC',
                ];
            break;

            default:
                // Nothing to do.
            break;
        }
    break;

    default:
        $select_incident_id_up = $selected;
        $select_incident_id_down = false;
        $select_title_up = false;
        $select_title_down = false;
        $select_group_company_up = false;
        $select_group_company_down = false;
        $select_status_resolution_up = false;
        $select_status_resolution_down = false;
        $select_priority_up = false;
        $select_priority_down = false;
        $select_creator_up = false;
        $select_creator_down = false;
        $select_owner_up = false;
        $select_owner_down = false;
        $order = [
            'field' => 'id_user',
            'order' => 'ASC',
        ];
    break;
}

if ($delete_incident) {
    // Call Integria IMS API method to delete an incident.
    $result_api_call_delete = integria_api_call(
        null,
        null,
        null,
        null,
        'delete_incident',
        [$delete_incident]
    );

    $incident_deleted_ok = ($result_api_call_delete !== false) ? true : false;

    ui_print_result_message(
        $incident_deleted_ok,
        __('Successfully deleted'),
        __('Could not be deleted')
    );
}

// ---- FILTERS ----
// API calls to fill the filters.
$status_incident = integriaims_get_details('status');
$group_incident = integriaims_get_details('group');
$priority_incident = integriaims_get_details('priority');
$resolution_incident = integriaims_get_details('resolution');


// TABLE FILTERS.
$table = new StdClass();
$table->width = '100%';
$table->size = [];
$table->size[0] = '33%';
$table->size[1] = '33%';
$table->size[2] = '33%';
$table->class = 'filter-table-adv';

$table->data = [];
$table->data[0][0] = html_print_label_input_block(
    __('Text filter'),
    html_print_input_text('incident_text', $incident_text, '', 30, 100, true)
);

$table->data[0][1] = html_print_label_input_block(
    __('Status'),
    html_print_select(
        $status_incident,
        'incident_status',
        $incident_status,
        '',
        __('All'),
        0,
        true
    )
);

$table->data[0][2] = html_print_label_input_block(
    __('Group'),
    html_print_select(
        $group_incident,
        'incident_group',
        $incident_group,
        '',
        __('All'),
        1,
        true
    )
);

$table->data[1][0] = html_print_label_input_block(
    __('Owner'),
    html_print_autocomplete_users_from_integria(
        'incident_owner',
        $incident_owner,
        true,
        '30',
        false,
        false,
        'w100p'
    ),
    ['div_class' => 'inline']
);

$table->data[1][1] = html_print_label_input_block(
    __('Creator'),
    html_print_autocomplete_users_from_integria(
        'incident_creator',
        $incident_creator,
        true,
        '30',
        false,
        false,
        'w100p'
    ),
    ['div_class' => 'inline']
);

$table->data[1][2] = html_print_label_input_block(
    __('Priority'),
    html_print_select(
        $priority_incident,
        'incident_priority',
        $incident_priority,
        '',
        __('All'),
        -1,
        true
    )
);

$table->data[2][0] = html_print_label_input_block(
    __('Resolution'),
    html_print_select(
        $resolution_incident,
        'incident_resolution',
        $incident_resolution,
        '',
        __('All'),
        '',
        true
    )
);

$input_date = '<div>';
$input_date .= html_print_input_text_extended(
    'created_from',
    $created_from,
    'created_from',
    '',
    12,
    50,
    false,
    '',
    'placeholder="'.__('Created from').'"',
    true
);
$input_date .= html_print_input_text_extended(
    'created_to',
    $created_to,
    'created_to',
    '',
    12,
    50,
    false,
    '',
    'class="mrgn_lft_5px" placeholder="'.__('Created to').'"',
    true
);
$input_date .= '</div>';

$table->data[2][2] = html_print_label_input_block(
    __('Date'),
    $input_date
);

// Send filters to get_tickets_integriaims().
$tickets_filters = [
    'incident_text'       => $incident_text,
    'incident_status'     => $incident_status,
    'incident_group'      => $incident_group,
    'incident_owner'      => $incident_owner,
    'incident_creator'    => $incident_creator,
    'incident_priority'   => $incident_priority,
    'incident_resolution' => $incident_resolution,
    'created_from'        => $created_from,
    'created_to'          => $created_to,
];

// Data to export to csv file.
$decode_csv = base64_encode(json_encode($tickets_filters));

// Full url with all filters.
$url = ui_get_full_url(
    'index.php?sec=incident&sec2=operation/incidents/list_integriaims_incidents&incident_text='.$incident_text.'&incident_status='.$incident_status.'&incident_group='.$incident_group.'&incident_owner='.$incident_owner.'&incident_creator='.$incident_creator.'&incident_priority='.$incident_priority.'&incident_resolution='.$incident_resolution.'&created_from='.$created_from.'&created_to='.$created_to.'&offset='.$offset.'&sort_field='.$sort_field.'&sort='.$sort
);

// ---- PRINT TABLE FILTERS ----
$integria_incidents_form = '<form method="post" action="'.$url.'" class="pdd_0px">';
$integria_incidents_form .= html_print_table($table, true);
$buttons = html_print_submit_button(
    __('Filter'),
    'filter_button',
    false,
    [
        'icon' => 'search',
        'mode' => 'mini secondary',
    ],
    true
);
$buttons .= html_print_button(
    __('Export to CSV'),
    'csv_export',
    false,
    "blockResubmit($(this)); location.href='operation/incidents/integriaims_export_csv.php?tickets_filters=$decode_csv'",
    [
        'icon' => 'cog',
        'mode' => 'mini secondary',
    ],
    true
);

$integria_incidents_form .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => $buttons,
    ],
    true
);
$integria_incidents_form .= '</form>';

ui_toggle(
    $integria_incidents_form,
    '<span class="subsection_header_title">'.__('Filters').'</span>',
    'filter_form',
    '',
    true,
    false,
    '',
    'white-box-content',
    'box-flat white_table_graph fixed_filter_bar'
);

/*
 * Order api call 'get_incidents'.
 *
 * resolution    = $array_get_incidents[$key][12]
 * id_incidencia = $array_get_incidents[$key][0]
 * titulo        = $array_get_incidents[$key][3]
 * id_grupo      = $array_get_incidents[$key][8]
 * estado        = $array_get_incidents[$key][6]
 * prioridad     = $array_get_incidents[$key][7]
 * actualizacion = $array_get_incidents[$key][9]
 * id_creator    = $array_get_incidents[$key][10]
 *
 */

// ---- LIST OF INCIDENTS ----
// Get list of incidents.
$array_get_incidents = get_tickets_integriaims($tickets_filters);

$props = [
    'order'               => $order,
    'group_incident'      => $group_incident,
    'status_incident'     => $status_incident,
    'resolution_incident' => $resolution_incident,
];

usort(
    $array_get_incidents,
    function ($a, $b) use ($props) {
        $order_field = $props['order']['field'];

        $item_a = $a[$order_field];
        $item_b = $b[$order_field];

        if ($order_field === 'group_company') {
            $item_a = $props['group_incident'][$a[8]];
            $item_b = $props['group_incident'][$b[8]];
        } else if ($order_field === 'status_resolution') {
            $item_a = $props['status_incident'][$a[6]].' / '.$props['resolution_incident'][$a[12]];
            $item_b = $props['status_incident'][$b[6]].' / '.$props['resolution_incident'][$b[12]];
        }

        if ($props['order']['order'] === 'DESC') {
            return $item_a < $item_b;
        } else {
            return $item_a > $item_b;
        }
    }
);

// Prepare pagination.
$incidents_limit = $config['block_size'];
$incidents_paginated = array_slice($array_get_incidents, $offset, $incidents_limit, true);

// TABLE INCIDENTS.
$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table';
$table->head = [];

$url_incident_id_up = $url.'&sort_field=incident_id&sort=up';
$url_incident_id_down = $url.'&sort_field=incident_id&sort=down';
$url_title_up = $url.'&sort_field=title&sort=up';
$url_title_down = $url.'&sort_field=title&sort=down';
$url_group_company_up = $url.'&sort_field=group_company&sort=up';
$url_group_company_down = $url.'&sort_field=group_company&sort=down';
$url_status_resolution_up = $url.'&sort_field=status_resolution&sort=up';
$url_status_resolution_down = $url.'&sort_field=status_resolution&sort=down';
$url_priority_up = $url.'&sort_field=priority&sort=up';
$url_priority_down = $url.'&sort_field=priority&sort=down';
$url_creator_up = $url.'&sort_field=creator&sort=up';
$url_creator_down = $url.'&sort_field=creator&sort=down';
$url_owner_up = $url.'&sort_field=owner&sort=up';
$url_owner_down = $url.'&sort_field=owner&sort=down';

$table->head[0] = __('ID').ui_get_sorting_arrows($url_incident_id_up, $url_incident_id_down, $select_incident_id_up, $select_incident_id_down);
$table->head[1] = __('Title').ui_get_sorting_arrows($url_title_up, $url_title_down, $select_title_up, $select_title_down);
$table->head[2] = __('Group/Company').ui_get_sorting_arrows($url_group_company_up, $url_group_company_down, $select_group_company_up, $select_group_company_down);
$table->head[3] = __('Status/Resolution').ui_get_sorting_arrows($url_status_resolution_up, $url_status_resolution_down, $select_status_resolution_up, $select_status_resolution_down);
$table->head[4] = __('Priority').ui_get_sorting_arrows($url_priority_up, $url_priority_down, $select_priority_up, $select_priority_down);
$table->head[5] = __('Updated/Started');
$table->head[6] = __('Creator').ui_get_sorting_arrows($url_creator_up, $url_creator_down, $select_creator_up, $select_creator_down);
$table->head[7] = __('Owner').ui_get_sorting_arrows($url_owner_up, $url_owner_down, $select_owner_up, $select_owner_down);
$table->head[8] = '';

$table->data = [];
$i = 0;

foreach ($incidents_paginated as $key => $value) {
    if ($array_get_incidents[$key][6] == 0) {
        $status_incident[$array_get_incidents[$key][6]] = __('None');
    }

    if ($array_get_incidents[$key][12] == 0) {
        $resolution_incident[$array_get_incidents[$key][12]] = __('None');
    }

    $table->data[$i][0] = '#'.$array_get_incidents[$key][0];
    $table->data[$i][1] = '<a href="'.ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/dashboard_detail_integriaims_incident&incident_id='.$array_get_incidents[$key][0]).'">';
    $table->data[$i][1] .= ui_print_truncate_text($array_get_incidents[$key][3], 160, false);
    $table->data[$i][1] .= '</a>';
    $table->data[$i][2] = $group_incident[$array_get_incidents[$key][8]];
    $table->data[$i][3] = $status_incident[$array_get_incidents[$key][6]].' / '.$resolution_incident[$array_get_incidents[$key][12]];
    $table->data[$i][4] = ui_print_integria_incident_priority($array_get_incidents[$key][7], $priority_incident[$array_get_incidents[$key][7]]);
    $table->data[$i][5] = $array_get_incidents[$key][9].' / '.$array_get_incidents[$key][1];
    $table->data[$i][6] = $array_get_incidents[$key][10];
    $table->data[$i][7] = $array_get_incidents[$key][5];
    $table->data[$i][8] = '';
    $table->cellclass[$i][8] = 'table_action_buttons';
    $table->data[$i][8] .= '<a href="'.ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/configure_integriaims_incident&incident_id='.$array_get_incidents[$key][0]).'">';
    $table->data[$i][8] .= html_print_image('images/edit.svg', true, ['title' => __('Edit')]);
    $table->data[$i][8] .= '</a>';

    $table->data[$i][8] .= '<a id="link_delete_incident" href="'.ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/list_integriaims_incidents&delete_incident='.$array_get_incidents[$key][0]).'"        
    onClick="javascript:if (!confirm(\''.__('Are you sure?').'\')) return false;">';
    $table->data[$i][8] .= html_print_image('images/delete.svg', true, ['title' => __('Delete'), 'class' => 'invert_filter main_menu_icon']);
    $table->data[$i][8] .= '</a>';

    $i++;
}

$tablePagination = '';
// Show table incidents.
if (empty($table->data) === true) {
    ui_print_info_message(['no_close' => true, 'message' => __('No tickets to show').'.' ]);
} else {
    html_print_table($table);
    $tablePagination = ui_pagination(
        count($array_get_incidents),
        $url,
        $offset,
        0,
        true,
        'offset',
        false,
        ''
    );
}

// Show button to create incident.
echo '<form method="POST" action="'.ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/configure_integriaims_incident').'">';
html_print_action_buttons(
    html_print_submit_button(
        __('Create'),
        'create_new_incident',
        false,
        [ 'icon' => 'next' ],
        true
    ),
    [
        'type'          => 'data_table',
        'class'         => 'fixed_action_buttons',
        'right_content' => $tablePagination,
    ]
);
echo '</form>';

// Datapicker library for show calendar.
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');
?>

<script language="javascript" type="text/javascript">
    $(document).ready( function() {
        $("#created_from, #created_to").datepicker({
            dateFormat: "<?php echo DATE_FORMAT_JS; ?>"
        });  
    });
</script>
