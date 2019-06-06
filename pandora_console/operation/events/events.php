<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
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

if (! check_acl($config['id_user'], 0, 'ER')
    && ! check_acl($config['id_user'], 0, 'EW')
    && ! check_acl($config['id_user'], 0, 'EM')
) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access event viewer'
    );
    include 'general/noaccess.php';
    return;
}


$event_a = check_acl($config['id_user'], 0, 'ER');
$event_w = check_acl($config['id_user'], 0, 'EW');
$event_m = check_acl($config['id_user'], 0, 'EM');
$access = ($event_a == true) ? 'ER' : (($event_w == true) ? 'EW' : (($event_m == true) ? 'EM' : 'ER'));

// Load specific stylesheet.
ui_require_css_file('events');

// Get requests.
$id_group = get_parameter('filter[id_group]');
$event_type = get_parameter('filter[event_type]');
$severity = get_parameter('filter[severity]');
$status = get_parameter('filter[status]');
$search = get_parameter('filter[search]');
$text_agent = get_parameter('filter[text_agent]');
$id_agent = get_parameter('filter[id_agent]');
$id_agent_module = get_parameter('filter[id_agent_module]');
$pagination = get_parameter('filter[pagination]');
$event_view_hr = get_parameter('filter[event_view_hr]', 8);
$id_user_ack = get_parameter('filter[id_user_ack]');
$group_rep = get_parameter('filter[group_rep]');
$tag_with = get_parameter('filter[tag_with]', io_json_mb_encode([]));
$tag_without = get_parameter(
    'filter[tag_without]',
    io_json_mb_encode([])
);
$filter_only_alert = get_parameter('filter[filter_only_alert]');
$id_group_filter = get_parameter('filter[id_group_filter]');
$date_from = get_parameter('filter[date_from]');
$date_to = get_parameter('filter[date_to]');
$source = get_parameter('filter[source]');
$id_extra = get_parameter('filter[id_extra]');
$user_comment = get_parameter('filter[user_comment]');

// Datatables offset, limit.
$start = get_parameter('start', 0);
$length = get_parameter('length', $config['block_size']);

if (io_safe_output($tag_with) == '["0"]') {
    $tag_with = '[]';
}

if (io_safe_output($tag_without) == '["0"]') {
    $tag_without = '[]';
}

// Ajax responses.
if (is_ajax()) {
    $get_filter_values = get_parameter('get_filter_values', 0);
    $save_event_filter = get_parameter('save_event_filter', 0);
    $update_event_filter = get_parameter('update_event_filter', 0);
    $get_event_filters = get_parameter('get_event_filters', 0);
    $get_events = get_parameter('get_events', 0);

    if ($get_events) {
        $sql_post = ' AND te.utimestamp > UNIX_TIMESTAMP(now() - INTERVAL '.$event_view_hr.' hour)';
        $events = events_get_events_grouped(
            // Sql_post.
            $sql_post,
            // Offset.
            $start,
            // Pagination.
            $length,
            // Meta.
            false,
            // History.
            false,
            // Total.
            false
        );

        if ($events) {
            $data = array_reduce(
                $events,
                function ($carry, $item) {
                    $carry[] = (object) $item;
                    return $carry;
                }
            );
        }

        $count = events_get_events_grouped(
            // Sql_post.
            $sql_post,
            // Offset.
            $start,
            // Pagination.
            $length,
            // Meta.
            false,
            // History.
            false,
            // Total.
            true
        );

        // RecordsTotal && recordsfiltered resultados totales.
        echo json_encode(
            [
                'data'            => $data,
                'recordsTotal'    => $count,
                'recordsFiltered' => $count,
            ]
        );
    }

    // AJAX section ends.
    exit;
}


// View.
$pure = get_parameter('pure', 0);
$url = ui_get_full_url('index.php?sec=eventos&sec2=operation/events/events');

// Concatenate parameters.
$url .= '';

if (is_metaconsole()) {
    // Load metaconsole frame.
    enterprise_hook('open_meta_frame');
} else if ($pure) {
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
    echo '<div class="vc-refr">';
    echo '<div class="vc-countdown"></div>';
    echo '<div id="vc-refr-form">';
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
    $fullscreen['text'] = '<a href="'.$url.'&amp;pure=1">'.html_print_image('images/full_screen.png', true, ['title' => __('Full screen')]).'</a>';

    // Event list.
    $list['active'] = false;
    $list['text'] = '<a href="index.php?sec=eventos&sec2=operation/events/events&amp;pure='.$config['pure'].'">'.html_print_image('images/events_list.png', true, ['title' => __('Event list')]).'</a>';

    // History event list.
    $history_list['active'] = false;
    $history_list['text'] = '<a href="index.php?sec=eventos&sec2=operation/events/events&amp;pure='.$config['pure'].'&amp;section=history&amp;history=1">'.html_print_image('images/books.png', true, ['title' => __('History event list')]).'</a>';

    // RSS.
    $rss['active'] = false;
    $rss['text'] = '<a href="operation/events/events_rss.php?user='.$config['id_user'].'&hashup='.$hashup.'&'.$params.'">'.html_print_image('images/rss.png', true, ['title' => __('RSS Events')]).'</a>';

    // Marquee.
    $marquee['active'] = false;
    $marquee['text'] = '<a href="operation/events/events_marquee.php">'.html_print_image('images/heart.png', true, ['title' => __('Marquee display')]).'</a>';

    // CSV.
    $csv['active'] = false;
    $csv['text'] = '<a href="operation/events/export_csv.php?'.$params.'">'.html_print_image('images/csv_mc.png', true, ['title' => __('Export to CSV file')]).'</a>';

    // Sound events.
    $sound_event['active'] = false;
    $sound_event['text'] = '<a href="javascript: openSoundEventWindow();">'.html_print_image('images/sound.png', true, ['title' => __('Sound events')]).'</a>';

    // If the user has administrator permission display manage tab.
    if (check_acl($config['id_user'], 0, 'EW') || check_acl($config['id_user'], 0, 'EM')) {
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

// Load filter form.
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
        __('All events'),
        __('Group events'),
        __('Group agents'),
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

// Source.
$data = html_print_input_text('source', $source, '', 35, 255, true);
$in = '<div class="filter_input"><label>'.__('Source').'</label>';
$in .= $data.'</div>';
$inputs[] = $in;


// Extra ID.
$data = html_print_input_text('id_extra', $id_extra, '', 11, 255, true);
$in = '<div class="filter_input"><label>'.__('Extra ID').'</label>';
$in .= $data.'</div>';
$inputs[] = $in;

// Comment.
$data = html_print_input_text(
    'user_comment',
    $user_comment,
    '',
    35,
    255,
    true
);
$in = '<div class="filter_input"><label>'.__('Comment').'</label>';
$in .= $data.'</div>';
$inputs[] = $in;


// Advanced filter.
$adv_filter = '';

// Load view.
$filter = join('', $inputs);
$filter .= ui_toggle(
    $adv_filter,
    __('Advanced options'),
    '',
    '',
    true,
    true,
    'white_box white_box_opened',
    'no-border'
);

try {
    ui_print_datatable(
        [
            'id'                 => 'events',
            'class'              => 'info_table',
            'style'              => 'width: 100%;',
            'ajax_url'           => 'operation/events/events',
            'ajax_data'          => ['get_events' => 1],
            'pagination_options' => [
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
            'form'               => [
                'class'  => 'flex-row',
                'html'   => $filter,
                'inputs' => [],
            ],
            'columns'            => [
                'id_evento',
                'id_agente',
            // 'id_usuario',
            // 'id_grupo',
            // 'estado',
                'evento',
                'agent_name',
                'timestamp',
            // 'utimestamp',
            // 'event_type',
            // 'id_agentmodule',
            // 'id_alert_am',
                'criticity',
                'user_comment',
                'tags',
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
            ],
            'ajax_postprocess'   => '
        function (item) {
            item.id_evento = "#"+item.id_evento;
            var color = "'.COL_UNKNOWN.'";
            var text = "UNKNOWN";
            switch (item.criticity) {
                case "'.EVENT_CRIT_CRITICAL.'": 
                    text = "CRITICAL";
                    color = "'.COL_CRITICAL.'";
                break;

                case "'.EVENT_CRIT_MAINTENANCE.'": 
                    text = "MAINTENANCE";
                    color = "'.COL_MAINTENANCE.'";
                break;

                case "'.EVENT_CRIT_INFORMATIONAL.'": 
                    text = "INFORMATIONAL";
                    color = "'.COL_INFORMATIONAL.'";
                break;

                case "'.EVENT_CRIT_MAJOR.'": 
                    text = "MAJOR";
                    color = "'.COL_MAJOR.'";
                break;

                case "'.EVENT_CRIT_MINOR.'": 
                    text = "MINOR";
                    color = "'.COL_MINOR.'";
                break;

                case "'.EVENT_CRIT_NORMAL.'": 
                    text = "NORMAL";
                    color = "'.COL_NORMAL.'";
                break;

                case "'.EVENT_CRIT_WARNING.'": 
                    text = "WARNING";
                    color = "'.COL_WARNING.'";
                break;

            }
            item.criticity = \'<div class="criticity" style="background: \';
            item.criticity += color + \'">\' + text + "</div>";
        }',
        ]
    );
} catch (Exception $e) {
    ui_print_error_message($e->getMessage());
}

// Close viewer.
enterprise_hook('close_meta_frame');
