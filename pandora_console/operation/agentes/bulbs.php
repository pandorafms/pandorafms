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

echo '
<table cellpadding="3" cellspacing="3">
	<tr>
	<td class="f9i">' . print_status_image(STATUS_AGENT_OK, __('All Monitors OK'), true) . __('All Monitors OK') . '</td>
	<td class="f9i">' . print_status_image(STATUS_MODULE_CRITICAL, __('At least one monitor fails'), true) . __('At least one monitor fails') . '</td>
	<td class="f9i">' . print_status_image(STATUS_MODULE_WARNING, __('Change between Green/Red state'), true) . __('Change between Green/Red state') . '</td>

	<td class="f9i">' . print_status_image(STATUS_ALERT_FIRED, __('Alert fired'), true) . __('Alert fired') . '</td>
	<td class="f9i">' . print_status_image(STATUS_ALERT_DISABLED, __('Alert disabled'), true) . __('Alerts disabled') . '</td>

	</tr><tr>

	<td class="f9i">' . print_status_image(STATUS_AGENT_NO_MONITORS, __('Agent without monitors'), true) . __('Agent without monitors') . '</td>
	<td class="f9i">' . print_status_image(STATUS_AGENT_NO_DATA, __('Agent without data'), true) . __('Agent without data') .  '</td>
	<td class="f9i">' . print_status_image(STATUS_AGENT_DOWN, __('Agent down'), true) . __('Agent down') . '</td>

	<td class="f9i">' . print_status_image(STATUS_ALERT_NOT_FIRED, __('Alert not fired'), true) . __('Alert not fired') . '</td>

	</tr>
	</table>
';
?>
