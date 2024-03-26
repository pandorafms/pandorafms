<?php
/**
 * Modal LTS versions update manager.
 *
 * @category   Update Manager
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

// Begin.
global $config;

check_login();
// The ajax is in include/ajax/update_manager.php.
if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

if (is_ajax()) {
    $stopShowingModal = get_parameter('stopShowingModal', 0);
    if ($stopShowingModal === '1') {
        db_process_sql_update(
            'tusuario',
            ['stop_lts_modal' => '1'],
            ['id_user' => $config['id_user']],
        );
    }

    return;
}

require_once $config['homedir'].'/vendor/autoload.php';

$php_version = phpversion();
$php_version_array = explode('.', $php_version);
if ($php_version_array[0] < 7) {
    include_once 'general/php_message.php';
}

?>

<!-- Lts Updates. -->
<div id="lts-updates" title="
    <?php echo __('LTS versions'); ?>
    " class="invisible">
    <div style="display: flex; justify-content: space-between">
        <div style="width: 250px; padding: 36px">
            <?php
            echo html_print_image(
                'images/info-warning.svg',
                true,
                [ 'style' => 'padding-top: 30px' ]
            );
            ?>
        </div>
        <div style="padding: 5px 90px 5px 5px;">
            <p class="lato font_10pt bolder">
                <?php
                echo __('There are two types of versions in Pandora FMS: the LTS versions (Long-Term Support), e.g: 772 LTS, and the RRR (Regular Rolling Release) versions, e.g: 771, 773, 774, 775.');
                ?>
            </p>
            <p class="lato font_10pt bolder">
                <?php
                echo __('LTS versions have frequent, periodic updates (fixes), correcting both security problems and critical bugs detected in the tool. These are the versions we recommend to use in production environments.');
                ?>
            </p>
            <p class="lato font_10pt bolder">
                <?php
                echo __('RRR versions incorporate new features in each version, as well as bug fixes, but due to their dynamic nature, errors are more likely.');
                ?>
            </p>
        </div>
    </div>
</div>
<?php
$stop_lts_modal = db_get_value('stop_lts_modal', 'tusuario', 'id_user', $config['id_user']);
if ($stop_lts_modal === '0') {
    ?>
<script type="text/javascript">
    $(document).ready(function() {
        // Lts Updates.
        $("#lts-updates").dialog({
            resizable: true,
            draggable: true,
            modal: true,
            width: 740,
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            closeOnEscape: true,
            buttons: [{
                text: "OK",
                click: function() {
                    var no_show_more = $('#checkbox-no_show_more').is(':checked');
                    if (no_show_more === true){
                        $.ajax({
                            url: 'ajax.php',
                            data: {
                                page: 'godmode/update_manager/modal_lts_update',
                                stopShowingModal: 1,
                            },
                            type: 'POST',
                            async: false,
                            dataType: 'json'
                        });
                    }
                    $(this).dialog("close");
                }
            }],
            open: function(event, ui) {
                $(".ui-dialog-titlebar-close").hide();
                $("div.ui-dialog-buttonset").addClass('flex-rr-sb-important');
                $("div.ui-dialog-buttonset").append(`
                <div class="welcome-wizard-buttons">
                    <label class="flex-row-center">
                        <input type="checkbox" id="checkbox-no_show_more" class="welcome-wizard-do-not-show"/>
                        <?php echo __('Do not show anymore'); ?>
                    </label>
                </div>
                `);
            }
        });
    });
</script>
    <?php
}
