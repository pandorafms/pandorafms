<?php
/**
 * Update manager client library.
 *
 * @category   Functions Update Manager
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

/*
 *
 * Registration functions - Start.
 *
 */


/**
 * Verifies registration state.
 *
 * @return boolean Status.
 */
function update_manager_verify_registration()
{
    global $config;

    if (isset($config['pandora_uid']) === true
        && $config['pandora_uid'] != ''
        && $config['pandora_uid'] != 'OFFLINE'
    ) {
        // Verify with UpdateManager.
        return true;
    }

    return false;
}


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
    $output .= '<div id="configuration_wizard" title="'.__('%s configuration wizard', get_product_name()).'" style="display: none;">';

    $output .= '<div style="font-size: 10pt; margin: 20px;">';
    $output .= __('Please fill the following information in order to configure your %s instance successfully', get_product_name()).'.';
    $output .= '</div>';

    $output .= '<div style="">';
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

    $output .= '<div style="float: left">';
    $output .= html_print_submit_button(
        __('Cancel'),
        'cancel',
        false,
        'class="ui-widget ui-state-default ui-corner-all ui-button-text-only sub cancel submit-cancel" style="width:100px;"',
        true
    );
    $output .= '</div>';
    $output .= '<div style="float: right">';
    $output .= html_print_submit_button(
        __('Continue'),
        'register-next',
        false,
        'class="ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next" style="width:100px;"',
        true
    );
    $output .= '</div>';
    $output .= '<div id="all-required" style="clear:both; float: right; margin-right: 30px; display: none; color: red;">';
    $output .= __('All fields required');
    $output .= '</div>';
    $output .= '</div>';

    $output .= '</div>';

    // Verification modal.
    $output .= '<div id="wiz_ensure_cancel" title="Confirmation Required" style="display: none;">';
    $output .= '<div style="font-size: 12pt; margin: 20px;">';
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
        open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); }
    });

    default_language_displayed = $("#language").val();

    $(".ui-widget-overlay").css("background", "#000");
    $(".ui-widget-overlay").css("opacity", 0.6);
    $(".ui-draggable").css("cursor", "inherit");


    // CLICK EVENTS: Cancel and Registration
    $("#submit-cancel").click (function (e) {
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

    $("#submit-register-next").click (function () {
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


/**
 * Parse registration wiz.
 *
 * @return array Status feedback.
 */
function registration_wiz_process()
{
    global $config;

    $register_pandora = get_parameter('register_pandora', 0);
    $next_check = (time() + 1 * SECONDS_1DAY);
    $ui_feedback = [
        'status'  => true,
        'message' => '',
    ];

    // Pandora register update.
    $um_message = update_manager_register_instance();
    $ui_feedback['message'] .= $um_message['message'].'<br><br>';
    $ui_feedback['status'] = $um_message['success'] && $ui_feedback['status'];

    if ($ui_feedback['status']) {
        // Store next identification reminder.
        config_update_value(
            'identification_reminder_timestamp',
            $next_check
        );
    }

    return $ui_feedback;
}


/**
 * Shows a modal to register current console in UpdateManager.
 *
 * @param boolean $return   Return or show html.
 * @param boolean $launch   Execute wizard.
 * @param string  $callback Call function when done.
 *
 * @return string HTML code.
 */
function registration_wiz_modal(
    $return=false,
    $launch=true,
    $callback=false
) {
    global $config;
    $output = '';

    $product_name = get_product_name();

    $output .= '<div id="registration_wizard" title="';
    $output .= __('Register to Update Manager');
    $output .= '" style="display: none;">';
    $output .= '<div style="margin: 5px 0 10px; float: left; padding-left: 15px;">';
    $output .= html_print_image('images/pandora_circle_big.png', true);
    $output .= '</div>';

    $output .= '<div style="font-size: 12pt; margin: 5px 20px; float: left; padding-top: 23px;">';
    $output .= __(
        'Keep this %s console up to date with latest updates.',
        $product_name
    );
    $output .= '</div>';

    $output .= '<div class="license_text" style="clear:both;">';
    $output .= '<p>';
    $output .= __('When you subscribe to the %s Update Manager service, you accept that we register your %s instance as an identifier on a database owned by %s. This data will solely be used to provide you with information about %s and will not be conceded to third parties. You can unregister from said database at any time from the Update Manager options.', $product_name, $product_name, $product_name, $product_name);
    $output .= '</p>';
    $output .= '</div>';

    $output .= '<div class="submit_buttons_container">';
    $output .= '<div style="float: left;">';
    $output .= html_print_submit_button(
        __('Cancel'),
        'cancel_registration',
        false,
        'class="ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel"',
        true
    );
    $output .= '</div>';
    $output .= '<div style="float: right;">';
    $output .= html_print_submit_button(
        __('OK!'),
        'register',
        false,
        'class="ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next" style="width:100px;"',
        true
    );
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<div style="clear:both"></div>';
    $output .= '<br/>';
    $output .= '</div>';

    // Verification modal.
    $output .= '<div id="reg_ensure_cancel" title="Confirmation Required" style="display: none;">';
    $output .= '<div style="font-size: 12pt; margin: 20px;">';
    $output .= __('Are you sure you don\'t want to use update manager?');
    $output .= '<p>';
    $output .= __('You will need to update your system manually, through source code or RPM packages to be up to date with latest updates.');
    $output .= '</p>';
    $output .= '</div>';
    $output .= '</div>';

    // Results modal.
    $output .= '<div id="reg_result" title="Registration process result" style="display: none;">';
    $output .= '<div id="reg_result_content" style="font-size: 12pt; margin: 20px;">';
    $output .= '</div>';
    $output .= '</div>';

    ob_start();
    ?>
<script type="text/javascript">

function show_registration_wizard() {
    $("#registration_wizard").dialog({
        resizable: true,
        draggable: true,
        modal: true,
        width: 630,
        overlay: {
                opacity: 0.5,
                background: "black"
            },
        closeOnEscape: false,
        open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); }
    });

    default_language_displayed = $("#language").val();

    $(".ui-widget-overlay").css("background", "#000");
    $(".ui-widget-overlay").css("opacity", 0.6);
    $(".ui-draggable").css("cursor", "inherit");


    // CLICK EVENTS: Cancel and Registration
    $("#submit-cancel_registration").click (function (e) {
        e.preventDefault();
        $("#reg_ensure_cancel").dialog({
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
                                "cancel_registration": 1
                            },
                            function (data) {}
                        );
                        $(this).dialog("close");
                        $("#registration_wizard" ).dialog('close');
                    }
                }
            ]
        });

        $("#reg_ensure_cancel").dialog('open');
    });

    $("#submit-register").click (function () {
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
                    "register_console": 1
                },
                function (data) {
                    cl = '';
                    msg = 'no response';

                    try {
                        json = JSON.parse(data);
                        cl = json.status
                        msg = json.message;

                    } catch (error) {
                        msg = 'Failed: ' + error;
                        cl = 'error';
                    }

                    if (!cl || cl == 'error') {
                        cl = 'error';
                    } else {
                        // Success.
                    }

                    $('#reg_result_content').html(msg);
                    $('#reg_result').addClass(cl);
                    $('#reg_result').dialog({
                        buttons: [
                            {
                                "text": "OK",
                                "class": "submit-next",
                                "click": function() {
                                    $(this).dialog('close');
                                    $("#registration_wizard").dialog('close');
                                    <?php
                                    if (isset($callback) && $callback != '') {
                                        echo $callback;
                                    }
                                    ?>
                                }
                            }
                        ]
                    });
                }
            );
        }
    });
}

    <?php
    if ($launch === true) {
        ?>
        $(document).ready (function () {
            show_registration_wizard();
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


/**
 * Parse newsletter wiz.
 *
 * @return array Status feedback.
 */
function newsletter_wiz_process()
{
    global $config;

    $email = get_parameter('email', '');

    // Pandora newsletter update.
    $um_message = update_manager_insert_newsletter($email);

    $ui_feedback['message'] = $um_message['message'];

    if ($um_message['success']) {
        // Success or already registered.
        db_process_sql_update(
            'tusuario',
            ['middlename' => 1],
            ['id_user' => $config['id_user']]
        );
        $ui_feedback['status'] = $um_message['success'];
    } else {
        $ui_feedback['status'] = false;
    }

    return $ui_feedback;
}


/**
 * Show a modal allowing the user register into newsletter.
 *
 * @param boolean $return   Print content o return it.
 * @param boolean $launch   Launch directly on load or not.
 * @param string  $callback Call function when done.
 *
 * @return string HTML code.
 */
function newsletter_wiz_modal(
    $return=false,
    $launch=true,
    $callback=false
) {
    global $config;

    $output = '';

    $product_name = get_product_name();
    $email = db_get_value(
        'email',
        'tusuario',
        'id_user',
        $config['id_user']
    );

    // Avoid to show default email.
    if ($email == 'admin@example.com') {
        $email = '';
    }

    $output .= '<div id="newsletter_wizard" title="';
    $output .= __('Do you want to be up to date?');
    $output .= '" style="display: none;">';
    $output .= '<div style="margin: 5px 0 10px; float: left; padding-left: 15px;">';
    $output .= html_print_image('images/pandora_circle_big.png', true);
    $output .= '</div>';

    $output .= '<div style="font-size: 12pt; margin: 5px 20px; float: left; padding-top: 23px;">';
    $output .= __(
        'Subscribe to our newsletter',
        $product_name
    );
    $output .= '</div>';

    $output .= '<div class="license_text" style="clear:both;">';
    $output .= '<p>Stay up to date with updates, upgrades and promotions by subscribing to our newsletter.</p>';
    $output .= '<p>';
    $output .= __(
        'By subscribing to the newsletter, you accept that your email will be transferred to a database owned by %s. These data will be used only to provide you with information about %s and will not be given to third parties. You can unsubscribe from this database at any time from the newsletter subscription options.',
        $product_name,
        $product_name
    );

    $output .= '</p>';

    $output .= '</div>';
    // Show regiter to newsletter state.
    $show_newsletter = ($display_newsletter !== true) ? 'inline-block' : 'none';

    $output .= '<div style="margin-left: 4em;">';
    $output .= '<div id="box_newsletter">';
    $output .= '<span id="label-email-newsletter">'.__('Email').' </span>';
    $output .= html_print_input_text_extended(
        'email-newsletter',
        $email,
        'text-email-newsletter',
        '',
        30,
        255,
        false,
        '',
        ['style' => 'display:'.$show_newsletter.'; width: 200px;'],
        true
    );
    $output .= '</div><br /><br />';

    $output .= '<div class="submit_buttons_container">';
    $output .= '<div style="float: left;">';
    $output .= html_print_submit_button(
        __('Cancel'),
        'cancel_newsletter',
        false,
        'class="ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel" style="color: red; width:100px;"',
        true
    );
    $output .= '</div>';
    $output .= '<div style="float: right;">';
    $output .= html_print_submit_button(
        __('OK!'),
        'newsletter',
        false,
        'class="ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next" style="width:100px;"',
        true
    );
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<div style="clear:both"></div>';
    $output .= '<br/>';
    $output .= '</div>';
    $output .= '</div>';

    // Verification modal.
    $output .= '<div id="news_ensure_cancel" title="Confirmation Required" style="display: none;">';
    $output .= '<div style="font-size: 12pt; margin: 20px;">';
    $output .= __('Are you sure you don\'t want to subscribe?');
    $output .= '<p>';
    $output .= __('You will miss all news about amazing features and fixes!');
    $output .= '</p>';
    $output .= '</div>';
    $output .= '</div>';

    // Results modal.
    $output .= '<div id="news_result" title="Subscription process result" style="display: none;">';
    $output .= '<div id="news_result_content" style="font-size: 12pt; margin: 20px;">';
    $output .= '</div>';
    $output .= '</div>';

    ob_start();
    ?>
<script type="text/javascript">

function show_newsletter_wizard() {
    $("#newsletter_wizard").dialog({
        resizable: true,
        draggable: true,
        modal: true,
        width: 630,
        overlay: {
                opacity: 0.5,
                background: "black"
            },
        closeOnEscape: false,
        open: function(event, ui) { $(".ui-dialog-titlebar-close").hide(); }
    });

    default_language_displayed = $("#language").val();

    $(".ui-widget-overlay").css("background", "#000");
    $(".ui-widget-overlay").css("opacity", 0.6);
    $(".ui-draggable").css("cursor", "inherit");


    // CLICK EVENTS: Cancel and Registration
    $("#submit-cancel_newsletter").click (function (e) {
        e.preventDefault();
        $("#news_ensure_cancel").dialog({
            buttons: [
                {
                    text: "No",
                    "class": 'submit-cancel',
                    click : function() {
                        $(this).dialog('close');
                    }
                },
                {
                    text: "Yes",
                    "class": 'submit-next',
                    click : function() {
                        jQuery.post ("ajax.php",
                            {
                                "page": "general/register",
                                "cancel_newsletter": 1
                            },
                            function (data) {}
                        );
                        $(this).dialog("close");
                        $("#newsletter_wizard" ).dialog('close');
                    }
                }
            ]
        });

        $("#news_ensure_cancel").dialog('open');
    });

    $("#submit-newsletter").click (function () {
        // All fields are required.
        if ($("#text-email").val() == '') {
            $("#all-required").show();
        } else {
            var timezone = $("#timezone").val();
            var language = $("#language").val();
            var email_identification = $("#text-email-newsletter").val();

            if (email_identification == '') {
                msg = '<?php echo __('You must specify an email'); ?>';
                $('#news_result_content').html(msg);
                $('#news_result').dialog({
                    buttons: {
                        'Ok': function() {
                            $(this).dialog('close');
                        }
                    }
                });
            } else {

                jQuery.post (
                    "ajax.php",
                    {
                        "page": "general/register",
                        "register_newsletter": 1,
                        "email": email_identification
                    },
                    function (data) {
                        cl = '';
                        msg = 'no response';

                        try {
                            json = JSON.parse(data);
                            cl = json.status
                            msg = json.message;

                        } catch (error) {
                            msg = 'Failed: ' + error;
                            cl = 'error';
                        }

                        if (!cl || cl == 'error') {
                            cl = 'error';
                        } else {
                            // Success.
                        }

                        $('#news_result_content').html(msg);
                        $('#news_result').addClass(cl);
                        $('#news_result').dialog({
                            buttons: {
                                'Ok': function() {
                                    $(this).dialog('close');
                                    $("#newsletter_wizard").dialog('close');
                                    <?php
                                    if (isset($callback) && $callback != '') {
                                        echo $callback;
                                    }
                                    ?>
                                }
                            }
                        });
                    }
                );
            }
        }
    });
}

    <?php
    if ($launch === true) {
        ?>
        $(document).ready (function () {
            show_newsletter_wizard();
        });
        <?php
    }
    ?>


</script>

    <?php
    $output .= ob_get_clean();

    if (!$return) {
        echo $output;
    }

    return $output;
}


/*
 *
 * Registration functions - End.
 *
 */


/**
 * Prepare configuration values.
 *
 * @return array UM Configuration tokens.
 */
function update_manager_get_config_values()
{
    global $config;
    global $build_version;
    global $pandora_version;

    enterprise_include_once('include/functions_license.php');

    $license = db_get_value(
        db_escape_key_identifier('value'),
        'tupdate_settings',
        db_escape_key_identifier('key'),
        'customer_key'
    );

    $data = enterprise_hook('license_get_info');

    if ($data === ENTERPRISE_NOT_HOOK) {
        $limit_count = db_get_value_sql('SELECT count(*) FROM tagente');
    } else {
        $limit_count = $data['count_enabled'];
    }

    return [
        'license'        => $license,
        'current_update' => update_manager_get_current_package(),
        'limit_count'    => $limit_count,
        'build'          => $build_version,
        'version'        => $pandora_version,
        'puid'           => $config['pandora_uid'],
    ];
}


/**
 * Function to remove dir and files inside.
 *
 * @param string $dir Path to dir.
 *
 * @return void
 */
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);

        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (filetype($dir.'/'.$object) == 'dir') {
                    rrmdir($dir.'/'.$object);
                } else {
                    unlink($dir.'/'.$object);
                }
            }
        }

        reset($objects);
        rmdir($dir);
    } else {
        unlink($dir);
    }
}


/**
 * Install updates step2.
 *
 * @return void
 */
function update_manager_install_package_step2()
{
    global $config;

    ob_clean();

    $package = (string) get_parameter('package');
    $package = trim($package);

    $version = (string) get_parameter('version', 0);

    $path = sys_get_temp_dir().'/pandora_oum/'.$package;

    // All files extracted.
    $files_total = $path.'/files.txt';
    // Files copied.
    $files_copied = $path.'/files.copied.txt';
    $return = [];

    if (file_exists($files_copied)) {
        unlink($files_copied);
    }

    if (file_exists($path)) {
        $files_h = fopen($files_total, 'r');
        if ($files_h === false) {
            $return['status'] = 'error';
            $return['message'] = __('An error ocurred while reading a file.');
            echo json_encode($return);
            return;
        }

        while ($line = stream_get_line($files_h, 65535, "\n")) {
            $line = trim($line);

            // Tries to move the old file to the directory backup
            // inside the extracted package.
            if (file_exists($config['homedir'].'/'.$line)) {
                rename($config['homedir'].'/'.$line, $path.'/backup/'.$line);
            }

            // Tries to move the new file to the Integria directory.
            $dirname = dirname($line);
            if (!file_exists($config['homedir'].'/'.$dirname)) {
                $dir_array = explode('/', $dirname);
                $temp_dir = '';
                foreach ($dir_array as $dir) {
                    $temp_dir .= '/'.$dir;
                    if (!file_exists($config['homedir'].$temp_dir)) {
                        mkdir($config['homedir'].$temp_dir);
                    }
                }
            }

            if (is_dir($path.'/'.$line)) {
                if (!file_exists($config['homedir'].'/'.$line)) {
                    mkdir($config['homedir'].'/'.$line);
                    file_put_contents($files_copied, $line."\n", (FILE_APPEND | LOCK_EX));
                }
            } else {
                // Copy the new file.
                if (rename($path.'/'.$line, $config['homedir'].'/'.$line)) {
                    // Append the moved file to the copied files txt.
                    if (!file_put_contents($files_copied, $line."\n", (FILE_APPEND | LOCK_EX))) {
                        // If the copy process fail, this code tries to
                        // restore the files backed up before.
                        $files_copied_h = fopen($files_copied, 'r');
                        if ($files_copied_h === false) {
                            $backup_status = __('Some of your old files might not be recovered.');
                        } else {
                            while ($line_c = stream_get_line($files_copied_h, 65535, "\n")) {
                                $line_c = trim($line_c);
                                if (!rename($path.'/backup/'.$line, $config['homedir'].'/'.$line_c)) {
                                    $backup_status = __('Some of your files might not be recovered.');
                                }
                            }

                            if (!rename($path.'/backup/'.$line, $config['homedir'].'/'.$line)) {
                                $backup_status = __('Some of your files might not be recovered.');
                            }

                            fclose($files_copied_h);
                        }

                        fclose($files_h);
                        $return['status'] = 'error';
                        $return['message'] = __(
                            'Line "%s" not copied to the progress file.',
                            $line
                        ).'&nbsp;'.$backup_status;
                        echo json_encode($return);
                        return;
                    }
                } else {
                    // If the copy process fail, this code tries to restore
                    // the files backed up before.
                    $files_copied_h = fopen($files_copied, 'r');
                    if ($files_copied_h === false) {
                        $backup_status = __('Some of your files might not be recovered.');
                    } else {
                        while ($line_c = stream_get_line($files_copied_h, 65535, "\n")) {
                            $line_c = trim($line_c);
                            if (!rename(
                                $path.'/backup/'.$line,
                                $config['homedir'].'/'.$line
                            )
                            ) {
                                $backup_status = __('Some of your old files might not be recovered.');
                            }
                        }

                        fclose($files_copied_h);
                    }

                    fclose($files_h);
                    $return['status'] = 'error';
                    $return['message'] = __(
                        'Line "%s" not copied to the progress file.',
                        $line
                    ).'&nbsp;'.$backup_status;
                    echo json_encode($return);
                    return;
                }
            }
        }

        fclose($files_h);
    } else {
        $return['status'] = 'error';
        $return['message'] = __('The package does not exist');
        echo json_encode($return);
        return;
    }

    update_manager_enterprise_set_version($version);
    $product_name = get_product_name();

    // Generate audit entry.
    db_pandora_audit(
        'Update '.$product_name,
        'Update version: '.$version.' of '.$product_name.' by '.$config['id_user']
    );

    $return['status'] = 'success';
    $return['message'] = __('The package is installed.');
    echo json_encode($return);

}


/**
 * Launch update manager client.
 *
 * @return void
 */
function update_manager_main()
{
    global $config;
    ?>
    <script type="text/javascript">
        <?php
        echo 'var unknown_error_update_manager = "'.__('There is a unknown error.').'";';
        ?>
    </script>
    <script src="include/javascript/update_manager.js"></script>
    <script type="text/javascript">
        var version_update = "";
        var stop_check_progress = 0;

        $(document).ready(function() {
            check_online_free_packages();
        });
    </script>
    <?php
}


/**
 * Check updates available (opensource).
 *
 * @return boolean Packages available or not.
 */
function update_manager_check_online_free_packages_available()
{
    global $config;

    $update_message = '';

    $um_config_values = update_manager_get_config_values();

    $params = [
        'action'          => 'newest_package',
        'license'         => $um_config_values['license'],
        'limit_count'     => $um_config_values['limit_count'],
        'current_package' => $um_config_values['current_update'],
        'version'         => $um_config_values['version'],
        'build'           => $um_config_values['build'],
        'puid'            => $um_config_values['puid'],
    ];

    $curlObj = curl_init();
    curl_setopt($curlObj, CURLOPT_URL, get_um_url().'server.php');
    curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlObj, CURLOPT_POST, true);
    curl_setopt($curlObj, CURLOPT_POSTFIELDS, $params);
    curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curlObj, CURLOPT_CONNECTTIMEOUT, 4);

    if (isset($config['update_manager_proxy_server'])) {
        curl_setopt($curlObj, CURLOPT_PROXY, $config['update_manager_proxy_server']);
    }

    if (isset($config['update_manager_proxy_port'])) {
        curl_setopt($curlObj, CURLOPT_PROXYPORT, $config['update_manager_proxy_port']);
    }

    if (isset($config['update_manager_proxy_user'])) {
        curl_setopt($curlObj, CURLOPT_PROXYUSERPWD, $config['update_manager_proxy_user'].':'.$config['update_manager_proxy_password']);
    }

    $result = curl_exec($curlObj);
    $http_status = curl_getinfo($curlObj, CURLINFO_HTTP_CODE);
    curl_close($curlObj);

    if ($result === false) {
        return false;
    } else if ($http_status >= 400 && $http_status < 500) {
        return false;
    } else if ($http_status >= 500) {
        return false;
    } else {
        $result = json_decode($result, true);

        if (empty($result)) {
            return false;
        } else {
            return true;
        }
    }
}


/**
 * Update process, online packages.
 *
 * @param boolean $is_ajax Is ajax call o direct call.
 *
 * @return string HTML update message.
 */
function update_manager_check_online_free_packages($is_ajax=true)
{
    global $config;

    $update_message = '';

    $um_config_values = update_manager_get_config_values();

    $params = [
        'action'          => 'newest_package',
        'license'         => $um_config_values['license'],
        'limit_count'     => $um_config_values['limit_count'],
        'current_package' => $um_config_values['current_update'],
        'version'         => $um_config_values['version'],
        'build'           => $um_config_values['build'],
        'puid'            => $um_config_values['puid'],
    ];

    /*
     * To test using shell execute:
     * wget https://artica.es/pandoraupdate7/server.php -O- \
     * --no-check-certificate --post-data \
     * "action=newest_package&license=PANDORA_FREE&limit_count=1&current_package=1&version=v5.1RC1&build=PC140625"
     */

    $curlObj = curl_init();
    curl_setopt($curlObj, CURLOPT_URL, get_um_url().'server.php');
    curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlObj, CURLOPT_POST, true);
    curl_setopt($curlObj, CURLOPT_POSTFIELDS, $params);
    curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, false);
    if (isset($config['update_manager_proxy_server'])) {
        curl_setopt(
            $curlObj,
            CURLOPT_PROXY,
            $config['update_manager_proxy_server']
        );
    }

    if (isset($config['update_manager_proxy_port'])) {
        curl_setopt(
            $curlObj,
            CURLOPT_PROXYPORT,
            $config['update_manager_proxy_port']
        );
    }

    if (isset($config['update_manager_proxy_user'])) {
        curl_setopt(
            $curlObj,
            CURLOPT_PROXYUSERPWD,
            $config['update_manager_proxy_user'].':'.$config['update_manager_proxy_password']
        );
    }

    $result = curl_exec($curlObj);
    $http_status = curl_getinfo($curlObj, CURLINFO_HTTP_CODE);
    curl_close($curlObj);

    if ($result === false) {
        if ($is_ajax) {
            echo __('Could not connect to internet');
        } else {
            $update_message = __('Could not connect to internet');
        }
    } else if ($http_status >= 400 && $http_status < 500) {
        if ($is_ajax) {
            echo __('Server not found.');
        } else {
            $update_message = __('Server not found.');
        }
    } else if ($http_status >= 500) {
        if ($is_ajax) {
            echo $result;
        } else {
            $update_message = $result;
        }
    } else {
        if ($is_ajax) {
            $result = json_decode($result, true);

            if (!empty($result)) {
                ?>
                <script type="text/javascript">
                    var mr_available = "<?php echo __('Minor release available'); ?>\n";
                    var package_available = "<?php echo __('New package available'); ?>\n";
                    var mr_not_accepted = "<?php echo __('Minor release rejected. Changes will not apply.'); ?>\n";
                    var mr_not_accepted_code_yes = "<?php echo __('Minor release rejected. The database will not be updated and the package will apply.'); ?>\n";
                    var mr_cancel = "<?php echo __('Minor release rejected. Changes will not apply.'); ?>\n";
                    var package_cancel = "<?php echo __('These package changes will not apply.'); ?>\n";
                    var package_not_accepted = "<?php echo __('Package rejected. These package changes will not apply.'); ?>\n";
                    var mr_success = "<?php echo __('Database successfully updated'); ?>\n";
                    var mr_error = "<?php echo __('Error in MR file'); ?>\n";
                    var package_success = "<?php echo __('Package updated successfully'); ?>\n";
                    var package_error = "<?php echo __('Error in package updated'); ?>\n";
                    var bad_mr_file = "<?php echo __('Database MR version is inconsistent, do you want to apply the package?'); ?>\n";
                    var mr_available_header = "<?php echo __('There are db changes'); ?>\n";
                    var text1_mr_file = "<?php echo __('There are new database changes available to apply. Do you want to start the DB update process?'); ?>\n";
                    var text2_mr_file = "<?php echo __('We recommend launching '); ?>\n";
                    var text3_mr_file = "<?php echo __('planned downtime'); ?>\n";

                    var language = "<?php echo $config['language']; ?>";
                    var docsUrl = (language === "es")
                        ? "http://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Actualizacion#Versi.C3.B3n_7.0NG_.28_Rolling_Release_.29"
                        : "http://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Anexo_Upgrade#Version_7.0NG_.28_Rolling_Release_.29";

                    var text4_mr_file = "<?php echo __(' to this process'); ?>";
                    text4_mr_file += "<br><br>";
                    text4_mr_file += "<a style=\"font-size:10pt;font-style:italic;\" target=\"blank\" href=\"" + docsUrl + "\">";
                    text4_mr_file += "<?php echo __('About minor release update'); ?>";
                    text4_mr_file += "</a>";

                    var text1_package_file = "<?php echo __('There is a new update available'); ?>\n";
                    var text2_package_file = "<?php echo __('There is a new update available to apply. Do you want to start the update process?'); ?>\n";
                    var applying_mr = "<?php echo __('Applying DB MR'); ?>\n";
                    var cancel_button = "<?php echo __('Cancel'); ?>\n";
                    var ok_button = "<?php echo __('Ok'); ?>\n";
                    var apply_mr_button = "<?php echo __('Apply MR'); ?>\n";
                    var apply_button = "<?php echo __('Apply'); ?>\n";
                </script>
                <?php
                $baseurl = ui_get_full_url(false, false, false, false);
                echo '<p><b>There is a new version:</b> '.$result[0]['version'].'</p>';
                echo "<a class='update_manager_button' href='javascript: update_last_package(\"".base64_encode($result[0]['file_name']).'", "'.$result[0]['version'].'", "'.$baseurl."\");'>".__('Update').'</a>';
            } else {
                echo __('There is no update available.');
            }

            return $update_message;
        } else {
            if (!empty($result)) {
                $result = json_decode($result, true);
                $update_message = 'There is a new version: '.$result[0]['version'];
            }

            return $update_message;
        }
    }

}


/**
 * Executes an action against UpdateManager.
 *
 * @param string  $action            Action to perform.
 * @param boolean $additional_params Extra parameters (optional).
 *
 * @return array With UM response.
 */
function update_manager_curl_request($action, $additional_params=false)
{
    global $config;

    $error_array = [
        'success'        => true,
        'update_message' => '',
    ];
    $update_message = '';

    $um_config_values = update_manager_get_config_values();

    $params = [
        'license'         => $um_config_values['license'],
        'limit_count'     => $um_config_values['limit_count'],
        'current_package' => $um_config_values['current_update'],
        'version'         => $um_config_values['version'],
        'build'           => $um_config_values['build'],
        'puid'            => $um_config_values['puid'],
    ];
    if ($additional_params !== false) {
        $params = array_merge($params, $additional_params);
    }

    $params['action'] = $action;

    $curlObj = curl_init();
    curl_setopt($curlObj, CURLOPT_URL, get_um_url().'server.php');
    curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlObj, CURLOPT_POST, true);
    curl_setopt($curlObj, CURLOPT_POSTFIELDS, $params);
    curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, false);
    if (isset($config['update_manager_proxy_server'])) {
        curl_setopt($curlObj, CURLOPT_PROXY, $config['update_manager_proxy_server']);
    }

    if (isset($config['update_manager_proxy_port'])) {
        curl_setopt($curlObj, CURLOPT_PROXYPORT, $config['update_manager_proxy_port']);
    }

    if (isset($config['update_manager_proxy_user'])) {
        curl_setopt($curlObj, CURLOPT_PROXYUSERPWD, $config['update_manager_proxy_user'].':'.$config['update_manager_proxy_password']);
    }

    $result = curl_exec($curlObj);
    $http_status = curl_getinfo($curlObj, CURLINFO_HTTP_CODE);
    curl_close($curlObj);

    $error_array['http_status'] = $http_status;

    if ($result === false) {
        $error_array['success'] = false;
        if ($is_ajax) {
            echo __('Could not connect to internet');
            return $error_array;
        } else {
            $error_array['update_message'] = __('Could not connect to internet');
            return $error_array;
        }
    } else if ($http_status >= 400 && $http_status < 500) {
        $error_array['success'] = false;
        if ($is_ajax) {
            echo __('Server not found.');
            return $error_array;
        } else {
            $error_array['update_message'] = __('Server not found.');
            return $error_array;
        }
    } else if ($http_status >= 500) {
        $error_array['success'] = false;
        if ($is_ajax) {
            echo $result;
            return $error_array;
        } else {
            $error_array['update_message'] = $result;
            return $error_array;
        }
    }

    $error_array['update_message'] = $result;
    return $error_array;

}


/**
 * Subscribes an email account into newsletter.
 *
 * @param string $email E-mail.
 *
 * @return array With success [true/false], message [string].
 */
function update_manager_insert_newsletter($email)
{
    global $config;

    if ($email === '') {
        return false;
    }

    $params = [
        'email'    => $email,
        'language' => $config['language'],
        'license'  => db_get_value_filter(
            'value',
            'tupdate_settings',
            ['key' => 'customer_key']
        ),
    ];

    $result = update_manager_curl_request('new_newsletter', $params);

    if (!$result['success']) {
        return [
            'success' => false,
            'message' => __('Remote server error on newsletter request'),
        ];
    }

    switch ($result['http_status']) {
        case 200:
            $message = json_decode($result['update_message'], true);
            if ($message['success'] == 1) {
                return [
                    'success' => true,
                    'message' => __('E-mail successfully subscribed to newsletter.'),
                ];
            } else {
                return [
                    'success' => true,
                    'message' => __('E-mail has already subscribed to newsletter.'),
                ];
            }

        default:
        return [
            'success' => false,
            'message' => __('Update manager returns error code: ').$result['http_status'].'.',
        ];
    }
}


/**
 * Registers this console into UpdateManager.
 *
 * @return array With success [true/false], message [string].
 */
function update_manager_register_instance()
{
    global $config;

    $email = db_get_value('email', 'tusuario', 'id_user', $config['id_user']);

    $um_config_values = update_manager_get_config_values();

    $params = [
        'action'          => 'newest_package',
        'license'         => $um_config_values['license'],
        'limit_count'     => $um_config_values['limit_count'],
        'current_package' => $um_config_values['current_update'],
        'version'         => $um_config_values['version'],
        'build'           => $um_config_values['build'],
        'puid'            => $um_config_values['puid'],
        'email'           => $email,
        'language'        => $config['language'],
        'timezone'        => $config['timezone'],
    ];

    $result = update_manager_curl_request('new_register', $params);

    if (!$result['success']) {
        return [
            'success' => false,
            'message' => __('Error while registering console.').'<br/>'.$result['update_message'],
        ];
    }

    switch ($result['http_status']) {
        case 200:
            // Retrieve the PUID.
            $message = json_decode($result['update_message'], true);

            if ($message['success'] == 1) {
                $puid = $message['pandora_uid'];
                config_update_value('pandora_uid', $puid);

                // The tupdate table is reused to display messages.
                // A specific entry to tupdate_package is required.
                // Then, this tupdate_package id is saved in tconfig.
                db_process_sql_insert(
                    'tupdate_package',
                    ['description' => '__UMMESSAGES__']
                );
                $id_um_package_messages = db_get_value(
                    'id',
                    'tupdate_package',
                    'description',
                    '__UMMESSAGES__'
                );
                config_update_value(
                    'id_um_package_messages',
                    $id_um_package_messages
                );
                return [
                    'success' => true,
                    'message' => __('Pandora successfully subscribed with UID: ').$puid.'.',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => __('Unsuccessful subscription.'),
                ];
            }

        default:
        return [
            'success' => false,
            'message' => __('Update manager returns error code: ').$result['http_status'].'.',
        ];
    }
}


/**
 * Extracts OUM package.
 *
 * @return boolean Success or not.
 */
function update_manager_extract_package()
{
    global $config;

    $path_package = $config['attachment_store'].'/downloads/last_package.tgz';

    ob_start();

    if (!defined('PHP_VERSION_ID')) {
        $version = explode('.', PHP_VERSION);
        define(
            'PHP_VERSION_ID',
            ($version[0] * 10000 + $version[1] * 100 + $version[2])
        );
    }

    $extracted = false;

    // Phar and exception working fine in 5.5.0 or higher.
    if (PHP_VERSION_ID >= 50505) {
        $phar = new PharData($path_package);
        try {
            $result = $phar->extractTo(
                $config['attachment_store'].'/downloads/',
                null,
                true
            );
            $extracted = true;
        } catch (Exception $e) {
            echo ' There\'s a problem ... -> '.$e->getMessage();
            $extracted = false;
        }
    }

    $return = true;

    if ($extracted === false) {
        // Phar extraction failed. Fallback to OS extraction.
        $return = false;

        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            // Unsupported OS.
            echo 'This OS ['.PHP_OS.'] does not support direct extraction of tgz files. Upgrade PHP version to be > 5.5.0';
        } else {
            $return = true;
            system('tar xzf "'.$path_package.'" -C '.$config['attachment_store'].'/downloads/');
        }
    }

    ob_end_clean();

    rrmdir($path_package);

    if ($result == 0) {
        db_process_sql_update(
            'tconfig',
            [
                'value' => json_encode(
                    [
                        'status'  => 'fail',
                        'message' => __('Failed extracting the package to temp directory.'),
                    ]
                ),
            ],
            ['token' => 'progress_update_status']
        );

        return false;
    }

    db_process_sql_update(
        'tconfig',
        ['value' => 50],
        ['token' => 'progress_update']
    );

    return $return;
}


/**
 * The update copy entire tgz or fail (leaving some parts copied
 * and others not).
 * This does not make changes on DB.
 *
 * @return boolean Success or not.
 */
function update_manager_starting_update()
{
    global $config;

    $full_path = $config['attachment_store'].'/downloads';

    $homedir = $config['homedir'];

    $result = update_manager_recurse_copy(
        $full_path,
        $homedir,
        ['install.php']
    );

    rrmdir($full_path.'/pandora_console');

    if (!$result) {
        db_process_sql_update(
            'tconfig',
            [
                'value' => json_encode(
                    [
                        'status'  => 'fail',
                        'message' => __('Failed the copying of the files.'),
                    ]
                ),
            ],
            ['token' => 'progress_update_status']
        );

        return false;
    } else {
        db_process_sql_update(
            'tconfig',
            ['value' => 100],
            ['token' => 'progress_update']
        );
        db_process_sql_update(
            'tconfig',
            [
                'value' => json_encode(
                    [
                        'status'  => 'end',
                        'message' => __('Package extracted successfully.'),
                    ]
                ),
            ],
            ['token' => 'progress_update_status']
        );

        return true;
    }
}


/**
 * Copies recursively extracted package updates to target path.
 *
 * @param string $src        Path.
 * @param string $dst        Path.
 * @param string $black_list Path.
 *
 * @return boolean Success or not.
 */
function update_manager_recurse_copy($src, $dst, $black_list)
{
    $dir = opendir($src);
    @mkdir($dst);
    @trigger_error('NONE');

    while (false !== ( $file = readdir($dir))) {
        if (( $file != '.' ) && ( $file != '..' ) && (!in_array($file, $black_list))) {
            if (is_dir($src.'/'.$file)) {
                if (!update_manager_recurse_copy(
                    $src.'/'.$file,
                    $dst.'/'.$file,
                    $black_list
                )
                ) {
                    return false;
                }
            } else {
                $result = copy($src.'/'.$file, $dst.'/'.$file);
                $error = error_get_last();

                if (strstr($error['message'], 'copy(')) {
                    return false;
                }
            }
        }
    }

    closedir($dir);

    return true;
}


/**
 * Updates current update (DB).
 *
 * @param string $current_package Current package.
 *
 * @return void
 */
function update_manager_set_current_package($current_package)
{
    if (enterprise_installed()) {
        $token = 'current_package_enterprise';
    } else {
        $token = 'current_package';
    }

    $col_value = db_escape_key_identifier('value');
    $col_key = db_escape_key_identifier('key');

    $value = db_get_value(
        $col_value,
        'tupdate_settings',
        $col_key,
        $token
    );

    if ($value === false) {
        db_process_sql_insert(
            'tupdate_settings',
            [
                $col_value => $current_package,
                $col_key   => $token,
            ]
        );
    } else {
        db_process_sql_update(
            'tupdate_settings',
            [$col_value => $current_package],
            [$col_key => $token]
        );
    }
}


/**
 * Retrieves current update from DB.
 *
 * @return string Current update.
 */
function update_manager_get_current_package()
{
    global $config;

    if (enterprise_installed()) {
        $token = 'current_package_enterprise';
    } else {
        $token = 'current_package';
    }

    $current_update = db_get_value(
        db_escape_key_identifier('value'),
        'tupdate_settings',
        db_escape_key_identifier('key'),
        $token
    );

    if ($current_update === false) {
        $current_update = 0;
        if (isset($config[$token])) {
            $current_update = $config[$token];
        }
    }

    return $current_update;
}


/**
 * Function recursive delete directory.
 *
 * @param string $dir    Directory to delete.
 * @param array  $result Array result state and message.
 *
 * @return array Return result array with status 0 valid or 1 false and
 * type 'f' file and 'd' dir and route path file or directory.
 */
function rmdir_recursive(string $dir, array &$result)
{
    foreach (scandir($dir) as $file) {
        if ('.' === $file || '..' === $file) {
            continue;
        }

        if (is_dir($dir.'/'.$file) === true) {
            rmdir_recursive($dir.'/'.$file, $result);
        } else {
            $unlink = unlink($dir.'/'.$file);
            $res = [];
            $res['status'] = ($unlink === true) ? 0 : 1;
            $res['type'] = 'f';
            $res['path'] = $dir.'/'.$file;
            array_push($result, $res);
        }
    }

    $rmdir = rmdir($dir);
    $res = [];
    $res['status'] = ($rmdir === true) ? 0 : 1;
    $res['type'] = 'd';
    $res['path'] = $dir;
    array_push($result, $res);

    return $result;
}
