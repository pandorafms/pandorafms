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
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

$event_a = check_acl($config['id_user'], 0, 'ER');
$event_w = check_acl($config['id_user'], 0, 'EW');
$event_m = check_acl($config['id_user'], 0, 'EM');

if (! $event_a
    && ! $event_w
    && ! $event_m
) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access event viewer'
    );
    if (is_ajax()) {
        return ['error' => 'noaccess'];
    }

    include 'general/noaccess.php';
    return;
}


$access = ($event_a == true) ? 'ER' : (($event_w == true) ? 'EW' : (($event_m == true) ? 'EM' : 'ER'));


$readonly = false;
if (!is_metaconsole()
    && isset($config['event_replication'])
    && $config['event_replication'] == 1
    && $config['show_events_in_local'] == 1
) {
    $readonly = true;
}

// Load specific stylesheet.
ui_require_css_file('events');
ui_require_css_file('tables');
if (is_metaconsole()) {
    ui_require_css_file('tables_meta', ENTERPRISE_DIR.'/include/styles/');
}

// Load extra javascript.
ui_require_javascript_file('pandora_events');

// Get requests.
$default_filter = [
    'status'        => EVENT_NO_VALIDATED,
    'event_view_hr' => $config['event_view_hr'],
    'group_rep'     => 1,
    'tag_with'      => [],
    'tag_without'   => [],
    'history'       => false,
];

$fb64 = get_parameter('fb64', null);
if (isset($fb64)) {
    $filter = json_decode(base64_decode($fb64), true);
} else {
    $filter = get_parameter(
        'filter',
        $default_filter
    );
}

$id_group = get_parameter(
    'filter[id_group]',
    $filter['id_group']
);
$event_type = get_parameter(
    'filter[event_type]',
    $filter['event_type']
);
$severity = get_parameter(
    'filter[severity]',
    $filter['severity']
);
$status = get_parameter(
    'filter[status]',
    $filter['status']
);
$search = get_parameter(
    'filter[search]',
    $filter['search']
);
$text_agent = get_parameter(
    'filter[text_agent]',
    $filter['text_agent']
);
$id_agent = get_parameter(
    'filter[id_agent]',
    $filter['id_agent']
);
$id_agent_module = get_parameter(
    'filter[id_agent_module]',
    $filter['id_agent_module']
);
$pagination = get_parameter(
    'filter[pagination]',
    $filter['pagination']
);
$event_view_hr = get_parameter(
    'filter[event_view_hr]',
    $filter['event_view_hr']
);
$id_user_ack = get_parameter(
    'filter[id_user_ack]',
    $filter['id_user_ack']
);
$group_rep = get_parameter(
    'filter[group_rep]',
    $filter['group_rep']
);
$tag_with = get_parameter(
    'filter[tag_with]',
    $filter['tag_with']
);
$tag_without = get_parameter(
    'filter[tag_without]',
    $filter['tag_without']
);
$filter_only_alert = get_parameter(
    'filter[filter_only_alert]',
    $filter['filter_only_alert']
);
$id_group_filter = get_parameter(
    'filter[id_group_filter]',
    $filter['id_group_filter']
);
$date_from = get_parameter(
    'filter[date_from]',
    $filter['date_from']
);
$date_to = get_parameter(
    'filter[date_to]',
    $filter['date_to']
);
$source = get_parameter(
    'filter[source]',
    $filter['source']
);
$id_extra = get_parameter(
    'filter[id_extra]',
    $filter['id_extra']
);
$user_comment = get_parameter(
    'filter[user_comment]',
    $filter['user_comment']
);
$history = get_parameter(
    'history',
    $filter['history']
);
$section = get_parameter('section', false);

// Ajax responses.
if (is_ajax()) {
    $get_events = get_parameter('get_events', 0);
    // Datatables offset, limit.
    $start = get_parameter('start', 0);
    $length = get_parameter('length', $config['block_size']);

    if ($get_events) {
        try {
            ob_start();
            $order = get_datatable_order(true);

            if (is_array($order) && $order['field'] == 'mini_severity') {
                $order['field'] = 'te.criticity';
            }

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
                'te.user_comment',
                'te.tags',
                'te.source',
                'te.id_extra',
                'te.critical_instructions',
                'te.warning_instructions',
                'te.unknown_instructions',
                'te.owner_user',
                'if(te.ack_utimestamp > 0, from_unixtime(te.ack_utimestamp),"") as ack_utimestamp',
                'te.custom_data',
                'te.data',
                'te.module_status',
                'ta.alias as agent_name',
                'tg.nombre as group_name',
            ];
            if (!is_metaconsole()) {
                $fields[] = 'am.nombre as module_name';
                $fields[] = 'am.id_agente_modulo as id_agentmodule';
                $fields[] = 'ta.server_name as server_name';
            } else {
                $fields[] = 'ts.server_name as server_name';
                $fields[] = 'te.id_agentmodule';
                $fields[] = 'te.server_id';
            }

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
                $history
            );
            $count = events_get_all(
                'count',
                $filter
            );

            if ($count !== false) {
                $count = $count['0']['nitems'];
            }

            if ($events) {
                $data = array_reduce(
                    $events,
                    function ($carry, $item) {
                        $tmp = (object) $item;
                        $tmp->meta = is_metaconsole();
                        if (is_metaconsole()) {
                            if ($tmp->server_name !== null) {
                                $tmp->data_server = metaconsole_get_servers($tmp->server_id);
                                $tmp->server_url_hash = metaconsole_get_servers_url_hash($tmp->data_server);
                            }
                        }

                        $tmp->evento = str_replace('"', '', io_safe_output($tmp->evento));
                        if (strlen($tmp->evento) >= 255) {
                            $tmp->evento = ui_print_truncate_text($tmp->evento, 255, $tmp->evento, true, false);
                        }

                        if ($tmp->module_name) {
                            $tmp->module_name = io_safe_output($tmp->module_name);
                        }

                        if ($tmp->comments) {
                            $tmp->comments = ui_print_comments($tmp->comments);
                        }

                        $tmp->agent_name = io_safe_output($tmp->agent_name);
                        $tmp->ack_utimestamp = ui_print_timestamp(
                            $tmp->ack_utimestamp,
                            true
                        );
                        $tmp->timestamp = ui_print_timestamp(
                            $tmp->timestamp,
                            true
                        );

                        $tmp->data = format_numeric($tmp->data, 1);

                        $tmp->instructions = events_get_instructions($item);

                        $tmp->b64 = base64_encode(json_encode($tmp));

                        $carry[] = $tmp;
                        return $carry;
                    }
                );
            }

            // RecordsTotal && recordsfiltered resultados totales.
            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $count,
                    'recordsFiltered' => $count,
                ]
            );
            $response = ob_get_clean();
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

$user_filter = db_get_row_sql(
    sprintf(
        'SELECT f.id_filter, f.id_name
         FROM tevent_filter f
         INNER JOIN tusuario u
             ON u.default_event_filter=f.id_filter
         WHERE u.id_user = "%s" ',
        $config['id_user']
    )
);
if ($user_filter !== false) {
    $filter = events_get_event_filter($user_filter['id_filter']);
    if ($filter !== false) {
        $id_group = $filter['id_group'];
        $event_type = $filter['event_type'];
        $severity = $filter['severity'];
        $status = $filter['status'];
        $search = $filter['search'];
        $text_agent = $filter['text_agent'];
        $id_agent = $filter['id_agent'];
        $id_agent_module = $filter['id_agent_module'];
        $pagination = $filter['pagination'];
        $event_view_hr = $filter['event_view_hr'];
        $id_user_ack = $filter['id_user_ack'];
        $group_rep = $filter['group_rep'];
        $tag_with = json_decode(io_safe_output($filter['tag_with']));
        $tag_without = json_decode(io_safe_output($filter['tag_without']));

        $tag_with_base64 = base64_encode(json_encode($tag_with));
        $tag_without_base64 = base64_encode(json_encode($tag_without));

        $filter_only_alert = $filter['filter_only_alert'];
        $id_group_filter = $filter['id_group_filter'];
        $date_from = $filter['date_from'];
        $date_to = $filter['date_to'];
        $source = $filter['source'];
        $id_extra = $filter['id_extra'];
        $user_comment = $filter['user_comment'];
    }
}

// TAGS.
// Get the tags where the user have permissions in Events reading tasks.
$tags = tags_get_user_tags($config['id_user'], $access);

$tags_select_with = [];
$tags_select_without = [];
$tag_with_temp = [];
$tag_without_temp = [];
foreach ($tags as $id_tag => $tag) {
    if ((array_search($id_tag, $tag_with) === false)
        || (array_search($id_tag, $tag_with) === null)
    ) {
        $tags_select_with[$id_tag] = ui_print_truncate_text($tag, 50, true);
    } else {
        $tag_with_temp[$id_tag] = ui_print_truncate_text($tag, 50, true);
    }

    if ((array_search($id_tag, $tag_without) === false)
        || (array_search($id_tag, $tag_without) === null)
    ) {
        $tags_select_without[$id_tag] = ui_print_truncate_text($tag, 50, true);
    } else {
        $tag_without_temp[$id_tag] = ui_print_truncate_text($tag, 50, true);
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
if (is_metaconsole()) {
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
    '',
    false,
    'width: 200px;'
);

$data[1] = html_print_image(
    'images/darrowright.png',
    true,
    [
        'id'    => 'button-add_with',
        'style' => 'cursor: pointer;',
        'title' => __('Add'),
    ]
);

$data[1] .= html_print_input_hidden(
    'tag_with',
    $tag_with_base64,
    true
);

$data[1] .= '<br><br>'.html_print_image(
    'images/darrowleft.png',
    true,
    [
        'id'    => 'button-remove_with',
        'style' => 'cursor: pointer;',
        'title' => __('Remove'),
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
    '',
    false,
    'width: 200px;'
);

$tabletags_with->data[] = $data;
$tabletags_with->rowclass[] = '';


$tabletags_without = html_get_predefined_table('transparent', 2);
$tabletags_without->id = 'filter_events_tags_without';
$tabletags_without->width = '100%';
$tabletags_without->cellspacing = 4;
$tabletags_without->cellpadding = 4;
$tabletags_without->class = 'noshadow';
if (is_metaconsole()) {
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
    '',
    false,
    'width: 200px;'
);
$data[1] = html_print_image(
    'images/darrowright.png',
    true,
    [
        'id'    => 'button-add_without',
        'style' => 'cursor: pointer;',
        'title' => __('Add'),
    ]
);
$data[1] .= html_print_input_hidden(
    'tag_without',
    $tag_without_base64,
    true
);
$data[1] .= '<br><br>'.html_print_image(
    'images/darrowleft.png',
    true,
    [
        'id'    => 'button-remove_without',
        'style' => 'cursor: pointer;',
        'title' => __('Remove'),
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
    '',
    false,
    'width: 200px;'
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
    echo '<div id="vc-controls" style="z-index: 999">';

    echo '<div id="menu_tab">';
    echo '<ul class="mn">';

    // Quit fullscreen.
    echo '<li class="nomn">';
    echo '<a target="_top" href="'.$url.'&amp;pure=0">';
    echo html_print_image(
        'images/normal_screen.png',
        true,
        ['title' => __('Back to normal mode')]
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
    if (is_metaconsole()) {
        // Load metaconsole frame.
        enterprise_hook('open_meta_frame');
    }

    // Header.
    $pss = get_user_info($config['id_user']);
    $hashup = md5($config['id_user'].$pss['password']);

    // Fullscreen.
    $fullscreen['active'] = false;
    $fullscreen['text'] = '<a class="events_link" href="'.$url.'&amp;pure=1&">'.html_print_image('images/full_screen.png', true, ['title' => __('Full screen')]).'</a>';

    // Event list.
    $list['active'] = false;
    $list['text'] = '<a class="events_link" href="index.php?sec=eventos&sec2=operation/events/events&amp;pure='.$config['pure'].'&">'.html_print_image('images/events_list.png', true, ['title' => __('Event list')]).'</a>';

    // History event list.
    $history_list['active'] = false;
    $history_list['text'] = '<a class="events_link" href="index.php?sec=eventos&sec2=operation/events/events&amp;pure='.$config['pure'].'&amp;section=history&amp;history=1&">'.html_print_image('images/books.png', true, ['title' => __('History event list')]).'</a>';

    // RSS.
    $rss['active'] = false;
    $rss['text'] = '<a class="events_link" href="operation/events/events_rss.php?user='.$config['id_user'].'&hashup='.$hashup.'&">'.html_print_image('images/rss.png', true, ['title' => __('RSS Events')]).'</a>';

    // Marquee.
    $marquee['active'] = false;
    $marquee['text'] = '<a class="events_link" href="operation/events/events_marquee.php?">'.html_print_image('images/heart.png', true, ['title' => __('Marquee display')]).'</a>';

    // CSV.
    $csv['active'] = false;
    $csv['text'] = '<a class="events_link" href="operation/events/export_csv.php?'.$filter_b64.'">'.html_print_image('images/csv_mc.png', true, ['title' => __('Export to CSV file')]).'</a>';

    // Sound events.
    $sound_event['active'] = false;
    $sound_event['text'] = '<a href="javascript: openSoundEventWindow();">'.html_print_image('images/sound.png', true, ['title' => __('Sound events')]).'</a>';

    // If the user has administrator permission display manage tab.
    if ($event_w || $event_m) {
        // Manage events.
        $manage_events['active'] = false;
        $manage_events['text'] = '<a href="index.php?sec=eventos&sec2=godmode/events/events&amp;section=filter&amp;pure='.$config['pure'].'">'.html_print_image('images/setup.png', true, ['title' => __('Manage events')]).'</a>';

        $manage_events['godmode'] = true;

        $onheader = [
            'manage_events' => $manage_events,
            'fullscreen'    => $fullscreen,
            'list'          => $list,
            'history'       => $history_list,
            'rss'           => $rss,
            'marquee'       => $marquee,
            'csv'           => $csv,
            'sound_event'   => $sound_event,
        ];
    } else {
        $onheader = [
            'fullscreen'  => $fullscreen,
            'list'        => $list,
            'history'     => $history_list,
            'rss'         => $rss,
            'marquee'     => $marquee,
            'csv'         => $csv,
            'sound_event' => $sound_event,
        ];
    }

    // If the history event is not enabled, dont show the history tab.
    if (!isset($config['metaconsole_events_history']) || $config['metaconsole_events_history'] != 1) {
        unset($onheader['history']);
    }

    switch ($section) {
        case 'sound_event':
            $onheader['sound_event']['active'] = true;
            $section_string = __('Sound events');
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

    if (! defined('METACONSOLE')) {
        unset($onheader['history']);
        ui_print_page_header(
            __('Events'),
            'images/op_events.png',
            false,
            'eventview',
            false,
            $onheader,
            true,
            'eventsmodal'
        );
    } else {
        unset($onheader['rss']);
        unset($onheader['marquee']);
        unset($onheader['csv']);
        unset($onheader['sound_event']);
        unset($onheader['fullscreen']);
        ui_meta_print_header(__('Events'), $section_string, $onheader);
    }

    ?>
    <script type="text/javascript">
        function openSoundEventWindow() {
            url = "<?php echo ui_get_full_url('operation/events/sound_events.php'); ?>";
            window.open(
                url,
                '<?php __('Sound Alerts'); ?>',
                'width=600, height=450, toolbar=no, location=no, directories=no, status=no, menubar=no, resizable=no'
            ); 
        }
    </script>
    <?php
}

// Error div for ajax messages.
echo "<div id='show_message_error'>";
echo '</div>';


// Controls.
if (is_metaconsole() !== true) {
    if (isset($config['event_replication'])
        && $config['event_replication'] == 1
    ) {
        if ($config['show_events_in_local'] == 0) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access event viewer. View disabled due event replication.'
            );
            ui_print_info_message(
                [
                    'message'  => __(
                        'Event viewer is disabled due event replication. For more information, please contact with the administrator'
                    ),
                    'no_close' => true,
                ]
            );
            return;
        } else {
            $readonly = true;
        }
    }
}

/*
 * Load filter form.
 */

// Group.
$user_groups_array = users_get_groups_for_select(
    $config['id_user'],
    $access,
    true,
    true,
    false
);
$data = html_print_select(
    $user_groups_array,
    'id_group_filter',
    $id_group_filter,
    '',
    '',
    0,
    true,
    false,
    false,
    'w130'
);
$in = '<div class="filter_input"><label>'.__('Group').'</label>';
$in .= $data.'</div>';
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

// Criticity - severity.
$severity_select .= html_print_select(
    get_priorities(),
    'severity',
    $severity,
    '',
    __('All'),
    '-1',
    true,
    false,
    false
);
$in = '<div class="filter_input"><label>'.__('Severity').'</label>';
$in .= $severity_select.'</div>';
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
        0 => __('All events'),
        1 => __('Group events'),
        2 => __('Group agents'),
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
$in = '<div class="filter_input"><label>'.__('Free search').'</label>';
$in .= $data.'</div>';
$inputs[] = $in;

$buttons = [];

$buttons[] = [
    'id'      => 'load-filter',
    'class'   => 'float-left margin-right-2 sub config',
    'text'    => __('Load filter'),
    'onclick' => '',
];

if ($event_w || $event_m) {
    $buttons[] = [
        'id'      => 'save-filter',
        'class'   => 'float-left margin-right-2 sub wand',
        'text'    => __('Save filter'),
        'onclick' => '',
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

if ($meta) {
    $params['javascript_page'] = 'enterprise/meta/include/ajax/events.ajax';
}

$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_name'] = 'id_agent';
$params['hidden_input_idagent_value'] = $id_agent;
$params['size'] = '';

$data = ui_print_agent_autocomplete_input($params);
$in = '<div class="filter_input"><label>'.__('Agent search').'</label>';
$in .= $data.'</div>';
$adv_inputs[] = $in;

// Mixed. Metaconsole => server, Console => module.
if (is_metaconsole()) {
    $title = __('Server');
    $data = html_print_select_from_sql(
        'SELECT id, server_name FROM tmetaconsole_setup',
        'server_id',
        $server_id,
        '',
        __('All'),
        '0',
        true
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
    users_can_manage_group_all()
);

$data = html_print_select(
    $user_users,
    'id_user_ack',
    $id_user_ack,
    '',
    __('Any'),
    0,
    true
);
$in = '<div class="filter_input"><label>'.__('User ack.').'</label>';
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
    true
);
$in = '<div class="filter_input"><label>'.__('Alert events').'</label>';
$in .= $data.'</div>';
$adv_inputs[] = $in;

// Gap.
$adv_inputs[] = '<div class="filter_input"></div>';

// Date from.
$data = html_print_input_text(
    'date_from',
    $date_from,
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
$in = '<div class="filter_input">';
$in .= '<div class="filter_input_little"><label>'.__('Date from').'</label>';
$in .= $data.'</div>';

// Time from.
$data = html_print_input_text(
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
$in .= '<div class="filter_input_little"><label>'.__('Time from').'</label>';
$in .= $data.'</div>';
$in .= '</div>';
$adv_inputs[] = $in;

// Date to.
$data = html_print_input_text(
    'date_to',
    $date_to,
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
$in = '<div class="filter_input">';
$in .= '<div class="filter_input_little"><label>'.__('Date to').'</label>';
$in .= $data.'</div>';

// Time to.
$data = html_print_input_text(
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
$in .= '<div class="filter_input_little"><label>'.__('Time to').'</label>';
$in .= $data.'</div>';
$in .= '</div>';
$adv_inputs[] = $in;


// Tags.
if (is_metaconsole()) {
    $data = '<fieldset><legend style="padding:0px;">'.__('Events with following tags').'</legend>'.html_print_table($tabletags_with, true).'</fieldset>';
    $data .= '<fieldset><legend style="padding:0px;">'.__('Events without following tags').'</legend>'.html_print_table($tabletags_without, true).'</fieldset>';
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
    __('Advanced options'),
    '',
    '',
    true,
    true,
    'white_box white_box_opened',
    'no-border flex-row'
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
            // 'id_agente',
            // 'id_usuario',
            // 'id_grupo',
            // 'estado',
        'agent_name',
        'timestamp',
            // 'utimestamp',
            // 'event_type',
            // 'id_agentmodule',
            // 'id_alert_am',
        'event_type',
            // 'user_comment',
            // 'tags',
            // 'source',
            // 'id_extra',
            // 'critical_instructions',
            // 'warning_instructions',
            // 'unknown_instructions',
            // 'owner_user',
            // 'ack_utimestamp',
            // 'custom_data',
            // 'data',
            // 'module_status',
            // 'similar_ids',
            // 'event_rep',
            // 'timestamp_rep',
            // 'timestamp_rep_min',
            // 'module_name',
        [
            'text'  => 'options',
            'class' => 'action_buttons w120px',
        ],[
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
                'class' => 'action_buttons mw120px',
            ],[
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
    if ($user_filter !== false) {
        $active_filters_div .= io_safe_output($user_filter['id_name']);
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
    if ($group_rep == 0) {
        $active_filters_div .= __('All events.');
    } else if ($group_rep == 1) {
        $active_filters_div .= __('Group events');
    } else if ($group_rep == 2) {
        $active_filters_div .= __('Group agents.');
    }

    $active_filters_div .= '</div>';
    $active_filters_div .= '</div>';

    // Close.
    $active_filters_div .= '</div>';

    $table_id = 'events';
    $form_id = 'events_form';

    // Print datatable.
    ui_print_datatable(
        [
            'id'                  => $table_id,
            'class'               => 'info_table events',
            'style'               => 'width: 100%;',
            'ajax_url'            => 'operation/events/events',
            'ajax_data'           => [
                'get_events' => 1,
                'history'    => (int) $history,
            ],
            'form'                => [
                'id'            => $form_id,
                'class'         => 'flex-row',
                'html'          => $filter,
                'inputs'        => [],
                'extra_buttons' => $buttons,
            ],
            'extra_html'          => $active_filters_div,
            'pagination_options'  => [
                [
                    $config['block_size'],
                    10,
                    25,
                    100,
                    200,
                    500,
                    1000,
                    -1,
                ],
                [
                    $config['block_size'],
                    10,
                    25,
                    100,
                    200,
                    500,
                    1000,
                    'All',
                ],
            ],
            'order'               => [
                'field'     => 'timestamp',
                'direction' => 'desc',
            ],
            'column_names'        => $column_names,
            'columns'             => $fields,
            'no_sortable_columns' => [
                -1,
                -2,
                'column-instructions',
            ],
            'ajax_postprocess'    => 'process_datatables_item(item)',
            'drawCallback'        => 'process_datatables_callback(this, settings)',
        ]
    );
} catch (Exception $e) {
    ui_print_error_message($e->getMessage());
}

// Event responses.
$sql_event_resp = "SELECT id, name FROM tevent_response WHERE type LIKE 'command'";
$event_responses = db_get_all_rows_sql($sql_event_resp);

if ($config['event_replication'] != 1) {
    if ($event_w && !$readonly) {
        $array_events_actions['in_progress_selected'] = __('In progress selected');
        $array_events_actions['validate_selected'] = __('Validate selected');
    }

    if ($event_m == 1 && !$readonly) {
        $array_events_actions['delete_selected'] = __('Delete selected');
    }
}

foreach ($event_responses as $val) {
    $array_events_actions[$val['id']] = $val['name'];
}


echo '<div class="multi-response-buttons">';
echo '<form method="post" id="form_event_response">';
echo '<input type="hidden" id="max_execution_event_response" value="'.$config['max_execution_event_response'].'" />';
html_print_select($array_events_actions, 'response_id', '', '', '', 0, false, false, false);
echo '&nbsp&nbsp';
html_print_button(__('Execute event response'), 'submit_event_response', false, 'execute_event_response(true);', 'class="sub next"');
echo "<span id='response_loading_dialog' style='display:none'>".html_print_image('images/spinner.gif', true).'</span>';
echo '</form>';
echo '<span id="max_custom_event_resp_msg" style="display:none; color:#e63c52; line-height: 200%;">';
echo __(
    'A maximum of %s event custom responses can be selected',
    $config['max_execution_event_response']
).'</span>';
echo '<span id="max_custom_selected" style="display:none; color:#e63c52; line-height: 200%;">';
echo __(
    'Please, select an event'
).'</span>';
echo '</div>';


// Close viewer.
enterprise_hook('close_meta_frame');

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
html_print_input_hidden('filterid', $is_filter);
html_print_input_hidden(
    'ajax_file',
    ui_get_full_url('ajax.php', false, false, false)
);

// AJAX call options responses.
echo "<div id='event_details_window'></div>";
echo "<div id='event_response_window'></div>";
echo "<div id='event_response_command_window' title='".__('Parameters')."'></div>";

// Load filter div for dialog.
echo '<div id="load-modal-filter" style="display: none"></div>';
echo '<div id="save-modal-filter" style="display: none"></div>';

if ($_GET['refr'] || $do_refresh === true) {
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
            if(label == '<?php echo __('Agent name'); ?>') {
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
            until_time.setTime (until_time.getTime () + parseInt(<?php echo ($config['refr'] * 1000); ?>));
            return until_time;
        }

    }

}

function process_datatables_item(item) {

    // Url to go to node from meta.
    var server_url = '';
    var hashdata = '';
    if(item.meta === true){
        if(typeof item.data_server !== 'undefined' && typeof item.server_url_hash !== 'undefined'){
            server_url = item.data_server.server_url;
            hashdata = item.server_url_hash;
        }
    }


    // Show comments events.
    item.user_comment = item.comments
  
    if(item.comments.length > 80){

    item.user_comment += '&nbsp;&nbsp;<a id="show_comments" href="javascript:" onclick="show_event_dialog(\'';
    item.user_comment += item.b64+"','comments'," + $("#group_rep").val()+');">';
    item.user_comment += '<?php echo html_print_image('images/eye.png', true, ['title' => __('Show more')]); ?></a>';
    
    }
 
    // Grouped events.
    if(item.max_id_evento) {
        item.id_evento = item.max_id_evento
    }

    /* Event severity prepared */
    var color = "<?php echo COL_UNKNOWN; ?>";
    var text = "<?php echo __('UNKNOWN'); ?>";
    switch (item.criticity) {
        case "<?php echo EVENT_CRIT_CRITICAL; ?>":
            text = "<?php echo __('CRITICAL'); ?>";
            color = "<?php echo COL_CRITICAL; ?>";
        break;

        case "<?php echo EVENT_CRIT_MAINTENANCE; ?>":
            text = "<?php echo __('MAINTENANCE'); ?>";
            color = "<?php echo COL_MAINTENANCE; ?>";
        break;

        case "<?php echo EVENT_CRIT_INFORMATIONAL; ?>":
            text = "<?php echo __('INFORMATIONAL'); ?>";
            color = "<?php echo COL_INFORMATIONAL; ?>";
        break;

        case "<?php echo EVENT_CRIT_MAJOR; ?>":
            text = "<?php echo __('MAJOR'); ?>";
            color = "<?php echo COL_MAJOR; ?>";
        break;

        case "<?php echo EVENT_CRIT_MINOR; ?>":
            text = "<?php echo __('MINOR'); ?>";
            color = "<?php echo COL_MINOR; ?>";
        break;

        case "<?php echo EVENT_CRIT_NORMAL; ?>":
            text = "<?php echo __('NORMAL'); ?>";
            color = "<?php echo COL_NORMAL; ?>";
        break;

        case "<?php echo EVENT_CRIT_WARNING; ?>":
            text = "<?php echo __('WARNING'); ?>";
            color = "<?php echo COL_WARNING; ?>";
        break;
    }
    output = '<div data-title="';
    output += text;
    output += '" data-use_title_for_force_title="1" ';
    output += 'class="forced_title mini-criticity h100p" ';
    output += 'style="background: ' + color + '">';
    output += '</div>';

    // Add event severity to end of text.
    evn = '<a href="javascript:" onclick="show_event_dialog(\'';
    evn += item.b64+'\','+$("#group_rep").val()+');">';
    // Grouped events.
    if(item.event_rep && item.event_rep > 1) {
        evn += '('+item.event_rep+') ';
    }
    evn += item.evento+'</a>';

    item.mini_severity = '<div class="event flex-row h100p nowrap">';
    item.mini_severity += output;
    item.mini_severity += '</div>';

    criticity = '<div class="criticity" style="background: ';
    criticity += color + '">' + text + "</div>";

    // Grouped events.
    if(item.max_timestamp) {
        item.timestamp = item.max_timestamp;
    }

    /* Event type prepared. */
    switch (item.event_type) {
        case "<?php echo EVENTS_ALERT_FIRED; ?>":
        case "<?php echo EVENTS_ALERT_RECOVERED; ?>":
        case "<?php echo EVENTS_ALERT_CEASED; ?>":
        case "<?php echo EVENTS_ALERT_MANUAL_VALIDATION; ?>":
            text = "<?php echo __('ALERT'); ?>";
            color = "<?php echo COL_ALERTFIRED; ?>";
        break;

        case "<?php echo EVENTS_RECON_HOST_DETECTED; ?>":
        case "<?php echo EVENTS_SYSTEM; ?>":
        case "<?php echo EVENTS_ERROR; ?>":
        case "<?php echo EVENTS_NEW_AGENT; ?>":
        case "<?php echo EVENTS_CONFIGURATION_CHANGE; ?>":
            text = "<?php echo __('SYSTEM'); ?>";
            color = "<?php echo COL_MAINTENANCE; ?>";
        break;

        case "<?php echo EVENTS_GOING_UP_WARNING; ?>":
        case "<?php echo EVENTS_GOING_DOWN_WARNING; ?>":
            text = "<?php echo __('WARNING'); ?>";
            color = "<?php echo COL_WARNING; ?>";
        break;

        case "<?php echo EVENTS_GOING_DOWN_NORMAL; ?>":
        case "<?php echo EVENTS_GOING_UP_NORMAL; ?>":
            text = "<?php echo __('NORMAL'); ?>";
            color = "<?php echo COL_NORMAL; ?>";
        break;

        case "<?php echo EVENTS_GOING_DOWN_CRITICAL; ?>":
        case "<?php echo EVENTS_GOING_UP_CRITICAL; ?>":
            text = "<?php echo __('CRITICAL'); ?>";
            color = "<?php echo COL_CRITICAL; ?>";
        break;

        case "<?php echo EVENTS_UNKNOWN; ?>":
        case "<?php echo EVENTS_GOING_UNKNOWN; ?>":
        default:
            text = "<?php echo __('UNKNOWN'); ?>";
            color = "<?php echo COL_UNKNOWN; ?>";
        break;
    }

    event_type = '<div class="criticity" style="background: ';
    event_type += color + '">' + text + "</div>";

    /* Module status */
    /* Event severity prepared */
    var color = "<?php echo COL_UNKNOWN; ?>";
    var text = "<?php echo __('UNKNOWN'); ?>";
    switch (item.module_status) {
        case "<?php echo AGENT_MODULE_STATUS_NORMAL; ?>":
            text = "<?php echo __('NORMAL'); ?>";
            color = "<?php echo COL_NORMAL; ?>";
        break;

        case "<?php echo AGENT_MODULE_STATUS_CRITICAL_BAD; ?>":
            text = "<?php echo __('CRITICAL'); ?>";
            color = "<?php echo COL_CRITICAL; ?>";
        break;

        case "<?php echo AGENT_MODULE_STATUS_NO_DATA; ?>":
            text = "<?php echo __('NOT INIT'); ?>";
            color = "<?php echo COL_NOTINIT; ?>";
        break;

        case "<?php echo AGENT_MODULE_STATUS_CRITICAL_ALERT; ?>":
        case "<?php echo AGENT_MODULE_STATUS_NORMAL_ALERT; ?>":
        case "<?php echo AGENT_MODULE_STATUS_WARNING_ALERT; ?>":
            text = "<?php echo __('ALERT'); ?>";
            color = "<?php echo COL_ALERTFIRED; ?>";
        break;

        case "<?php echo AGENT_MODULE_STATUS_WARNING; ?>":
            text = "<?php echo __('WARNING'); ?>";
            color = "<?php echo COL_WARNING; ?>";
        break;
    }

    module_status = '<div class="criticity" style="background: ';
    module_status += color + '">' + text + "</div>";

    /* Options */
    // Show more.
    item.options = '<a href="javascript:" onclick="show_event_dialog(\'';
    item.options += item.b64+'\','+$("#group_rep").val();
    item.options += ')" ><?php echo html_print_image('images/eye.png', true, ['title' => __('Show more')]); ?></a>';

    <?php
    if (!$readonly) {
        ?>

    if (item.user_can_write == '1') {
        if (item.estado != '1') {
            // Validate.
            item.options += '<a href="javascript:" onclick="validate_event(dt_<?php echo $table_id; ?>,';
            if (item.max_id_evento) {
                item.options += item.max_id_evento+', '+ item.event_rep +', this)" id="val-'+item.max_id_evento+'">';
                item.options += '<?php echo html_print_image('images/tick.png', true, ['title' => __('Validate events')]); ?></a>';
            } else {
                item.options += item.id_evento+', 0, this)" id="val-'+item.id_evento+'">';
                item.options += '<?php echo html_print_image('images/tick.png', true, ['title' => __('Validate event')]); ?></a>';
            }
        }

        if (item.estado != '2') {
            // In process.
            item.options += '<a href="javascript:" onclick="in_process_event(dt_<?php echo $table_id; ?>,';
            if (item.max_id_evento) {
                item.options += item.max_id_evento+', '+ item.event_rep +', this)" id="proc-'+item.max_id_evento+'">';
            } else {
                item.options += item.id_evento+', 0, this)" id="proc-'+item.id_evento+'">';
            }
            item.options += '<?php echo html_print_image('images/hourglass.png', true, ['title' => __('Change to in progress status')]); ?></a>';
        }
    }

    if (item.user_can_manage == '1') {
        // Delete.
        item.options += '<a href="javascript:" onclick="delete_event(dt_<?php echo $table_id; ?>,';
        if (item.max_id_evento) {
            item.options += item.max_id_evento+', '+ item.event_rep +', this)" id="del-'+item.max_id_evento+'">';
            item.options += '<?php echo html_print_image('images/cross.png', true, ['title' => __('Delete events')]); ?></a>';
        } else {
            item.options += item.id_evento+', 0, this)" id="del-'+item.id_evento+'">';
            item.options += '<?php echo html_print_image('images/cross.png', true, ['title' => __('Delete event')]); ?></a>';
        }
    }
        <?php
    }
    ?>

    // Multi select.
    item.m = '<input name="checkbox-multi[]" type="checkbox" value="';
    item.m += item.id_evento+'" id="checkbox-multi-'+item.id_evento+'" ';
    if (item.max_id_evento) {
        item.m += ' event_rep="' + item.event_rep +'" ';
    } else {
        item.m += ' event_rep="0" ';
    }
    item.m += 'class="candeleted chk_val">';

    /* Status */
    img = '<?php echo html_print_image('images/star.png', true, ['title' => __('Unknown'), 'class' => 'forced-title']); ?>';
    switch (item.estado) {
        case "<?php echo EVENT_STATUS_NEW; ?>":
            img = '<?php echo html_print_image('images/star.png', true, ['title' => __('New event'), 'class' => 'forced-title']); ?>';
        break;

        case "<?php echo EVENT_STATUS_VALIDATED; ?>":
            img = '<?php echo html_print_image('images/tick.png', true, [ 'title' => __('Event validated'), 'class' => 'forced-title']); ?>';
        break;

        case "<?php echo EVENT_STATUS_INPROCESS; ?>":
            img = '<?php echo html_print_image('images/hourglass.png', true, [ 'title' => __('Event in process'), 'class' => 'forced-title']); ?>';
        break;
    }

    /* Update column content now to avoid json poisoning. */


    // Url to agent view.
    var url_link = '<?php echo ui_get_full_url('index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='); ?>';
    var url_link_hash = '';
    if(item.meta === true){   
        url_link = server_url+'/index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=';
        url_link_hash = hashdata;
    }


    /* Agent name link */
    if (item.id_agente > 0) {
        item.agent_name = '<a href="'+url_link+item.id_agente+url_link_hash+'">' + item.agent_name + '</a>';
    } else {
        item.agent_name = '';
    }

    /* Agent ID link */
    if (item.id_agente > 0) {
        <?php
        if (in_array('agent_name', $fields)) {
            ?>
            item.id_agente = '<a href="'+url_link+item.id_agente+url_link_hash+'">' + item.id_agente + '</a>';
            <?php
        } else {
            ?>
            item.id_agente = '<a href="'+url_link+item.id_agente+url_link_hash+'">' + item.agent_name + '</a>';
            <?php
        }
        ?>
    } else {
        item.id_agente = '';
    }

    item.estado = '<div>';
    item.estado += img;
    item.estado += '</div>';

    item.criticity = criticity;
    item.event_type = event_type;
    item.module_status = module_status;

    /* Event ID dash */
    item.id_evento = "#"+item.id_evento;

    /* Owner */
    if (item.owner_user == "0") {
        item.owner_user = '<?php echo __('System'); ?>';
    }

    // Add event severity format to itself.
    item.evento = evn;

    /* Group name */
    if (item.id_grupo == "0") {
        item.id_grupo = "<?php echo __('All'); ?>";
    } else {
        item.id_grupo = item.group_name;
    }

    /* Module name */
    item.id_agentmodule = item.module_name;
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
            values[this.name] = $(this).val();
        })

        values['history'] = "<?php echo (int) $history; ?>";

        var url = e.currentTarget.href;
        url += 'fb64=' + btoa(JSON.stringify(values));
        url += '&refr=' + '<?php echo $config['refr']; ?>';
        document.location = url;

    });

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
            $('#summary_hours').html('<?php echo __('Any'); ?>');
        } else if (hours == 1) {
            $('#summary_hours').html('<?php echo __('Last hour.'); ?>');
        } else {
            $('#summary_hours').html(hours + '<?php echo ' '.__('hours.'); ?>');
        }
    });

    $('#group_rep').on("change", function(){
        $('#summary_duplicates').html($("#group_rep option:selected").text());
    });

    /* Summary updates end. */

    /* Filter management */
    $('#load-filter').click(function (){
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
                        load_filter_modal: 1,
                        current_filter: $('#latest_filter_id').val()
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

    $('#save-filter').click(function (){
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
    if(pure == 1){
        var refresh_interval = parseInt('<?php echo ($config['refr'] * 1000); ?>');
        var until_time='';

        // If autorefresh is disabled, don't show the countdown   
        var refresh_time = '<?php echo $_GET['refr']; ?>'; 
        if(refresh_time == '' || refresh_time == 0){
            $('#refrcounter').toggle();
        }

        function events_refresh() {
            until_time = new Date();
            until_time.setTime (until_time.getTime () + parseInt(<?php echo ($config['refr'] * 1000); ?>));

            $("#refrcounter").countdown ({
                until: until_time,
                layout: '(%M%nn%M:%S%nn%S <?php echo __('Until next'); ?>)',
                labels: ['', '', '', '', '', '', ''],
                onExpiry: function () {
                    dt_events.draw(false);
                }
            });
        }
        // Start the countdown when page is loaded (first time).
        events_refresh();
        // Repeat countdown according to refresh_interval.
        setInterval(events_refresh, refresh_interval);


        $("select#refresh").change (function () {
            var href = window.location.href;

            inputs = $("#events_form :input");
            values = {};
            inputs.each(function() {
                values[this.name] = $(this).val();
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
    }

});



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

</script>
