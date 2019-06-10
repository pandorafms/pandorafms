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

// Load extra javascript.
ui_require_javascript_file('pandora_events');

// Get requests.
$id_group = get_parameter('filter[id_group]');
$event_type = get_parameter('filter[event_type]');
$severity = get_parameter('filter[severity]');
$status = get_parameter('filter[status]', EVENT_NO_VALIDATED);
$search = get_parameter('filter[search]');
$text_agent = get_parameter('filter[text_agent]');
$id_agent = get_parameter('filter[id_agent]');
$id_agent_module = get_parameter('filter[id_agent_module]');
$pagination = get_parameter('filter[pagination]');
$event_view_hr = get_parameter('filter[event_view_hr]', 1);
$id_user_ack = get_parameter('filter[id_user_ack]');
$group_rep = get_parameter('filter[group_rep]');
$tag_with = get_parameter('filter[tag_with]', []);
$tag_without = get_parameter('filter[tag_without]', []);
$filter_only_alert = get_parameter('filter[filter_only_alert]');
$id_group_filter = get_parameter('filter[id_group_filter]');
$date_from = get_parameter('filter[date_from]');
$date_to = get_parameter('filter[date_to]');
$source = get_parameter('filter[source]');
$id_extra = get_parameter('filter[id_extra]');
$user_comment = get_parameter('filter[user_comment]');

// Ajax responses.
if (is_ajax()) {
    $get_filter_values = get_parameter('get_filter_values', 0);
    $save_event_filter = get_parameter('save_event_filter', 0);
    $update_event_filter = get_parameter('update_event_filter', 0);
    $get_event_filters = get_parameter('get_event_filters', 0);
    $get_events = get_parameter('get_events', 0);
    $filter = get_parameter('filter', []);
    // Datatables offset, limit.
    $start = get_parameter('start', 0);
    $length = get_parameter('length', $config['block_size']);

    if ($get_events) {
        $order = get_datatable_order(true);

        $events = events_get_all(
            [
                'te.*',
                'ta.alias as agent_name',
                'tg.nombre as group_name',
            ],
            $filter,
            // Offset.
            $start,
            // Limit.
            $length,
            // Order.
            $order['direction'],
            // Sort field.
            $order['field']
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
                    $carry[] = (object) $item;
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
    }

    // AJAX section ends.
    exit;
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

// Source.
$data = html_print_input_text('source', $source, '', '', 255, true);
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
    '',
    255,
    true
);
$in = '<div class="filter_input"><label>'.__('Comment').'</label>';
$in .= $data.'</div>';
$inputs[] = $in;


/*
 * Advanced filter.
 */

$adv_inputs = [];

// Free search.
$data = html_print_input_text('search', $search, '', '', 255, true);
$in = '<div class="filter_input"><label>'.__('Free search').'</label>';
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
        'script',
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
        '-1' => __('All'),
        '0'  => __('Filter alert events'),
        '1'  => __('Only alert events'),
    ],
    'filter_only_alert',
    $filter_only_alert,
    '',
    '',
    '',
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
    $default_fields = [
        'evento',
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
        'options',
    ];
    $fields = explode(',', $config['event_fields']);

    // Always check something is shown.
    if (empty($fields)) {
        $fields = $default_fields;
    }

    // Always add options column.
    $fields = array_merge($fields, ['options']);

    // Get column names.
    $column_names = events_get_column_names($fields);

    // Open current filter quick reference.
    $active_filters_div = '<div class="filter_summary">';
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
    } else if ($event_view_hr == 2) {
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


    // Print datatable.
    ui_print_datatable(
        [
            'id'                 => 'events',
            'class'              => 'info_table events',
            'style'              => 'width: 100%;',
            'ajax_url'           => 'operation/events/events',
            'ajax_data'          => ['get_events' => 1],
            'form'               => [
                'class'  => 'flex-row',
                'html'   => $filter,
                'inputs' => [],
            ],
            'extra_html'         => $active_filters_div,
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
            'order'              => [
                'field'     => 'timestamp',
                'direction' => 'desc',
            ],
            'column_names'       => $column_names,
            'columns'            => $fields,
            'ajax_postprocess'   => '
        function (item) {
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
            output = \'<div data-title="\';
            output += text;
            output += \'" data-use_title_for_force_title="1" \';
            output += \'class="forced_title mini-criticity h100p" \';
            output += \'style="background: \' + color + \'">\';
            output += \'</div>\';

            evn = \'<div class="event flex-row h100p nowrap">\';
            evn += \'<div><a href="javascript:" onclick="show_event_dialog(\';
            evn += item.id_evento+\');">\';
            evn += item.evento+\'</a></div>\';
            evn += output;
            evn += \'</div>\'

            item.evento = evn;
            item.criticity = \'<div class="criticity" style="background: \';
            item.criticity += color + \'">\' + text + "</div>";


            // Event type.
            switch (item.event_type) {
                case "'.EVENTS_ALERT_FIRED.'":
                case "'.EVENTS_ALERT_RECOVERED.'":
                case "'.EVENTS_ALERT_CEASED.'":
                case "'.EVENTS_ALERT_MANUAL_VALIDATION.'":
                    text = "'.__('ALERT').'";
                    color = "'.COL_ALERTFIRED.'";
                break;

                case "'.EVENTS_RECON_HOST_DETECTED.'":
                case "'.EVENTS_SYSTEM.'":
                case "'.EVENTS_ERROR.'":
                case "'.EVENTS_NEW_AGENT.'":
                case "'.EVENTS_CONFIGURATION_CHANGE.'":
                    text = "'.__('SYSTEM').'";
                    color = "'.COL_MAINTENANCE.'";
                break;

                case "'.EVENTS_GOING_UP_WARNING.'":
                case "'.EVENTS_GOING_DOWN_WARNING.'":
                $tex = "'.__('WARNING').'";
                    color = "'.COL_WARNING.'";
                break;

                case "'.EVENTS_GOING_DOWN_NORMAL.'":
                case "'.EVENTS_GOING_UP_NORMAL.'":
                    text = "'.__('NORMAL').'";
                    color = "'.COL_NORMAL.'";
                break;

                case "'.EVENTS_GOING_DOWN_CRITICAL.'":
                case "'.EVENTS_GOING_UP_CRITICAL.'":
                    text = "'.__('CRITICAL').'";
                    color = "'.COL_CRITICAL.'";
                break;

                case "'.EVENTS_UNKNOWN.'":
                case "'.EVENTS_GOING_UNKNOWN.'":
                default:
                    text = "'.__('UNKNOWN').'";
                    color = "'.COL_UNKNOWN.'";
                break;
            }

            item.event_type = \'<div class="criticity" style="background: \';
            item.event_type += color + \'">\' + text + "</div>";

            if (item.id_agente > 0) {
                item.agent_name = \'<a href="'.ui_get_full_url('index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=').'\'+item.id_agente+\'">\' + item.agent_name + \'</a>\';
            }

            if (item.id_agente > 0) {
                item.id_agente = \'<a href="'.ui_get_full_url('index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=').'\'+item.id_agente+\'">\' + item.id_agente + \'</a>\';
            }

            if (item.id_grupo == "0") {
                item.id_grupo = "'.__('All').'";
            } else {
                item.id_grupo = item.group_name;
            }

            item.options = \'<a href="#">button</a>\';
            item.id_evento = "#"+item.id_evento;
        }',
        ]
    );
} catch (Exception $e) {
    ui_print_error_message($e->getMessage());
}

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
?>
<script type="text/javascript">

var select_with_tag_empty = <?php echo (int) $remove_with_tag_disabled; ?>;
var select_without_tag_empty = <?php echo (int) $remove_without_tag_disabled; ?>;
var origin_select_with_tag_empty = <?php echo (int) $add_with_tag_disabled; ?>;
var origin_select_without_tag_empty = <?php echo (int) $add_without_tag_disabled; ?>;

var val_none = 0;
var text_none = "<?php echo __('None'); ?>";
var group_agents_id = false;

$(document).ready( function() {
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
    
    // Don't collapse filter if update button has been pushed
    if ($("#hidden-open_filter").val() == 'true') {
        $("#event_control").toggle();
    }
    
    // If selected is not 'none' show filter name.
    if ( $("#filter_id").val() != 0 ) {
        $("#row_name").css('visibility', '');
        $("#submit-update_filter").css('visibility', '');
    }

    if ($("#hidden-id_name").val() == ''){
        if($("#hidden-filterid").val() != ''){
            $('#row_name').css('visibility', '');
            $("#submit-update_filter").css('visibility', '');
            jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                {"page" : "operation/events/events_list",
                "get_filter_values" : 1,
                "id" : $('#hidden-filterid').val()
                },
                function (data) {
                    jQuery.each (data, function (i, val) {
                        if (i == 'id_name')
                            $("#hidden-id_name").val(val);
                        if (i == 'id_group')
                            $("#id_group").val(val);
                        if (i == 'event_type')
                            $("#event_type").val(val);
                        if (i == 'severity')
                            $("#severity").val(val);
                        if (i == 'status')
                            $("#status").val(val);
                        if (i == 'search')
                            $("#text-search").val(val);
                        if (i == 'text_agent')
                            $("#text_id_agent").val(val);
                        if (i == 'id_agent')
                            $('input:hidden[name=id_agent]').val(val);
                        if (i == 'id_agent_module')
                            $('input:hidden[name=module_search_hidden]').val(val);
                        if (i == 'pagination')
                            $("#pagination").val(val);
                        if (i == 'event_view_hr')
                            $("#text-event_view_hr").val(val);
                        if (i == 'id_user_ack')
                            $("#id_user_ack").val(val);
                        if (i == 'group_rep')
                            $("#group_rep").val(val);
                        if (i == 'tag_with')
                            $("#hidden-tag_with").val(val);
                        if (i == 'tag_without')
                            $("#hidden-tag_without").val(val);
                        if (i == 'filter_only_alert')
                            $("#filter_only_alert").val(val);
                        if (i == 'id_group_filter')
                            $("#id_group_filter").val(val);
                        if (i == 'source')
                            $("#text-source").val(val);
                        if (i == 'id_extra')
                            $("#text-id_extra").val(val);
                        if (i == 'user_comment')
                            $("#text-user_comment").val(val);
                    });
                    reorder_tags_inputs();
                    // Update the info with the loaded filter
                    $('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + $("#hidden-id_name").val());

                },
                "json"
            );
        }
    }

    $("#submit-load_filter").click(function () {
        // If selected 'none' flush filter
        if ( $("#filter_id").val() == 0 ) {
            $("#hidden-id_name").val('');
            $("#id_group").val(0);
            $("#event_type").val('');
            $("#severity").val(-1);
            $("#status").val(3);
            $("#text-search").val('');
            $('input:hidden[name=id_agent]').val("");
            $('input:hidden[name=module_search_hidden]').val();
            $("#pagination").val(25);
            $("#text-event_view_hr").val(8);
            $("#id_user_ack").val(0);
            $("#group_rep").val(1);
            $("#tag").val('');
            $("#filter_only_alert").val(-1);
            $("#row_name").css('visibility', 'hidden');
            $("#submit-update_filter").css('visibility', 'hidden');
            $("#id_group").val(0);
            $("#text-date_from").val('');
            $("#text-date_to").val('');
            $("#pagination").val(20);
            $("#update_from_filter_table").val(1);
            $("#text_id_agent").val("");
            $("#text-source").val('');
            $("#text-id_extra").val('');
            $("#text-user_comment").val('');
            
            clear_tags_inputs();
            
            // Update the view of filter load with no loaded filters message
            $('#filter_loaded_span').html($('#not_filter_loaded_text').html());

            // Update the view with the loaded filter
            $('#submit-update').trigger('click');
        }
        // If filter selected then load filter
        else {
            $('#row_name').css('visibility', '');
            $("#submit-update_filter").css('visibility', '');
            jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                {"page" : "operation/events/events_list",
                "get_filter_values" : 1,
                "id" : $('#filter_id').val()
                },
                function (data) {
                    jQuery.each (data, function (i, val) {
                        if (i == 'id_name')
                            $("#hidden-id_name").val(val);
                        if (i == 'id_group')
                            $("#id_group").val(val);
                        if (i == 'event_type')
                            $("#event_type").val(val);
                        if (i == 'severity')
                            $("#severity").val(val);
                        if (i == 'status')
                            $("#status").val(val);
                        if (i == 'search')
                            $("#text-search").val(val);
                        if (i == 'text_agent')
                            $("#text_id_agent").val(val);
                        if (i == 'id_agent')
                            $('input:hidden[name=id_agent]').val(val);
                        if (i == 'id_agent_module')
                            $('input:hidden[name=module_search_hidden]').val(val);
                        if (i == 'pagination')
                            $("#pagination").val(val);
                        if (i == 'event_view_hr')
                            $("#text-event_view_hr").val(val);
                        if (i == 'id_user_ack')
                            $("#id_user_ack").val(val);
                        if (i == 'group_rep')
                            $("#group_rep").val(val);
                        if (i == 'tag_with')
                            $("#hidden-tag_with").val(val);
                        if (i == 'tag_without')
                            $("#hidden-tag_without").val(val);
                        if (i == 'filter_only_alert')
                            $("#filter_only_alert").val(val);
                        if (i == 'id_group_filter')
                            $("#id_group_filter").val(val);
                        if (i == 'date_from'){
                            if((val == '0000-00-00') || (val == null)) {
                                $("#text-date_from").val('');
                            } else {
                                $("#text-date_from").val(val.replace(/\-/g,"/"));
                            }
                        }
                        if (i == 'date_to'){
                            if((val == '0000-00-00') || (val == null)) {
                                $("#text-date_to").val('');
                            } else {
                                $("#text-date_to").val(val.replace(/\-/g,"/"));
                            }
                        }
                        if (i == 'source')
                            $("#text-source").val(val);
                        if (i == 'id_extra')
                            $("#text-id_extra").val(val);
                        if (i == 'user_comment')
                            $("#text-user_comment").val(val);
                    });
                    reorder_tags_inputs();
                    // Update the info with the loaded filter
                    $('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + $("#hidden-id_name").val());
                    
                    // Update the view with the loaded filter
                    $('#submit-update').trigger('click');
                },
                "json"
            );
        }
        
        // Close dialog
        $('.ui-dialog-titlebar-close').trigger('click');
    });
    
    // Filter save mode selector
    $("[name='filter_mode']").click(function() {
        if ($(this).val() == 'new') {
            $('#save_filter_row1').show();
            $('#save_filter_row2').show();
            $('#update_filter_row1').hide();
        }
        else {
            $('#save_filter_row1').hide();
            $('#save_filter_row2').hide();
            $('#update_filter_row1').show();
        }
    });
    
    // This saves an event filter
    $("#submit-save_filter").click(function () {
        // If the filter name is blank show error
        if ($('#text-id_name').val() == '') {
            $('#show_filter_error').html("<h3 class='error'><?php echo __('Filter name cannot be left blank'); ?></h3>");
            
            // Close dialog
            $('.ui-dialog-titlebar-close').trigger('click');
            return false;
        }
        
        var id_filter_save;
        
        jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            {
                "page" : "operation/events/events_list",
                "save_event_filter" : 1,
                "id_name" : $("#text-id_name").val(),
                "id_group" : $("select#id_group").val(),
                "event_type" : $("#event_type").val(),
                "severity" : $("#severity").val(),
                "status" : $("#status").val(),
                "search" : $("#text-search").val(),
                "text_agent" : $("#text_id_agent").val(),
                "id_agent" : $('input:hidden[name=id_agent]').val(),
                "id_agent_module" : $('input:hidden[name=module_search_hidden]').val(),
                "pagination" : $("#pagination").val(),
                "event_view_hr" : $("#text-event_view_hr").val(),
                "id_user_ack" : $("#id_user_ack").val(),
                "group_rep" : $("#group_rep").val(),
                "tag_with": Base64.decode($("#hidden-tag_with").val()),
                "tag_without": Base64.decode($("#hidden-tag_without").val()),
                "filter_only_alert" : $("#filter_only_alert").val(),
                "id_group_filter": $("#id_group_filter").val(),
                "date_from": $("#text-date_from").val(),
                "date_to": $("#text-date_to").val(),
                "source": $("#text-source").val(),
                "id_extra": $("#text-id_extra").val(),
                "user_comment": $("#text-user_comment").val()
            },
            function (data) {
                $(".info_box").hide();
                if (data == 'error') {
                    $(".info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "error_create_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
                else  if (data == 'duplicate') {
                    $(".info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "duplicate_create_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
                else {
                    id_filter_save = data;
                    
                    $(".info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "success_create_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
            });
        
        // First remove all options of filters select
        $('#filter_id').find('option').remove().end();
        // Add 'none' option the first
        $('#filter_id').append ($('<option></option>').html ( <?php echo "'".__('none')."'"; ?> ).attr ("value", 0));    
        // Reload filters select
        jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            {
                "page" : "operation/events/events_list",
                "get_event_filters" : 1
            },
            function (data) {
                jQuery.each (data, function (i, val) {
                    s = js_html_entity_decode(val);
                    
                    if (i == id_filter_save) {
                        $('#filter_id').append ($('<option selected="selected"></option>').html (s).attr ("value", i));
                    }
                    else {
                        $('#filter_id').append ($('<option></option>').html (s).attr ("value", i));      
                    }
                });
            },
            "json"
            );
        $("#submit-update_filter").css('visibility', '');
        
        // Close dialog
        $('.ui-dialog-titlebar-close').trigger('click');
        
        // Update the info with the loaded filter
        $("#hidden-id_name").val($('#text-id_name').val());
        $('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + $('#text-id_name').val());
        
        return false;
    });
    
    // This updates an event filter
    $("#submit-update_filter").click(function () {
        var id_filter_update =  $("#overwrite_filter").val();
        var name_filter_update = $("#overwrite_filter option[value='"+id_filter_update+"']").text();
        
        jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            {"page" : "operation/events/events_list",
            "update_event_filter" : 1,
            "id" : $("#overwrite_filter").val(),
            "id_group" : $("select#id_group").val(),
            "event_type" : $("#event_type").val(),
            "severity" : $("#severity").val(),
            "status" : $("#status").val(),
            "search" : $("#text-search").val(),
            "text_agent" : $("#text_id_agent").val(),
            "id_agent" : $('input:hidden[name=id_agent]').val(),
            "id_agent_module" : $('input:hidden[name=module_search_hidden]').val(),
            "pagination" : $("#pagination").val(),
            "event_view_hr" : $("#text-event_view_hr").val(),
            "id_user_ack" : $("#id_user_ack").val(),
            "group_rep" : $("#group_rep").val(),
            "tag_with" : Base64.decode($("#hidden-tag_with").val()),
            "tag_without" : Base64.decode($("#hidden-tag_without").val()),
            "filter_only_alert" : $("#filter_only_alert").val(),
            "id_group_filter": $("#id_group_filter").val(),
            "date_from": $("#text-date_from").val(),
            "date_to": $("#text-date_to").val(),
            "source": $("#text-source").val(),
            "id_extra": $("#text-id_extra").val(),
            "user_comment": $("#text-user_comment").val()
            },
            function (data) {
                $(".info_box").hide();
                if (data == 'ok') {
                    $(".info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "success_update_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
                else {
                    $(".info_box").filter(function(i, item) {
                        if ($(item).data('type_info_box') == "error_create_filter") {
                            return true;
                        }
                        else
                            return false;
                    }).show();
                }
            });
            
            // First remove all options of filters select
            $('#filter_id').find('option').remove().end();
            // Add 'none' option the first
            $('#filter_id').append ($('<option></option>').html ( <?php echo "'".__('none')."'"; ?> ).attr ("value", 0));    
            // Reload filters select
            jQuery.post ("<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                {"page" : "operation/events/events_list",
                    "get_event_filters" : 1
                },
                function (data) {
                    jQuery.each (data, function (i, val) {
                        s = js_html_entity_decode(val);
                        if (i == id_filter_update) {
                            $('#filter_id').append ($('<option selected="selected"></option>').html (s).attr ("value", i));
                        }
                        else {
                            $('#filter_id').append ($('<option></option>').html (s).attr ("value", i));
                        }
                    });
                },
                "json"
                );
                
            // Close dialog
            $('.ui-dialog-titlebar-close').trigger('click');
            
            // Update the info with the loaded filter
            $("#hidden-id_name").val($('#text-id_name').val());
            $('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + name_filter_update);
            return false;
    });
    
    // Change toggle arrow when it's clicked
    $("#tgl_event_control").click(function() {
        if ($("#toggle_arrow").attr("src").match(/[^\.]+down\.png/) == null) {
            var params = [];
            params.push("get_image_path=1");
            params.push("img_src=images/down.png");
            params.push("page=include/ajax/skins.ajax");
            params.push("only_src=1");
            jQuery.ajax ({
                data: params.join ("&"),
                type: 'POST',
                url: action="<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                success: function (data) {
                    $("#toggle_arrow").attr('src', data);
                }
            });
        }
        else {
            var params = [];
            params.push("get_image_path=1");
            params.push("img_src=images/go.png");
            params.push("page=include/ajax/skins.ajax");
            params.push("only_src=1");
            jQuery.ajax ({
                data: params.join ("&"),
                type: 'POST',
                url: action="<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                success: function (data) {
                    $("#toggle_arrow").attr('src', data);
                }
            });
        }
    });
    
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
    
});

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
    tags = jQuery.parseJSON(Base64.decode(tags_base64));
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
    tags = jQuery.parseJSON(Base64.decode(tags_base64));
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


</script>
