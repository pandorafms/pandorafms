<?php
//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2018 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

require_once($config['homedir']."/include/class/Tree.class.php");

class TreeGroup extends Tree {

	public function  __construct($type, $rootType = '', $id = -1, $rootID = -1, $serverID = false, $childrenMethod = "on_demand", $access = 'AR') {

		global $config;

        parent::__construct($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);

        $this->L2conditionInside = "AND (
            ta.id_grupo = " . $this->id . "
            OR tasg.id_group = " . $this->id . "
        )";
    }

    protected function getData() {
        if ($this->id == -1) {
            $this->getFirstLevel();
        } elseif ($this->type == 'group') {
            $this->getSecondLevel();
        } elseif ($this->type == 'agent') {
            $this->getThirdLevel();
        }
    }

    protected function getFirstLevel() {
        $processed_items = $this->getProcessedGroups();

			if (!empty($processed_items)) {
				// Filter by group name. This should be done after rerieving the items cause we need the possible items descendants
				if (!empty($this->filter['searchGroup'])) {
					// Save the groups which intersect with the user groups
					$groups = db_get_all_rows_filter('tgrupo', array('nombre' => '%' . $this->filter['searchGroup'] . '%'));
					if ($groups == false) $groups = array();
					$userGroupsACL = $this->userGroupsACL;
					$ids_hash = array_reduce($groups, function ($userGroups, $group) use ($userGroupsACL) {
						$group_id = $group['id_grupo'];
						if (isset($userGroupsACL[$group_id])) {
							$userGroups[$group_id] = $userGroupsACL[$group_id];
						}

						return $userGroups;
					}, array());

					$result = self::extractGroupsWithIDs($processed_items, $ids_hash);

					$processed_items = ($result === false) ? array() : $result;
				}

				// groupID filter. To access the view from tactical views f.e.
				if (!empty($this->filter['groupID'])) {
					$result = self::extractItemWithID($processed_items, $this->filter['groupID'], "group", $this->strictACL);

					$processed_items = ($result === false) ? array() : array($result);
				}
            }

		$this->tree = $processed_items;
    }
}

?>

