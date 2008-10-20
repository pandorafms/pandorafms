<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
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
	<img src='images/pixel_blue.png' width=40 height=18>  ".__('Agent without monitors')."</td>
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
