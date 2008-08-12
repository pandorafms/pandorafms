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
	<img src='images/pixel_green.png' width=40 height=18>  ".__('All Monitors OK')."</td>
	<td class='f9i'>
	<img src='images/pixel_red.png' width=40 height=18>  ".__('At least one monitor fails')."</td>
	<td class='f9i'>
	<img src='images/pixel_yellow.png' width=40 height=18>  ".__('Change between Green/Red state')."</td>
	<td class='f9i'>
	<img src='images/pixel_red.png' width=20 height=10>  ".__('Alert fired')."</td>
	<tr><td class='f9i'>
	<img src='images/pixel_gray.png' width=40 height=18>  ".__('Agent without monitors')."</td>
	<td class='f9i'>
	<img src='images/pixel_blue.png' width=40 height=18>  ".__('Agent without data')."</td>
	<td class='f9i'>
	<img src='images/pixel_fucsia.png' width=40 height=18>  ".__('Agent down')."</td>
	<td class='f9i'>
	<img src='images/pixel_green.png' width=20 height=10> ".__('Alert not fired')."</td>
	</tr>
	</table>
";
?>
