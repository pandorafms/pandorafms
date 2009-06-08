<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/* You can safely delete this file */

function hello_extension_main () {
	/* Here you can do almost all you want! */
	echo "<h1>Hello world!</h1>";
	echo "This is a sample of minimal extension";
}

/* This adds a option in the operation menu */
add_operation_menu_option ('Hello plugin!');

/* This sets the function to be called when the extension is selected in the operation menu */
add_extension_main_function ('hello_extension_main');
?>
