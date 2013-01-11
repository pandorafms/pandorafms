<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Component groups
 */


/**
 * Format components groups in tree way.
 *
 * @param array $groups The list of groups to create the treefield list.
 * @param integer $parent The id_group of parent actual scan branch.
 * @param integer $deep The level of profundity in the branch.
 *
 * @return array The treefield list of components groups.
 */
function component_groups_get_groups_tree_recursive($groups, $parent = 0, $deep = 0) {
	$return = array();

	foreach ($groups as $key => $group) { 
		if ($group['parent'] == $parent) {
			$group['deep'] = $deep;
			
			$branch = component_groups_get_groups_tree_recursive($groups, $key, $deep + 1);
			if (empty($branch)) {
				$group['hash_branch'] = false;
			}
			else {
				$group['hash_branch'] = true;
			}
			$return = $return + array($key => $group) + $branch;
		}
	}

	return $return;
}

?>
