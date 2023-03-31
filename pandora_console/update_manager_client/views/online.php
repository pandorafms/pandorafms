<?php
/**
 * UpdateManager Client UI welcome view.
 *
 * DO NOT EDIT THIS FILE.
 *
 * @category   View
 * @package    Update Manager UI View
 * @subpackage View
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
global $config;
?>
<head>
    <link rel="stylesheet" href="<?php $asset('resources/styles/um.css'); ?>?v=<?php echo $config['current_package']; ?>">
    <script src="<?php $asset('resources/javascript/umc.js'); ?>?v=<?php echo $config['current_package']; ?>" type="text/javascript"></script>
</head>


<div id="box_online">
    <div>
        <span class="loading" style="font-size: 18pt; display: none;">
            <img src="images/wait.gif">
        </span>

        <p style="font-weight: 600;"><?php echo __('The latest version of package installed is').':'; ?></p>
        <div id="pkg_version"><?php echo $version; ?></div>

        <div class="content">
            <?php
            if (empty($error) !== true) {
                ?>
                <h2>
                    <?php echo $error; ?>
                </h2>
                <?php
            } else {
                // Updates.
                ?>
                <div id="um-loading">
                    <p id="loading-msg"></p>
                </div>
                <div id="um-updates">
                </div>
                <div id="um-buttons" style="display:none;">
                    <button id="um-next"><?php echo __('Update to next version'); ?></button>
                    <button id="um-last"><?php echo __('Update to latest version'); ?></button>
                </div>

                <div id="um-result"></div>
                <div id="um-progress">
                    <h3 id="um-progress-version"></h3>
                    <div class="um-progress-bar-container general">
                        <span id="um-progress-general-label">0 %</span>
                        <div id="um-progress-general" class="um-progress-bar general"></div>
                    </div>

                    <h5 id="um-progress-description"></h5>
                    <div class="um-progress-bar-container task">
                        <span id="um-progress-task-label">0 %</span>
                        <div id="um-progress-task" class="um-progress-bar task"></div>
                    </div>
                </div>
                <div id="um-update-details">
                    <div id="um-update-details-header"></div>
                    <div id="um-update-details-content"></div>
                </div>

                <script type="text/javascript">
                    var texts = {
                        'updatingTo': "<?php echo __('Updating to'); ?> ",
                        'preventExitMsg': "<?php echo __('Do you really want to leave our brilliant application?'); ?>",
                        'alreadyUpdated': "<?php echo __('There are no updates available'); ?>",
                        'searchingUpdates': "<?php echo __('Searching for updates...'); ?>",
                        'updateText': "<?php echo __('Package'); ?>",
                        'updated': "<?php echo __('Successfully updated.'); ?>",
                    }

                    var clientMode = '<?php echo $mode; ?>';
                    var ajaxPage = '<?php echo $ajaxPage; ?>';

                    window.onload = function() {
                        var bsearch = document.getElementById('um-search');
                        var bnext = document.getElementById('um-next');
                        var blast = document.getElementById('um-last');
                        var result = document.getElementById('um-result');

                        <?php
                        if (isset($running) === true && $running === true) {
                            ?>
                            showProgress('<?php echo $ajax; ?>', '<?php echo $authCode; ?>');
                            <?php
                        } else {
                            ?>
                            // Search.
                            searchUpdates('<?php echo $ajax; ?>', '<?php echo $authCode; ?>');
                            <?php
                        }
                        ?>

                        bnext.addEventListener('click', function() {
                            blast.setAttribute('disable', true);
                            result.innerHTML = '';
                            umConfirm({
                                message: "<?php echo __('This action will upgrade this console to version '); ?> "+nextUpdateVersion+". <?php echo __('Are you sure?'); ?>",
                                title: "<?php echo __('Update to'); ?> "+nextUpdateVersion,
                                onAccept: function() {
                                    updateNext({
                                        url: '<?php echo $ajax; ?>',
                                        auth: '<?php echo $authCode; ?>',
                                        success: function(d) {
                                            umUINextUpdate(d.version);

                                            if (d.messages == null) {
                                                result.innerHTML = umSuccessMsg(
                                                    texts.updated
                                                );
                                            } else {
                                                result.innerHTML = umErrorMsg(
                                                    d.messages
                                                );
                                            }
                                        },
                                        error: function(e, r) {
                                            if (typeof r != "undefined" ) {
                                                result.innerHTML = umErrorMsg(
                                                    '<?php echo __('Failed to update to '); ?>' + nextUpdateVersion+' '+r
                                                );
                                            } else {
                                                result.innerHTML = umErrorMsg(
                                                    '<?php echo __('Failed to update to '); ?>' + nextUpdateVersion+' RC'+e
                                                );
                                            }
                                        },
                                    });
                                }
                            });

                            blast.setAttribute('disable', false);
                        });

                        blast.addEventListener('click', function() {
                            blast.setAttribute('disable', true);
                            result.innerHTML = '';
                            umConfirm({
                                message: "<?php echo __('This action will upgrade this console to version '); ?> "+lastUpdateVersion+". <?php echo __('Are you sure?'); ?>",
                                title: "<?php echo __('Update to'); ?> "+lastUpdateVersion,
                                onAccept: function() {
                                    updateLatest({
                                        url: '<?php echo $ajax; ?>',
                                        auth: '<?php echo $authCode; ?>',
                                        success: function(d) {
                                            umUINextUpdate(d.version);

                                            if (d.messages == null) {
                                                result.innerHTML = umSuccessMsg(
                                                    texts.updated
                                                );
                                            } else {
                                                result.innerHTML = umErrorMsg(
                                                    d.messages
                                                );
                                            }
                                        },
                                        error: function(e, r) {
                                            if (typeof r != "undefined" ) {
                                                result.innerHTML = umErrorMsg(r);
                                            } else {
                                                result.innerHTML = umErrorMsg(
                                                    '<?php echo __('Failed to update:'); ?> RC'+e
                                                );
                                            }
                                        },
                                    });
                                }
                            });

                            blast.setAttribute('disable', false);
                        });

                    }
                </script>
                <?php
            }
            ?>

        </div>
    </div>


    <div class="wu-bg">
        <img class="wu-box" src="images/Cube.png">
        <img class="wu-gear" src="images/Engranaje.png">
    </div>
</div>
