<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

echo "
<table cellpadding='3' cellspacing='3'>
	<tr><td class='f9i'>
	<img src='images/pixel_green.png' width=40 height=18>  ".$lang_label["green_light"]."</td>
	<td class='f9i'>
	<img src='images/pixel_red.png' width=40 height=18>  ".$lang_label["red_light"]."</td>
	<td class='f9i'>
	<img src='images/pixel_yellow.png' width=40 height=18>  ".$lang_label["yellow_light"]."</td>
	<td class='f9i'>
	<img src='images/pixel_red.png' width=20 height=10>  ".$lang_label["fired"]."</td>
	<tr><td class='f9i'>
	<img src='images/pixel_gray.png' width=40 height=18>  ".$lang_label["no_light"]."</td>
	<td class='f9i'>
	<img src='images/pixel_blue.png' width=40 height=18>  ".$lang_label["blue_light"]."</td>
	<td class='f9i'>
	<img src='images/pixel_fucsia.png' width=40 height=18>  ".$lang_label["broken_light"]."</td>
	<td class='f9i'>
	<img src='images/pixel_green.png' width=20 height=10> ".$lang_label["not_fired"]."</td>
	</tr>
	</table>
";
?>
