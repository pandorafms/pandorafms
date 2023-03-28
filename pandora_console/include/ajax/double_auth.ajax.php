<?php

/**
 * Double Authentication Ajax file.
 *
 * @category   Users
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
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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
global $config;

// Login check.
check_login();

// Security check.
$id_user = (string) get_parameter('id_user');
$FA_forced = (int) get_parameter('FA_forced');
$id_user_auth = (string) get_parameter('id_user_auth', $config['id_user']);


if ($id_user !== $config['id_user'] && $FA_forced != 1) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Double Authentication'
    );
    echo json_encode(-1);
    return;
}

// Load the class.
require_once $config['homedir'].'/include/auth/GAuth/Auth.php';

// Default lenght of the secret.
$secret_lenght = 16;
// Default lenght of the code.
$code_lenght = 6;

// Generate a new secret for the user.
$generate_double_auth_secret = (bool) get_parameter('generate_double_auth_secret');
if ($generate_double_auth_secret) {
    $gAuth = new \GAuth\Auth();
    $code = $gAuth->generateCode($secret_lenght);

    echo json_encode($code);
    return;
}

// Validate the provided secret with a code provided by the user.
// If the parameter 'save' is set to true, the secret will
// be stored into the database.
// The results can be true, false or 1 if the validation is true
// but the secret can't be stored into the database.
$validate_double_auth_code = (bool) get_parameter('validate_double_auth_code');
if ($validate_double_auth_code) {
    $result = false;

    $secret = (string) get_parameter('secret');

    if (!empty($secret) && strlen($secret) === $secret_lenght) {
        $code = (string) get_parameter('code');

        if (!empty($code) && strlen($code) === $code_lenght) {
            $save = (bool) get_parameter('save');

            if (!empty($code)) {
                $gAuth = new \GAuth\Auth($secret);
                $result = $gAuth->validateCode($code);
            }

            if ($result && $save) {
                // Delete the actual value (if exists)
                $where = ['id_user' => $id_user_auth];
                db_process_sql_delete('tuser_double_auth', $where);

                // Insert the new value
                $values = [
                    'id_user' => $id_user_auth,
                    'secret'  => $secret,
                ];
                $result = (bool) db_process_sql_insert('tuser_double_auth', $values);

                if (!$result) {
                    $result = 1;
                }
            }
        }
    }

    echo json_encode($result);
    return;
}

// Set the provided secret to the user.
$save_double_auth_secret = (bool) get_parameter('save_double_auth_secret');
if ($save_double_auth_secret) {
    $result = false;

    $secret = (string) get_parameter('secret');

    if (strlen($secret) === $secret_lenght) {
        // Delete the actual value (if exists).
        $where = ['id_user' => $id_user];
        db_process_sql_delete('tuser_double_auth', $where);
        // Insert the new value.
        $values = [
            'id_user' => $id_user,
            'secret'  => $secret,
        ];
        $result = (bool) db_process_sql_insert('tuser_double_auth', $values);
    }

    echo json_encode($result);
    return;
}

// Disable the double auth for the user.
$deactivate_double_auth = (bool) get_parameter('deactivate_double_auth');
if ($deactivate_double_auth) {
    $result = false;

    // Delete the actual value (if exists).
    $where = ['id_user' => $id_user];
    $result = db_process_sql_delete('tuser_double_auth', $where);

    echo json_encode($result);
    return;
}

// Get the info page to the container dialog.
$get_double_auth_data_page = (bool) get_parameter('get_double_auth_data_page');
if ($get_double_auth_data_page) {
    $secret = db_get_value('secret', 'tuser_double_auth', 'id_user', $id_user);

    if (empty($secret)) {
        return;
    }

    $html = '';
    $html .= '<div class="left_align">';
    $html .= '<p>';
    $html .= __('This is the private code that you should use with your authenticator app').'. ';
    $html .= __('You could enter the code manually or use the QR code to add it automatically').'.';
    $html .= '</p>';
    $html .= '</div>';
    $html .= '<div class="center_align">';
    $html .= __('Code').': <b>'.$secret.'</b>';
    $html .= '<br>';
    $html .= __('QR').': <br>';
    $html .= '<div id="qr-container"></div>';
    $html .= '</div>';

    ob_clean();
    ?>
    
<script type="text/javascript" src="../../include/javascript/qrcode.js"></script>
<script type="text/javascript">

    var secret = "<?php echo $secret; ?>";
    var id_user_auth = "<?php echo $id_user_auth; ?>";

    // QR code with the secret to add it to the app.
    paint_qrcode("otpauth://totp/"+id_user_auth+"?secret="+secret, $("div#qr-container").get(0), 200, 200);

    $("div#qr-container").attr("title", "").find("canvas").remove();
    // Don't delete this timeout. It's necessary to perform the style change.
    // Chrome min. milliseconds: 1.
    // Firefox min. milliseconds: 9.
    setTimeout(function() {
            $("div#qr-container").find("img").attr("style", "");
        }, 10);
</script>
    <?php
    $html .= ob_get_clean();

    echo $html;
    return;
}

// Get the info page to the container dialog.
$get_double_auth_info_page = (bool) get_parameter('get_double_auth_info_page');
if ($get_double_auth_info_page) {
    $container_id = (string) get_parameter('containerID');

    $html = '';
    $html .= '<div class="left_align">';
    $html .= '<p>';
    $html .= __('You are about to activate the double authentication').'. ';
    $html .= __(
        'With this option enabled, your account access will be more secure, 
		cause a code generated by other application will be required after the login'
    ).'. ';
    $html .= '</p>';
    $html .= '<p>';
    $html .= __('You will need to install the app from the following link before continue').'. ';
    $html .= '</p>';
    $html .= '</div>';
    $html .= '<br>';
    $html .= '<div class="flex flex-space-around">';
    $html .= html_print_button(__('Download the app'), 'google_authenticator_download', false, '', '', true);
    $html .= html_print_button(__('Continue'), 'continue_to_generate', false, '', '', true);
    $html .= '</div>';

    ob_clean();
    ?>
<script type="text/javascript">
    // Open the download page on click.
    $("#button-google_authenticator_download").click(function (e) {
        e.preventDefault();
        window.open("https://support.google.com/accounts/answer/1066447");
    });

    // Change the container content with the generation page.
    $("#button-continue_to_generate").click(function (e) {
        e.preventDefault();

        if (!confirm("<?php echo __('Are you installed the app yet?'); ?>")) {
            return false;
        }

        var containerID = "<?php echo $container_id; ?>";
        var id_user_auth = "<?php echo $id_user_auth; ?>";

        $("#"+containerID).html("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");

        $.ajax({
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            type: 'POST',
            dataType: 'html',
            data: {
                page: 'include/ajax/double_auth.ajax',
                id_user: "<?php echo $config['id_user']; ?>",
                id_user_auth: id_user_auth,
                get_double_auth_generation_page: 1,
                containerID: containerID
            },
            complete: function(xhr, textStatus) {
                
            },
            success: function(data, textStatus, xhr) {
                // isNaN = is not a number
                if (isNaN(data)) {
                    $("#"+containerID).html(data);
                }
                // data is a number, convert it to integer to do the compare
                else if (Number(data) === -1) {
                    $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
                }
                else {
                    $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('Error').'</div></b>'; ?>");
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('There was an error loading the data').'</div></b>'; ?>");
            }
        });
    });
</script>
    <?php
    $html .= ob_get_clean();

    echo $html;
    return;
}

// Get the page that generates a secret for the user.
$get_double_auth_generation_page = (bool) get_parameter('get_double_auth_generation_page');
if ($get_double_auth_generation_page) {
    $container_id = (string) get_parameter('containerID');

    $gAuth = new \GAuth\Auth();
    $secret = $gAuth->generateCode($secret_lenght);

    $html = '';
    $html .= '<div class="center_align">';
    $html .= '<p>';
    $html .= '<b>'.__('A private code has been generated').'</b>.';
    $html .= '</p>';
    $html .= '</div>';
    $html .= '<div class="left_align">';
    $html .= '<p>';
    $html .= __('Before continue, you should create a new entry into the authenticator app').'. ';
    $html .= __('You could enter the code manually or use the QR code to add it automatically').'.';
    $html .= '</p>';
    $html .= '</div>';
    $html .= '<div class="center_align">';
    $html .= __('Code').': <b>'.$secret.'</b>';
    $html .= '<br>';
    $html .= __('QR').': <br>';
    $html .= '<div id="qr-container"></div>';
    $html .= '<br><div class="flex flex-space-around">';
    $html .= html_print_button(__('Refresh code'), 'continue_to_generate', false, '', '', true);
    $html .= html_print_button(__('Continue'), 'continue_to_validate', false, '', '', true);
    $html .= '</div>';
    $html .= '</div>';

    ob_clean();
    ?>

<script type="text/javascript" src="../../include/javascript/qrcode.js"></script>
<script type="text/javascript">
    var secret = "<?php echo $secret; ?>";
    var id_user_auth = "<?php echo $id_user_auth; ?>";

    // QR code with the secret to add it to the app
    paint_qrcode("otpauth://totp/"+id_user_auth+"?secret="+secret, $("div#qr-container").get(0), 200, 200);

    $("div#qr-container").attr("title", "").find("canvas").remove();
    // Don't delete this timeout. It's necessary to perform the style change.
    // Chrome min. milliseconds: 1.
    // Firefox min. milliseconds: 9.
    setTimeout(function() {
            $("div#qr-container").find("img").attr("style", "");
        }, 10);

    // Load the same page with another secret
    $("#button-continue_to_generate").click(function(e) {
        e.preventDefault();

        var containerID = "<?php echo $container_id; ?>";

        $("#"+containerID).html("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");

        $.ajax({
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            type: 'POST',
            dataType: 'html',
            data: {
                page: 'include/ajax/double_auth.ajax',
                id_user: "<?php echo $config['id_user']; ?>",
                id_user_auth, id_user_auth,
                get_double_auth_generation_page: 1,
                containerID: containerID
            },
            complete: function(xhr, textStatus) {
                
            },
            success: function(data, textStatus, xhr) {
                // isNaN = is not a number
                if (isNaN(data)) {
                    $("#"+containerID).html(data);
                }
                // data is a number, convert it to integer to do the compare
                else if (Number(data) === -1) {
                    $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
                }
                else {
                    $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('Error').'</div></b>'; ?>");
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('There was an error loading the data').'</div></b>'; ?>");
            }
        });
    });

    // Load the validation page
    $("#button-continue_to_validate").click(function(e) {
        e.preventDefault();
        
        if (!confirm("<?php echo __('Are you introduced the code in the authenticator app yet?'); ?>")) {
            return false;
        }

        var containerID = "<?php echo $container_id; ?>";

        $("#"+containerID).html("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");

        $.ajax({
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            type: 'POST',
            dataType: 'html',
            data: {
                page: 'include/ajax/double_auth.ajax',
                id_user: "<?php echo $config['id_user']; ?>",
                id_user_auth: id_user_auth,
                get_double_auth_validation_page: 1,
                secret: secret,
                containerID: containerID
            },
            complete: function(xhr, textStatus) {
                
            },
            success: function(data, textStatus, xhr) {
                // isNaN = is not a number
                if (isNaN(data)) {
                    $("#"+containerID).html(data);
                }
                // data is a number, convert it to integer to do the compare
                else if (Number(data) === -1) {
                    $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
                }
                else {
                    $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('Error').'</div></b>'; ?>");
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('There was an error loading the data').'</div></b>'; ?>");
            }
        });
    });
</script>
    <?php
    $html .= ob_get_clean();

    echo $html;
    return;
}

// Get the validation page
$get_double_auth_validation_page = (bool) get_parameter('get_double_auth_validation_page');
if ($get_double_auth_validation_page) {
    $container_id = (string) get_parameter('containerID');
    $secret = (string) get_parameter('secret');

    if (empty($secret) || strlen($secret) != $secret_lenght) {
        echo json_encode(false);
        return;
    }

    $html = '';
    $html .= '<div class="left_align">';
    $html .= '<p>';
    $html .= __('Introduce a code generated by the app').'. ';
    $html .= __('If the code is valid, the double authentication will be activated').'.';
    $html .= '</p>';
    $html .= '</div>';
    $html .= '<br>';
    $html .= '<div class="center_align">';
    $html .= html_print_input_text('code', '', '', 50, $secret_lenght, true);
    $html .= '<div id="code_input_message" class="red"></div>';
    $html .= '<br><br>';
    $html .= '<div id="button-container" class="flex flex-space-around">';
    $html .= html_print_button(__('Validate code'), 'continue_to_validate', false, '', '', true);
    $html .= html_print_image('images/spinner.gif', true);
    $html .= '</div>';
    $html .= '</div>';

    ob_clean();
    ?>
<script type="text/javascript">
    $("div#button-container").find("img").hide();

    // Start the error message hiden
    $("div#code_input_message").hide();

    var secret = "<?php echo $secret; ?>";

    $("input#text-code").keypress(function() {
        $(this).removeClass("red").css('border-color', '#cbcbcb');
    });

    $("#button-continue_to_validate").click(function(e) {
        e.preventDefault();

        // Hide the error message
        $("div#code_input_message").hide();
        
        var containerID = "<?php echo $container_id; ?>";

        $("#button-continue_to_validate").prop('enabled', false).hide();
        $("div#button-container").find("img").show();

        $.ajax({
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            type: 'POST',
            dataType: 'json',
            data: {
                page: 'include/ajax/double_auth.ajax',
                id_user: "<?php echo $config['id_user']; ?>",
                id_user_auth: id_user_auth,
                validate_double_auth_code: 1,
                save: 1,
                secret: secret,
                code: function () {
                    return $("input#text-code").val();
                },
                containerID: containerID
            },
            complete: function(xhr, textStatus) {
                
            },
            success: function(data, textStatus, xhr) {
                // Valid code
                if (data === true) {
                    $("#"+containerID).html("<b><?php echo '<b><div class=\"green\">'.__('The code is valid, you can exit now').'</div></b>'; ?></b>");
                    $("input#checkbox-double_auth").prop( "checked", true );
                }
                // Invalid code
                else if (data === false) {
                    $("#button-continue_to_validate").prop('enabled', true).show();
                    $("div#button-container").find("img").hide();
                    $("input#text-code").addClass("red").css('border-color', '#c00');

                    $("div#code_input_message").html("<?php echo __('Invalid code'); ?>").show();
                }
                // Valid code but not saved
                else if (data === 1) {
                    $("#button-continue_to_validate").prop('enabled', true).show();
                    $("div#button-container").find("img").hide();
                    $("input#text-code").addClass("red").css('border-color', '#c00');

                    $("div#code_input_message").html("<?php echo __('The code is valid, but it was an error saving the data'); ?>").show();
                }
                // Authentication error
                else if (data === -1) {
                    $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
                }
                // Not expected results
                else {
                    $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('Error').'</div></b>'; ?>");
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $("#"+containerID).html("<?php echo '<b><div class=\"red\">'.__('There was an error loading the data').'</div></b>'; ?>");
            }
        });
    });
</script>
    <?php
    $html .= ob_get_clean();

    echo $html;
    return;
}

return;

