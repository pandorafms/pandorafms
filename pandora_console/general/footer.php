<?PHP
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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

global $config;

echo "<center>";
echo '<a class="white_bold" target="_new" href="general/license/pandora_info_'.$config["language"].'.html">
Pandora FMS '.$pandora_version.' Build '.$build_version.'<br>'.
lang_string ("gpl_notice").'</a><br>';
	if (isset($_SERVER['REQUEST_TIME'])) {
		$time = $_SERVER['REQUEST_TIME'];
	} else {
		$time = time();
	}
	echo "<a class='white'>".$lang_label["gen_date"]." ".date("D F d, Y H:i:s", $time)."</a><br>";
echo "</center>";
?>
