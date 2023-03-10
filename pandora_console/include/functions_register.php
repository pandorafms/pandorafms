<?php
/**
 * Update Manager registration functions.
 *
 * @category   Library
 * @package    Pandora FMS
 * @subpackage Register
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

// Begin.


/**
 * Parses responses from configuration wizard.
 *
 * @return void
 */
function config_wiz_process()
{
    global $config;
    $email = get_parameter('email', false);
    $timezone = get_parameter('timezone', false);
    $language = get_parameter('language', false);

    if ($email !== false) {
        config_update_value('language', $language);
    }

    if ($timezone !== false) {
        config_update_value('timezone', $timezone);
    }

    if ($email !== false) {
        db_process_sql_update(
            'tusuario',
            ['email' => $email],
            ['id_user' => $config['id_user']]
        );
    }

    // Update the alert action Mail to XXX/Administrator
    // if it is set to default.
    $mail_check = 'yourmail@domain.es';
    $mail_alert = alerts_get_alert_action_field1(1);
    if ($mail_check === $mail_alert && $email !== false) {
        alerts_update_alert_action(
            1,
            [
                'field1'          => $email,
                'field1_recovery' => $email,
            ]
        );
    }

    config_update_value('initial_wizard', 1);
}


/**
 * Generates base code to print main configuration modal.
 *
 * Asks for timezone, mail.
 *
 * @param boolean $return   Print output or not.
 * @param boolean $launch   Process JS modal.
 * @param string  $callback Call to JS function at end.
 *
 * @return string HTML.
 */
function config_wiz_modal(
    $return=false,
    $launch=true,
    $callback=false
) {
    global $config;

    $email = db_get_value('email', 'tusuario', 'id_user', $config['id_user']);
    // Avoid to show default email.
    if ($email == 'admin@example.com') {
        $email = '';
    }

    $output = '';

    // Prints first step pandora registration.
    $output .= '<div id="configuration_wizard" title="'.__('%s configuration wizard', get_product_name()).'" class="invisible">';

    $output .= '<div id="help_dialog">';
    $output .= __('Please fill the following information in order to configure your %s instance successfully', get_product_name()).'.';
    $output .= '</div>';

    $output .= '<div  >';
    $table = new StdClass();
    $table->class = 'databox filters';
    $table->width = '100%';
    $table->data = [];
    $table->size = [];
    $table->size[0] = '40%';
    $table->style[0] = 'font-weight:bold';
    $table->size[1] = '60%';
    $table->border = '5px solid';

    $table->data[0][0] = __('Language code');
    $table->data[0][1] = html_print_select_from_sql(
        'SELECT id_language, name FROM tlanguage',
        'language',
        $config['language'],
        '',
        '',
        '',
        true
    );

    $zone_name = [
        'Africa'     => __('Africa'),
        'America'    => __('America'),
        'Antarctica' => __('Antarctica'),
        'Arctic'     => __('Arctic'),
        'Asia'       => __('Asia'),
        'Atlantic'   => __('Atlantic'),
        'Australia'  => __('Australia'),
        'Europe'     => __('Europe'),
        'Indian'     => __('Indian'),
        'Pacific'    => __('Pacific'),
        'UTC'        => __('UTC'),
    ];

    if ($zone_selected == '') {
        if ($config['timezone'] != '') {
            $zone_array = explode('/', $config['timezone']);
            $zone_selected = $zone_array[0];
        } else {
            $zone_selected = 'Europe';
        }
    }

    $timezones = timezone_identifiers_list();
    foreach ($timezones as $timezone) {
        if (strpos($timezone, $zone_selected) !== false) {
            $timezone_country = preg_replace('/^.*\//', '', $timezone);
            $timezone_n[$timezone] = $timezone_country;
        }
    }

    $table->data[2][0] = __('Timezone setup').' '.ui_print_help_tip(
        __('Must have the same time zone as the system or database to avoid mismatches of time.'),
        true
    );
    $table->data[2][1] = html_print_select($zone_name, 'zone', $zone_selected, 'show_timezone()', '', '', true);
    $table->data[2][1] .= '&nbsp;&nbsp;'.html_print_select($timezone_n, 'timezone', $config['timezone'], '', '', '', true);

    $table->data[4][0] = __('E-mail for receiving alerts');
    $table->data[4][1] = html_print_input_text('email', $email, '', 50, 255, true);

    $output .= html_print_table($table, true);
    $output .= '</div>';

    $output .= '<div class="left">';
    $output .= html_print_submit_button(
        __('Cancel'),
        'cancel',
        false,
        ['icon' => 'cancel'],
        true
    );
    $output .= '</div>';
    $output .= '<div class="right">';
    $output .= html_print_submit_button(
        __('Continue'),
        'register-next',
        false,
        ['icon' => 'next'],
        true
    );
    $output .= '</div>';
    $output .= '<div id="all-required" class="all_required">';
    $output .= __('All fields required');
    $output .= '</div>';
    $output .= '</div>';

    $output .= '</div>';

    // Verification modal.
    $output .= '<div id="wiz_ensure_cancel" title="Confirmation Required" class="invisible">';
    $output .= '<div class="font_12_20">';
    $output .= __('Are you sure you don\'t want to configure a base email?');
    $output .= '<p>';
    $output .= __('You could change this options later in "alert actions" and setting your account.');
    $output .= '</p>';
    $output .= '</div>';
    $output .= '</div>';

    ob_start();
    ?>

<script type="text/javascript">
function show_timezone () {
    zone = $("#zone").val();

    $.ajax({
        type: "POST",
        url: "ajax.php",
        data: "page=godmode/setup/setup&select_timezone=1&zone=" + zone,
        dataType: "json",
        success: function(data) {
            $("#timezone").empty();
            jQuery.each (data, function (id, value) {
                timezone = value;
                var timezone_country = timezone.replace (/^.*\//g, "");
                $("select[name='timezone']")
                .append(
                    $("<option>")
                    .val(timezone)
                    .html(timezone_country)
                );
            });
        }
    });
}

$("#language").click(function () {
    var change_language = $("#language").val();

    if (change_language === default_language_displayed) return;
    jQuery.post (
        "ajax.php",
        {
            "page": "general/register",
            "change_language": change_language
        },
        function (data) {}
    ).done(function () {
        location.reload();
    });
});

function show_configuration_wizard() {
    $("#configuration_wizard").dialog({
        resizable: true,
        draggable: true,
        modal: true,
        width: 630,
        overlay: {
                opacity: 0.5,
                background: "black"
            },
        closeOnEscape: false,
        open: function(event, ui) { 
            $(".ui-dialog-titlebar-close").hide();
            if ($.ui && $.ui.dialog && $.ui.dialog.prototype._allowInteraction) {
                    var ui_dialog_interaction = $.ui.dialog.prototype._allowInteraction;
                    $.ui.dialog.prototype._allowInteraction = function(e) {
                    if ($(e.target).closest('.select2-dropdown').length) return true;
                        return ui_dialog_interaction.apply(this, arguments);
                    };
            }
        },
        _allowInteraction: function (event) {
            return !!$(event.target).is(".select2-input") || this._super(event);
        }             
    });

    default_language_displayed = $("#language").val();

    $(".ui-widget-overlay").css("background", "#000");
    $(".ui-widget-overlay").css("opacity", 0.6);
    $(".ui-draggable").css("cursor", "inherit");


    // CLICK EVENTS: Cancel and Registration
    $("#button-cancel").click (function (e) {
        e.preventDefault();
        $("#wiz_ensure_cancel").dialog({
            buttons: [
                {
                    "text": "No",
                    "class": 'submit-cancel',
                    "click" : function() {
                        $(this).dialog("close");
                    }
                },
                {
                    "text": "Yes",
                    "class": 'submit-next',
                    "click" : function() {
                        jQuery.post (
                            "ajax.php",
                            {
                                "page": "general/register",
                                "cancel_wizard": 1
                            },
                            function (data) {}
                        );
                        $(this).dialog("close");
                        $("#configuration_wizard" ).dialog('close');
                    }
                }
            ]
        });

        $("#wiz_ensure_cancel").dialog('open');
    });

    $("#button-register-next").click (function () {
        // All fields are required.
        if ($("#text-email").val() == '') {
            $("#all-required").show();
        } else {
            var timezone = $("#timezone").val();
            var language = $("#language").val();
            var email_identification = $("#text-email").val();

            jQuery.post (
                "ajax.php",
                {
                    "page": "general/register",
                    "save_required_wizard": 1,
                    "email": email_identification,
                    "language": language,
                    "timezone": timezone
                },
                function (data) {
                    <?php
                    if (isset($callback) && $callback != '') {
                        echo $callback;
                    }
                    ?>
                }
            );

            $("#configuration_wizard").dialog('close');
        }
    });
}

    <?php
    if ($launch === true) {
        ?>
        $(document).ready (function () {
            show_configuration_wizard();
        });
        <?php
    }
    ?>

</script>

    <?php
    // Add js.
    $output .= ob_get_clean();

    if ($return === false) {
        echo $output;
    }

    return $output;

}
