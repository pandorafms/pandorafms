<?php

// Pandora FMS - the Flexible Monitoring System
// =============================================
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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,
// USA.

if (isset($_SERVER['REQUEST_TIME'])) {
	$time = $_SERVER['REQUEST_TIME'];
} else {
	$time = time();
}

echo '<a class="white_bold" target="_new" href="general/license/pandora_info_'.$config["language"].'.html">Pandora FMS '.$pandora_version.' - Build '.$build_version.'<br>';
echo '<a class="white">'. __('Page generated at') . ' '. print_timestamp ($time, true, array ("prominent" => "timestamp")); //Always use timestamp here

if ((isset($develop_bypass)) AND ($develop_bypass == 1)) {
	echo ' - Saved '.format_numeric ($sql_cache["saved"]).' Queries';
}
echo '</a><br />';
echo '<a href="http://www.mozilla-europe.org/en/firefox/"><img src="'.$config["homeurl"].'/images/firefox.png" align="middle" title="'.__('Pandora FMS console is best viewed with Firefox web browser').'" /></a>';
?>
