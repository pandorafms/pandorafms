<?php
/**
 * Event list.
 *
 * @category   Events
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

// Load global vars.
global $config;

require_once $config['homedir'].'/include/functions_events.php';
// Event processing functions.
require_once $config['homedir'].'/include/functions_alerts.php';
// Alerts processing functions.
require_once $config['homedir'].'/include/functions_agents.php';
// Agents functions.
require_once $config['homedir'].'/include/functions_users.php';
// Users functions.
require_once $config['homedir'].'/include/functions_graph.php';
require_once $config['homedir'].'/include/functions_ui.php';


// Check access.
check_login();

enterprise_include_once('/include/class/CommandCenter.class.php');

$event_a = (bool) check_acl($config['id_user'], 0, 'ER');
$event_w = (bool) check_acl($config['id_user'], 0, 'EW');
$event_m = (bool) check_acl($config['id_user'], 0, 'EM');

if ($event_a === false
    && $event_w === false
    && $event_m === false
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access event viewer'
    );
    if (is_ajax() === true) {
        return ['error' => 'noaccess'];
    }

    include 'general/noaccess.php';
    return;
}


$access = ($event_a === true) ? 'ER' : (($event_w === true) ? 'EW' : (($event_m === true) ? 'EM' : 'ER'));

$readonly = false;
// Load specific stylesheet.
ui_require_css_file('events');
ui_require_css_file('tables');
if (is_metaconsole() === true) {
    ui_require_css_file('tables');
    // ui_require_css_file('meta_tables', ENTERPRISE_DIR.'/meta/styles/');
    ui_require_css_file('meta_events', ENTERPRISE_DIR.'/meta/styles/');
}

// Load extra javascript.
ui_require_javascript_file('pandora_events');

// Get requests.
$default_filter = [
    'status'        => EVENT_NO_VALIDATED,
    'event_view_hr' => $config['event_view_hr'],
    'group_rep'     => EVENT_GROUP_REP_EVENTS,
    'tag_with'      => [],
    'tag_without'   => [],
    'history'       => false,
];

$fb64 = get_parameter('fb64', null);
if (isset($fb64) === true) {
    $filter = json_decode(base64_decode($fb64), true);
    $filter['tag_with'] = [];
    $filter['tag_without'] = [];
} else {
    $filter = get_parameter(
        'filter',
        $default_filter
    );
}

$id_group_filter = get_parameter(
    'filter[id_group_filter]',
    ($filter['id_group_filter'] ?? '')
);

$id_group = get_parameter(
    'filter[id_group]',
    ($filter['id_group'] ?? $id_group_filter)
);

$event_type = get_parameter(
    'filter[event_type]',
    ($filter['event_type'] ?? '')
);
$severity = get_parameter(
    'filter[severity]',
    ($filter['severity'] ?? '')
);
$status = get_parameter(
    'filter[status]',
    ($filter['status'] ?? '')
);
$search = get_parameter(
    'filter[search]',
    ($filter['search'] ?? '')
);
$not_search = get_parameter(
    'filter[not_search]',
    0
);
$text_agent = get_parameter(
    'filter[text_agent]',
    ($filter['text_agent'] ?? '')
);
$id_agent = get_parameter(
    'filter[id_agent]',
    ($filter['id_agent'] ?? '')
);
$text_module = get_parameter(
    'filter[module_search]',
    ($filter['module_search'] ?? '')
);
$id_agent_module = get_parameter(
    'id_agent_module',
    get_parameter(
        'filter[id_agent_module]',
        ($filter['id_agent_module'] ?? '')
    )
);
$pagination = get_parameter(
    'filter[pagination]',
    ($filter['pagination'] ?? '')
);
$event_view_hr = get_parameter(
    'filter[event_view_hr]',
    ($filter['event_view_hr'] ?? '')
);
$id_user_ack = get_parameter(
    'filter[id_user_ack]',
    ($filter['id_user_ack'] ?? '')
);
$owner_user = get_parameter(
    'filter[owner_user]',
    ($filter['owner_user'] ?? '')
);
$group_rep = get_parameter(
    'filter[group_rep]',
    ($filter['group_rep'] ?? '')
);
$tag_with = get_parameter(
    'filter[tag_with]',
    ($filter['tag_with'] ?? '')
);
$tag_without = get_parameter(
    'filter[tag_without]',
    ($filter['tag_without'] ?? '')
);
$filter_only_alert = get_parameter(
    'filter[filter_only_alert]',
    ($filter['filter_only_alert'] ?? '')
);
$search_secondary_groups = get_parameter(
    'filter[search_secondary_groups]',
    0
);
$search_recursive_groups = get_parameter(
    'filter[search_recursive_groups]',
    ($filter['search_recursive_groups'] ?? '')
);
$id_group_filter = get_parameter(
    'filter[id_group_filter]',
    ($filter['id_group'] ?? '')
);
$date_from = get_parameter(
    'filter[date_from]',
    ($filter['date_from'] ?? '')
);
$date_to = get_parameter(
    'filter[date_to]',
    ($filter['date_to'] ?? '')
);
$time_from = get_parameter(
    'filter[time_from]',
    ($filter['time_from'] ?? '')
);
$time_to = get_parameter(
    'filter[time_to]',
    ($filter['time_to'] ?? '')
);
$source = get_parameter(
    'filter[source]',
    ($filter['source'] ?? '')
);
$id_extra = get_parameter(
    'filter[id_extra]',
    ($filter['id_extra'] ?? '')
);
$user_comment = get_parameter(
    'filter[user_comment]',
    ($filter['user_comment'] ?? '')
);
$history = get_parameter(
    'history',
    ($filter['history'] ?? '')
);
$section = get_parameter('section', false);

$id_source_event = get_parameter(
    'filter[id_source_event]',
    ($filter['id_source_event'] ?? '')
);

$server_id = get_parameter(
    'filter[server_id]',
    ($filter['server_id'] ?? '')
);

if (empty($id_agent) === true) {
    $id_agent = get_parameter(
        'id_agent',
        ($filter['id_agent'] ?? '')
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

    if (empty($server_id) === true) {
        $server_id = array_keys($servers);
    } else {
        if (is_array($server_id) === false) {
            if (is_numeric($server_id) === true) {
                if ($server_id !== 0) {
                    $server_id = [$filter['server_id']];
                } else {
                    $server_id = array_keys($servers);
                }
            } else {
                $server_id = explode(',', $filter['server_id']);
            }
        }
    }
}

$custom_data_filter_type = get_parameter(
    'filter[custom_data_filter_type]',
    ($filter['custom_data_filter_type'] ?? '')
);

$custom_data = get_parameter(
    'filter[custom_data]',
    ($filter['custom_data'] ?? '')
);

if (is_metaconsole() === true
    && is_array($server_id) === false
) {
    // Connect to node database.
    $id_node = (int) $server_id;
    if ($id_node !== 0) {
        if (metaconsole_connect(null, $id_node) !== NOERR) {
            return false;
        }
    }
}

if (empty($text_agent) === true
    && empty($id_agent) === false
) {
    $text_agent = agents_get_alias($id_agent);
}

if (empty($text_module) === true && empty($id_agent_module) === false) {
    $text_module = modules_get_agentmodule_name($id_agent_module);
    $text_agent = agents_get_alias(modules_get_agentmodule_agent($id_agent_module));
}

if (is_metaconsole() === true
    && is_array($server_id) === false
) {
    // Return to metaconsole database.
    if ($id_node != 0) {
        metaconsole_restore_db();
    }
}

// Ajax responses.
if (is_ajax() === true) {
    $get_events = (int) get_parameter('get_events', 0);
    $table_id = get_parameter('table_id', '');
    $groupRecursion = (bool) get_parameter('groupRecursion', false);

    // Datatables offset, limit.
    $start = get_parameter('start', 0);
    $length = get_parameter(
        'length',
        $config['block_size']
    );

    if ($get_events !== 0) {
        try {
            ob_start();

            $fields = [
                'te.id_evento',
                'te.id_agente',
                'te.id_usuario',
                'te.id_grupo',
                'te.estado',
                'te.timestamp',
                'te.evento',
                'te.utimestamp',
                'te.event_type',
                'te.id_alert_am',
                'te.criticity',
                'te.tags',
                'te.source',
                'te.id_extra',
                'te.critical_instructions',
                'te.warning_instructions',
                'te.unknown_instructions',
                'te.owner_user',
                'if(te.ack_utimestamp > 0, te.ack_utimestamp,"") as ack_utimestamp',
                'te.custom_data',
                'te.data',
                'te.module_status',
                'ta.alias as agent_name',
                'tg.nombre as group_name',
                'ta.direccion',
            ];

            if (strpos($config['event_fields'], 'user_comment') !== false
                || empty($user_comment) === false
                || empty($search) === false
            ) {
                $fields[] = 'te.user_comment';
            }

            $order = get_datatable_order(true);

            if (is_array($order) === true && $order['field'] === 'mini_severity') {
                $order['field'] = 'te.criticity';
            }

            // Find the order field and set the table and field name.
            foreach ($fields as $field) {
                if (str_contains($field, $order['field']) === true) {
                    switch ($field) {
                        case 'ta.alias as agent_name':
                            $order['field'] = 'agent_name';
                        break;

                        case 'if(te.ack_utimestamp > 0, te.ack_utimestamp,"") as ack_utimestamp':
                            $order['field'] = 'ack_utimestamp';
                        break;

                        default:
                            $order['field'] = $field;
                        break;
                    }

                    continue;
                }
            }

            $fields[] = 'am.nombre as module_name';
            $fields[] = 'am.id_agente_modulo as id_agentmodule';
            $fields[] = 'am.custom_id as module_custom_id';
            $fields[] = 'ta.server_name as server_name';

            $events = events_get_all(
                // Fields.
                $fields,
                // Filter.
                $filter,
                // Offset.
                $start,
                // Limit.
                $length,
                // Order.
                $order['direction'],
                // Sort field.
                $order['field'],
                // History.
                $history,
                false,
                '',
                false,
                $groupRecursion
            );

            $buffers = [];
            if (is_metaconsole() === false
                || (is_metaconsole() === true
                && empty($filter['server_id']) === false
                && is_array($filter['server_id']) === false)
            ) {
                $count = events_get_all(
                    'count',
                    $filter,
                    null,
                    null,
                    null,
                    null,
                    $history,
                    false,
                    '',
                    false,
                    $groupRecursion
                );

                if ($count !== false) {
                    $count = $count['0']['nitems'];
                }
            } else {
                $buffers = $events['buffers'];
                $count = $events['total'];
                $events = $events['data'];
            }

            if (empty($events) === false) {
                $data = array_reduce(
                    $events,
                    function ($carry, $item) use ($table_id) {
                        global $config;

                        $tmp = (object) $item;
                        $tmp->meta = is_metaconsole();

                        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
                        $server_url = '';
                        $hashdata = '';
                        if ($tmp->meta === true) {
                            if ($tmp->server_name !== null) {
                                $data_server = metaconsole_get_servers(
                                    $tmp->server_id
                                );

                                // Url to go to node from meta.
                                if (isset($data_server) === true
                                    && $data_server !== false
                                ) {
                                    $server_url = $data_server['server_url'];
                                    $hashdata = metaconsole_get_servers_url_hash(
                                        $data_server
                                    );
                                }
                            }
                        }

                        $tmp->evento = str_replace('"', '', io_safe_output($tmp->evento));
                        if (strlen($tmp->evento) >= 255) {
                            $tmp->evento = ui_print_truncate_text(
                                $tmp->evento,
                                255,
                                $tmp->evento,
                                true,
                                false
                            );
                        }

                        if (empty($tmp->module_name) === false) {
                            $tmp->module_name = io_safe_output($tmp->module_name);
                        }

                        if (empty($tmp->comments) === false) {
                            $tmp->comments = ui_print_comments($tmp->comments);
                        }

                        // Show last event.
                        if (isset($tmp->max_id_evento) === true
                            && $tmp->max_id_evento !== $tmp->id_evento
                        ) {
                            $max_event = db_get_row_sql(
                                sprintf(
                                    'SELECT criticity,
                                        `timestamp`
                                    FROM tevento
                                    WHERE id_evento = %s',
                                    $tmp->max_id_evento
                                )
                            );

                            if ($max_event !== false
                                && empty($max_event) === false
                            ) {
                                $tmp->timestamp = $max_event['timestamp'];
                                $tmp->criticity = $max_event['criticity'];
                            }
                        }

                        $tmp->agent_name = io_safe_output($tmp->agent_name);

                        $tmp->ack_utimestamp_raw = $tmp->ack_utimestamp;

                        $tmp->ack_utimestamp = ui_print_timestamp(
                            (empty($tmp->ack_utimestamp) === true) ? 0 : $tmp->ack_utimestamp,
                            true
                        );

                        $user_timezone = users_get_user_by_id($_SESSION['id_usuario'])['timezone'];
                        if (empty($user_timezone) === true) {
                            if (date_default_timezone_get() !== $config['timezone']) {
                                $timezone = timezone_open(date_default_timezone_get());
                                $datetime_eur = date_create('now', timezone_open($config['timezone']));
                                $dif = timezone_offset_get($timezone, $datetime_eur);
                                date($config['date_format'], $dif);
                                if (!date('I')) {
                                    // For summer -3600sec.
                                    $dif -= 3600;
                                }

                                $total_sec = strtotime($tmp->timestamp);
                                $total_sec += $dif;
                                $last_contact = date($config['date_format'], $total_sec);
                                $last_contact_value = ui_print_timestamp($last_contact, true);
                            } else {
                                $title = date($config['date_format'], strtotime($tmp->timestamp));
                                $value = ui_print_timestamp(strtotime($tmp->timestamp), true);
                                $last_contact_value = '<span title="'.$title.'">'.$value.'</span>';
                            }
                        } else {
                            date_default_timezone_set($user_timezone);
                            $title = date($config['date_format'], strtotime($tmp->timestamp));
                            $value = ui_print_timestamp(strtotime($tmp->timestamp), true);
                            $last_contact_value = '<span title="'.$title.'">'.$value.'</span>';
                        }

                        $tmp->timestamp = $last_contact_value;

                        if (is_numeric($tmp->data) === true) {
                            $tmp->data = format_numeric(
                                $tmp->data,
                                $config['graph_precision']
                            );
                        } else {
                            $tmp->data = ui_print_truncate_text($tmp->data, 10);
                        }

                        $tmp->instructions = events_get_instructions($item);

                        $tmp->b64 = base64_encode(json_encode($tmp));

                        // Show comments events.
                        if (empty($tmp->comments) === false) {
                            $tmp->user_comment = $tmp->comments;
                            if ($tmp->comments !== 'undefined' && strlen($tmp->comments) > 80) {
                                $tmp->user_comment .= '&nbsp;&nbsp;';
                                $tmp->user_comment .= '<a id="show_comments" href="javascript:" onclick="show_event_dialog(\'';
                                $tmp->user_comment .= $tmp->b64;
                                $tmp->user_comment .= '\',\'comments\')>;';
                                $tmp->user_comment .= html_print_image(
                                    'images/details.svg',
                                    true,
                                    [
                                        'title' => __('Show more'),
                                        'class' => 'invert_filter main_menu_icon',
                                    ]
                                );
                                $tmp->user_comment .= '</a>';
                            }
                        }

                        // Grouped events.
                        if (isset($tmp->max_id_evento) === true
                            && empty($tmp->max_id_evento) === false
                        ) {
                            $tmp->id_evento = $tmp->max_id_evento;
                        }

                        // Event severity prepared.
                        switch ($tmp->criticity) {
                            case EVENT_CRIT_CRITICAL:
                                $text = __('CRITICAL');
                                $color = COL_CRITICAL;
                            break;

                            case EVENT_CRIT_MAINTENANCE:
                                $text = __('MAINTENANCE');
                                $color = COL_MAINTENANCE;
                            break;

                            case EVENT_CRIT_INFORMATIONAL:
                                $text = __('INFORMATIONAL');
                                $color = COL_INFORMATIONAL;
                            break;

                            case EVENT_CRIT_MAJOR:
                                $text = __('MAJOR');
                                $color = COL_MAJOR;
                            break;

                            case EVENT_CRIT_MINOR:
                                $text = __('MINOR');
                                $color = COL_MINOR;
                            break;

                            case EVENT_CRIT_NORMAL:
                                $text = __('NORMAL');
                                $color = COL_NORMAL;
                            break;

                            case EVENT_CRIT_WARNING:
                                $text = __('WARNING');
                                $color = COL_WARNING;
                            break;

                            default:
                                $color = COL_UNKNOWN;
                                $text = __('UNKNOWN');
                            break;
                        }

                        $output = '<div data-title="';
                        $output .= $text;
                        $output .= '" data-use_title_for_force_title="1" ';
                        $output .= 'class="forced_title mini-criticity h100p" ';
                        $output .= ('style="background: '.$color.'">');
                        $output .= '</div>';

                        $tmp->mini_severity = '<div class="event flex-row h100p nowrap">';
                        $tmp->mini_severity .= $output;
                        $tmp->mini_severity .= '</div>';

                        $criticity = '<div class="criticity forced_title" style="background: ';
                        $criticity .= $color.'" data-title="'.$text.'" data-use_title_for_force_title="1">'.$text.'</div>';
                        $tmp->criticity = $criticity;

                        // Add event severity to end of text.
                        $evn = '<a href="javascript:" onclick="show_event_dialog(\''.$tmp->b64.'\')">';

                        // Grouped events.
                        if (isset($tmp->event_rep) === true && $tmp->event_rep > 1) {
                            $evn .= '('.$tmp->event_rep.') ';
                        }

                        $evn .= $tmp->evento.'</a>';

                        // Add event severity format to itself.
                        $tmp->evento = $evn;

                        // Grouped events.
                        if (isset($item->max_timestamp) === true
                            && ($item->max_timestamp) === false
                        ) {
                            $item->timestamp = $item->max_timestamp;
                        }

                        // Event type prepared.
                        switch ($tmp->event_type) {
                            case EVENTS_ALERT_FIRED:
                            case EVENTS_ALERT_RECOVERED:
                            case EVENTS_ALERT_CEASED:
                            case EVENTS_ALERT_MANUAL_VALIDATION:
                                $text = __('ALERT');
                                $color = COL_ALERTFIRED;
                            break;

                            case EVENTS_RECON_HOST_DETECTED:
                            case EVENTS_SYSTEM:
                            case EVENTS_ERROR:
                            case EVENTS_NEW_AGENT:
                            case EVENTS_CONFIGURATION_CHANGE:
                                $text = __('SYSTEM');
                                $color = COL_MAINTENANCE;
                            break;

                            case EVENTS_GOING_UP_WARNING:
                            case EVENTS_GOING_DOWN_WARNING:
                                $text = __('WARNING');
                                $color = COL_WARNING;
                            break;

                            case EVENTS_GOING_DOWN_NORMAL:
                            case EVENTS_GOING_UP_NORMAL:
                                $text = __('NORMAL');
                                $color = COL_NORMAL;
                            break;

                            case EVENTS_GOING_DOWN_CRITICAL:
                            case EVENTS_GOING_UP_CRITICAL:
                                $text = __('CRITICAL');
                                $color = COL_CRITICAL;
                            break;

                            case EVENTS_UNKNOWN:
                            case EVENTS_GOING_UNKNOWN:
                            default:
                                $text = __('UNKNOWN');
                                $color = COL_UNKNOWN;
                            break;
                        }

                        $event_type = '<div class="event_module_background_state forced_title" style="background: ';
                        $event_type .= $color.'" data-title="'.$text.'" data-use_title_for_force_title="1">&nbsp;</div>';
                        $tmp->event_type = $event_type;

                        // Module status.
                        // Event severity prepared.
                        switch ($tmp->module_status) {
                            case AGENT_MODULE_STATUS_NORMAL:
                                $text = __('NORMAL');
                                $color = COL_NORMAL;
                            break;

                            case AGENT_MODULE_STATUS_CRITICAL_BAD:
                                $text = __('CRITICAL');
                                $color = COL_CRITICAL;
                            break;

                            case AGENT_MODULE_STATUS_NO_DATA:
                                $text = __('NOT INIT');
                                $color = COL_NOTINIT;
                            break;

                            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                            case AGENT_MODULE_STATUS_NORMAL_ALERT:
                            case AGENT_MODULE_STATUS_WARNING_ALERT:
                                $text = __('ALERT');
                                $color = COL_ALERTFIRED;
                            break;

                            case AGENT_MODULE_STATUS_WARNING:
                                $text = __('WARNING');
                                $color = COL_WARNING;
                            break;

                            default:
                                $text = __('UNKNOWN');
                                $color = COL_UNKNOWN;
                            break;
                        }

                        $module_status = '<div class="status_rounded_rectangles forced_title" style="background: ';
                        $module_status .= $color.'" data-title="'.$text.'" data-use_title_for_force_title="1">&nbsp;</div>';
                        $tmp->module_status = $module_status;

                        // Status.
                        switch ($tmp->estado) {
                            case EVENT_STATUS_NEW:
                                $img = html_print_image(
                                    'images/star@svg.svg',
                                    true,
                                    [
                                        'title' => __('New event'),
                                        'class' => 'forced-title invert_filter main_menu_icon',
                                    ]
                                );
                                $state = 0;
                            break;

                            case EVENT_STATUS_VALIDATED:
                                $state = 1;
                                $img = html_print_image(
                                    'images/validate.svg',
                                    true,
                                    [
                                        'title' => __('Event validated'),
                                        'class' => 'forced-title invert_filter main_menu_icon',
                                    ]
                                );
                            break;

                            case EVENT_STATUS_INPROCESS:
                                $state = 2;
                                $img = html_print_image(
                                    'images/clock.svg',
                                    true,
                                    [
                                        'title' => __('Event in process'),
                                        'class' => 'forced-title invert_filter height_20px',
                                    ]
                                );
                            break;

                            default:
                                $img = html_print_image(
                                    'images/star@svg.svg',
                                    true,
                                    [
                                        'title' => __('Unknown'),
                                        'class' => 'forced-title',
                                    ]
                                );
                                $state = 0;
                            break;
                        }

                        $draw_state = '<div class="mrgn_lft_17px">';
                        $draw_state .= '<span class="invisible">';
                        $draw_state .= $state;
                        $draw_state .= '</span>';
                        $draw_state .= $img;
                        $draw_state .= '</div>';
                        $tmp->estado = $draw_state;

                        // Owner.
                        if (empty($tmp->owner_user) === true) {
                            $tmp->owner_user = __('System');
                        } else {
                            $tmp->owner_user = get_user_fullname($tmp->owner_user).' ('.$tmp->owner_user.')';
                        }

                        // Group name.
                        if (empty($tmp->id_grupo) === true) {
                            $tmp->id_grupo = __('All');
                        } else {
                            $tmp->id_grupo = $tmp->group_name;
                        }

                        // Module name.
                        $tmp->id_agentmodule = $tmp->module_name;

                        // Options.
                        // Show more.
                        $tmp->options = '<a href="javascript:" onclick="show_event_dialog(\''.$tmp->b64.'\')">';
                        $tmp->options .= html_print_image(
                            'images/details.svg',
                            true,
                            [
                                'title' => __('Show more'),
                                'class' => 'invert_filter',
                            ]
                        );
                        $tmp->options .= '</a>';

                        if (isset($tmp->server_id) === false) {
                            $tmp->server_id = 0;
                        }

                        if ((int) $tmp->user_can_write === 1) {
                            if ((int) $tmp->estado !== 1) {
                                // Validate.
                                $tmp->options .= '<a href="javascript:" onclick="validate_event(\''.$table_id.'\',';
                                if (isset($tmp->max_id_evento) === true
                                    && empty($tmp->max_id_evento) === false
                                ) {
                                    $id_val = $tmp->max_id_evento;
                                    if (is_metaconsole() === true) {
                                        $id_val .= '-'.$tmp->server_id;
                                    }

                                    $tmp->options .= $tmp->max_id_evento.', ';
                                    $tmp->options .= $tmp->event_rep.', this, '.$tmp->server_id.')"';
                                    $tmp->options .= ' id="val-'.$id_val.'">';
                                    $tmp->options .= html_print_image(
                                        'images/validate.svg',
                                        true,
                                        [
                                            'title' => __('Validate events'),
                                            'class' => 'invert_filter main_menu_icon',
                                        ]
                                    );
                                    $tmp->options .= '</a>';
                                } else {
                                    $id_val = $tmp->id_evento;
                                    if (is_metaconsole() === true) {
                                        $id_val .= '-'.$tmp->server_id;
                                    }

                                    $tmp->options .= $tmp->id_evento.', 0, this, ';
                                    $tmp->options .= $tmp->server_id.')" id="val-'.$id_val.'">';
                                    $tmp->options .= html_print_image(
                                        'images/validate.svg',
                                        true,
                                        [
                                            'title' => __('Validate event'),
                                            'class' => 'invert_filter main_menu_icon',
                                        ]
                                    );
                                    $tmp->options .= '</a>';
                                }
                            }

                            if ((int) $tmp->estado !== 2) {
                                // In process.
                                $tmp->options .= '<a href="javascript:" onclick="in_process_event(\''.$table_id.'\',';
                                if (isset($tmp->max_id_evento) === true
                                    && empty($tmp->max_id_evento) === false
                                ) {
                                    $id_proc = $tmp->max_id_evento;
                                    if (is_metaconsole() === true) {
                                        $id_proc .= '-'.$tmp->server_id;
                                    }

                                    $tmp->options .= $tmp->max_id_evento.', '.$tmp->event_rep.', this, ';
                                    $tmp->options .= $tmp->server_id.')" id="proc-'.$id_proc.'">';
                                } else {
                                    $id_proc = $tmp->id_evento;
                                    if (is_metaconsole() === true) {
                                        $id_proc .= '-'.$tmp->server_id;
                                    }

                                    $tmp->options .= $tmp->id_evento.', 0, this, ';
                                    $tmp->options .= $tmp->server_id.')" id="proc-'.$id_proc.'">';
                                }

                                $tmp->options .= html_print_image(
                                    'images/clock.svg',
                                    true,
                                    [
                                        'title' => __('Change to in progress status'),
                                        'class' => 'invert_filter main_menu_icon',
                                    ]
                                );
                                $tmp->options .= '</a>';
                            }
                        }

                        if ((int) $tmp->user_can_manage === 1) {
                            // Delete.
                            $tmp->options .= '<a href="javascript:" onclick="delete_event(\''.$table_id.'\',';
                            if (isset($tmp->max_id_evento) === true
                                && empty($tmp->max_id_evento) === false
                            ) {
                                $id_del = $tmp->max_id_evento;
                                if (is_metaconsole() === true) {
                                    $id_del .= '-'.$tmp->server_id;
                                }

                                $tmp->options .= $tmp->max_id_evento.', '.$tmp->event_rep;
                                $tmp->options .= ', this, '.$tmp->server_id.')" id="del-'.$id_del.'">';
                                $tmp->options .= html_print_image(
                                    'images/delete.svg',
                                    true,
                                    [
                                        'title' => __('Delete events'),
                                        'class' => 'invert_filter main_menu_icon',
                                    ]
                                );
                                $tmp->options .= '</a>';
                            } else {
                                $id_del = $tmp->id_evento;
                                if (is_metaconsole() === true) {
                                    $id_del .= '-'.$tmp->server_id;
                                }

                                $tmp->options .= $tmp->id_evento.', 0, this, ';
                                $tmp->options .= $tmp->server_id.')" id="del-'.$id_del.'">';
                                $tmp->options .= html_print_image(
                                    'images/delete.svg',
                                    true,
                                    [
                                        'title' => __('Delete event'),
                                        'class' => 'invert_filter main_menu_icon',
                                    ]
                                );
                                $tmp->options .= '</a>';
                            }
                        }

                        // Multi select.
                        $value_checkbox = $tmp->id_evento;
                        if (is_metaconsole() === true) {
                            $value_checkbox .= '|'.$tmp->server_id;
                        }

                        $tmp->m = '<input name="checkbox-multi[]" type="checkbox" value="';
                        $tmp->m .= $value_checkbox.'" id="checkbox-multi-'.$tmp->id_evento.'" ';
                        if (isset($tmp->max_id_evento) === true
                            && empty($tmp->max_id_evento) === false
                        ) {
                            $tmp->m .= ' event_rep="'.$tmp->event_rep.'" ';
                        } else {
                            $tmp->m .= ' event_rep="0" ';
                        }

                        $tmp->m .= 'class="candeleted chk_val">';

                        // Url to agent view.
                        $url_link = ui_get_full_url(
                            'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='
                        );
                        $url_link_hash = '';
                        if ($tmp->meta === true) {
                            $url_link = $server_url;
                            $url_link .= '/index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=';
                            $url_link_hash = $hashdata;
                        }

                        // Agent name link.
                        if ($tmp->id_agente > 0) {
                            $draw_agent_name = '<a href="'.$url_link.$tmp->id_agente.$url_link_hash.'">';
                            $draw_agent_name .= $tmp->agent_name;
                            $draw_agent_name .= '</a>';
                            $tmp->agent_name = $draw_agent_name;
                        } else {
                            $tmp->agent_name = '';
                        }

                        // Agent ID link.
                        if ($tmp->id_agente > 0) {
                            $draw_agent_id = '<a href="'.$url_link.$tmp->id_agente.$url_link_hash.'">';
                            $draw_agent_id .= $tmp->id_agente;
                            $draw_agent_id .= '</a>';
                            $tmp->id_agente = $draw_agent_id;
                        } else {
                            $tmp->id_agente = '';
                        }

                        if (empty($tmp->custom_data) === false) {
                            $custom_data = json_decode(io_safe_output($tmp->custom_data), true);
                            $custom_data_str = '';
                            if (isset($custom_data) === true && empty($custom_data) === false) {
                                foreach ($custom_data as $key => $value) {
                                    $custom_data_str .= $key.' = '.$value.'<br>';
                                }
                            }

                            $tmp->custom_data = $custom_data_str;
                        }

                        $carry[] = $tmp;
                        return $carry;
                    }
                );
            }

            // RecordsTotal && recordsfiltered resultados totales.
            echo json_encode(
                [
                    'data'            => ($data ?? []),
                    'buffers'         => $buffers,
                    'recordsTotal'    => $count,
                    'recordsFiltered' => $count,
                ]
            );
            $response = ob_get_clean();

            // Clean output buffer.
            while (ob_get_level() !== 0) {
                ob_end_clean();
            }
        } catch (Exception $e) {
            echo json_encode(
                ['error' => $e->getMessage()]
            );
        }

        // If not valid it will throw an exception.
        json_decode($response);
        if (json_last_error() == JSON_ERROR_NONE) {
            // If valid dump.
            echo $response;
        } else {
            echo json_encode(
                ['error' => $response]
            );
        }
    }

    // AJAX section ends.
    exit;
}

/*
 * Load user default form.
 */

$load_filter_id = (int) get_parameter('filter_id', 0);
$fav_menu = [];
if ($load_filter_id === 0) {
    // Load user filter.
    if (is_metaconsole() === true) {
        $loaded_filter = db_get_row_sql(
            sprintf(
                'SELECT f.id_filter, f.id_name
                 FROM tevent_filter f
                 INNER JOIN tusuario u
                     ON u.metaconsole_default_event_filter=f.id_filter
                 WHERE u.id_user = "%s" ',
                $config['id_user']
            )
        );
    } else {
        $loaded_filter = db_get_row_sql(
            sprintf(
                'SELECT f.id_filter, f.id_name
                 FROM tevent_filter f
                 INNER JOIN tusuario u
                     ON u.default_event_filter=f.id_filter
                 WHERE u.id_user = "%s" ',
                $config['id_user']
            )
        );
    }
} else {
    // Load filter selected by user.
    $loaded_filter['id_filter'] = $load_filter_id;
    $loaded_filter['id_name'] = db_get_value(
        'id_name',
        'tevent_filter',
        'id_filter',
        $load_filter_id
    );

    // Fav menu
    $fav_menu = [
        'id_element' => $load_filter_id,
        'url'        => 'operation/events/events&pure=&load_filter=1&filter_id='.$load_filter_id,
        'label'      => $loaded_filter['id_name'],
        'section'    => 'Events',
    ];
}

// Do not load the user filter if we come from the 24h event graph.
$from_event_graph = get_parameter('filter[from_event_graph]', ($filter['from_event_graph'] ?? ''));
if ($loaded_filter !== false && $from_event_graph != 1 && isset($fb64) === false) {
    $filter = events_get_event_filter($loaded_filter['id_filter']);
    if ($filter !== false) {
        $id_group = $filter['id_group'];
        $event_type = $filter['event_type'];
        $severity = $filter['severity'];
        $status = $filter['status'];
        $search = $filter['search'];
        $not_search = $filter['not_search'];
        $text_agent = $filter['text_agent'];
        $id_agent = $filter['id_agent'];
        $id_agent_module = $filter['id_agent_module'];
        $text_module = io_safe_output(
            db_get_value_filter(
                'nombre',
                'tagente_modulo',
                ['id_agente_modulo' => $filter['id_agent_module']]
            )
        );
        $pagination = $filter['pagination'];
        $event_view_hr = $filter['event_view_hr'];
        $id_user_ack = $filter['id_user_ack'];
        $owner_user = $filter['owner_user'];
        $group_rep = $filter['group_rep'];
        $tag_with = json_decode(io_safe_output($filter['tag_with']));
        $tag_without = json_decode(io_safe_output($filter['tag_without']));

        $tag_with_base64 = base64_encode(json_encode($tag_with));
        $tag_without_base64 = base64_encode(json_encode($tag_without));

        $filter_only_alert = $filter['filter_only_alert'];
        $search_secondary_groups = ($filter['search_secondary_groups'] ?? 0);
        $search_recursive_groups = ($filter['search_recursive_groups'] ?? 0);
        $id_group_filter = $filter['id_group_filter'];
        $date_from = $filter['date_from'];
        $time_from = $filter['time_from'];
        $date_to = $filter['date_to'];
        $time_to = $filter['time_to'];
        $source = $filter['source'];
        $id_extra = $filter['id_extra'];
        $user_comment = $filter['user_comment'];
        $id_source_event = ($filter['id_source_event'] ?? '');
        $server_id = '';
        if (empty($filter['server_id']) === false) {
            if (is_array($server_id) === false) {
                if (is_numeric($server_id) === true) {
                    if ($server_id !== 0) {
                        $server_id = [$filter['server_id']];
                    } else {
                        $server_id = array_keys($servers);
                    }
                } else {
                    $server_id = explode(',', $filter['server_id']);
                }
            }
        }

        $custom_data = $filter['custom_data'];
        $custom_data_filter_type = $filter['custom_data_filter_type'];
    }
}

// TAGS.
// Get the tags where the user have permissions in Events reading tasks.
$tags = tags_get_user_tags($config['id_user'], $access);

$tags_select_with = [];
$tags_select_without = [];
$tag_with_temp = [];
$tag_without_temp = [];
if (is_array($tag_with) === false) {
    $tag_with = json_decode(base64_decode($tag_with), true);
}

if (is_array($tag_without) === false) {
    $tag_without = json_decode(base64_decode($tag_without), true);
}


foreach ((array) $tags as $id_tag => $tag) {
    if (is_array($tag_with) === true
        && ((array_search($id_tag, $tag_with) === false) || (array_search($id_tag, $tag_with) === null))
    ) {
        $tags_select_with[$id_tag] = $tag;
    } else {
        $tag_with_temp[$id_tag] = $tag;
    }

    if (is_array($tag_without) === true
        && ((array_search($id_tag, $tag_without) === false) || (array_search($id_tag, $tag_without) === null))
    ) {
        $tags_select_without[$id_tag] = $tag;
    } else {
        $tag_without_temp[$id_tag] = $tag;
    }
}

$add_with_tag_disabled = empty($tags_select_with);
$remove_with_tag_disabled = empty($tag_with_temp);
$add_without_tag_disabled = empty($tags_select_without);
$remove_without_tag_disabled = empty($tag_without_temp);

$tabletags_with = html_get_predefined_table('transparent', 2);
$tabletags_with->id = 'filter_events_tags_with';
$tabletags_with->width = '100%';
$tabletags_with->cellspacing = 4;
$tabletags_with->cellpadding = 4;
$tabletags_with->class = 'noshadow';
$tabletags_with->styleTable = 'border: 0px;';
if (is_metaconsole() === true) {
    $tabletags_with->class = 'nobady';
    $tabletags_with->cellspacing = 0;
    $tabletags_with->cellpadding = 0;
}


$data = [];

$data[0] = html_print_select(
    $tags_select_with,
    'select_with',
    '',
    '',
    '',
    0,
    true,
    true,
    true,
    'select_tags',
    false,
    false,
    false,
    false,
    false,
    '',
    false,
    false,
    false,
    25
);

$data[1] = html_print_image(
    'images/darrowright.png',
    true,
    [
        'id'    => 'button-add_with',
        'style' => 'cursor: pointer;',
        'title' => __('Add'),
        'class' => 'invert_filter',
    ]
);

$data[1] .= html_print_input_hidden(
    'tag_with',
    ($tag_with_base64 ?? ''),
    true
);

$data[1] .= '<br><br>'.html_print_image(
    'images/darrowleft.png',
    true,
    [
        'id'    => 'button-remove_with',
        'style' => 'cursor: pointer;',
        'title' => __('Remove'),
        'class' => 'invert_filter',
    ]
);

$data[2] = html_print_select(
    $tag_with_temp,
    'tag_with_temp',
    [],
    '',
    '',
    0,
    true,
    true,
    true,
    'select_tags',
    false,
    false,
    false,
    false,
    false,
    '',
    false,
    false,
    false,
    25
);

$tabletags_with->data[] = $data;
$tabletags_with->rowclass[] = '';


$tabletags_without = html_get_predefined_table('transparent', 2);
$tabletags_without->id = 'filter_events_tags_without';
$tabletags_without->width = '100%';
$tabletags_without->cellspacing = 4;
$tabletags_without->cellpadding = 4;
$tabletags_without->class = 'noshadow';
if (is_metaconsole() === true) {
    $tabletags_without->class = 'nobady';
    $tabletags_without->cellspacing = 0;
    $tabletags_without->cellpadding = 0;
}

$tabletags_without->styleTable = 'border: 0px;';

$data = [];
$data[0] = html_print_select(
    $tags_select_without,
    'select_without',
    '',
    '',
    '',
    0,
    true,
    true,
    true,
    'select_tags',
    false,
    false,
    false,
    false,
    false,
    '',
    false,
    false,
    false,
    25
);
$data[1] = html_print_image(
    'images/darrowright.png',
    true,
    [
        'id'    => 'button-add_without',
        'style' => 'cursor: pointer;',
        'title' => __('Add'),
        'class' => 'invert_filter',
    ]
);
$data[1] .= html_print_input_hidden(
    'tag_without',
    ($tag_without_base64 ?? ''),
    true
);
$data[1] .= '<br><br>'.html_print_image(
    'images/darrowleft.png',
    true,
    [
        'id'    => 'button-remove_without',
        'style' => 'cursor: pointer;',
        'title' => __('Remove'),
        'class' => 'invert_filter',
    ]
);
$data[2] = html_print_select(
    $tag_without_temp,
    'tag_without_temp',
    [],
    '',
    '',
    0,
    true,
    true,
    true,
    'select_tags',
    false,
    false,
    false,
    false,
    false,
    '',
    false,
    false,
    false,
    25
);
$tabletags_without->data[] = $data;
$tabletags_without->rowclass[] = '';

if (io_safe_output($tag_with) == '["0"]') {
    $tag_with = '[]';
}

if (io_safe_output($tag_without) == '["0"]') {
    $tag_without = '[]';
}

/*
 * END OF TAGS.
 */

// View.
$pure = get_parameter('pure', 0);
$url = ui_get_full_url('index.php?sec=eventos&sec2=operation/events/events');

// Concatenate parameters.
$url .= '';

if ($pure) {
    // Fullscreen.
    // Floating menu - Start.
    echo '<div id="vc-controls" class="zindex999"">';

    echo '<div id="menu_tab" class="menu_tab_pure">';
    echo '<ul class="mn">';

    // Quit fullscreen.
    echo '<li class="nomn">';
    echo '<a target="_top" href="'.$url.'&amp;pure=0">';
    echo html_print_image(
        'images/exit_fullscreen@svg.svg',
        true,
        [
            'title' => __('Back to normal mode'),
            'class' => 'invert_filter',
        ]
    );
    echo '</a>';
    echo '</li>';

    // Countdown.
    echo '<li class="nomn">';
    echo '<div class="events-refr">';
    echo '<div class="events-countdown"><span id="refrcounter"></span></div>';
    echo '<div id="events-refr-form">';
    echo __('Refresh').':';
    echo html_print_select(
        get_refresh_time_array(),
        'refresh',
        $refr,
        '',
        '',
        0,
        true,
        false,
        false
    );
    echo '</div>';
    echo '</div>';
    echo '</li>';

    // Console name.
    echo '<li class="nomn">';
    echo '<div class="vc-title">'.__('Event viewer').'</div>';
    echo '</li>';

    echo '</ul>';
    echo '</div>';

    echo '</div>';
    // Floating menu - End.
    ui_require_jquery_file('countdown');
} else {
    // Header.
    $pss = get_user_info($config['id_user']);
    $hashup = md5($config['id_user'].$pss['password']);

    // Fullscreen.
    $fullscreen['active'] = false;
    $fullscreen['text'] = '<a class="events_link" href="'.$url.'&amp;pure=1&">'.html_print_image(
        'images/fullscreen@svg.svg',
        true,
        [
            'title' => __('Full screen'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>';

    // Event list.
    $list['active'] = false;
    $list['text'] = '<a class="events_link" href="index.php?sec=eventos&sec2=operation/events/events&amp;pure='.$config['pure'].'&">'.html_print_image(
        'images/event.svg',
        true,
        [
            'title' => __('Event list'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>';

    // History event list.
    $history_list['active'] = false;
    $history_list['text'] = '<a class="events_link" href="index.php?sec=eventos&sec2=operation/events/events&amp;pure='.$config['pure'].'&amp;section=history&amp;history=1&">'.html_print_image(
        'images/books.png',
        true,
        [
            'title' => __('History event list'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>';

    // RSS.
    $rss['active'] = false;
    $rss['text'] = '<a class="events_link" href="operation/events/events_rss.php?user='.$config['id_user'].'&hashup='.$hashup.'&">'.html_print_image(
        'images/rrs@svg.svg',
        true,
        [
            'title' => __('RSS Events'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>';

    // CSV.
    $csv['active'] = false;
    $csv['text'] = '<a class="events_link" onclick="blockResubmit($(this))" href="'.ui_get_full_url(false, false, false, false).'operation/events/export_csv.php?'.($filter_b64 ?? '').'">'.html_print_image(
        'images/file-csv.svg',
        true,
        [
            'title' => __('Export to CSV file'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>';

    // Acoustic console.
    $sound_event['active'] = false;

    // Sound Events.
    $data_sound = base64_encode(
        json_encode(
            [
                'title'        => __('Sound Console'),
                'start'        => __('Start'),
                'stop'         => __('Stop'),
                'noAlert'      => __('No alert'),
                'silenceAlarm' => __('Silence alarm'),
                'url'          => ui_get_full_url('ajax.php'),
                'page'         => 'include/ajax/events',
                'urlSound'     => 'include/sounds/',
            ]
        )
    );

    $sound_event['text'] = '<a href="javascript: openSoundEventModal(`'.$data_sound.'`);">'.html_print_image(
        'images/sound_console@svg.svg',
        true,
        [
            'title' => __('Acoustic console'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>';

    // If the user has administrator permission display manage tab.
    if ($event_w === true || $event_m === true) {
        // Manage events.
        $manage_events['active'] = false;
        $manage_events['text'] = '<a href="index.php?sec=eventos&sec2=godmode/events/events&amp;section=filter&amp;pure='.$config['pure'].'">'.html_print_image(
            'images/configuration@svg.svg',
            true,
            [
                'title' => __('Manage events'),
                'class' => 'invert_filter main_menu_icon',
            ]
        ).'</a>';

        $manage_events['godmode'] = true;

        $onheader = [
            'manage_events' => $manage_events,
            'fullscreen'    => $fullscreen,
            'list'          => $list,
            'history'       => $history_list,
            'rss'           => $rss,
            'csv'           => $csv,
            'sound_event'   => $sound_event,
        ];
    } else {
        $onheader = [
            'fullscreen'  => $fullscreen,
            'list'        => $list,
            'history'     => $history_list,
            'rss'         => $rss,
            'csv'         => $csv,
            'sound_event' => $sound_event,
        ];
    }

    // If the history event is not enabled, dont show the history tab.
    if (isset($config['history_db_enabled']) === false
        || (bool) $config['history_db_enabled'] === false
    ) {
        unset($onheader['history']);
    }

    switch ($section) {
        case 'sound_event':
            $onheader['sound_event']['active'] = true;
            $section_string = __('Acoustic console');
        break;

        case 'history':
            $onheader['history']['active'] = true;
            $section_string = __('History');
        break;

        default:
            $onheader['list']['active'] = true;
            $section_string = __('List');
        break;
    }

    if (is_metaconsole() === true) {
        unset($onheader['rss']);
        unset($onheader['sound_event']);
        unset($onheader['fullscreen']);
    }

        unset($onheader['history']);
        ui_print_standard_header(
            __('Events list'),
            'images/event.svg',
            false,
            'eventview',
            false,
            (array) $onheader,
            [
                [
                    'link'  => '',
                    'label' => __('Events'),
                ],
            ],
            $fav_menu
        );
}

if (enterprise_installed() === true) {
    if (isset($config['merge_process_events']) === true
        && empty($config['merge_process_events']) === false
    ) {
        ui_require_css_file('command_center', ENTERPRISE_DIR.'/include/styles/');

        ui_require_javascript_file(
            'pandora_command_center',
            ENTERPRISE_DIR.'/include/javascript/'
        );

        $commandCenter = 'CommandCenterController';
        if (class_exists($commandCenter) === true) {
            $events_merge_state = $commandCenter::displayEventsProgress();
            if (empty($events_merge_state) === false) {
                echo '<div class="view_events_merge_process_events">';
                echo $events_merge_state;
                echo '</div>';
            }
        }

        $tittle_error = __('Errors');
        echo '<div id="dialog-error-node-'.$config['metaconsole_node_id'].'" title="'.$tittle_error.'"></div>';
    }
}

// Error div for ajax messages.
html_print_div(
    [
        'id'      => 'show_message_error',
        'content' => '',
    ]
);

if (enterprise_hook(
    'enterprise_acl',
    [
        $config['id_user'],
        'eventos',
        'execute_event_responses',
    ]
) === false
) {
    $readonly = true;
}

/*
 * Load filter form.
 */

// Group.
if ($id_group === null) {
    $id_group = 0;
}

$data = html_print_input(
    [
        'name'           => 'id_group_filter',
        'returnAllGroup' => true,
        'privilege'      => 'AR',
        'type'           => 'select_groups',
        'selected'       => $id_group,
        'nothing'        => false,
        'return'         => true,
        'size'           => '100%',
    ]
);
$in = '<div class="filter_input"><label>'.__('Group').'</label>';
$in .= $data;

// Search recursive groups.
$data = html_print_checkbox_switch(
    'search_recursive_groups',
    $search_recursive_groups,
    $search_recursive_groups,
    true,
    false,
    'checked_slide_events(this);',
    true
);

$in_group = '<div class="display-initial">';
$in_group .= $data;
$in_group .= '<label class="vert-align-bottom pdd_r_20px">';
$in_group .= __('Group recursion');
$in_group .= ui_print_help_tip(
    __('WARNING: This could cause a performace impact.'),
    true
);
$in_group .= '</label>';
$in .= $in_group;

// Search secondary groups.
$data = html_print_checkbox_switch(
    'search_secondary_groups',
    $search_secondary_groups,
    $search_secondary_groups,
    true,
    false,
    'checked_slide_events(this);',
    true
);

$in_sec_group .= $data;
$in_sec_group .= '<label class="vert-align-bottom">';
$in_sec_group .= __('Search in secondary groups');
$in_sec_group .= ui_print_help_tip(
    __('WARNING: This could cause a performace impact.'),
    true
);
$in_sec_group .= '</label>';
$in_sec_group .= '</div>';
$in .= $in_sec_group;

$in .= '</div>';
$inputs[] = $in;

// Event type.
$types = get_event_types();
$types['not_normal'] = __('Not normal');
$data = html_print_select(
    $types,
    'event_type',
    $event_type,
    '',
    __('All'),
    '',
    true
);
$in = '<div class="filter_input"><label>'.__('Event type').'</label>';
$in .= $data.'</div>';
$inputs[] = $in;

// Event status.
$data = html_print_select(
    events_get_all_status(),
    'status',
    $status,
    '',
    '',
    '',
    true
);
$in = '<div class="filter_input"><label>'.__('Event status').'</label>';
$in .= $data.'</div>';
$inputs[] = $in;

// Max hours old.
$data = html_print_input_text(
    'event_view_hr',
    $event_view_hr,
    '',
    5,
    255,
    true
);
$in = '<div class="filter_input"><label>'.__('Max. hours old').'</label>';
$in .= $data.'</div>';
$inputs[] = $in;

// Duplicates group { events | agents }.
$data = html_print_select(
    [
        EVENT_GROUP_REP_ALL      => __('All events'),
        EVENT_GROUP_REP_EVENTS   => __('Group events'),
        EVENT_GROUP_REP_AGENTS   => __('Group agents'),
        EVENT_GROUP_REP_EXTRAIDS => __('Group extra id'),
    ],
    'group_rep',
    $group_rep,
    '',
    '',
    0,
    true
);
$in = '<div class="filter_input"><label>'.__('Repeated').'</label>';
$in .= $data.'</div>';
$inputs[] = $in;

// Free search.
$data = html_print_input_text('search', $search, '', '', 255, true);

// Search recursive groups.
$data .= '<div class="display-initial">';
$data .= html_print_checkbox_switch(
    'not_search',
    $not_search,
    $not_search,
    true,
    false,
    'checked_slide_events(this);',
    true
);

$data .= ui_print_help_tip(
    __('Search for elements NOT containing given text.'),
    true
);
$data .= '</div>';

$in = '<div class="filter_input filter_input_not_search"><label>'.__('Free search').'</label>';
$in .= $data;
$in .= '</div>';
$inputs[] = $in;

if (is_array($severity) === false) {
    if (empty($severity) === true && $severity !== '0') {
        $severity = -1;
    } else {
        $severity = explode(',', $severity);
    }
}

// Criticity - severity.
$data = html_print_select(
    get_priorities(),
    'severity',
    $severity,
    '',
    __('All'),
    -1,
    true,
    true,
    true,
    '',
    false,
    false,
    false,
    3
);
$in = '<div class="filter_input"><label>'.__('Severity').'</label>';
$in .= $data.'</div>';
$inputs[] = $in;

// Trick view in table.
$inputs[] = '<div class="w100p pdd_t_15px"></div>';

$buttons = [];

$buttons[] = [
    'id'      => 'load-filter',
    'class'   => 'float-left margin-right-2',
    'text'    => __('Load filter'),
    'onclick' => '',
    'icon'    => 'load',
];

if ($event_w === true || $event_m === true) {
    $buttons[] = [
        'id'      => 'save-filter',
        'class'   => 'margin-right-2',
        'text'    => __('Save filter'),
        'onclick' => '',
        'icon'    => 'save',
    ];
}

/*
 * Advanced filter.
 */

$adv_inputs = [];


// Source.
$data = html_print_input_text('source', $source, '', '', 255, true);
$in = '<div class="filter_input"><label>'.__('Source').'</label>';
$in .= $data.'</div>';
$adv_inputs[] = $in;


// Extra ID.
$data = html_print_input_text('id_extra', $id_extra, '', 11, 255, true);
$in = '<div class="filter_input"><label>'.__('Extra ID').'</label>';
$in .= $data.'</div>';
$adv_inputs[] = $in;

// Comment.
$data = html_print_input_text(
    'user_comment',
    $user_comment,
    '',
    '',
    255,
    true
);
$in = '<div class="filter_input"><label>'.__('Comment').'</label>';
$in .= $data.'</div>';
$adv_inputs[] = $in;

// Agent search.
$params = [];
$params['show_helptip'] = true;
$params['input_name'] = 'text_agent';
$params['value'] = $text_agent;
$params['return'] = true;

if (is_metaconsole() === true) {
    $params['javascript_page'] = 'enterprise/meta/include/ajax/events.ajax';
}

$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_name'] = 'id_agent';
$params['hidden_input_idagent_value'] = $id_agent;
$params['size'] = '';

if ($id_agent !== null) {
    if (is_metaconsole() === true) {
        $metaconsole_agent = db_get_row_sql(
            sprintf(
                'SELECT alias, server_name
                 FROM tmetaconsole_agent
                 WHERE id_tagente = "%d" ',
                $id_agent
            )
        );

        if ($metaconsole_agent !== false) {
            $params['value'] = $metaconsole_agent['alias'].' ('.$metaconsole_agent['server_name'].')';
        }
    } else {
        $params['value'] = agents_get_alias($id_agent);
    }
}

$data = ui_print_agent_autocomplete_input($params);
$in = '<div class="filter_input agent-min-w100p"><label>'.__('Agent search');

$in .= '</label>'.$data.'</div>';
$adv_inputs[] = $in;

// Mixed. Metaconsole => server, Console => module.
if (is_metaconsole() === true) {
    $title = __('Server');
    $data = html_print_select(
        $servers,
        'server_id',
        $server_id,
        '',
        '',
        0,
        true,
        true,
        true,
        '',
        false,
        'height: 60px;'
    );
} else {
    $title = __('Module search');
    $data = html_print_autocomplete_modules(
        'module_search',
        $text_module,
        false,
        true,
        '',
        [],
        true,
        $id_agent_module,
        ''
    );
}

$in = '<div class="filter_input"><label>'.$title.'</label>';
$in .= $data.'</div>';
$adv_inputs[] = $in;

// User ack.
$user_users = users_get_user_users(
    $config['id_user'],
    $access,
    true
);

$data = html_print_select(
    $user_users,
    'id_user_ack',
    $id_user_ack,
    '',
    __('Any'),
    0,
    true,
    false,
    true,
    '',
    false,
    'width: 400px'
);
$in = '<div class="filter_input"><label>'.__('User ack.').'</label>';
$in .= $data.'</div>';
$adv_inputs[] = $in;

$data = html_print_select(
    $user_users,
    'owner_user',
    $owner_user,
    '',
    __('Any'),
    0,
    true,
    false,
    true,
    '',
    false,
    'width: 400px'
);
$in = '<div class="filter_input"><label>'.__('Owner').'</label>';
$in .= $data.'</div>';
$adv_inputs[] = $in;

// Only alert events.
$data = html_print_select(
    [
        '0' => __('Filter alert events'),
        '1' => __('Only alert events'),
    ],
    'filter_only_alert',
    $filter_only_alert,
    '',
    __('All'),
    -1,
    true,
    false,
    true,
    '',
    false,
    'width: 400px'
);

$adv_inputs[] = html_print_div(
    [
        'class'   => 'filter_input',
        'content' => sprintf(
            '<label>%s</label>%s',
            __('Alert events'),
            $data
        ),
    ],
    true
);

if (is_metaconsole() === true) {
    $data = html_print_input_text(
        'id_source_event',
        $id_source_event,
        '',
        5,
        255,
        true
    );

    $adv_inputs[] = html_print_div(
        [
            'class'   => 'filter_input',
            'content' => sprintf(
                '<label>%s</label>%s',
                __('Id source event'),
                $data
            ),
        ],
        true
    );
}

// Date from.
$inputDateFrom = html_print_input_text(
    'date_from',
    ($date_from === '0000-00-00') ? '' : $date_from,
    '',
    false,
    10,
    true,
    // Disabled.
    false,
    // Required.
    false,
    // Function.
    '',
    // Class.
    '',
    // OnChange.
    '',
    // Autocomplete.
    'off'
);

// Time from.
$inputTimeFrom = html_print_input_text(
    'time_from',
    $time_from,
    '',
    false,
    10,
    true,
    // Disabled.
    false,
    // Required.
    false,
    // Function.
    '',
    // Class.
    '',
    // OnChange.
    '',
    // Autocomplete.
    'off'
);

// Date and Time From.
$adv_inputs[] = html_print_div(
    [
        'class'   => 'filter_input',
        'content' => sprintf(
            '<label>%s</label><div class="datetime-adv-opt">%s<span>:</span>%s</div>',
            __('From (date:time)'),
            $inputDateFrom,
            $inputTimeFrom
        ),
    ],
    true
);

// Time to.
$inputTimeTo = html_print_input_text(
    'time_to',
    $time_to,
    '',
    false,
    10,
    true,
    // Disabled.
    false,
    // Required.
    false,
    // Function.
    '',
    // Class.
    '',
    // OnChange.
    '',
    // Autocomplete.
    'off'
);

// Date to.
$inputDateTo = html_print_input_text(
    'date_to',
    ($date_to === '0000-00-00') ? '' : $date_to,
    '',
    false,
    10,
    true,
    // Disabled.
    false,
    // Required.
    false,
    // Function.
    '',
    // Class.
    '',
    // OnChange.
    '',
    // Autocomplete.
    'off'
);

// Date and Time To.
$adv_inputs[] = html_print_div(
    [
        'class'   => 'filter_input',
        'content' => sprintf(
            '<label>%s</label><div class="datetime-adv-opt">%s<span>:</span>%s</div>',
            __('To (date:time)'),
            $inputDateTo,
            $inputTimeTo
        ),
    ],
    true
);

// Custom data filter type.
$custom_data_filter_type_input = html_print_select(
    [
        '0' => __('Filter custom data by field name'),
        '1' => __('Filter custom data by field value'),
    ],
    'custom_data_filter_type',
    $custom_data_filter_type,
    '',
    false,
    -1,
    true,
    false,
    true,
    '',
    false,
    'width: 400px'
);

$adv_inputs[] = html_print_div(
    [
        'class'   => 'filter_input',
        'content' => sprintf(
            '<label>%s</label>%s',
            __('Custom data filter'),
            $custom_data_filter_type_input
        ),
    ],
    true
);

// Custom data.
$custom_data_input = html_print_input_text(
    'custom_data',
    $custom_data,
    '',
    5,
    255,
    true
);

$adv_inputs[] = html_print_div(
    [
        'class'   => 'filter_input',
        'content' => sprintf(
            '<label>%s</label>%s',
            __('Custom data search'),
            $custom_data_input
        ),
    ],
    true
);

// Tags.
if (is_metaconsole() === true) {
    $data = '<fieldset><legend class="pdd_0px">'.__('Events with following tags').'</legend>'.html_print_table($tabletags_with, true).'</fieldset>';
    $data .= '<fieldset><legend class="pdd_0px">'.__('Events without following tags').'</legend>'.html_print_table($tabletags_without, true).'</fieldset>';
} else {
    $data = '<fieldset><legend>'.__('Events with following tags').'</legend>'.html_print_table($tabletags_with, true).'</fieldset>';
    $data .= '<fieldset><legend>'.__('Events without following tags').'</legend>'.html_print_table($tabletags_without, true).'</fieldset>';
}

$in = '<div class="filter_input large">';
$in .= $data.'</div>';
$adv_inputs[] = $in;

// Load view.
$adv_filter = join('', $adv_inputs);
$filter = join('', $inputs);
$filter .= ui_toggle(
    $adv_filter,
    '<span class="subsection_header_title">'.__('Advanced options').'</span>',
    '',
    '',
    true,
    true,
    'white_box white_box_opened no_border',
    'advanced-options-events no-border flex-row',
    'box-flat white_table_graph w100p'
);

try {
    $checkbox_all = html_print_checkbox(
        'all_validate_box',
        1,
        false,
        true
    );

    $default_fields = [
        [
            'text'  => 'evento',
            'class' => 'mw120px',
        ],
        [
            'text'  => 'mini_severity',
            'class' => 'no-padding',
        ],
        'id_evento',
        'agent_name',
        'timestamp',
        'event_type',
        [
            'text'  => 'options',
            'class' => 'table_action_buttons w120px',
        ],
        [
            'text'  => 'm',
            'extra' => $checkbox_all,
            'class' => 'mw120px',
        ],
    ];
    $fields = explode(',', $config['event_fields']);

    // Always check something is shown.
    if (empty($fields)) {
        $fields = $default_fields;
    }

    if (in_array('mini_severity', $fields) > 0) {
        $fields[array_search('mini_severity', $fields)] = [
            'text'  => 'mini_severity',
            'class' => 'no-padding-imp',
        ];
    }

    // Identifies column instructions to make it unsortable.
    if (in_array('instructions', $fields) > 0) {
        $fields[array_search('instructions', $fields)] = [
            'text'  => 'instructions',
            'class' => 'column-instructions',
        ];
    }

    $evento_id = array_search('evento', $fields);
    if ($evento_id !== false) {
        $fields[$evento_id] = [
            'text'  => 'evento',
            'class' => 'mw250px',
        ];
    }

    // Always add options column.
    $fields = array_merge(
        $fields,
        [
            [
                'text'  => 'options',
                'class' => 'table_action_buttons mw120px',
            ],
            [
                'text'  => 'm',
                'extra' => $checkbox_all,
                'class' => 'w20px no-text-imp',
            ],
        ]
    );

    // Get column names.
    $column_names = events_get_column_names($fields, true);

    foreach ($column_names as $key => $column) {
        if (is_array($column) && $column['text'] == 'S') {
            $column_names[$key]['style'] = 'padding-left: 1em !important;';
        }
    }

    // Open current filter quick reference.
    $active_filters_div = '<div class="filter_summary">';

    // Current filter.
    $active_filters_div .= '<div>';
    $active_filters_div .= '<div class="label box-shadow">'.__('Current filter').'</div>';
    $active_filters_div .= '<div id="current_filter" class="content">';
    if ($loaded_filter !== false) {
        $active_filters_div .= htmlentities(io_safe_output($loaded_filter['id_name']));
    } else {
        $active_filters_div .= __('Not set.');
    }

    $active_filters_div .= '</div>';
    $active_filters_div .= '</div>';

    // Event status.
    $active_filters_div .= '<div>';
    $active_filters_div .= '<div class="label box-shadow">'.__('Event status').'</div>';
    $active_filters_div .= '<div id="summary_status" class="content">';
    switch ($status) {
        case EVENT_ALL:
        default:
            $active_filters_div .= __('Any status.');
        break;

        case EVENT_NEW:
            $active_filters_div .= __('New events.');
        break;

        case EVENT_VALIDATE:
            $active_filters_div .= __('Validated.');
        break;

        case EVENT_PROCESS:
            $active_filters_div .= __('In proccess.');
        break;

        case EVENT_NO_VALIDATED:
            $active_filters_div .= __('Not validated.');
        break;
    }

    $active_filters_div .= '</div>';
    $active_filters_div .= '</div>';

    // Max. hours old.
    $active_filters_div .= '<div>';
    $active_filters_div .= '<div class="label box-shadow">'.__('Max. hours old').'</div>';
    $active_filters_div .= '<div id="summary_hours" class="content">';
    if ($event_view_hr == 0) {
        $active_filters_div .= __('Any time.');
    } else if ($event_view_hr == 1) {
        $active_filters_div .= __('Last hour.');
    } else if ($event_view_hr > 1) {
        $active_filters_div .= __('Last %d hours.', $event_view_hr);
    }

    $active_filters_div .= '</div>';
    $active_filters_div .= '</div>';

    // Duplicates.
    $active_filters_div .= '<div>';
    $active_filters_div .= '<div class="label box-shadow">'.__('Duplicated').'</div>';
    $active_filters_div .= '<div id="summary_duplicates" class="content">';
    if ($group_rep == EVENT_GROUP_REP_ALL) {
        $active_filters_div .= __('All events.');
    } else if ($group_rep == EVENT_GROUP_REP_EVENTS) {
        $active_filters_div .= __('Group events');
    } else if ($group_rep == EVENT_GROUP_REP_AGENTS) {
        $active_filters_div .= __('Group agents.');
    } else if ($group_rep == EVENT_GROUP_REP_EXTRAIDS) {
        $active_filters_div .= __('Group extra id.');
    }

    $active_filters_div .= '</div>';
    $active_filters_div .= '</div>';

    // Close.
    $active_filters_div .= '</div>';
    // $active_filters_div .= '<div id="events_buffers_display"></div>';
    $table_id = 'table_events';
    $form_id = 'events_form';

    $show_hide_filters = '';
    if ((int) $_GET['pure'] === 1) {
        $show_hide_filters = 'invisible';
    }

    // Print datatable.
    html_print_div(
        [
            'class'   => 'events_table_wrapper',
            'style'   => 'margin-top: 0px; margin-bottom: 0px;',
            'content' => ui_print_datatable(
                [
                    'id'                             => $table_id,
                    'class'                          => 'info_table events',
                    'style'                          => 'width: 99%;',
                    'ajax_url'                       => 'operation/events/events',
                    'ajax_data'                      => [
                        'get_events' => 1,
                        'history'    => (int) $history,
                        'table_id'   => $table_id,
                    ],
                    'form'                           => [
                        'id'            => $form_id,
                        'class'         => 'flex-row',
                        'html'          => $filter,
                        'inputs'        => [],
                        'extra_buttons' => $buttons,
                    ],
                    'extra_html'                     => $active_filters_div,
                    'pagination_options'             => [
                        [
                            $config['block_size'],
                            10,
                            25,
                            100,
                            200,
                            500,
                        ],
                        [
                            $config['block_size'],
                            10,
                            25,
                            100,
                            200,
                            500,
                        ],
                    ],
                    'order'                          => [
                        'field'     => 'timestamp',
                        'direction' => 'desc',
                    ],
                    'column_names'                   => $column_names,
                    'columns'                        => $fields,
                    'no_sortable_columns'            => [
                        -1,
                        -2,
                        'column-instructions',
                    ],
                    'ajax_return_operation'          => 'buffers',
                    'ajax_return_operation_function' => 'process_buffers',
                    'drawCallback'                   => 'process_datatables_callback(this, settings)',
                    'print'                          => false,
                    'csv'                            => 0,
                    'filter_main_class'              => 'events-pure box-flat white_table_graph fixed_filter_bar '.$show_hide_filters,
                ],
            ),
        ]
    );
} catch (Exception $e) {
    ui_print_error_message($e->getMessage());
}

// Close.
echo '<div id="events_buffers_display"></div>';

// Event responses.
if (is_user_admin($config['id_user'])) {
    $sql_event_resp = "SELECT id, name FROM tevent_response WHERE type LIKE 'command'";
    $event_responses = db_get_all_rows_sql($sql_event_resp);
} else {
    $id_groups = array_keys(users_get_groups(false, 'EW'));
    $event_responses = db_get_all_rows_filter(
        'tevent_response',
        [
            'id_group' => $id_groups,
            'type'     => 'command',
        ]
    );
}

$array_events_actions = [];
if ($event_w === true && $readonly === false) {
    $array_events_actions['in_progress_selected'] = __('In progress selected');
    $array_events_actions['validate_selected'] = __('Validate selected');
}

if ($event_m === true && $readonly === false) {
    $array_events_actions['delete_selected'] = __('Delete selected');
}

foreach ($event_responses as $val) {
    $array_events_actions[$val['id']] = $val['name'];
}

if (check_acl(
    $config['id_user'],
    0,
    'EW'
)
) {
    echo '<div class="multi-response-buttons">';
    echo '<form method="post" id="form_event_response">';
    echo '<input type="hidden" id="max_execution_event_response" value="'.$config['max_execution_event_response'].'" />';

    $elements = html_print_button(
        __('Execute event response'),
        'submit_event_response',
        false,
        'execute_event_response(true);',
        [ 'icon' => 'cog' ],
        true
    );

    $elements .= html_print_select(
        $array_events_actions,
        'response_id',
        '',
        '',
        '',
        0,
        true,
        false,
        false
    );

    html_print_action_buttons(
        $elements,
        [ 'type' => 'data_table' ]
    );

    echo "<span id='response_loading_dialog' class='invisible'>".html_print_image(
        'images/spinner.gif',
        true
    ).'</span>';
    echo '</form>';
    echo '<span id="max_custom_event_resp_msg" style="display: none; color: #e63c52; line-height: 200%;">';
    echo __(
        'A maximum of %s event custom responses can be selected',
        $config['max_execution_event_response']
    ).'</span>';
    echo '<span id="max_custom_selected" style="display: none; color: #e63c52; line-height: 200%;">';
    echo __(
        'Please, select an event'
    ).'</span>';
    echo '</div>';
}

// Datepicker requirements.
ui_require_css_file('datepicker');
ui_include_time_picker();
ui_require_jquery_file(
    'ui.datepicker-'.get_user_language(),
    'include/javascript/i18n/'
);

// End. Load required JS.
html_print_input_hidden('meta', (int) is_metaconsole());
html_print_input_hidden('history', (int) $history);
html_print_input_hidden(
    'ajax_file',
    ui_get_full_url('ajax.php', false, false, false)
);

// AJAX call options responses.
echo "<div id='event_details_window'></div>";
echo "<div id='event_response_window'></div>";
echo "<div id='event_response_command_window' title='".__('Parameters')."'></div>";

// Load filter div for dialog.
echo '<div id="load-modal-filter" style="display:none"></div>';
echo '<div id="save-modal-filter" style="display:none"></div>';

$autorefresh_draw = false;
if ($_GET['refr'] || (bool) ($do_refresh ?? false) === true) {
    $autorefresh_draw = true;
}

?>
<script type="text/javascript">
var loading = 0;
var select_with_tag_empty = <?php echo (int) $remove_with_tag_disabled; ?>;
var select_without_tag_empty = <?php echo (int) $remove_without_tag_disabled; ?>;
var origin_select_with_tag_empty = <?php echo (int) $add_with_tag_disabled; ?>;
var origin_select_without_tag_empty = <?php echo (int) $add_without_tag_disabled; ?>;

var val_none = 0;
var text_none = "<?php echo __('None'); ?>";
var group_agents_id = false;
var test;
/* Datatables auxiliary functions starts */
function process_datatables_callback(table, settings) {
    var api = table.api();
    var rows = api.rows( {page:'current'} ).nodes();
    var last=null;
    var last_count=0;
    var events_per_group = [];
    var j=0;

    // Only while grouping by agents.
    if($('#group_rep').val() == '2') {
        test = api;
        target = -1;
        for (var i =0 ; i < api.columns()['0'].length; i++) {
            var label = $(api.table().column(i).header()).text();
            if(label == '<?php echo __('Agent ID'); ?>') {
                // Agent id.
                target = i;
            }
            if(label == '<?php echo addslashes(__('Agent name')); ?>') {
                // Agent id.
                target = i;
                break;
            }
        }

        // Cannot group without agent_id or agent_name.
        if (target < 0) {
            return;
        }

        api.column(target, {page:'current'} )
        .data()
        .each( function ( group, i ) {
            $(rows).eq( i ).show();
            if ( last !== group ) {
                $(rows).eq( i ).before(
                    '<tr class="group"><td colspan="100%">'
                    +'<?php echo __('Agent').' '; ?>'
                    +group+' <?php echo __('has at least').' '; ?>'
                    +'<span style="cursor: pointer" id="s'+j+'">'+'</span>'
                    +'<?php echo ' '.__('events'); ?>'
                    +'</td></tr>'
                );
                events_per_group.push(i - last_count);
                last_count = i;
                last = group;
                j += 1;
            }
        });
        events_per_group.push(rows.length - last_count);
    
        for( j=0; j<events_per_group.length; j++ ) {
            $('#s'+j).text(events_per_group[j+1]);
        }

        /* Grouped by agent toggle view. */
        $("tr.group td span").on('click', function(e){
            var id = this.id.substring(1)*1;
            var from = events_per_group[id];
            var to = events_per_group[id+1] + from;
            for (var i = from; i < to; i++) {
                $(rows).eq(i).toggle();
            }

        })
    }

    var autorefresh_draw = '<?php echo $autorefresh_draw; ?>';
    if (autorefresh_draw == true){
        $("#refrcounter").countdown('change', {
            until: countdown_repeat()
        });

        function countdown_repeat() {
            var until_time = new Date();
            until_time.setTime (until_time.getTime () + parseInt(<?php echo($config['refr'] * 1000); ?>));
            return until_time;
        }

    }

    // Uncheck checkbox to select all.
    if ($('#checkbox-all_validate_box').length) {
        $('#checkbox-all_validate_box').uncheck();
    }
}
/* Datatables auxiliary functions ends */

/* Tag management starts */
function click_button_remove_tag(what_button) {
    if (what_button == "with") {
        id_select_origin = "#select_with";
        id_select_destiny = "#tag_with_temp";
        id_button_remove = "#button-remove_with";
        id_button_add = "#button-add_with";
        
        select_origin_empty = origin_select_with_tag_empty;
    }
    else { //without
        id_select_origin = "#select_without";
        id_select_destiny = "#tag_without_temp";
        id_button_remove = "#button-remove_without";
        id_button_add = "#button-add_without";
        
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
        id_button_remove = "#button-remove_with";
        id_button_add = "#button-add_with";
    }
    else { //without
        id_select_origin = "#select_without";
        id_select_destiny = "#tag_without_temp";
        id_button_remove = "#button-remove_without";
        id_button_add = "#button-add_without";
    }

    $(id_select_origin + " option:selected").each(function() {
        if (what_button == 'with') {
            select_destiny_empty = select_with_tag_empty;
        }
        else { //without
            select_destiny_empty = select_without_tag_empty;
        }


        without_val = $(this).val();
        if(without_val == null) {
            next;
        }
        without_text = $(this).text();

        if (select_destiny_empty) {
            $(id_select_destiny).empty();

            if (what_button == 'with') {
                select_with_tag_empty = false;
            }
            else { //without
                select_without_tag_empty = false;
            }
        }

        $(id_select_destiny).append($("<option value='" + without_val + "'>" + without_text + "</option>"));
        $(id_select_origin + " option:selected").remove();
        $(id_button_remove).removeAttr('disabled');

        if ($(id_select_origin + " option").length == 0) {
            $(id_select_origin).append($("<option value='" + val_none + "'>" + text_none + "</option>"));
            $(id_button_add).attr('disabled', 'true');

            if (what_button == 'with') {
                origin_select_with_tag_empty = true;
            }
            else { //without
                origin_select_without_tag_empty = true;
            }
        }

        replace_hidden_tags(what_button);
    });

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

    $(id_hidden).val(Base64.encode(JSON.stringify(value_store)));
}

function clear_tags_inputs() {
    $("#hidden-tag_with").val(Base64.encode(JSON.stringify([])));
    $("#hidden-tag_without").val(Base64.encode(JSON.stringify([])));
    reorder_tags_inputs();
}

function reorder_tags_inputs() {
    $('#select_with option[value="' + val_none + '"]').remove();
    jQuery.each($("#tag_with_temp option"), function(key, element) {
        val = $(element).val();
        text = $(element).text();

        if (val == val_none)
            return;

        $("#select_with").append($("<option value='" + val + "'>" + text + "</option>"));
    });
    $("#tag_with_temp option").remove();

    $('#select_without option[value="' + val_none + '"]').remove();
    jQuery.each($("#tag_without_temp option"), function(key, element) {
        val = $(element).val();
        text = $(element).text();

        if (val == val_none)
            return;

        $("#select_without").append($("<option value='" + val + "'>" + text + "</option>"));
    });
    $("#tag_without_temp option").remove();

    tags_base64 = $("#hidden-tag_with").val();
    if (tags_base64.length > 0) {
        tags = jQuery.parseJSON(Base64.decode(tags_base64));
    } else {
        tags = [];
    }
    jQuery.each(tags, function(key, element) {
        if ($("#select_with option[value='" + element + "']").length == 1) {
            text = $("#select_with option[value='" + element + "']").text();
            val = $("#select_with option[value='" + element + "']").val();
            $("#tag_with_temp").append($("<option value='" + val + "'>" + text + "</option>"));
            $("#select_with option[value='" + element + "']").remove();
        }
    });
    if ($("#select_with option").length == 0) {
        origin_select_with_tag_empty = true;
        $("#button-add_with").attr('disabled', 'true');
        $("#select_with").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
    }
    else {
        origin_select_with_tag_empty = false;
        $("#button-add_with").removeAttr('disabled');
    }
    if ($("#tag_with_temp option").length == 0) {
        select_with_tag_empty = true;
        $("#button-remove_with").attr('disabled', 'true');
        $("#tag_with_temp").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
    }
    else {
        select_with_tag_empty = false;
        $("#button-remove_with").removeAttr('disabled');
    }

    tags_base64 = $("#hidden-tag_without").val();
    if (tags_base64.length > 0) {
        tags = jQuery.parseJSON(Base64.decode(tags_base64));
    } else {
        tags = [];
    }
    jQuery.each(tags, function(key, element) {
        if ($("#select_without option[value='" + element + "']").length == 1) {
            text = $("#select_without option[value='" + element + "']").text();
            val = $("#select_without option[value='" + element + "']").val();
            $("#tag_without_temp").append($("<option value='" + val + "'>" + text + "</option>"));
            $("#select_without option[value='" + element + "']").remove();
        }
    });
    if ($("#select_without option").length == 0) {
        origin_select_without_tag_empty = true;
        $("#button-add_without").attr('disabled', 'true');
        $("#select_without").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
    }
    else {
        origin_select_without_tag_empty = false;
        $("#button-add_without").removeAttr('disabled');
    }
    if ($("#tag_without_temp option").length == 0) {
        select_without_tag_empty = true;
        $("#button-remove_without").attr('disabled', 'true');
        $("#tag_without_temp").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
    }
    else {
        select_without_tag_empty = false;
        $("#button-remove_without").removeAttr('disabled');
    }
}
/* Tag management ends */
$(document).ready( function() {

    let refresco = <?php echo get_parameter('refr', 0); ?>;
    $('#refresh option[value='+refresco+']').attr('selected', 'selected');

    /* Filter to a href */
    $('.events_link').on('click', function(e) {
        e.preventDefault();

        inputs = $("#<?php echo $form_id; ?> :input");
        values = {};
        inputs.each(function() {
            if (this.name === 'server_id') {
                values[this.name] = $(this).val().join();
            } else {
                values[this.name] = $(this).val();
            }
        })

        values['history'] = "<?php echo (int) $history; ?>";

        var url = e.currentTarget.href;
        url += 'fb64=' + btoa(JSON.stringify(values));
        url += '&refr=' + '<?php echo $config['refr']; ?>';
        document.location = url;

    });

    var show_event_dialog = "<?php echo get_parameter('show_event_dialog', ''); ?>";
    if (show_event_dialog !== ''){
        show_event_dialo(show_event_dialog);
    }

    /* Multi select handler */
    $('#checkbox-all_validate_box').on('change', function() {
        if($('#checkbox-all_validate_box').is(":checked")) {
            $('.chk_val').check();
        } else {
            $('.chk_val').uncheck();
        }
    });



    /* Update summary */
    $("#status").on("change",function(){
        $('#summary_status').html($("#status option:selected").text());
    });

    $("#text-event_view_hr").on("keyup",function(){
        hours = $('#text-event_view_hr').val();
        if (hours == '' || hours == 0 ) {
            $('#summary_hours').text('<?php echo __('Any'); ?>');
        } else if (hours == 1) {
            $('#summary_hours').text('<?php echo __('Last hour.'); ?>');
        } else {
            $('#summary_hours').text(hours + '<?php echo ' '.__('hours.'); ?>');
        }
    });

    $('#group_rep').on("change", function(){
        $('#summary_duplicates').html($("#group_rep option:selected").text());
    });

    /* Summary updates end. */

    /* Filter management */
    $('#button-load-filter').click(function (){
        if($('#load-filter-select').length) {
            $('#load-filter-select').dialog();
        } else {
            if (loading == 0) {
                loading = 1
                $.ajax({
                    method: 'POST',
                    url: '<?php echo ui_get_full_url('ajax.php'); ?>',
                    data: {
                        page: 'include/ajax/events',
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
        if($('#save-filter-select').length) {
            $('#save-filter-select').dialog();
        } else {
            if (loading == 0) {
                loading = 1
                $.ajax({
                    method: 'POST',
                    url: '<?php echo ui_get_full_url('ajax.php'); ?>',
                    data: {
                        page: 'include/ajax/events',
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

    /* Filter management ends */

    /* Tag management */
    id_select_destiny = "#tag_with_temp";
    id_hidden = "#hidden-tag_with";

    value_store = [];
    
    jQuery.each($(id_select_destiny + " option"), function(key, element) {
        val = $(element).val();
        
        value_store.push(val);
    });
    
    $(id_hidden).val(Base64.encode(JSON.stringify(value_store)));
    
    id_select_destiny2 = "#tag_without_temp";
    id_hidden2 = "#hidden-tag_without";
    
    value_store2 = [];
    
    jQuery.each($(id_select_destiny2 + " option"), function(key, element) {
        val = $(element).val();
        
        value_store2.push(val);
    });
    
    $(id_hidden2).val(Base64.encode(JSON.stringify(value_store2)));

    $("#text-date_from, #text-date_to").datepicker(
        {dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
    
    $("#button-add_with").click(function() {
        click_button_add_tag("with");
        });
    
    $("#button-add_without").click(function() {
        click_button_add_tag("without");
        });
    
    $("#button-remove_with").click(function() {
        click_button_remove_tag("with");
    });
    
    $("#button-remove_without").click(function() {
        click_button_remove_tag("without");
    });
    

    //Autorefresh in fullscreen
    var pure = '<?php echo $pure; ?>';
    var pure = '<?php echo $pure; ?>';
    if(pure == 1){
        var refresh_interval = parseInt('<?php echo($config['refr'] * 1000); ?>');
        var until_time='';

        // If autorefresh is disabled, don't show the countdown   
        var refresh_time = '<?php echo $_GET['refr']; ?>'; 
        if(refresh_time == '' || refresh_time == 0){
            $('#refrcounter').toggle();
        }

        function events_refresh() {
            until_time = new Date();
            until_time.setTime (until_time.getTime () + parseInt(<?php echo($config['refr'] * 1000); ?>));

            $("#refrcounter").countdown ({
                until: until_time,
                layout: '(%M%nn%M:%S%nn%S <?php echo __('Until next'); ?>)',
                labels: ['', '', '', '', '', '', ''],
                onExpiry: function () {
                    $("#table_events")
                    .DataTable()
                    .draw(false);
                }
            });
        }
        // Start the countdown when page is loaded (first time).
        events_refresh();
        // Repeat countdown according to refresh_interval.
        setInterval(events_refresh, refresh_interval);


        $("select#refresh").on('select2:select', function () {
            var href = window.location.href;

            inputs = $("#events_form :input");
            values = {};
            inputs.each(function() {
                if (this.name === 'server_id') {
                    values[this.name] = $(this).val().join();
                } else {
                    values[this.name] = $(this).val();
                }
            })

            var newValue = btoa(JSON.stringify(values));           
            var fb64 = '<?php echo $fb64; ?>';  
            // Check if the filters have changed.
            if(fb64 !== newValue){
                href = href.replace(fb64, newValue);
            } 
                
            href = href.replace('refr='+refresh_time, 'refr='+this.value);

            $(document).attr("location", href);
        });

        $("div.events-pure").removeClass("fixed_filter_bar invisible");
        $("div#principal_action_buttons").addClass("w100p");
        $("table#table_events").addClass("margn-b-50px");

        $('#refresh').val('<?php echo $config['refr']; ?>').trigger('change');

    }

});

function checked_slide_events(element) {
    var value = $("#checkbox-"+element.name).val();
    if (value == 0) {
        $("#checkbox-"+element.name).val(1);
    } else {
        $("#checkbox-"+element.name).val(0);
    }
}

function datetime_picker_callback() {
    $("#text-time_from, #text-time_to").timepicker({
        showSecond: true,
        timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
        timeOnlyTitle: '<?php echo __('Choose time'); ?>',
        timeText: '<?php echo __('Time'); ?>',
        hourText: '<?php echo __('Hour'); ?>',
        minuteText: '<?php echo __('Minute'); ?>',
        secondText: '<?php echo __('Second'); ?>',
        currentText: '<?php echo __('Now'); ?>',
        closeText: '<?php echo __('Close'); ?>'});
        
    $("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
    
    $.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
};

datetime_picker_callback();

function show_instructions(id){
    title = "<?php echo __('Instructions'); ?>";
    $('#hidden_event_instructions_' + id).dialog({
        title: title,
        width: 600
    });
}

$(document).ready(function () {
    let moduleLabel = $('#text-module_search').prev();
    let moduleTip = $('#text-module_search').next().next();
    moduleLabel.append(moduleTip);

    $('.white_table_graph_header').first().append($('.filter_summary'));
});

// Show the modal window of an event
function show_event_dialo(event, dialog_page) {
    var ajax_file = getUrlAjax();

    var view = ``;

    if ($("#event_details_window").length) {
        view = "#event_details_window";
    } else if ($("#sound_event_details_window").length) {
        view = "#sound_event_details_window";
    }

    if (dialog_page == undefined) {
        dialog_page = "general";
    }

    try {
        event = event.replaceAll("&#x20;", "+");
        event = JSON.parse(atob(event), true);
    } catch (e) {
        console.error(e);
        return;
    }

    var inputs = $("#events_form :input");
    var values = {};
    inputs.each(function() {
        values[this.name] = $(this).val();
    });

    // Metaconsole mode flag
    var meta = $("#hidden-meta").val();

    // History mode flag
    var history = $("#hidden-history").val();

    jQuery.post(
        ajax_file,
        {
        page: "include/ajax/events",
        get_extended_event: 1,
        dialog_page: dialog_page,
        event: event,
        meta: meta,
        history: history,
        filter: values
        },
        function(data) {
        $(view)
            .hide()
            .empty()
            .append(data)
            .dialog({
            title: event.evento,
            resizable: true,
            draggable: true,
            modal: true,
            minWidth: 875,
            minHeight: 600,
            close: function() {
                $("#refrcounter").countdown("resume");
                $("div.vc-countdown").countdown("resume");
            },
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            width: 710,
            height: 650,
            autoOpen: true,
            open: function() {
                if (
                $.ui &&
                $.ui.dialog &&
                $.ui.dialog.prototype._allowInteraction
                ) {
                var ui_dialog_interaction =
                    $.ui.dialog.prototype._allowInteraction;
                $.ui.dialog.prototype._allowInteraction = function(e) {
                    if ($(e.target).closest(".select2-dropdown").length)
                    return true;
                    return ui_dialog_interaction.apply(this, arguments);
                };
                }
            },
            _allowInteraction: function(event) {
                return !!$(event.target).is(".select2-input") || this._super(event);
            }
            })
            .show();

        $("#refrcounter").countdown("pause");
        $("div.vc-countdown").countdown("pause");

        forced_title_callback();
        },
        "html"
    );
    return false;
}
</script>
