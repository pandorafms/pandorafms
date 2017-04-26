<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

// ui_require_css_file('update_manager', 'godmode/update_manager/');

check_login ();

// ui_require_css_file('update_manager', 'godmode/update_manager/');
if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}
$baseurl = ui_get_full_url(false, false, false, false);

?>

<!-- Add the stylesheet here cause somehow the 'ui_require_css_file' is not working on the metaconsole and there is no time to fix it -->
<link rel="stylesheet" type="text/css" href="<?php echo $baseurl; ?>/godmode/update_manager/update_manager.css">

<script type="text/javascript">
	var drop_the_package_here_or = "<?php echo __('Drop the package here or'); ?>\n";
	var browse_it = "<?php echo __('browse it'); ?>\n";
	var the_package_has_been_uploaded_successfully = "<?php echo __('The package has been uploaded successfully.'); ?>\n";
	var remember_that_this_package_will = "<?php echo __('Remember that this package will override the actual Pandora FMS files and it is recommended to do a backup before continue with the update.'); ?>\n";
	var click_on_the_file_below_to_begin = "<?php echo __('Click on the file below to begin.'); ?>\n";
	var updating = "<?php echo __('Updating'); ?>\n";
	var package_updated_successfully = "<?php echo __('Package updated successfully.'); ?>\n";
	var if_there_are_any_database_change = "<?php echo __('If there are any database change, it will be applied on the next login.'); ?>\n";
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
	var text1_mr_file = "<?php echo __('There are a new database changes available to apply. Do you want to start the DB update process?'); ?>\n";
	var text2_mr_file = "<?php echo __('We recommend launch a '); ?>\n";
	var text3_mr_file = "<?php echo __('planned downtime'); ?>\n";
	var text4_mr_file = "<?php echo __(' to this process'); ?>\n";
	var text1_package_file = "<?php echo __('There is a new update available'); ?>\n";
	var text2_package_file = "<?php echo __('There is a new update available to apply. Do you want to start the update process?'); ?>\n";
	var applying_mr = "<?php echo __('Applying DB MR'); ?>\n";
	var cancel_button = "<?php echo __('Cancel'); ?>\n";
	var ok_button = "<?php echo __('Ok'); ?>\n";
	var apply_mr_button = "<?php echo __('Apply MR'); ?>\n";
	var apply_button = "<?php echo __('Apply'); ?>\n";
</script>

<form id="form-offline_update" method="post" enctype="multipart/form-data" class="fileupload_form">
	<div></div>
	<ul></ul>
</form>

<script src="<?php echo $baseurl; ?>/include/javascript/jquery.fileupload.js"></script>
<script src="<?php echo $baseurl; ?>/include/javascript/jquery.iframe-transport.js"></script>
<script src="<?php echo $baseurl; ?>/include/javascript/jquery.knob.js"></script>

<script src="<?php echo $baseurl; ?>/include/javascript/update_manager.js"></script>

<script type="text/javascript">
	form_upload("<?php echo $baseurl; ?>");
</script>
