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

ui_require_css_file('update_manager', 'godmode/update_manager/');

?>
<script type="text/javascript">
	<?php
	echo "var drop_the_package_here_or ='" . __('Drop the package here or') . "';\n";
	echo "var browse_it ='" . __('browse it') . "';\n";
	echo "var the_package_has_been_uploaded_successfully ='" . __('The package has been uploaded successfully.') . "';\n";
	echo "var remember_that_this_package_will ='" . __('Remember that this package will override the actual Pandora FMS files and it is recommended to do a backup before continue with the update.') . "';\n";
	echo "var click_on_the_file_below_to_begin ='" . __('Click on the file below to begin.') . "';\n";
	echo "var updating ='" . __('Updating') . "';\n";
	echo "var package_updated_successfully ='" . __('Package updated successfully.') . "';\n";
	echo "var if_there_are_any_database_change ='" . __('If there are any database change, it will be applied on the next login.') . "';\n";
	echo "var package_not_updated ='" . __('Package not updated.') . "';\n";
	?>
</script>

<form id="form-offline_update" method="post" enctype="multipart/form-data" class="fileupload_form">
	<div></div>
	<ul></ul>
</form>

<script src="include/javascript/jquery.fileupload.js"></script>
<script src="include/javascript/jquery.iframe-transport.js"></script>
<script src="include/javascript/jquery.knob.js"></script>

<script src="include/javascript/update_manager.js"></script>

<script type="text/javascript">
	form_upload();
</script>