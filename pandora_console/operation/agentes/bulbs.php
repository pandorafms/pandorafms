<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

echo "
<table cellpadding='3' cellspacing='3'>
	<tr><td class='f9i'>
	<img src='images/b_green.gif'> - ".$lang_label["green_light"]."</td>
	<td class='f9i'>
	<img src='images/b_red.gif'> - ".$lang_label["red_light"]."</td>
	<td class='f9i'>
	<img src='images/b_yellow.gif'> - ".$lang_label["yellow_light"]."</td>
	<tr><td class='f9i'>
	<img src='images/b_white.gif'> - ".$lang_label["no_light"]."</td>
	<td class='f9i'>
	<img src='images/b_blue.gif'> - ".$lang_label["blue_light"]."</td>
	<td class='f9i'>
	<img src='images/b_down.gif'> - ".$lang_label["broken_light"]."</td>
</table>
";
?>