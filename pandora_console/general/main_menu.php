<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006-2007 Jose Navarro jose@jnavarro.net
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

require ("operation/menu.php");
if (! isset ($_SESSION["id_usuario"])) {
	echo '<div class="f10">' . __('You\'re not connected');
	echo '<br /><br />';
	echo '<form method="post" action="index.php?login=1">';
	echo '<div class="f9b">Login</div><input class="login" type="text" name="nick">';
	echo '<div class="f9b">Password</div><input class="login" type="password" name="pass">';
	echo '<div><input name="login" type="submit" class="sub" value="' . __('Login') .'"></div>';
	echo '<br />IP: <b class="f10">' . $REMOTE_ADDR . '</b><br /></div>';
	
} else {
	require ("godmode/menu.php");
	require ("links_menu.php");
}
?>
