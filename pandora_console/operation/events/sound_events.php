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


global $config;
require_once '../../include/config.php';
require_once '../../include/functions.php';
require_once '../../include/functions_db.php';
require_once '../../include/functions_events.php';
require_once '../../include/functions_ui.php';
require_once '../../include/auth/mysql.php';
require_once $config['homedir'].'/include/class/HTML.class.php';

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

if (is_metaconsole() === true) {
    $redirect_metaconsole = '../../';
} else {
    $redirect_metaconsole = '';
}

echo '<html>';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
echo '<title>'.__('Acoustic console').'</title>';
echo '<link rel="stylesheet" href="../../include/styles/pandora_minimal.css" type="text/css" />';
echo '<link rel="stylesheet" href="../../include/styles/js/jquery-ui.min.css" type="text/css" />';
echo '<link rel="stylesheet" href="../../include/styles/js/jquery-ui_custom.css" type="text/css" />';
echo '<link rel="stylesheet" href="../../include/styles/select2.min.css" type="text/css" />';
echo '<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css" />';

echo ui_require_css_file('wizard', 'include/styles/', true);
echo ui_require_css_file('discovery', 'include/styles/', true);
echo ui_require_css_file('sound_events', 'include/styles/', true);
echo ui_require_css_file('events', 'include/styles/', true);

echo '<script type="text/javascript" src="../../include/javascript/jquery.current.js"></script>';
echo '<script type="text/javascript" src="../../include/javascript/jquery.pandora.js"></script>';
echo '<script type="text/javascript" src="../../include/javascript/jquery-ui.min.js"></script>';
echo '<script type="text/javascript" src="../../include/javascript/select2.min.js"></script>';
echo '<script type="text/javascript" src="../../include/javascript/pandora.js"></script>';
echo '<script type="text/javascript" src="../../include/javascript/pandora_ui.js"></script>';


echo '<link rel="icon" href="../../'.ui_get_favicon().'" type="image/ico" />';
if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
    echo '<link rel="stylesheet" href="../../include/styles/pandora_black.css" type="text/css" />';
} else {
    echo '<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css" />';
}

echo '</head>';
echo '<body style="overflow: hidden;">';
$output = '<div id="tabs-sound-modal">';
    // Header tabs.
    $output .= '<ul class="tabs-sound-modal-options">';
        $output .= '<li>';
        $output .= '<a href="#tabs-sound-modal-1">';
        $output .= html_print_image(
            'images/gear.png',
            true,
            [
                'title' => __('Options'),
                'class' => 'invert_filter',
            ]
        );
        $output .= '</a>';
        $output .= '</li>';
        $output .= '<li>';
        $output .= '<a href="#tabs-sound-modal-2">';
        $output .= html_print_image(
            'images/list.png',
            true,
            [
                'title' => __('Events list'),
                'class' => 'invert_filter',
                'div_sty'
            ]
        );
        $output .= '</a>';
        $output .= '</li>';
        $output .= '</ul>';

        // Content tabs.
        $output .= '<div id="tabs-sound-modal-1" style="height: 350px;">';
        $output .= '<h3 class="title-discovered-alerts">';
        $output .= __('Console configuration').ui_print_help_tip(__('Warning: Minimizing this window will cause the Acoustic Console to not work as expected'), true);
        $output .= '</h3>';
        $inputs = [];

        // Load filter.
        $fields = \events_get_event_filter_select();
        $inputs[] = [
            'label'     => \__('Set condition'),
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
            'class'         => 'interval-sounds',
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
                    'label'     => __('Sound duration'),
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

        $eventsounds = db_get_all_rows_sql('SELECT * FROM tevent_sound WHERE active = 1');
        if ($eventsounds !== false) {
            foreach ($eventsounds as $key => $row) {
                $sounds[$row['sound']] = $row['name'];
            }
        }

        $inputs[] = [
            'class'         => 'test-sounds',
            'direct'        => 1,
            'block_content' => [
                [
                    'label'     => \__('Sound melody'),
                    'arguments' => [
                        'type'     => 'select',
                        'fields'   => $sounds,
                        'name'     => 'sound_id',
                        'selected' => 'Star_Trek_emergency_simulation.wav',
                        'return'   => true,
                        'class'    => 'fullwidth',
                    ],
                ],
                [
                    'arguments' => [
                        'type'       => 'button',
                        'name'       => 'melody_sound',
                        'label'      => __('Test sound'),
                        'attributes' => ['icon' => 'sound'],
                        'return'     => true,
                    ],
                ],
            ],
        ];

        // Print form.
        $output .= HTML::printForm(
            [
                'form'   => [
                    'action' => '',
                    'method' => 'POST',
                ],
                'inputs' => $inputs,
            ],
            true,
            false
        );
        $output .= '</div>';

        $output .= '<div id="tabs-sound-modal-2" style="height: 350px;">';
        $output .= '<h3 class="title-discovered-alerts">';
        $output .= __('Discovered alerts');
        $output .= '</h3>';
        $output .= '<div class="empty-discovered-alerts">';
        $output .= html_print_image(
            'images/no-alerts-discovered.png',
            true,
            [
                'title' => __('No alerts discovered'),
                'class' => 'invert_filter',
            ]
        );
        $output .= '<span class="text-discovered-alerts">';
        $output .= __('Congrats! there’s nothing to show');
        $output .= '</span>';
        $output .= '</div>';
        $output .= '<div class="elements-discovered-alerts" style="max-height:315px !important;"><ul></ul></div>';
        $output .= html_print_input_hidden(
            'ajax_file_sound_console',
            ui_get_full_url('ajax.php', false, false, false),
            true
        );
        $output .= html_print_input_hidden(
            'meta',
            is_metaconsole(),
            true
        );
        $output .= '<div id="sound_event_details_window"></div>';
        $output .= '<div id="sound_event_response_window"></div>';
        $output .= '</div>';
        $output .= '</div>';

        $output .= '<div class="actions-sound-modal">';
        $output .= '<div id="progressbar_time"></div>';
        $output .= '<div class="buttons-sound-modal mrgn_top_10px">';
        $output .= html_print_button(
            __('Start'),
            'start-search',
            false,
            '',
            [
                'icon'  => 'play',
                'class' => 'mrgn_lft_20px',
            ],
            true
        );
        // $output .= html_print_submit_button(
        // [
        // 'label'      => __('Start'),
        // 'type'       => 'button',
        // 'name'       => 'start-search',
        // 'attributes' => [ 'class' => 'play' ],
        // 'return'     => true,
        // ],
        // 'div',
        // true
        // );
        $output .= '<div class="container-button-alert mrgn_right_20px">';
        $output .= html_print_input(
            [
                'type'       => 'button',
                'name'       => 'no-alerts',
                'label'      => __('No alerts'),
                'attributes' => ['class' => 'secondary alerts'],
                'return'     => true,
            ],
            'div',
            true
        );
        $output .= '</div>';

        $output .= html_print_input(
            [
                'type'   => 'hidden',
                'name'   => 'mode_alert',
                'value'  => 0,
                'return' => true,
            ],
            'div',
            true
        );
        $output .= '</div>';
        $output .= '</div>';
        $output .= html_print_div(['id' => 'forced_title_layer', 'class' => 'forced_title_layer', 'hidden' => true]);
        echo $output;
        ?>

<script type="text/javascript">

function test_sound_button(test_sound, urlSound) {
    if (test_sound === true) {
        $("#button-melody_sound").addClass("blink-image");
        add_audio(urlSound);
    } else {
        $("#button-melody_sound").removeClass("blink-image");
        remove_audio();
    }
}

function action_events_sound(mode) {
    if (mode === true) {
        // Enable tabs.
        $("#tabs-sound-modal").tabs("option", "disabled", [0]);
        // Active tabs.
        $("#tabs-sound-modal").tabs("option", "active", 1);
        // Change mode.
        $("#hidden-mode_alert").val(1);
        // Change img button.
        $("#button-start-search").children("div").removeClass("play")
        $("#button-start-search").children("div").addClass("stop");

        // Change value button.
        $("#button-start-search").val("Stop");
        $("#button-start-search > span").text("Stop");
        // Add Progress bar.
        listen_event_sound();
    } else {
        // Enable tabs.
        $("#tabs-sound-modal").tabs("option", "disabled", [1]);
        // Active tabs.
        $("#tabs-sound-modal").tabs("option", "active", 0);
        // Change mode.
        $("#hidden-mode_alert").val(0);
        // Change img button.
        $("#button-start-search").children("div").removeClass("stop")
        $("#button-start-search").children("div").addClass("play")
        // Change value button.
        $("#button-start-search").val("Start");
        $("#button-start-search > span").text("Start");
        // Remove progress bar.
        $("#progressbar_time").empty();
        // Remove audio.
        remove_audio();
        // Clean events.
        $("#tabs-sound-modal .elements-discovered-alerts ul").empty();
        $("#tabs-sound-modal .empty-discovered-alerts").removeClass(
        "invisible_important"
        );
        // Change img button.
        $("#button-no-alerts")
        .removeClass("silence-alerts")
        .addClass("alerts");
        // Change value button.
        $("#button-no-alerts").val("No alert");
        $("#button-no-alerts > span").text("No alert");

        // Background button.
        $(".container-button-alert").removeClass("fired");
    }
}

function add_audio(urlSound) {
    var sound = urlSound;
    $(".actions-sound-modal").append(
        "<audio id='id_sound_event' src='" +
        sound +
        "' autoplay='true' hidden='true' loop='false'>"
    );
}

function remove_audio() {
    $(".actions-sound-modal audio").remove();
    //buttonBlink();
}

function listen_event_sound() {
    progressTimeBar(
        "progressbar_time",
        $("#interval").val(),
        "infinite",
        function() {
        // Search events.
        check_event_sound();
        }
    );
}

function check_event_sound() {
    $(".elements-discovered-alerts ul li").each(function() {
        let element_time = $(this)
        .children(".li-hidden")
        .val();
        let obj_time = new Date(element_time);
        let current_dt = new Date();
        let timestamp = current_dt.getTime() - obj_time.getTime();
        timestamp = timestamp / 1000;
        if (timestamp <= 60) {
            timestamp = Math.round(timestamp) + " seconds";
        } else if (timestamp <= 3600) {
            let minute = Math.floor((timestamp / 60) % 60);
            minute = minute < 10 ? "0" + minute : minute;
            let second = Math.floor(timestamp % 60);
            second = second < 10 ? "0" + second : second;
            timestamp = minute + " minutes " + second + " seconds";
        } else {
            let hour = Math.floor(timestamp / 3600);
            hour = hour < 10 ? "0" + hour : hour;
            let minute = Math.floor((timestamp / 60) % 60);
            minute = minute < 10 ? "0" + minute : minute;
            let second = Math.round(timestamp % 60);
            second = second < 10 ? "0" + second : second;
            timestamp = hour + " hours " + minute + " minutes " + second + " seconds";
        }
        $(this)
            .children(".li-time")
            .children("span")
            .html(timestamp);
    });
    jQuery.post(
        $('#hidden-ajax_file_sound_console').val(),
        {
        page: "include/ajax/events",
        get_events_fired: 1,
        filter_id: $("#tabs-sound-modal #filter_id").val(),
        interval: $("#tabs-sound-modal #interval").val(),
        time_sound: $("#tabs-sound-modal #time_sound").val()
        },
        function(data) {
            if (data != false) {
                // Hide empty.
                $("#tabs-sound-modal .empty-discovered-alerts").addClass(
                "invisible_important"
                );
                // Change img button.
                $("#button-no-alerts")
                .removeClass("alerts")
                .addClass("silence-alerts");
                // Change value button.
                $("#button-no-alerts").val("Silence alarm");
                $("#button-no-alerts > span").text("Silence alarm");

                // Background button.
                $(".container-button-alert").addClass("fired");
                
                // Remove audio.
                remove_audio();
                var urlSound = '../../include/sounds/'+$('#sound_id :selected').val();
                console.log(urlSound)
                // Apend audio.
                add_audio(urlSound);

                // Add elements.
                data.forEach(function(element) {
                    console.log(element);
                var li = document.createElement("li");
                var b64 = btoa(JSON.stringify(element));
                li.insertAdjacentHTML(
                    "beforeend",
                    '<div class="li-priority">' + element.priority + "</div>"
                );
                li.insertAdjacentHTML(
                    "beforeend",
                    '<div class="li-type">' + element.type + "</div>"
                );
                li.insertAdjacentHTML(
                    "beforeend",
                    `<div class="li-title"><a href="javascript:" onclick="open_window_dialog('`+b64+`')">${element.message}</a></div>`
                );
                li.insertAdjacentHTML(
                    "beforeend",
                    '<div class="li-time">' + element.timestamp + "</div>"
                );
                li.insertAdjacentHTML(
                    "beforeend",
                    '<input type="hidden" value="' + element.event_timestamp + '" class="li-hidden"/>'
                );
                $("#tabs-sound-modal .elements-discovered-alerts ul").prepend(li);
                });

                // -100 delay sound.
                setTimeout(
                remove_audio,
                parseInt($("#tabs-sound-modal #time_sound").val()) * 1000 - 100
                );
            }
        },
        "json"
    );
}

function open_window_dialog(data) {
    window.open(window.location.origin+'/pandora_console/index.php?sec=eventos&sec2=operation/events/events&show_event_dialog='+data);
    //show_event_dialog(data);
}

$(document).ready(function(){

    $("#tabs-sound-modal").tabs({
        disabled: [1]
    });

    // Test sound.
    $("#button-melody_sound").click(function() {
        var sound = false;
        if ($("#id_sound_event").length == 0) {
            sound = true;
        }
        var urlSound = '../../include/sounds/'+$('#sound_id :selected').val();
        urlSound
        test_sound_button(sound, urlSound);
    });

    // Play Stop.
    $("#button-start-search").click(function() {
        var mode = $("#hidden-mode_alert").val();
        var action = false;
        if (mode == 0) {
            action = true;
        }
        if ($("#button-start-search").hasClass("play")){
            $("#modal-sound").css({
                height: "500px"
            });
            $("#modal-sound").parent().css({
                height: "800px"
            });
        } else {
            $("#modal-sound").css({
                height: "450px"
            });
        }
        action_events_sound(action);
    });

    // Silence Alert.
    $("#button-no-alerts").click(function() {
        if ($("#button-no-alerts").hasClass("silence-alerts") === true) {
        // Remove audio.
        remove_audio();

        // Clean events.
        $("#tabs-sound-modal .elements-discovered-alerts ul").empty();
        $("#tabs-sound-modal .empty-discovered-alerts").removeClass(
            "invisible_important"
        );

        // Clean progress.
        $("#progressbar_time").empty();

        // Change img button.
        $("#button-no-alerts")
            .removeClass("silence-alerts")
            .addClass("alerts");
        // Change value button.
        $("#button-no-alerts").val("No alert");
        $("#button-no-alerts > span").text("No alert");

        // Background button.
        $(".container-button-alert").removeClass("fired");

        // New progress.
        listen_event_sound();
        }
    });
});

</script>

<?php
echo '</body>';

while (ob_get_length() > 0) {
    ob_end_flush();
}

echo '</html>';

