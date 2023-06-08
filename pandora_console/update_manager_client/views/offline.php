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
 * Copyright (c) 2005-2023 Pandora FMS
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

    <script type="text/javascript">
        if (typeof $ != "function") {
            // Dynamically include jquery if not added to this page.
            document.write('<script type="text/javascript" src="<?php echo $asset('resources/javascript/jquery-3.3.1.min.js'); ?>"></'+'script>');
            document.write('<script type="text/javascript" src="<?php echo $asset('resources/javascript/jquery-ui.min.js'); ?>"></'+'script>');
            document.write('<script type="text/javascript" src="<?php echo $asset('resources/javascript/jquery.fileupload.js'); ?>"></'+'script>');
        }
    </script>
    <script src="<?php $asset('resources/javascript/umc.js'); ?>?v=<?php echo $config['current_package']; ?>" type="text/javascript"></script>
    <script src="<?php $asset('resources/javascript/umc_offline.js'); ?>?v=<?php echo $config['current_package']; ?>" type="text/javascript"></script>
    <script src="<?php $asset('resources/javascript/jquery.fileupload.js'); ?>" type="text/javascript"></script>
    <script src="<?php $asset('resources/javascript/jquery.iframe-transport.js'); ?>" type="text/javascript"></script>
    <script src="<?php $asset('resources/javascript/jquery.knob.js'); ?>" type="text/javascript"></script>
    <link rel="stylesheet" href="<?php $asset('resources/styles/um.css'); ?>?v=<?php echo $config['current_package']; ?>">
</head>

<div id="box_offline">
    <form id="form-offline_update" method="post" enctype="multipart/form-data" class="fileupload_form">
        <input type="hidden" name="page" value="<?php echo $ajaxPage; ?>"/>
        <input type="hidden" name="ajax" value="1" />
        <input type="hidden" name="action" value="uploadOUM" />
        <input type="hidden" name="cors" value="<?php echo $authCode; ?>" />
        <input type="hidden" name="mode" value="offline" />

        <div></div>
        <ul id="result"></ul>
    </form>
</div>

<script type="text/javascript">
    var texts = {
        'dropZoneText': "<?php echo __('Drop the package here or'); ?>",
        'browse': "<?php echo __('Browse it'); ?>",
        'uploadSuccess': "<?php echo __('The package has been uploaded successfully.'); ?>",
        'uploadMessage': "<?php echo __("Please keep in mind that this package is going to override your actual %s files and that it's recommended to conduct a backup before continuing the updating process.", get_product_name()); ?>",
        'clickToStart': "<?php echo __('Click on the file below to begin.'); ?>",
        'ensureUpdate': "<?php echo __('This action will upgrade this console to version '); ?> ",
        'ensureServerUpdate': "<?php echo __('This action will upgrade all servers to version '); ?> ",
        'ensure': "<?php echo __('Are you sure?'); ?>",
        'updatingTo': "<?php echo __('Updating to'); ?> ",
        'preventExitMsg': "<?php echo __('Do you really want to leave our brilliant application?'); ?>",
        'alreadyUpdated': "<?php echo __('There are no updates available'); ?>",
        'searchingUpdates': "<?php echo __('Searching for updates...'); ?>",
        'updateText': "<?php echo __('Package'); ?>",
        'updated': "<?php echo __('Successfully updated.'); ?>",
        'rejectedUpdate': "<?php echo __('Package rejected. These package changes will not apply.'); ?>",
        'warning': "<?php echo __('Warning'); ?>",
        'unoficialWarning': "<?php echo __('This update does not correspond with next version of %s. Are you sure you want to install it?', get_product_name()); ?>",
        'unoficialServerWarning': "<?php echo __('This server update does not correspond with current console version. Are you sure you want to install it?'); ?>",
        'invalidPackage': "<?php echo __('File name does not match required format: package_NUMBER.oum or pandorafms_server[_enterprise]-7.0NG.NUMBER_x86[_64].tar.gz, you can use numbers with decimals.'); ?>",
        'fileList': "<?php echo __('Files included in this package'); ?>",
        'ignoresign': "<?php echo __('Ignore'); ?>",
        'verifysigntitle': "<?php echo __('Verify package signature'); ?>",
        'verifysigns': "<?php echo __('Copy into the textarea the signature validation token you can retrieve from %s and press OK to verify the package, press ignore to avoid signature verification', 'https://support.pandorafms.com'); ?>",
        'notGoingToInstallUnoficialServerWarning': "<?php echo __('This server update does not correspond with current console version and is not going to be installed unless patches are allowed. Please enable patches in update manager settings.'); ?>",
        'notGoingToInstallUnoficialWarning': "<?php echo __('This update does not correspond with next version of %s and is not going to be installed unless patches are allowed. Please enable patches in update manager settings.', get_product_name()); ?>",
    }

    var clientMode = <?php echo $mode; ?>;
    var insecureMode = <?php echo ($insecure === true) ? 'true' : 'false'; ?>;
    var ajaxPage = '<?php echo $ajaxPage; ?>';
    window.onload = function() {
        form_upload(
            "<?php echo $ajax; ?>",
            "<?php echo $authCode; ?>",
            "<?php echo $version; ?>"
        );
    }

    var ImSureWhatImDoing = <?php echo (false === $allowOfflinePatches) ? 'false' : 'true'; ?>;

</script>
