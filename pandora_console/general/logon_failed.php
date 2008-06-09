<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, <slerena@gmail.com>
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

?>

<div class='databox' id='login'>
	<div id='login_f' class='databox'>
		<h1 id="log_f" style='margin-top: 0px;' class="error"><?php echo $lang_label['err_auth']; ?></h1>
		<div id='noa' style='width:50px' >
			<img src='images/noaccess.png' alt='No access'>
		</div>

		<div style='width: 350px'>
			<a href="index.php"><img src="images/pandora_logo.png" border="0"></a><br>
			<?php echo $pandora_version; ?>
		</div>

		<div class="msg"><?php echo $lang_label["err_auth_msg"]; ?></div>
	</div>
</div>
