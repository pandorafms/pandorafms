<?php
// Pandora FMS - the Flexible Monitoring System
// =============================================
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


echo "<center>";


echo '<a class="white_bold" target="_new" href="general/license/pandora_info_'.$config["language"].'.html">Pandora FMS '.$pandora_version.' - Build '.$build_version.'<br>';
echo '<a class="white">'. __('Page generated at') . ' '. format_datetime ($time);

if ((isset($develop_bypass)) AND ($develop_bypass == 1)) {
	echo ' - Saved '.format_numeric ($sql_cache["saved"]).' Queries';
}
echo '</a><br>';
echo "<a href='http://www.mozilla.org'><img src='images/firefox.gif' align='middle' title='Pandora FMS console is best viewed with firefox'></a>";
echo "</center>";

?>
