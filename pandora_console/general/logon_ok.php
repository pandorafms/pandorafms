<?php
/**
 * Extension to self monitor Pandora FMS Console
 *
 * @category   Main page
 * @package    Pandora FMS
 * @subpackage Introduction
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

 // Config functions.
require_once 'include/config.php';

// This solves problems in enterprise load.
global $config;

check_login();

require_once 'include/functions_reporting.php';
require_once 'include/functions_tactical.php';
require_once $config['homedir'].'/include/functions_graph.php';

if (tags_has_user_acl_tags()) {
    ui_print_tags_warning();
}

$user_strict = (bool) db_get_value(
    'strict_acl',
    'tusuario',
    'id_user',
    $config['id_user']
);
$all_data = tactical_status_modules_agents(
    $config['id_user'],
    $user_strict,
    'AR',
    $user_strict
);
$data = [];

$data['monitor_not_init'] = (int) $all_data['_monitors_not_init_'];
$data['monitor_unknown'] = (int) $all_data['_monitors_unknown_'];
$data['monitor_ok'] = (int) $all_data['_monitors_ok_'];
$data['monitor_warning'] = (int) $all_data['_monitors_warning_'];
$data['monitor_critical'] = (int) $all_data['_monitors_critical_'];
$data['monitor_not_normal'] = (int) $all_data['_monitor_not_normal_'];
$data['monitor_alerts'] = (int) $all_data['_monitors_alerts_'];
$data['monitor_alerts_fired'] = (int) $all_data['_monitors_alerts_fired_'];

$data['total_agents'] = (int) $all_data['_total_agents_'];

$data['monitor_checks'] = (int) $all_data['_monitor_checks_'];
if (!empty($all_data)) {
    if ($data['monitor_not_normal'] > 0 && $data['monitor_checks'] > 0) {
        $data['monitor_health'] = format_numeric((100 - ($data['monitor_not_normal'] / ($data['monitor_checks'] / 100))), 1);
    } else {
        $data['monitor_health'] = 100;
    }

    if ($data['monitor_not_init'] > 0 && $data['monitor_checks'] > 0) {
        $data['module_sanity'] = format_numeric((100 - ($data['monitor_not_init'] / ($data['monitor_checks'] / 100))), 1);
    } else {
        $data['module_sanity'] = 100;
    }

    if (isset($data['alerts'])) {
        if ($data['monitor_alerts_fired'] > 0 && $data['alerts'] > 0) {
            $data['alert_level'] = format_numeric((100 - ($data['monitor_alerts_fired'] / ($data['alerts'] / 100))), 1);
        } else {
            $data['alert_level'] = 100;
        }
    } else {
        $data['alert_level'] = 100;
        $data['alerts'] = 0;
    }

    $data['monitor_bad'] = ($data['monitor_critical'] + $data['monitor_warning']);

    if ($data['monitor_bad'] > 0 && $data['monitor_checks'] > 0) {
        $data['global_health'] = format_numeric((100 - ($data['monitor_bad'] / ($data['monitor_checks'] / 100))), 1);
    } else {
        $data['global_health'] = 100;
    }

    $data['server_sanity'] = format_numeric((100 - $data['module_sanity']), 1);
}

ui_require_css_file('logon');

echo '<div id="welcome_panel">';

//
// Overview Table.
//
$table = new stdClass();
$table->class = 'no-class';
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->head = [];
$table->data = [];
$table->headstyle[0] = 'text-align:center;';
$table->width = '100%';
$table->head_colspan[0] = 4;

// Indicators.
$tdata = [];
$stats = reporting_get_stats_indicators($data, 120, 10, false);
$status = '<table class="status_tactical">';
foreach ($stats as $stat) {
    $status .= '<tr><td><b>'.$stat['title'].'</b></td><td>'.$stat['graph'].'</td></tr>';
}

$status .= '</table>';
$table->data[0][0] = $status;
$table->rowclass[] = '';

$table->data[] = $tdata;

// Alerts.
$tdata = [];
$tdata[0] = reporting_get_stats_alerts($data);
$table->rowclass[] = '';
$table->data[] = $tdata;

// Modules by status.
$tdata = [];
$tdata[0] = reporting_get_stats_modules_status($data, 180, 100);
$table->rowclass[] = '';
$table->data[] = $tdata;

// Total agents and modules.
$tdata = [];
$tdata[0] = reporting_get_stats_agents_monitors($data);
$table->rowclass[] = '';
$table->data[] = $tdata;

// Users.
if (users_is_admin()) {
    $tdata = [];
    $tdata[0] = reporting_get_stats_users($data);
    $table->rowclass[] = '';
    $table->data[] = $tdata;
}

ui_toggle(
    html_print_table($table, true),
    __('%s Overview', get_product_name()),
    '',
    'overview',
    false
);
unset($table);

echo '<div id="right">';

// News.
require_once 'general/news_dialog.php';
$options = [];
$options['id_user'] = $config['id_user'];
$options['modal'] = false;
$options['limit'] = 3;
$news = get_news($options);


if (!empty($news)) {
    ui_require_css_file('news');
    // NEWS BOARD.
    if ($config['prominent_time'] == 'timestamp') {
        $comparation_suffix = '';
    } else {
        $comparation_suffix = __('ago');
    }


    $output_news = '<div id="news_board" class="new">';
    foreach ($news as $article) {
        $image = false;
        if ($article['text'] == '&amp;lt;p&#x20;style=&quot;text-align:&#x20;center;&#x20;font-size:&#x20;13px;&quot;&amp;gt;Hello,&#x20;congratulations,&#x20;if&#x20;you&apos;ve&#x20;arrived&#x20;here&#x20;you&#x20;already&#x20;have&#x20;an&#x20;operational&#x20;monitoring&#x20;console.&#x20;Remember&#x20;that&#x20;our&#x20;forums&#x20;and&#x20;online&#x20;documentation&#x20;are&#x20;available&#x20;24x7&#x20;to&#x20;get&#x20;you&#x20;out&#x20;of&#x20;any&#x20;trouble.&#x20;You&#x20;can&#x20;replace&#x20;this&#x20;message&#x20;with&#x20;a&#x20;personalized&#x20;one&#x20;at&#x20;Admin&#x20;tools&#x20;-&amp;amp;gt;&#x20;Site&#x20;news.&amp;lt;/p&amp;gt;&#x20;') {
            $image = true;
        }

        $text_bbdd = io_safe_output($article['text']);
        $text = html_entity_decode($text_bbdd);
        $output_news .= '<span class="green_title">'.$article['subject'].'</span>';
        $output_news .= '<div class="new content">';
        $output_news .= '<p>'.__('by').' <b>'.$article['author'].'</b> <i>'.ui_print_timestamp($article['timestamp'], true).'</i> '.$comparation_suffix.'</p>';
        if ($image) {
            $output_news .= '<center><img src="./images/welcome_image.png" alt="img colabora con nosotros - Support" width="191" height="207"></center>';
        }

        $output_news .= nl2br($text);
        $output_news .= '</div>';
    }

    $output_news .= '</div>';

    // News board.
    ui_toggle(
        $output_news,
        __('News board'),
        '',
        'news',
        false
    );
    // END OF NEWS BOARD.
}

// LAST ACTIVITY.
// Show last activity from this user.
$table = new stdClass();
$table->class = 'no-td-padding info_table';
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->width = '100%';
// Don't specify px.
$table->data = [];
$table->size = [];
$table->headstyle = [];
$table->size[0] = '5%';
$table->size[1] = '15%';
$table->headstyle[1] = 'min-width: 12em;';
$table->size[2] = '5%';
$table->headstyle[2] = 'min-width: 65px;';
$table->size[3] = '10%';
$table->size[4] = '25%';
$table->head = [];
$table->head[0] = __('User');
$table->head[1] = __('Action');
$table->head[2] = __('Date');
$table->head[3] = __('Source IP');
$table->head[4] = __('Comments');
$table->align[4] = 'left';
$sql = sprintf(
    'SELECT id_usuario,accion, ip_origen,descripcion,utimestamp
            FROM tsesion
            WHERE (`utimestamp` > UNIX_TIMESTAMP(NOW()) - '.SECONDS_1WEEK.") 
                AND `id_usuario` = '%s' ORDER BY `utimestamp` DESC LIMIT 10",
    $config['id_user']
);


$sessions = db_get_all_rows_sql($sql);

if ($sessions === false) {
    $sessions = [];
}

foreach ($sessions as $session) {
    $data = [];
    $session_id_usuario = $session['id_usuario'];
    $session_ip_origen = $session['ip_origen'];


    $data[0] = '<strong>'.$session_id_usuario.'</strong>';
    $data[1] = ui_print_session_action_icon($session['accion'], true).' '.$session['accion'];
    $data[2] = ui_print_help_tip(
        date($config['date_format'], $session['utimestamp']),
        true
    ).human_time_comparation($session['utimestamp'], 'tiny');
    $data[3] = $session_ip_origen;
    $description = str_replace([',', ', '], ', ', $session['descripcion']);
    if (strlen($description) > 100) {
        $data[4] = '<div >'.io_safe_output(substr($description, 0, 150).'...').'</div>';
    } else {
        $data[4] = '<div >'.io_safe_output($description).'</div>';
    }

    array_push($table->data, $data);
}

$activity .= html_print_table($table, true);
unset($table);

ui_toggle(
    $activity,
    __('Latest activity'),
    '',
    'activity',
    false,
    false,
    '',
    'white-box-content padded'
);
// END OF LAST ACTIVIYY.
// Close right panel.
echo '</div>';

// Close welcome panel.
echo '</div>';
