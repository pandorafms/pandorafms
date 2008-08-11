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

echo "
<table cellpadding='3' cellspacing='3'>
	<tr><td class='f9i'>
	<img src='images/pixel_green.png' width=40 height=18>  ".__('green_light')."</td>
	<td class='f9i'>
	<img src='images/pixel_red.png' width=40 height=18>  ".__('red_light')."</td>
	<td class='f9i'>
	<img src='images/pixel_yellow.png' width=40 height=18>  ".__('yellow_light')."</td>
	<td class='f9i'>
	<img src='images/pixel_red.png' width=20 height=10>  ".__('fired')."</td>
	<tr><td class='f9i'>
	<img src='images/pixel_gray.png' width=40 height=18>  ".__('no_light')."</td>
	<td class='f9i'>
	<img src='images/pixel_blue.png' width=40 height=18>  ".__('blue_light')."</td>
	<td class='f9i'>
	<img src='images/pixel_fucsia.png' width=40 height=18>  ".__('broken_light')."</td>
	<td class='f9i'>
	<img src='images/pixel_green.png' width=20 height=10> ".__('not_fired')."</td>
	</tr>
	</table>
";
?>
