<?php
/**
 * Events sounds.
 *
 * @category   Sounds
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
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

require_once '../../include/config.php';
require_once '../../include/functions.php';
require_once '../../include/functions_db.php';
require_once '../../include/auth/mysql.php';

global $config;

// Check user.
check_login();
$config['id_user'] = $_SESSION['id_usuario'];

$event_a = check_acl($config['id_user'], 0, 'ER');
$event_w = check_acl($config['id_user'], 0, 'EW');
$event_m = check_acl($config['id_user'], 0, 'EM');
$access = ($event_a == true) ? 'ER' : (($event_w == true) ? 'EW' : (($event_m == true) ? 'EM' : 'ER'));

if (check_acl($config['id_user'], 0, 'ER') === false
    && check_acl($config['id_user'], 0, 'EW') === false
    && check_acl($config['id_user'], 0, 'EM') === false
) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access event viewer'
    );
    include 'general/noaccess.php';

    return;
}

ob_start('ui_process_page_head');
ob_start();
echo '<html>';
echo '<head>';

echo '<title>'.__('Sound Events').'</title>';
ui_require_css_file('wizard');
ui_require_css_file('discovery');
?>
<style type='text/css'>
    * {
        margin: 0;
        padding: 0;
    }

    img {
        border: 0;
    }

    ul.wizard li > label:not(.p-switch):first-of-type {
        width: inherit;
    }
    form {
        margin-top: -3px;
        margin-bottom: 10px;
    }
    table {
        margin-top: 5px;
    }

    .events_fired {
        background: white;
        padding: 20px;
        overflow: auto;
        height: 200px;
        margin-bottom: 5px;
    }

    .events_fired li {
        padding: 5px;
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
    .events_fired li div {
        margin-right: 10px;
    }

    .events_fired li div.flex0 {
        flex: 0;
    }

    .events_fired li div.flex-time {
        flex: 0 1 100px;
        text-align: end;
    }

    .events_fired li div.mess {
        width: 100%;
    }

    .forced_title.mini-criticity {
        width: 10px;
        height: 30px;
    }

    .progressbar {
        width: 100%;
        margin: 5px 0px;
    }
    .progressbar .inner {
        height: 10px;
        animation: progressbar-countdown;
        /* Placeholder, this will be updated using javascript */
        animation-duration: 40s;
        /* We stop in the end */
        animation-iteration-count: 1;
        /* Stay on pause when the animation is finished finished */
        animation-fill-mode: forwards;
        /* We start paused, we start the animation using javascript */
        animation-play-state: paused;
        /* We want a linear animation, ease-out is standard */
        animation-timing-function: linear;
    }
    @keyframes progressbar-countdown {
        0% {
            width: 100%;
            background: #82b92e;
        }
        100% {
            width: 0%;
            background: #e63c52;
        }
    }
</style>
<?php
echo '<link rel="icon" href="../../'.ui_get_favicon().'" type="image/ico" />';
if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
    echo '<link rel="stylesheet" href="../../include/styles/pandora_black.css" type="text/css" />';
} else {
    echo '<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css" />';
}

echo '</head>';
echo "<body class='sound_events'>";
echo "<h1 class='modalheaderh1'>".__('Sound console').'</h1>';

// Connection lost alert.
ui_require_css_file('register', 'include/styles/', true);
$conn_title = __('Connection with server has been lost');
$conn_text = __('Connection to the server has been lost. Please check your internet connection or contact with administrator.');
ui_require_javascript_file('connection_check');
set_js_value('absolute_homeurl', ui_get_full_url(false, false, false, false));
ui_print_message_dialog(
    $conn_title,
    $conn_text,
    'connection',
    '/images/error_1.png'
);

$inputs = [];

// Load filter.
$fields = \events_get_event_filter_select();
$inputs[] = [
    'label'     => \__('Load filter'),
    'class'     => 'flex-row',
    'arguments' => [
        'type'          => 'select',
        'fields'        => $fields,
        'name'          => 'filter_id',
        'selected'      => 0,
        'return'        => true,
        'nothing'       => \__('All new events'),
        'nothing_value' => 0,
        'class'         => 'fullwidth',
    ],
];

$times_interval = [
    10 => '10 '.__('seconds'),
    15 => '15 '.__('seconds'),
    30 => '30 '.__('seconds'),
    60 => '60 '.__('seconds'),
];

$times_sound = [
    2  => '2 '.__('seconds'),
    5  => '5 '.__('seconds'),
    10 => '10 '.__('seconds'),
    15 => '15 '.__('seconds'),
    30 => '30 '.__('seconds'),
    60 => '60 '.__('seconds'),
];

$inputs[] = [
    'class'         => 'flex-row flex-row-center',
    'direct'        => 1,
    'block_content' => [
        [
            'label'     => __('Interval'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $times_interval,
                'name'     => 'interval',
                'selected' => 10,
                'return'   => true,
            ],
        ],
        [
            'label'     => __('Time Sound'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $times_sound,
                'name'     => 'time_sound',
                'selected' => 10,
                'return'   => true,
            ],
        ],
    ],
];

$sounds = [
    'aircraftalarm.wav'                  => 'Air craft alarm',
    'air_shock_alarm.wav'                => 'Air shock alarm',
    'alien_alarm.wav'                    => 'Alien alarm',
    'alien_beacon.wav'                   => 'Alien beacon',
    'bell_school_ringing.wav'            => 'Bell school ringing',
    'Door_Alarm.wav'                     => 'Door alarm',
    'EAS_beep.wav'                       => 'EAS beep',
    'Firewarner.wav'                     => 'Fire warner',
    'HardPCMAlarm.wav'                   => 'Hard PCM Alarm',
    'negativebeep.wav'                   => 'Negative beep',
    'Star_Trek_emergency_simulation.wav' => 'StarTrek emergency simulation',
];

$inputs[] = [
    'label'     => \__('Sounds'),
    'class'     => 'flex-row',
    'arguments' => [
        'type'     => 'select',
        'fields'   => $sounds,
        'name'     => 'sound_id',
        'selected' => 'Star_Trek_emergency_simulation.wav',
        'return'   => true,
        'class'    => 'fullwidth',
    ],
];

// Print form.
HTML::printForm(
    [
        'form'   => [
            'action' => '',
            'method' => 'POST',
        ],
        'inputs' => $inputs,
    ],
    false,
    true
);

$result = '<div><ul class="events_fired">';
$result .= '<li class="events_fired_li_empty">';
$result .= ui_print_info_message(__('Events not found'), '', true);
$result .= '</li>';
$result .= '</ul></div>';

$result .= '<div id="progressbar_time"></div>';

echo $result;

$table = new StdClass;
$table->width = '100%';
$table->class = 'sound_div_background text_center';

$table->data[0][0] = '<a href="javascript: toggleButton();">';
$table->data[0][0] .= html_print_image(
    'images/play.button.png',
    true,
    ['id' => 'button']
);
$table->data[0][0] .= '</a>';

$table->data[0][1] = '<a href="javascript: ok();">';
$table->data[0][1] .= html_print_image(
    'images/ok.button.png',
    true,
    ['style' => 'margin-left: 15px;']
);
$table->data[0][1] .= '</a>';

$table->data[0][2] = '<a href="javascript: test_sound_button();">';
$table->data[0][2] .= html_print_image(
    'images/icono_test.png',
    true,
    [
        'id'    => 'button_try',
        'style' => 'margin-left: 15px;',
    ]
);
$table->data[0][2] .= '</a>';

$table->data[0][3] = html_print_image(
    'images/tick_sound_events.png',
    true,
    [
        'id'    => 'button_status',
        'style' => 'margin-left: 15px;',
    ]
);

html_print_table($table);
?>

<script type="text/javascript">
var control = false;

var running = false;
var id_row = 0;
var button_play_status = "play";
var test_sound = false;

function test_sound_button() {
    if (!test_sound) {
        $("#button_try").attr('src', '../../images/icono_test.png');
        $('body').append("<audio src='../../include/sounds/Star_Trek_emergency_simulation.wav' autoplay='true' hidden='true' loop='false'>");
        test_sound = true;
    } else {
        $("#button_try").attr('src', '../../images/icono_test.png');
        $('body audio').remove();
        test_sound = false;
    }
}

function toggleButton() {
    if (button_play_status == 'pause') {
        $("#button").attr('src', '../../images/play.button.png');
        stopSound();
        control.paused();

        button_play_status = 'play';
    }
    else {
        $("#button").attr('src', '../../images/pause.button.png');
        forgetPreviousEvents();
        startSound();

        button_play_status = 'pause';
    }
}

function ok() {
    $('#button_status').attr('src','../../images/tick_sound_events.png');
    $('audio').remove();
    $('.events_fired').empty();
}

function stopSound() {
    $('audio').remove();
    $('body').css('background', '#494949');
    running = false;
}

function startSound() {
    running = true;
}

function forgetPreviousEvents() {
    if(control === false) {
        running = true;
        control = progressTimeBar(
            "progressbar_time",
            $("#interval").val(),
            'infinite',
            function() {
                check_event();
            }
        );
    } else {
        control.start();
    }
}

function check_event() {
    if (running) {
        var sound = '../../include/sounds/' + $('#sound_id').val();
        jQuery.post ("../../ajax.php",
            {
                "page" : "include/ajax/events",
                "get_events_fired": 1,
                "filter_id": $('#filter_id').val(),
                "interval": $('#interval').val(),
                "time_sound": $('#time_sound').val(),
            },
            function (data) {
                if(data != false) {
                    $('.events_fired_li_empty').remove();

                    $('#button_status')
                        .attr(
                            'src','../../images/sound_events_console_alert.gif'
                        );
                    $('audio').remove();

                    $('body')
                        .append(
                            "<audio id='audio-boom' src='" + sound + "' autoplay='true' hidden='true' loop='true' >"
                        );

                    data.forEach(function (element) {
                        var li = document.createElement('li');
                        li.insertAdjacentHTML(
                            'beforeend',
                            '<div class="flex0">'+element.priority+'</div>'
                        );
                        li.insertAdjacentHTML(
                            'beforeend',
                            '<div class="flex0">'+element.type+'</div>'
                        );
                        li.insertAdjacentHTML(
                            'beforeend',
                            '<div class="mess">'+element.message+'</div>'
                        );
                        li.insertAdjacentHTML(
                            'beforeend',
                            '<div class="flex-time">'+element.timestamp+'</div>'
                        );
                        $('.events_fired').append(li);
                    });

                    function removeAudio() {
                        $('audio').remove();
                    }

                    // -100 delay sound.
                    setTimeout(
                        removeAudio,
                        (parseInt($('#time_sound').val())  * 1000) - 100
                    );
                }
            },
            "json"
        );
    }
}

</script>

<?php
echo '</body>';

while (ob_get_length() > 0) {
    ob_end_flush();
}

echo '</html>';

