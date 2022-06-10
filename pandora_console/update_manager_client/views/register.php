<?php
/**
 * UpdateManager Client UI registration process view.
 *
 * DO NOT EDIT THIS FILE.
 *
 * @category   View
 * @package    Update Manager UI View
 * @subpackage View
 * @version    1.0.0
 * @license    See below
 *
 * ______ ___ _______ _______ ________
 * | __ \.-----.--.--.--| |.-----.----.-----. | ___| | | __|
 * | __/| _ | | _ || _ | _| _ | | ___| |__ |
 * |___| |___._|__|__|_____||_____|__| |___._| |___| |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * ============================================================================
 */

$product_name = get_product_name(); ?>
<head>

    <script type="text/javascript">
        if (typeof $ != "function") {
            // Dynamically include jquery if not added to this page.
            document.write('<script type="text/javascript" src="<?php echo $asset('resources/javascript/jquery-3.3.1.min.js'); ?>"></'+'script>');
            document.write('<script type="text/javascript" src="<?php echo $asset('resources/javascript/jquery-ui.min.js'); ?>"></'+'script>');
        }
    </script>
    <script src="<?php $asset('resources/javascript/umc.js'); ?>" type="text/javascript"></script>
    <link rel="stylesheet" href="<?php $asset('resources/styles/jquery-ui.min.css'); ?>">
    <link rel="stylesheet" href="<?php $asset('resources/styles/um.css'); ?>">
    <link rel="stylesheet" href="<?php $asset('resources/styles/pandora.css'); ?>">
</head>

<div id="registration_wizard" title="
    <?php echo __('Register to WARP Update'); ?>
    " class="invisible">
    <div class="register_update_manager">
        <?php echo html_print_image('images/pandora_circle_big.png', true); ?>
    </div>

    <div class="newsletter_div">
        <?php echo __('Keep this %s console up to date with latest updates.', $product_name); ?>
    </div>

    <div class="license_text both">
        <p>
            <?php
            echo __(
                'When you subscribe to the WARP update service for %s, you accept that we
            register your %s instance as an identifier on a database owned by %s. This data will solely be used to
            provide you with information about %s and will not be conceded to third parties. You can unregister from
            said database at any time from the WARP update options.',
                $product_name,
                $product_name,
                $product_name,
                $product_name
            );
            ?>
        </p>

        <p>
            <?php echo __('Visit our privacy policy for more information'); ?>:
            <a href="https://pandorafms.com/privacy-policy/" target="_blank">https://pandorafms.com/privacy-policy/</a>
        </p>

        <input id="registration-email" type="email" placeholder="<?php echo __('Your email'); ?>" />
    </div>

    <div class="submit_buttons_container">
        <div class="left">
            <?php
            echo html_print_submit_button(
                __('Cancel'),
                'cancel_registration',
                false,
                'class="ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel"',
                true
            );
            ?>
        </div>
        <div class="right">
            <?php
            echo html_print_submit_button(
                __('OK!'),
                'register',
                false,
                'class="ui-widget
            ui-state-default ui-corner-all ui-button-text-only sub ok submit-next w100px"',
                true
            );
            ?>
        </div>
    </div>
</div>

<!-- Verification modal.. -->
<div id="reg_ensure_cancel" title="Confirmation Required" class="invisible">
    <div class="font_12_20">
        <?php echo __('Are you sure you don\'t want to use WARP update?'); ?>
        <p>
            <?php
            echo __(
                'You will need to update your system manually, through source code or RPM
            packages to be up to date with latest updates.'
            );
            ?>
        </p>
    </div>

</div>

<!-- Results modal. -->
<div id="reg_result" title="Registration process result" class="invisible">
    <div id="reg_result_content" class="font_12_20">
    </div>
</div>

<script type="text/javascript">
var clientMode = '<?php echo $mode; ?>';
var ajaxPage = '<?php echo $ajaxPage; ?>';
$(document).ready(function() {
  $("#registration_wizard").dialog({
    resizable: true,
    draggable: true,
    modal: true,
    width: 740,
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    closeOnEscape: false,
    open: function(event, ui) {
      $(".ui-dialog-titlebar-close").hide();
    }
  });
});

// CLICK EVENTS: Cancel and Registration
$("#submit-cancel_registration").click(function(e) {
  e.preventDefault();
  $("#reg_ensure_cancel").dialog({
    buttons: [
      {
        text: "No",
        class: "submit-cancel",
        click: function() {
          $(this).dialog("close");
        }
      },
      {
        text: "Yes",
        class: "submit-next",
        click: function() {
          ajax({
            url: "<?php echo $ajax; ?>",
            cors: "<?php echo $authCode; ?>",
            page: ajaxPage,
            dataType: 'json',
            data: { action: 'unregister' },
            success: function(data) {
                $("#reg_ensure_cancel").dialog("close");
                $("#registration_wizard").dialog("close");
            },
            error: function(code, rq) {
                $("#reg_ensure_cancel").html(rq.response);
            }
          });
        }
      }
    ]
  });

  $("#reg_ensure_cancel").dialog("open");
});

$("#submit-register").click(function() {
  if (validateEmail($('#registration-email').val())) {
    // All fields are required.
    ajax({
        url: "<?php echo $ajax; ?>",
        cors: "<?php echo $authCode; ?>",
        page: ajaxPage,
        dataType: 'json',
        data: {
            action: 'register',
            email: $('#registration-email').val()
        },
        success: function(data) {
        let cl = "";
        data = data.result;

        if (data.error != null) {
            cl = 'error';
            $("#reg_result_content").html(
                '<?php echo __('Unsuccessful subscription'); ?><br>'
                + data.error
            );
        } else {
            $("#reg_result_content").html(
                '<?php echo __('Pandora successfully subscribed with UID: '); ?>'
                + data.result
            );
        }

        $("#reg_result").addClass(cl);
        $("#reg_result").dialog({
            buttons: [
            {
                text: "OK",
                class: "submit-next",
                click: function() {
                    $(this).dialog("close");
                    $("#registration_wizard").dialog("close");
                    location.reload();
                }
            }
            ]
        });
        },
        error: function(code, rq) {
        let cl = "error"
        let msg = '';

        try {
            let json = JSON.parse(rq.response);
            msg = json.error;
        } catch (error) {
            msg = rq.response;
        }

        $("#reg_result_content").html(msg);
        $("#reg_result").addClass(cl);
        $("#reg_result").dialog({
            buttons: [
            {
                text: "OK",
                class: "submit-next",
                click: function() {
                $(this).dialog("close");
                }
            }
            ]
        });
        }
    });
  } else {
    $('#registration-email').css('border', '1px solid red');
  }
});

</script>
