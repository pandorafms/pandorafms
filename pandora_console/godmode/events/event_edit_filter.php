<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

check_login();

$event_w = check_acl($config['id_user'], 0, 'EW');
$event_m = check_acl($config['id_user'], 0, 'EM');
$access = ($event_w == true) ? 'EW' : (($event_m == true) ? 'EM' : 'EW');

if (!$event_w && !$event_m) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access events filter editor'
    );
    include 'general/noaccess.php';
    return;
}

$id = (int) get_parameter('id');
$update = (string) get_parameter('update', 0);
$create = (string) get_parameter('create', 0);

$strict_user = db_get_value(
    'strict_acl',
    'tusuario',
    'id_user',
    $config['id_user']
);

if ($id) {
    $restrict_all_group = false;

    if (!users_can_manage_group_all('EW') === true
        && !users_can_manage_group_all('EM') === true
    ) {
        $restrict_all_group = true;
    }

    $permission = events_check_event_filter_group($id, $restrict_all_group);
    if (!$permission) {
        // User doesn't have permissions to see this filter
        include 'general/noaccess.php';

        return;
    }
}

if ($id) {
    $filter = events_get_event_filter($id);
    $id_group_filter = $filter['id_group_filter'];
    $id_group = $filter['id_group'];
    $id_name = $filter['id_name'];
    $event_type = $filter['event_type'];
    $severity = explode(',', $filter['severity']);
    $status = $filter['status'];
    $search = $filter['search'];
    $not_search = $filter['not_search'];
    $text_agent = $filter['text_agent'];
    $id_agent = $filter['id_agent'];
    $text_module = $filter['text_module'];
    $id_agent_module = $filter['id_agent_module'];
    $pagination = $filter['pagination'];
    $event_view_hr = $filter['event_view_hr'];
    $id_user_ack = $filter['id_user_ack'];
    $owner_user = $filter['owner_user'];
    $group_rep = $filter['group_rep'];
    $date_from = str_replace('-', '/', $filter['date_from']);
    $date_to = str_replace('-', '/', $filter['date_to']);
    $source = $filter['source'];
    $id_extra = $filter['id_extra'];
    $user_comment = $filter['user_comment'];

    $tag_with_json = $filter['tag_with'];
    $tag_with_json_clean = io_safe_output($tag_with_json);
    $tag_with_base64 = base64_encode($tag_with_json_clean);

    $tag_without_json = $filter['tag_without'];
    $tag_without_json_clean = io_safe_output($tag_without_json);
    $tag_without_base64 = base64_encode($tag_without_json_clean);

    $filter_only_alert = $filter['filter_only_alert'];
    $search_secondary_groups = $filter['search_secondary_groups'];
    $search_recursive_groups = $filter['search_recursive_groups'];
    $custom_data = $filter['custom_data'];
    $custom_data_filter_type = $filter['custom_data_filter_type'];

    if ($id_agent_module != 0) {
        $text_module = modules_get_agentmodule_name($id_agent_module);
        if ($text_module == false) {
            $text_module = '';
        }
    }

    if ($id_agent != 0) {
        $text_agent = agents_get_alias($id_agent);
        if ($text_agent == false) {
            $text_agent = '';
        }
    }

    $server_id = ($filter['server_id'] ?? '');
} else {
    $id_group = '';
    $id_group_filter = '';
    $id_name = '';
    $event_type = '';
    $severity = '';
    $status = '';
    $search = '';
    $not_search = 0;
    $text_agent = '';
    $pagination = '';
    $event_view_hr = '';
    $id_user_ack = '';
    $owner_user = '';
    $group_rep = '';
    $date_from = '';
    $date_to = '';

    $tag_with_json = $tag_with_json_clean = json_encode([]);
    $tag_with_base64 = base64_encode($tag_with_json);
    $tag_without_json = $tag_without_json_clean = json_encode([]);
    $tag_without_base64 = base64_encode($tag_without_json);
    $filter_only_alert = '';
    $search_secondary_groups = 0;
    $search_recursive_groups = 0;
    $server_id = '';
}

if ($update || $create) {
    $id_group = (string) get_parameter('id_group');
    $id_group_filter = get_parameter('id_group_filter');
    $id_name = (string) get_parameter('id_name');
    $event_type = get_parameter('event_type', '');
    $severity = implode(',', get_parameter('severity', -1));
    $status = get_parameter('status', '');
    $search = get_parameter('search', '');
    $not_search = get_parameter_switch('not_search', 0);
    $text_agent = get_parameter('text_agent', '');
    $id_agent = (int) get_parameter('id_agent');
    $text_module = get_parameter('text_module', '');
    $id_agent_module = (int) get_parameter('module_search_hidden');
    if ($text_module === '') {
        $text_module = io_safe_output(
            db_get_value_filter(
                'nombre',
                'tagente_modulo',
                ['id_agente_modulo' => $id_agent_module]
            )
        );
    }

    $pagination = get_parameter('pagination', '');
    $event_view_hr = get_parameter('event_view_hr', '');
    $id_user_ack = get_parameter('id_user_ack', '');
    $owner_user = get_parameter('owner_user', '');
    $group_rep = get_parameter('group_rep', '');
    $date_from = get_parameter('date_from', '');
    $date_to = get_parameter('date_to', '');
    $source = get_parameter('source');
    $id_extra = get_parameter('id_extra');
    $user_comment = get_parameter('user_comment');

    $tag_with_base64 = get_parameter('tag_with', json_encode([]));
    $tag_with_json = io_safe_input(base64_decode($tag_with_base64));

    $tag_without_base64 = get_parameter('tag_without', json_encode([]));
    $tag_without_json = io_safe_input(base64_decode($tag_without_base64));

    $filter_only_alert = get_parameter('filter_only_alert', '');
    $search_secondary_groups = get_parameter('search_secondary_groups', 0);
    $search_recursive_groups = get_parameter('search_recursive_groups', 0);

    $custom_data = get_parameter('custom_data', '');
    $custom_data_filter_type = get_parameter('custom_data_filter_type', '');

    $server_id = '';
    if (is_metaconsole() === true) {
        $servers_array = get_parameter('server_id', []);
        $server_id = implode(',', $servers_array);
    }

    $values = [
        'id_name'                 => $id_name,
        'id_group_filter'         => $id_group_filter,
        'id_group'                => $id_group,
        'event_type'              => $event_type,
        'severity'                => $severity,
        'status'                  => $status,
        'search'                  => $search,
        'not_search'              => $not_search,
        'text_agent'              => $text_agent,
        'id_agent_module'         => $id_agent_module,
        'id_agent'                => $id_agent,
        'pagination'              => $pagination,
        'event_view_hr'           => $event_view_hr,
        'id_user_ack'             => $id_user_ack,
        'owner_user'              => $owner_user,
        'group_rep'               => $group_rep,
        'tag_with'                => $tag_with_json,
        'tag_without'             => $tag_without_json,
        'date_from'               => $date_from,
        'date_to'                 => $date_to,
        'source'                  => $source,
        'id_extra'                => $id_extra,
        'user_comment'            => $user_comment,
        'filter_only_alert'       => $filter_only_alert,
        'search_secondary_groups' => $search_secondary_groups,
        'search_recursive_groups' => $search_recursive_groups,
        'custom_data'             => $custom_data,
        'custom_data_filter_type' => $custom_data_filter_type,
        'server_id'               => $server_id,
    ];

    $severity = explode(',', $severity);
}

if ($update) {
    if ($id_name == '') {
        ui_print_error_message(__('Not updated. Blank name'));
    } else {
        $result = db_process_sql_update(
            'tevent_filter',
            $values,
            ['id_filter' => $id]
        );

        ui_update_name_fav_element($id, 'Events', $id_name);

        ui_print_result_message(
            $result,
            __('Successfully updated'),
            __('Not updated. Error updating data')
        );
    }
}

if ($create) {
    if (!empty($values['id_name'])) {
        $id = db_process_sql_insert('tevent_filter', $values);

        if ($id === false) {
            ui_print_error_message('Error creating filter');
        } else {
            ui_print_success_message('Filter created successfully');
        }
    } else {
        ui_print_error_message('Filter name must not be empty');
    }
}

$own_info = get_user_info($config['id_user']);

$table = new stdClass();
$table->width = '1366px';
// $table->width = '100%';
$table->border = 0;
$table->cellspacing = 0;
$table->cellpadding = 0;
$table->size[0] = '50%';
$table->size[1] = '50%';
$table->class = 'databox filters events-filters-create pdd_10px';
$table->style[0] = 'vertical-align: top;';
$table->rowspan = [];
$table->rowspan[3][0] = 2;

$table->valign[1] = 'top';

$table->data = [];

$table->data[0][0] = html_print_label_input_block(
    __('Filter name'),
    html_print_input_text(
        'id_name',
        $id_name,
        false,
        20,
        80,
        true,
        false,
        false,
        '',
        'w100p'
    )
);

$returnAllGroup = users_can_manage_group_all();
// If the user can't manage All group but the filter is for All group, the user should see All group in the select.
if ($returnAllGroup === false && $id_group_filter == 0) {
    $returnAllGroup = true;
}

$table->data[0][1] = html_print_label_input_block(
    __('Save in group').ui_print_help_tip(__('This group will be use to restrict the visibility of this filter with ACLs'), true),
    '<div class="w100p">'.html_print_select_groups(
        $config['id_user'],
        $access,
        $returnAllGroup,
        'id_group_filter',
        $id_group_filter,
        '',
        '',
        -1,
        true,
        false,
        false,
        '',
        false,
        '',
        false,
        false,
        'id_grupo',
        $strict_user
    ).'</div>'
);
$return_all_group = false;

if (users_can_manage_group_all('AR') === true) {
    $return_all_group = true;
}

$display_all_group = (users_is_admin() || users_can_manage_group_all('AR'));
$table->data[2][0] = html_print_label_input_block(
    __('Group'),
    '<div class="w100p">'.html_print_select_groups(
        $config['id_user'],
        'AR',
        $return_all_group,
        'id_group',
        $id_group,
        '',
        '',
        '',
        true
    ).'</div>'
);

$types = get_event_types();
// Expand standard array to add not_normal (not exist in the array, used only for searches)
$types['not_normal'] = __('Not normal');
$table->data[2][1] = html_print_label_input_block(
    __('Event type'),
    '<div class="w100p">'.html_print_select(
        $types,
        'event_type',
        $event_type,
        '',
        __('All'),
        '',
        true,
        false,
        false,
        'w100p'
    ).'</div>'
);

if (empty($severity) && $severity !== '0') {
    $severity = -1;
}

$table->data[3][0] = html_print_label_input_block(
    __('Severity'),
    html_print_select(
        get_priorities(),
        'severity[]',
        $severity,
        '',
        __('All'),
        -1,
        true,
        true,
        true,
        '',
        false,
        'width: 100%'
    )
);

$fields = events_get_all_status();
$table->data[3][1] = html_print_label_input_block(
    __('Event status'),
    html_print_select(
        $fields,
        'status',
        $status,
        '',
        '',
        '',
        true,
        false,
        true,
        '',
        false,
        'width: 100%'
    )
);

$table->data[4][1] = html_print_label_input_block(
    __('Free search'),
    '<div class="flex_center">'.html_print_input_text(
        'search',
        $search,
        '',
        15,
        255,
        true,
        false,
        false,
        '',
        'w96p mrgn_right_15px'
    ).' '.html_print_checkbox_switch(
        'not_search',
        $not_search,
        $not_search,
        true,
        false,
        'checked_slide_events(this);',
        true
    ).'</div>'
);

$params = [];
$params['show_helptip'] = true;
$params['input_name'] = 'text_agent';
$params['value'] = $text_agent;
$params['return'] = true;

if (is_metaconsole()) {
    $params['javascript_page'] = 'enterprise/meta/include/ajax/events.ajax';
} else {
    $params['print_hidden_input_idagent'] = true;
    $params['hidden_input_idagent_name'] = 'id_agent';
    $params['hidden_input_idagent_value'] = $id_agent;
}

$table->data[5][0] = html_print_label_input_block(
    __('Agent search'),
    '<div class="w100p">'.ui_print_agent_autocomplete_input($params).'</div>'
);

$lpagination[25] = 25;
$lpagination[50] = 50;
$lpagination[100] = 100;
$lpagination[200] = 200;
$lpagination[500] = 500;
$table->data[5][1] = html_print_label_input_block(
    __('Block size for pagination'),
    '<div class="w100p">'.html_print_select(
        $lpagination,
        'pagination',
        $pagination,
        '',
        __('Default'),
        $config['block_size'],
        true,
        false,
        true,
        '',
        false,
        'width: 100%'
    ).'</div>'
);

$table->data[6][0] = html_print_label_input_block(
    __('Max. hours old'),
    '<div class="w100p">'.html_print_input_text(
        'event_view_hr',
        $event_view_hr,
        '',
        5,
        255,
        true,
        false,
        false,
        '',
        'w100p'
    ).'</div>'
);

if ($strict_user) {
    $users = [$config['id_user'] => $config['id_user']];
} else {
    $users = users_get_user_users(
        $config['id_user'],
        $access,
        users_can_manage_group_all()
    );
}

$table->data[6][1] = html_print_label_input_block(
    __('User ack.').' '.ui_print_help_tip(
        __('Choose between the users who have validated an event. '),
        true
    ),
    '<div class="w100p">'.html_print_select(
        $users,
        'id_user_ack',
        $id_user_ack,
        '',
        __('Any'),
        0,
        true,
        false,
        true,
        'w100p'
    ).'</div>'
);

$table->data[7][0] = html_print_label_input_block(
    __('Owner.'),
    '<div class="w100p">'.html_print_select(
        $users,
        'owner_user',
        $owner_user,
        '',
        __('Any'),
        0,
        true,
        false,
        true,
        'w100p'
    ).'</div>'
);
$repeated_sel = [
    EVENT_GROUP_REP_ALL      => __('All events'),
    EVENT_GROUP_REP_EVENTS   => __('Group events'),
    EVENT_GROUP_REP_AGENTS   => __('Group agents'),
    EVENT_GROUP_REP_EXTRAIDS => __('Group extra id'),
];

$table->data[7][1] = html_print_label_input_block(
    __('Repeated'),
    '<div class="w100p">'.html_print_select(
        $repeated_sel,
        'group_rep',
        $group_rep,
        '',
        '',
        '',
        true,
        false,
        true,
        'w100p'
    ).'</div>'
);

$date_from = html_print_label_input_block(
    __('Date from'),
    '<div class="w100p">'.html_print_input_text(
        'date_to',
        $date_to,
        '',
        15,
        10,
        true,
        false,
        false,
        '',
        'w100p'
    ).'</div>'
);

$date_to = html_print_label_input_block(
    __('Date from'),
    '<div class="w100p">'.html_print_input_text(
        'date_to',
        $date_to,
        '',
        15,
        10,
        true,
        false,
        false,
        '',
        'w100p'
    ).'</div>'
);

$table->data[8][0] = '<div class="flex-row">'.$date_from.$date_to.'</div>';

$tag_with = json_decode($tag_with_json_clean, true);
if (empty($tag_with)) {
    $tag_with = [];
}

$tag_without = json_decode($tag_without_json_clean, true);
if (empty($tag_without)) {
    $tag_without = [];
}

// Fix : only admin users can see all tags
$tags = tags_get_user_tags($config['id_user'], $access);

$tags_select_with = [];
$tags_select_without = [];
$tag_with_temp = [];
$tag_without_temp = [];

foreach ($tags as $id_tag => $tag) {
    if (array_search($id_tag, $tag_with) === false) {
        $tags_select_with[$id_tag] = $tag;
    } else {
        $tag_with_temp[$id_tag] = $tag;
    }

    if (array_search($id_tag, $tag_without) === false) {
        $tags_select_without[$id_tag] = $tag;
    } else {
        $tag_without_temp[$id_tag] = $tag;
    }
}

$add_with_tag_disabled = empty($tags_select_with);
$remove_with_tag_disabled = empty($tag_with_temp);
$add_without_tag_disabled = empty($tags_select_without);
$remove_without_tag_disabled = empty($tag_without_temp);

$table->data[8][0] = html_print_label_input_block(
    __('Events with following tags'),
    '<div class="w100p">'.html_print_select(
        $tags_select_with,
        'select_with',
        '',
        '',
        '',
        0,
        true,
        false,
        true,
        'w100p'
    ).'</div>'
);

$table->data[8][1] = html_print_label_input_block(
    '&nbsp;',
    '<div class="w100p">'.html_print_button(
        __('Add'),
        'add_whith',
        $add_with_tag_disabled,
        '',
        ['class' => 'submitButton mini'],
        true
    ).'</div>'
);

$table->data[9][0] = html_print_label_input_block(
    '',
    '<div class="w100p no-margin-top">'.html_print_select(
        $tag_with_temp,
        'tag_with_temp',
        [],
        '',
        '',
        0,
        true,
        true,
        true,
        '',
        false,
        'width: 100%; height: 50px;'
    ).'</div>'
).html_print_input_hidden(
    'tag_with',
    $tag_with_base64,
    true
);

$table->data[9][1] = html_print_label_input_block(
    '&nbsp;',
    '<div class="w100p">'.html_print_button(
        __('Remove'),
        'remove_whith',
        false,
        '',
        [
            'mode'  => 'link',
            'class' => 'submitButton',
        ],
        true
    ).'</div>'
);

$table->data[10][0] = html_print_label_input_block(
    __('Events without following tags'),
    '<div class="w100p">'.html_print_select(
        $tags_select_without,
        'select_without',
        '',
        '',
        '',
        0,
        true,
        false,
        true,
        'w100p'
    ).'</div>'
);

$table->data[10][1] = html_print_label_input_block(
    '&nbsp;',
    '<div class="w100p">'.html_print_button(
        __('Add'),
        'add_whithout',
        $add_without_tag_disabled,
        '',
        ['class' => 'submitButton mini'],
        true
    ).'</div>'
);

$table->data[11][0] = html_print_label_input_block(
    '',
    '<div class="w100p no-margin-top">'.html_print_select(
        $tag_without_temp,
        'tag_without_temp',
        [],
        '',
        '',
        0,
        true,
        true,
        true,
        '',
        false,
        'width: 100%; height: 50px;'
    ).'</div>'
).html_print_input_hidden(
    'tag_without',
    $tag_without_base64,
    true
);

$table->data[11][1] = html_print_label_input_block(
    '&nbsp;',
    '<div class="w100p">'.html_print_button(
        __('Remove'),
        'remove_whithout',
        false,
        '',
        [
            'mode'  => 'link',
            'class' => 'submitButton',
        ],
        true
    ).'</div>'
);

$table->data[12][0] = html_print_label_input_block(
    __('Alert events'),
    '<div class="w100p">'.html_print_select(
        [
            '-1' => __('All'),
            '0'  => __('Filter alert events'),
            '1'  => __('Only alert events'),
        ],
        'filter_only_alert',
        $filter_only_alert,
        '',
        '',
        '',
        true,
        false,
        true,
        'w100p'
    ).'</div>'
);

if (!is_metaconsole()) {
    $table->data[12][1] = html_print_label_input_block(
        __('Module search'),
        '<div class="w100p module-search">'.html_print_autocomplete_modules(
            'module_search',
            $text_module,
            false,
            true,
            '',
            [],
            true,
            $id_agent_module
        ).'</div>'
    );
} else {
    $table->data[12][1] = '';
}

$table->data[13][0] = html_print_label_input_block(
    __('Source'),
    '<div class="w100p">'.html_print_input_text(
        'source',
        $source,
        '',
        35,
        255,
        true,
        false,
        false,
        '',
        'w100p'
    ).'</div>'
);

$table->data[13][1] = html_print_label_input_block(
    __('Extra ID'),
    '<div class="w100p">'.html_print_input_text(
        'id_extra',
        $id_extra,
        '',
        11,
        255,
        true,
        false,
        false,
        '',
        'w100p'
    ).'</div>'
);

$table->data[14][0] = html_print_label_input_block(
    __('Comment'),
    '<div class="w100p">'.html_print_input_text(
        'user_comment',
        $user_comment,
        '',
        35,
        255,
        true,
        false,
        false,
        '',
        'w100p'
    ).'</div>'
);

$table->data[14][1] = html_print_label_input_block(
    __('Custom data filter type'),
    '<div class="w100p">'.html_print_select(
        [
            '0' => __('Filter custom data by name field'),
            '1' => __('Filter custom data by value field'),
        ],
        'custom_data_filter_type',
        $custom_data_filter_type,
        '',
        false,
        '',
        true,
        false,
        true,
        'w100p'
    ).'</div>'
);

$table->data[15][0] = html_print_label_input_block(
    __('Custom data'),
    '<div class="w100p">'.html_print_input_text(
        'custom_data',
        $custom_data,
        '',
        35,
        255,
        true,
        false,
        false,
        '',
        'w100p'
    ).'</div>'
);

if (is_metaconsole()) {
    $table->data[15][1] = html_print_label_input_block(
        __('Id souce event'),
        '<div class="w100p">'.html_print_input_text(
            'id_source_event',
            $id_source_event,
            '',
            35,
            255,
            true,
            false,
            false,
            '',
            'w100p'
        ).'</div>'
    );
}

if (is_metaconsole() === true) {
    $servers = metaconsole_get_servers();
    if (is_array($servers) === true) {
        $servers = array_reduce(
            $servers,
            function ($carry, $item) {
                $carry[$item['id']] = $item['server_name'];
                return $carry;
            }
        );
    } else {
        $servers = [];
    }

    $servers[0] = __('Metaconsola');

    if ($server_id === '') {
        $server_id = array_keys($servers);
    } else {
        if (is_array($server_id) === false) {
            if (is_numeric($server_id) === true) {
                if ($server_id !== 0) {
                    $server_id = [$server_id];
                } else {
                    $server_id = array_keys($servers);
                }
            } else {
                $server_id = explode(',', $server_id);
            }
        }
    }

    $table->data[16][0] = html_print_label_input_block(
        __('Server'),
        '<div class="w100p">'.html_print_select(
            $servers,
            'server_id[]',
            $server_id,
            '',
            '',
            0,
            true,
            true,
            true,
            'w100p'
        ).'</div>'
    );
}

echo '<form method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=edit_filter&pure='.$config['pure'].'">';
html_print_table($table);


echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
    html_print_input_hidden('update', 1);
    html_print_input_hidden('id', $id);
    $actionButtons = html_print_submit_button(__('Update'), 'crt', false, ['icon' => 'update'], true);
} else {
    html_print_input_hidden('create', 1);
    $actionButtons = html_print_submit_button(__('Create'), 'crt', false, ['icon' => 'wand'], true);
}

html_print_action_buttons($actionButtons, ['type' => 'form_action']);

echo '</div>';
echo '</form>';

ui_require_jquery_file('bgiframe');
ui_require_jquery_file('json');
?>
<script language="javascript" type="text/javascript">
/*<![CDATA[ */

var select_with_tag_empty = <?php echo (int) $remove_with_tag_disabled; ?>;
var select_without_tag_empty = <?php echo (int) $remove_without_tag_disabled; ?>;
var origin_select_with_tag_empty = <?php echo (int) $add_with_tag_disabled; ?>;
var origin_select_without_tag_empty = <?php echo (int) $add_without_tag_disabled; ?>;

var val_none = 0;
var text_none = "<?php echo __('None'); ?>";

$(document).ready( function() {
    $("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});

    $("#button-add_whith").click(function() {
        click_button_add_tag("with");
        });
    
    $("#button-add_whithout").click(function() {
        click_button_add_tag("without");
        });
    
    $("#button-remove_whith").click(function() {
        click_button_remove_tag("with");
    });
    
    $("#button-remove_whithout").click(function() {
        click_button_remove_tag("without");
    });
    
});

function checked_slide_events(element) {
    var value = $("#checkbox-"+element.name).val();
    if (value == 0) {
        $("#checkbox-"+element.name).val(1);
    } else {
        $("#checkbox-"+element.name).val(0);
    }
}

function click_button_remove_tag(what_button) {
    if (what_button == "with") {
        id_select_origin = "#select_with";
        id_select_destiny = "#tag_with_temp";
        id_button_remove = "#button-remove_whith";
        id_button_add = "#button-add_whith";
        
        select_origin_empty = origin_select_with_tag_empty;
    }
    else { //without
        id_select_origin = "#select_without";
        id_select_destiny = "#tag_without_temp";
        id_button_remove = "#button-remove_whithout";
        id_button_add = "#button-add_whithout";
        
        select_origin_empty = origin_select_without_tag_empty;
    }
    
    if ($(id_select_destiny + " option:selected").length == 0) {
        return; //Do nothing
    }
    
    if (select_origin_empty) {
        $(id_select_origin + " option").remove();
        
        if (what_button == "with") {
            origin_select_with_tag_empty = false;
        }
        else { //without
            origin_select_without_tag_empty = false;
        }
        
        $(id_button_add).removeAttr('disabled');
    }
    
    //Foreach because maybe the user select several items in
    //the select.
    jQuery.each($(id_select_destiny + " option:selected"), function(key, element) {
        val = $(element).val();
        text = $(element).text();
        
        $(id_select_origin).append($("<option value='" + val + "'>" + text + "</option>"));
    });
    
    $(id_select_destiny + " option:selected").remove();
    
    if ($(id_select_destiny + " option").length == 0) {
        $(id_select_destiny).append($("<option value='" + val_none + "'>" + text_none + "</option>"));
        $(id_button_remove).attr('disabled', 'true');
        
        if (what_button == 'with') {
            select_with_tag_empty = true;
        }
        else { //without
            select_without_tag_empty = true;
        }
    }
    
    replace_hidden_tags(what_button);
}

function click_button_add_tag(what_button) {
    if (what_button == 'with') {
        id_select_origin = "#select_with";
        id_select_destiny = "#tag_with_temp";
        id_button_remove = "#button-remove_whith";
        id_button_add = "#button-add_whith";
        
        select_destiny_empty = select_with_tag_empty;
    }
    else { //without
        id_select_origin = "#select_without";
        id_select_destiny = "#tag_without_temp";
        id_button_remove = "#button-remove_whithout";
        id_button_add = "#button-add_whithout";
        
        select_destiny_empty = select_without_tag_empty;
    }
    
    without_val = $(id_select_origin).val();
    without_text = $(id_select_origin + " option:selected").text();
    
    if (select_destiny_empty) {
        $(id_select_destiny).empty();
        
        if (what_button == 'with') {
            select_with_tag_empty = false;
        }
        else { //without
            select_without_tag_empty = false;
        }
    }
    
    $(id_select_destiny)
        .append($("<option value='" + without_val + "'>" + without_text + "</option>"));
    $(id_select_origin + " option:selected").remove();
    $(id_button_remove).removeAttr('disabled');
    
    if ($(id_select_origin + " option").length == 0) {
        $(id_select_origin)
            .append($("<option value='" + val_none + "'>" + text_none + "</option>"));
        $(id_button_add).attr('disabled', 'true');
        
        if (what_button == 'with') {
            origin_select_with_tag_empty = true;
        }
        else { //without
            origin_select_without_tag_empty = true;
        }
    }
    
    replace_hidden_tags(what_button);
}

function replace_hidden_tags(what_button) {
    if (what_button == 'with') {
        id_select_destiny = "#tag_with_temp";
        id_hidden = "#hidden-tag_with";
    }
    else { //without
        id_select_destiny = "#tag_without_temp";
        id_hidden = "#hidden-tag_without";
    }
    
    value_store = [];
    
    jQuery.each($(id_select_destiny + " option"), function(key, element) {
        val = $(element).val();
        
        value_store.push(val);
    });
    
    $(id_hidden).val(Base64.encode(jQuery.toJSON(value_store)));
}
/* ]]> */
</script>
