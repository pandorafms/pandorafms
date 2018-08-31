<?php
//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

class Tree {
	protected $type = null;
	protected $rootType = null;
	protected $id = -1;
	protected $rootID = -1;
	protected $serverID = false;
	protected $tree = array();
	protected $filter = array();
	protected $childrenMethod = "on_demand";

	protected $userGroupsACL;
	protected $userGroups;
	protected $userGroupsArray;

	protected $strictACL = false;
	protected $acltags = false;
	protected $access = false;

	public function __construct($type, $rootType = '', $id = -1, $rootID = -1, $serverID = false, $childrenMethod = "on_demand", $access = 'AR') {

		$this->type = $type;
		$this->rootType = !empty($rootType) ? $rootType : $type;
		$this->id = $id;
		$this->rootID = !empty($rootID) ? $rootID : $id;
		$this->serverID = $serverID;
		$this->childrenMethod = $childrenMethod;
		$this->access = $access;

		$userGroupsACL = users_get_groups(false, $this->access);
		$this->userGroupsACL = empty($userGroupsACL) ? false : $userGroupsACL;
		$this->userGroups = $this->userGroupsACL;
		$this->userGroupsArray = array_keys($this->userGroups);

		global $config;
		include_once($config['homedir']."/include/functions_servers.php");
		include_once($config['homedir']."/include/functions_modules.php");
		require_once($config['homedir']."/include/functions_tags.php");
		enterprise_include_once("include/functions_agents.php");

		if (is_metaconsole()) enterprise_include_once("meta/include/functions_ui_meta.php");

		$this->strictACL = false;

		$this->acltags = tags_get_user_groups_and_tags($config['id_user'], $this->access);
	}

	public function setFilter($filter) {
		$this->filter = $filter;
	}

	protected function getDisplayHierarchy() {
		return $this->filter['searchHirearchy'] ||
			(empty($this->filter['searchAgent']) && empty($this->filter['searchModule']));
	}

	protected function getEmptyModuleFilterStatus() {
		return (
			!isset($this->filter['statusModule']) ||
			$this->filter['statusModule'] == -1
		);
	}

	protected function getAgentStatusFilter ($status = -1) {
		if ($status == -1)
			$status = $this->filter['statusAgent'];

		$agent_status_filter = "";
		switch ($status) {
			case AGENT_STATUS_NOT_INIT:
				$agent_status_filter = " AND (ta.total_count = 0
											OR ta.total_count = ta.notinit_count) ";
				break;
			case AGENT_STATUS_CRITICAL:
				$agent_status_filter = " AND ta.critical_count > 0 ";
				break;
			case AGENT_STATUS_WARNING:
				$agent_status_filter = " AND (ta.critical_count = 0
											AND ta.warning_count > 0) ";
				break;
			case AGENT_STATUS_UNKNOWN:
				$agent_status_filter = " AND (ta.critical_count = 0
											AND ta.warning_count = 0
											AND ta.unknown_count > 0) ";
				break;
			case AGENT_STATUS_NORMAL:
				$agent_status_filter = " AND (ta.critical_count = 0
											AND ta.warning_count = 0
											AND ta.unknown_count = 0
											AND ta.normal_count > 0) ";
				break;
		}

		return $agent_status_filter;
	}

	protected function getModuleStatusFilter () {
		$show_init_condition = ($this->filter['show_not_init_agents'])
			? ""
			: " AND ta.notinit_count <> ta.total_count";

		if ($this->getEmptyModuleFilterStatus()) {
			return $show_init_condition;
		}

		$field_filter = modules_get_counter_by_states($this->filter['statusModule']);
		if ($field_filter === false) return " AND 1=0";

		return "AND ta.$field_filter > 0" . $show_init_condition;
	}

	protected function getModuleStatusFilterFromTestado ($state = false) {
		$selected_status = ($state !== false)
			? $state
			: $this->filter['statusModule'];

		switch ($selected_status) {
			case AGENT_MODULE_STATUS_CRITICAL_ALERT:
			case AGENT_MODULE_STATUS_CRITICAL_BAD:
				return " AND (tae.estado = ".AGENT_MODULE_STATUS_CRITICAL_ALERT."
											OR tae.estado = ".AGENT_MODULE_STATUS_CRITICAL_BAD.") ";
			case AGENT_MODULE_STATUS_WARNING_ALERT:
			case AGENT_MODULE_STATUS_WARNING:
				return " AND (tae.estado = ".AGENT_MODULE_STATUS_WARNING_ALERT."
											OR tae.estado = ".AGENT_MODULE_STATUS_WARNING.") ";
			case AGENT_MODULE_STATUS_UNKNOWN:
				return " AND tae.estado = ".AGENT_MODULE_STATUS_UNKNOWN." ";
			case AGENT_MODULE_STATUS_NO_DATA:
			case AGENT_MODULE_STATUS_NOT_INIT:
				return " AND (tae.estado = ".AGENT_MODULE_STATUS_NO_DATA."
											OR tae.estado = ".AGENT_MODULE_STATUS_NOT_INIT.") ";
			case AGENT_MODULE_STATUS_NORMAL_ALERT:
			case AGENT_MODULE_STATUS_NORMAL:
				return " AND (tae.estado = ".AGENT_MODULE_STATUS_NORMAL_ALERT."
											OR tae.estado = ".AGENT_MODULE_STATUS_NORMAL.") ";
		}
		return "";
	}

	protected function getAgentCounterColumnsSql ($agent_table) {
		// Add the agent counters to the columns

		if ($this->filter['statusAgent'] == -1) {
			// Critical
			$agent_critical_filter = $this->getAgentStatusFilter(AGENT_STATUS_CRITICAL);
			$agents_critical_count = "($agent_table
										$agent_critical_filter) AS total_critical_count";
			// Warning
			$agent_warning_filter = $this->getAgentStatusFilter(AGENT_STATUS_WARNING);
			$agents_warning_count = "($agent_table
										$agent_warning_filter) AS total_warning_count";
			// Unknown
			$agent_unknown_filter = $this->getAgentStatusFilter(AGENT_STATUS_UNKNOWN);
			$agents_unknown_count = "($agent_table
										$agent_unknown_filter) AS total_unknown_count";
			// Normal
			$agent_normal_filter = $this->getAgentStatusFilter(AGENT_STATUS_NORMAL);
			$agents_normal_count = "($agent_table
										$agent_normal_filter) AS total_normal_count";
			// Not init
			if($this->filter['show_not_init_agents']){
				$agent_not_init_filter = $this->getAgentStatusFilter(AGENT_STATUS_NOT_INIT);
				$agents_not_init_count = "($agent_table
											$agent_not_init_filter) AS total_not_init_count";
			}
			else{
				$agent_not_init_filter = 0;
				$agents_not_init_count = 0;
			}

			// Alerts fired
			$agents_fired_count = "($agent_table
										AND ta.fired_count > 0) AS total_fired_count";
			// Total
			$agents_total_count = "($agent_table) AS total_count";

			$columns = "$agents_critical_count, $agents_warning_count, "
				. "$agents_unknown_count, $agents_normal_count, $agents_not_init_count, "
				. "$agents_fired_count, $agents_total_count";
		}
		else {
			// Alerts fired
			$agents_fired_count = "($agent_table
										AND ta.fired_count > 0) AS total_fired_count";
			// Total
			$agents_total_count = "($agent_table) AS total_count";

			switch ($this->filter['statusAgent']) {
				case AGENT_STATUS_NOT_INIT:
					// Not init
					$agent_not_init_filter = $this->getAgentStatusFilter(AGENT_STATUS_NOT_INIT);
					$agents_not_init_count = "($agent_table
												$agent_not_init_filter) AS total_not_init_count";
					$columns = "$agents_not_init_count, $agents_fired_count, $agents_total_count";
					break;
				case AGENT_STATUS_CRITICAL:
					// Critical
					$agent_critical_filter = $this->getAgentStatusFilter(AGENT_STATUS_CRITICAL);
					$agents_critical_count = "($agent_table
												$agent_critical_filter) AS total_critical_count";
					$columns = "$agents_critical_count, $agents_fired_count, $agents_total_count";
					break;
				case AGENT_STATUS_WARNING:
					// Warning
					$agent_warning_filter = $this->getAgentStatusFilter(AGENT_STATUS_WARNING);
					$agents_warning_count = "($agent_table
												$agent_warning_filter) AS total_warning_count";
					$columns = "$agents_warning_count, $agents_fired_count, $agents_total_count";
					break;
				case AGENT_STATUS_UNKNOWN:
					// Unknown
					$agent_unknown_filter = $this->getAgentStatusFilter(AGENT_STATUS_UNKNOWN);
					$agents_unknown_count = "($agent_table
												$agent_unknown_filter) AS total_unknown_count";
					$columns = "$agents_unknown_count, $agents_fired_count, $agents_total_count";
					break;
				case AGENT_STATUS_NORMAL:
					// Normal
					$agent_normal_filter = $this->getAgentStatusFilter(AGENT_STATUS_NORMAL);
					$agents_normal_count = "($agent_table
												$agent_normal_filter) AS total_normal_count";
					$columns = "$agents_normal_count, $agents_fired_count, $agents_total_count";
					break;
			}
		}

		return $columns;
	}

	protected function getAgentCountersSql ($agent_table) {
		global $config;

		$columns = $this->getAgentCounterColumnsSql($agent_table);
		$columns = "SELECT $columns FROM dual LIMIT 1";

		return $columns;
	}

	protected function getSql ($item_for_count = false) {
		// Get the type
		if (empty($this->type))
			$type = 'none';
		else
			$type = $this->type;

		// Get the root type
		if (empty($this->rootType))
			$rootType = 'none';
		else
			$rootType = $this->rootType;

		// Get the parent
		$parent = $this->id;

		// Get the root id
		$rootID = $this->rootID;

		// Get the server id
		$serverID = $this->serverID;

		// Agent name filter
		$agent_search_filter = "";
		if (!empty($this->filter['searchAgent'])) {
			$agent_search_filter = " AND LOWER(ta.alias) LIKE LOWER('%".$this->filter['searchAgent']."%')";
		}

		//Search hirearchy
		$search_hirearchy = false;
		if($this->filter['searchHirearchy']){
			$search_hirearchy = true;
		}

		// Agent status filter
		$agent_status_filter = "";
		if (isset($this->filter['statusAgent'])
				&& $this->filter['statusAgent'] != AGENT_STATUS_ALL
				&& !$this->strictACL) {
			$agent_status_filter = $this->getAgentStatusFilter($this->filter['statusAgent']);
		}

		// Agents join
		$agents_join = "";
		if (!empty($agent_search_filter) || !empty($agent_status_filter)) {
			$agents_join = "INNER JOIN tagente ta
								ON ta.disabled = 0
									AND tam.id_agente = ta.id_agente
									$agent_search_filter
									$agent_status_filter";
		}

		// Module name filter
		$module_search_filter = "";
		if (!empty($this->filter['searchModule'])) {
			$module_search_filter = " AND tam.nombre LIKE '%".$this->filter['searchModule']."%' ";
		}

		$module_status_from_agent = $this->getModuleStatusFilter();

		// Module status filter
		$module_status_filter = $this->getModuleStatusFilterFromTestado();

		// Modules join
		$modules_join = "";
		$module_status_join = 'INNER JOIN tagente_estado tae
			ON tam.id_agente_modulo = tae.id_agente_modulo  ';
		if (!$this->filter['show_not_init_modules']) {
			$module_status_join .= " AND tae.estado <> ".AGENT_MODULE_STATUS_NO_DATA."
										AND tae.estado <> ".AGENT_MODULE_STATUS_NOT_INIT." ";
		}
		if (!empty($module_status_filter)) {
			$module_status_join .= $module_status_filter;
		}
		if (!empty($module_search_filter)) {
			$modules_join = "INNER JOIN tagente_modulo tam
								ON tam.disabled = 0
									AND ta.id_agente = tam.id_agente
									$module_search_filter
							$module_status_join";
		}
		$sql = false;

		switch ($rootType) {
			case 'group':
				// ACL Group
				$user_groups_str = "-1";
				$group_filter =  "";

				if (empty($this->userGroups)) {
					return;
				}

				// Asking for a specific group.
				if ($item_for_count !== false) {
					if (!isset($this->userGroups[$item_for_count])) {
						return;
					}
				}
				// Asking for all groups.
				elseif (users_can_manage_group_all("AR")) {
					$user_groups_str = implode(",", $this->userGroupsArray);
					$group_filter = "";
					$user_groups_condition = "";
				} else {
					$user_groups_str = implode(",", $this->userGroupsArray);
					$user_groups_condition = "WHERE ta.id_grupo IN($user_groups_str)";
					$group_filter = "AND (
						ta.id_grupo IN ($user_groups_str)
						OR tasg.id_group IN ($user_groups_str)
					)";
				}

				if(!$search_hirearchy && (!empty($agent_search_filter) || !empty($module_search_filter))){
					
					if(is_metaconsole()){
						$id_groups_agents = db_get_all_rows_sql(
							" SELECT DISTINCT(ta.id_grupo)
								FROM tmetaconsole_agent ta
								LEFT JOIN tmetaconsole_agent_secondary_group tasg
									ON ta.id_agente = tasg.id_agent
								WHERE ta.disabled = 0
								$agent_search_filter"
						);
						$id_secondary_groups_agents = db_get_all_rows_sql(
							" SELECT DISTINCT(tasg.id_group)
								FROM tmetaconsole_agent ta
								LEFT JOIN tmetaconsole_agent_secondary_group tasg
									ON ta.id_agente = tasg.id_agent
								WHERE ta.disabled = 0
								$agent_search_filter"
						);
					}
					else{
						$id_groups_agents = db_get_all_rows_sql(
							" SELECT DISTINCT(ta.id_grupo)
								FROM tagente ta
								LEFT JOIN tagent_secondary_group tasg
									ON ta.id_agente = tasg.id_agent
								, tagente_modulo tam
								WHERE tam.id_agente = ta.id_agente
								AND ta.disabled = 0
								$agent_search_filter
								$module_search_filter"
						);
						$id_secondary_groups_agents = db_get_all_rows_sql(
							" SELECT DISTINCT(tasg.id_group)
								FROM tagente ta
								LEFT JOIN tagent_secondary_group tasg
									ON ta.id_agente = tasg.id_agent
								, tagente_modulo tam
								WHERE tam.id_agente = ta.id_agente
								AND ta.disabled = 0
								$agent_search_filter
								$module_search_filter"
						);
					}
					
					if($id_groups_agents != false){
						foreach	($id_groups_agents as $key => $value) {
							$id_groups_agents_array[$value['id_grupo']] = $value['id_grupo'];
						}
						foreach	($id_secondary_groups_agents as $key => $value) {
							$id_groups_agents_array[$value['id_group']] = $value['id_group'];
						}
						$user_groups_array = explode(",", $user_groups_str);
						$user_groups_array = array_intersect($user_groups_array, $id_groups_agents_array);
						$user_groups_str = implode("," , $user_groups_array);
					}
					else{
						$user_groups_str = false;
					}
				}

				switch ($type) {
					// Get the agents of a group
					case 'group':
						if (empty($rootID) || $rootID == -1) {
							if(!$search_hirearchy && (!empty($agent_search_filter) || !empty($module_search_filter))){
								$columns = 'tg.id_grupo AS id, tg.nombre AS name, tg.icon';
							}
							else{
								$columns = 'tg.id_grupo AS id, tg.nombre AS name, tg.parent, tg.icon';
							}

							$order_fields = 'tg.nombre ASC, tg.id_grupo ASC';

							if (!is_metaconsole()) {
								// Groups SQL
								if ($item_for_count === false) {
									$sql = "SELECT $columns
											FROM tgrupo tg
											$user_groups_str_condition
											ORDER BY $order_fields";
								}
								// Counters SQL
								else {
									$agent_table = "SELECT COUNT(DISTINCT(ta.id_agente))
													FROM tagente ta
													LEFT JOIN tagent_secondary_group tasg
														ON ta.id_agente=tasg.id_agent
													LEFT JOIN tagente_modulo tam
														ON tam.disabled = 0
															AND ta.id_agente = tam.id_agente
															$module_search_filter
													$module_status_join
													WHERE ta.disabled = 0
														AND (
															ta.id_grupo = $item_for_count
															OR tasg.id_group = $item_for_count
														)
														$group_filter
														$agent_search_filter
														$agent_status_filter";
									$sql = $this->getAgentCountersSql($agent_table);
								}
							}
							// Metaconsole
							else {
								// Groups SQL
								if ($item_for_count === false) {
									$sql = "SELECT $columns
											FROM tgrupo tg
											$user_groups_str_condition
											ORDER BY $order_fields";
								}
								// Counters SQL
								else {
									$agent_table = "SELECT COUNT(DISTINCT(ta.id_agente))
													FROM tagente ta
													LEFT JOIN tagent_secondary_group tasg
														ON ta.id_agente = tasg.id_agent
													WHERE ta.disabled = 0
														AND (
															ta.id_grupo = $item_for_count
															OR tasg.id_group = $item_for_count
														)
														$group_filter
														$agent_search_filter
														$agent_status_filter";
									$sql = $this->getAgentCountersSql($agent_table);
								}
							}
						}
						else {
							if (!is_metaconsole()) {

								$columns = 'ta.id_agente AS id, ta.nombre AS name, ta.alias,
									ta.fired_count, ta.normal_count, ta.warning_count,
									ta.critical_count, ta.unknown_count, ta.notinit_count,
									ta.total_count, ta.quiet';
								$search_module_jj = "";
								if(!empty($this->filter['searchModule'])){
									$columns .=",
										SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_CRITICAL_ALERT) . ", 1, 0)) as state_critical,
										SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_WARNING_ALERT) . ", 1, 0)) as state_warning,
										SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_UNKNOWN) . ", 1, 0)) as state_unknown,
										SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_NO_DATA) . ", 1, 0)) as state_notinit,
										SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_NORMAL) . ", 1, 0)) as state_normal,
										SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado() . ",1,0)) as state_total
									";
									$search_module_jj = "INNER JOIN tagente_estado tae
										ON tae.id_agente_modulo = tam.id_agente_modulo";
									$having_conditional = "HAVING state_total > 0";
								}

								$order_fields = 'ta.alias ASC, ta.id_agente ASC';
								$inner_or_left = $this->filter['show_not_init_agents']
									? "LEFT"
									: "INNER";
								$sql = "SELECT $columns
										FROM tagente ta
										LEFT JOIN tagent_secondary_group tasg
											ON tasg.id_agent = ta.id_agente
										$inner_or_left JOIN tagente_modulo tam
											ON tam.disabled = 0
												AND ta.id_agente = tam.id_agente
										$search_module_jj
										WHERE ta.disabled = 0
											AND (
												ta.id_grupo = $rootID
												OR tasg.id_group = $rootID
											)
											$module_status_from_agent
											$group_filter
											$agent_search_filter
											$agent_status_filter
											$module_search_filter
										GROUP BY ta.id_agente
										$having_conditional
										ORDER BY $order_fields";
							}
							else {
								$columns = 'ta.id_tagente AS id, ta.nombre AS name, ta.alias,
									ta.fired_count, ta.normal_count, ta.warning_count,
									ta.critical_count, ta.unknown_count, ta.notinit_count,
									ta.total_count, ta.quiet, ta.id_tmetaconsole_setup AS server_id';
								$order_fields = 'ta.alias ASC, ta.id_tagente ASC';

								$sql = "SELECT $columns
										FROM tmetaconsole_agent ta
										LEFT JOIN tmetaconsole_agent_secondary_group tasg
											ON ta.id_agente = tasg.id_agent
										WHERE ta.disabled = 0
											AND  (
												ta.id_grupo = $rootID
												OR tasg.id_group = $rootID
											)
											$group_filter
											$agent_search_filter
											$agent_status_filter
										GROUP BY ta.id_agente
										ORDER BY $order_fields";
							}
						}
						break;
					// Get the modules of an agent
					case 'agent':
						$columns = 'tam.id_agente_modulo AS id, 
							tam.parent_module_id AS parent, 
							tam.nombre AS name, tam.id_tipo_modulo, 
							tam.id_modulo, tae.estado, tae.datos';
						$order_fields = 'tam.nombre ASC, tam.id_agente_modulo ASC';

						// Set for the common ACL only. The strict ACL case is different (groups and tags divided).
						// The modules only have visibility in two cases:
						// 1. The user has access to the group of its agent and this group hasn't tags.
						// 2. The user has access to the group of its agent, this group has tags and the module
						// has any of this tags.
						$tag_join = '';
						// $rootID it the agent group id in this case
						if (!empty($this->acltags) && isset($this->acltags[$rootID])) {
							$tags_str = $this->acltags[$rootID];

							if (!empty($tags_str)) {
								$tag_join = sprintf('INNER JOIN ttag_module ttm
															ON tam.id_agente_modulo = ttm.id_agente_modulo
																AND ttm.id_tag IN (%s)', $tags_str);
							}
						}

						$sql = "SELECT DISTINCT $columns
								FROM tagente_modulo tam
								$tag_join
								$module_status_join
								INNER JOIN tagente ta
									ON ta.disabled = 0
								LEFT JOIN tagent_secondary_group tasg
									ON ta.id_agente = tasg.id_agent
										AND tam.id_agente = ta.id_agente
										AND ta.id_grupo = $rootID
										$group_filter
										$agent_search_filter
										$agent_status_filter
								WHERE tam.disabled = 0
									AND tam.id_agente = $parent
									$module_search_filter
								ORDER BY $order_fields";
						break;
				}
				break;
			case 'tag':
				// ACL Group
				$group_acl =  "";
				if (!$this->strictACL) {
					if (!empty($this->userGroups)) {
						$user_groups_str = implode(",", array_keys($this->userGroups));
						$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
					}
					else {
						$group_acl = "AND ta.id_grupo = -1";
					}
				}
				else {
					if (!empty($this->acltags) && !empty($rootID) && $rootID != -1) {
						$groups = array();
						foreach ($this->acltags as $group_id => $tags_str) {
							if (!empty($tags_str)) {
								$tags = explode(",", $tags_str);

								if (in_array($rootID, $tags)) {
									$hierarchy_groups = groups_get_id_recursive($group_id);
									$groups = array_merge($groups, $hierarchy_groups);
								}
							}
						}
						if (!empty($groups)) {
							if (array_search(0, $groups) === false) {
								$user_groups_str = implode(",", $groups);
								$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
							}
						}
						else {
							$group_acl = "AND ta.id_grupo = -1";
						}
					}
					else {
						$group_acl = "AND ta.id_grupo = -1";
					}
				}

				switch ($type) {
					// Get the agents of a tag
					case 'tag':
						if (empty($rootID) || $rootID == -1) {
							if ($this->strictACL)
								return false;

							// tagID filter. To access the view from tactical views f.e.
							$tag_filter = '';
							if (!empty($this->filter['tagID'])) {
								$tag_filter = "WHERE tt.id_tag = " . $this->filter['tagID'];
							}

							$columns = 'tt.id_tag AS id, tt.name AS name';
							$group_by_fields = 'tt.id_tag, tt.name';
							$order_fields = 'tt.name ASC, tt.id_tag ASC';

							// Tags SQL
							if ($item_for_count === false) {
								$sql = "SELECT $columns
										FROM ttag tt
										INNER JOIN ttag_module ttm
											ON tt.id_tag = ttm.id_tag
										INNER JOIN tagente_modulo tam
											ON tam.disabled = 0
												AND ttm.id_agente_modulo = tam.id_agente_modulo
												$module_search_filter
										$module_status_join
										INNER JOIN tagente ta
											ON ta.disabled = 0
											AND tam.id_agente = ta.id_agente
											$group_acl
											$agent_search_filter
											$agent_status_filter
										$tag_filter
										GROUP BY $group_by_fields
										ORDER BY $order_fields";
							}
							// Counters SQL
							else {
								$agent_table = "SELECT COUNT(DISTINCT(ta.id_agente))
												FROM tagente ta
												INNER JOIN tagente_modulo tam
													ON tam.disabled = 0
														AND ta.id_agente = tam.id_agente
														$module_search_filter
												$module_status_join
												INNER JOIN ttag_module ttm
													ON tam.id_agente_modulo = ttm.id_agente_modulo
														AND ttm.id_tag = $item_for_count
												WHERE ta.disabled = 0
													$group_acl
													$agent_search_filter
													$agent_status_filter";
								$sql = $this->getAgentCountersSql($agent_table);
							}
						}
						else {
							$columns = 'ta.id_agente AS id, ta.nombre AS name, ta.alias,
								ta.fired_count, ta.normal_count, ta.warning_count,
								ta.critical_count, ta.unknown_count, ta.notinit_count,
								ta.total_count, ta.quiet';
							$group_by_fields = 'ta.id_agente, ta.nombre, ta.alias,
								ta.fired_count, ta.normal_count, ta.warning_count,
								ta.critical_count, ta.unknown_count, ta.notinit_count,
								ta.total_count, ta.quiet';
							$order_fields = 'ta.alias ASC, ta.id_agente ASC';

							$sql = "SELECT $columns
									FROM tagente ta
									INNER JOIN tagente_modulo tam
										ON tam.disabled = 0
											AND ta.id_agente = tam.id_agente
											$module_search_filter
									$module_status_join
									INNER JOIN ttag_module ttm
										ON tam.id_agente_modulo = ttm.id_agente_modulo
											AND ttm.id_tag = $rootID
									WHERE ta.disabled = 0
										$group_acl
										$agent_search_filter
										$agent_status_filter
									GROUP BY $group_by_fields
									ORDER BY $order_fields";
						}
						break;
					// Get the modules of an agent
					case 'agent':
						$columns = 'tam.id_agente_modulo AS id, tam.nombre AS name,
							tam.id_tipo_modulo, tam.id_modulo, tae.estado, tae.datos';
						$order_fields = 'tam.nombre ASC, tam.id_agente_modulo ASC';

						// Set for the common ACL only. The strict ACL case is different (groups and tags divided).
						// The modules only have visibility in two cases:
						// 1. The user has access to the group of its agent and this group hasn't tags.
						// 2. The user has access to the group of its agent, this group has tags and the module
						// has any of this tags.
						$tag_filter = '';
						if (!$this->strictACL) {
							// $parent is the agent id
							$group_id = (int) db_get_value('id_grupo', 'tagente', 'id_agente', $parent);
							if (empty($group_id)) {
								// ACL error, this will restrict (fuck) the module search
								$tag_filter = 'AND 1=0';
							}
							else if (!empty($this->acltags) && isset($this->acltags[$group_id])) {
								$tags_str = $this->acltags[$group_id];

								if (!empty($tags_str)) {
									$tag_filter = sprintf('AND ttm.id_tag IN (%s)', $tags_str);
								}
							}
						}

						$sql = "SELECT $columns
								FROM tagente_modulo tam
								INNER JOIN ttag_module ttm
									ON tam.id_agente_modulo = ttm.id_agente_modulo
										AND ttm.id_tag = $rootID
										$tag_filter
								$module_status_join
								INNER JOIN tagente ta
									ON ta.disabled = 0
										AND tam.id_agente = ta.id_agente
										$group_acl
										$agent_search_filter
										$agent_status_filter
								WHERE tam.disabled = 0
									AND tam.id_agente = $parent
									$module_search_filter
								ORDER BY $order_fields";
						break;
				}
				break;
			case 'os':
				// ACL Group
				$group_acl =  "";
				if (!empty($this->userGroups)) {
					$user_groups_str = implode(",", array_keys($this->userGroups));
					$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
				}
				else {
					$group_acl = "AND ta.id_grupo = -1";
				}

				switch ($type) {
					// Get the agents of an os
					case 'os':
						if (empty($rootID) || $rootID == -1) {
							$columns = 'tos.id_os AS id, tos.name AS name, tos.icon_name AS os_icon';
							$group_by_fields = 'tos.id_os, tos.name, tos.icon_name';
							$order_fields = 'tos.icon_name ASC, tos.id_os ASC';

							// OS SQL
							if ($item_for_count === false) {
								$sql = "SELECT $columns
										FROM tconfig_os tos
										INNER JOIN tagente ta
											ON ta.disabled = 0
												AND ta.id_os = tos.id_os
												$agent_search_filter
												$agent_status_filter
												$group_acl
										$modules_join
										GROUP BY $group_by_fields
										ORDER BY $order_fields";
							}
							// Counters SQL
							else {
								$agent_table = "SELECT COUNT(DISTINCT(ta.id_agente))
												FROM tagente ta
												$modules_join
												WHERE ta.disabled = 0
													AND ta.id_os = $item_for_count
													$group_acl
													$agent_search_filter
													$agent_status_filter";
								$sql = $this->getAgentCountersSql($agent_table);
							}
						}
						else {
							$columns = 'ta.id_agente AS id, ta.nombre AS name, ta.alias,
								ta.fired_count, ta.normal_count, ta.warning_count,
								ta.critical_count, ta.unknown_count, ta.notinit_count,
								ta.total_count, ta.quiet';
							$search_module_jj = "";
							$columns .=",
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_CRITICAL_ALERT) . ", 1, 0)) as state_critical,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_WARNING_ALERT) . ", 1, 0)) as state_warning,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_UNKNOWN) . ", 1, 0)) as state_unknown,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_NO_DATA) . ", 1, 0)) as state_notinit,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_NORMAL) . ", 1, 0)) as state_normal,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado() . ",1,0)) as state_total
							";
							$search_module_jj = "INNER JOIN tagente_estado tae
									ON tae.id_agente_modulo = tam.id_agente_modulo";

							$order_fields = 'ta.alias ASC, ta.id_agente ASC';
							$inner_or_left = $this->filter['show_not_init_agents']
								? "LEFT"
								: "INNER";
							$sql = "SELECT $columns
									FROM tagente ta
									LEFT JOIN tagent_secondary_group tasg
										ON tasg.id_agent = ta.id_agente
									$inner_or_left JOIN tagente_modulo tam
										ON tam.disabled = 0
											AND ta.id_agente = tam.id_agente
									$search_module_jj
									WHERE ta.disabled = 0
										AND ta.id_os = $rootID
										$module_status_from_agent
										$group_filter
										$agent_search_filter
										$agent_status_filter
										$module_search_filter
									GROUP BY ta.id_agente
									HAVING state_total > 0
									ORDER BY $order_fields";
						}
						break;
					// Get the modules of an agent
					case 'agent':
						$columns = 'tam.id_agente_modulo AS id, tam.nombre AS name,
							tam.id_tipo_modulo, tam.id_modulo, tae.estado, tae.datos';
						$order_fields = 'tam.nombre ASC, tam.id_agente_modulo ASC';

						$os_filter = "AND ta.id_os = $rootID";
						$agent_filter = "AND ta.id_agente = $parent";

						// Set for the common ACL only. The strict ACL case is different (groups and tags divided).
						// The modules only have visibility in two cases:
						// 1. The user has access to the group of its agent and this group hasn't tags.
						// 2. The user has access to the group of its agent, this group has tags and the module
						// has any of this tags.
						$tag_join = '';
						if (!$this->strictACL) {
							// $parent is the agent id
							$group_id = (int) db_get_value('id_grupo', 'tagente', 'id_agente', $parent);
							if (empty($group_id)) {
								// ACL error, this will restrict (fuck) the module search
								$tag_join = 'INNER JOIN ttag_module tta
												ON 1=0';
							}
							else if (!empty($this->acltags) && isset($this->acltags[$group_id])) {
								$tags_str = $this->acltags[$group_id];

								if (!empty($tags_str)) {
									$tag_join = sprintf('INNER JOIN ttag_module ttm
																ON tam.id_agente_modulo = ttm.id_agente_modulo
																	AND ttm.id_tag IN (%s)', $tags_str);
								}
							}
						}

						$sql = "SELECT $columns
								FROM tagente_modulo tam
								$tag_join
								$module_status_join
								INNER JOIN tagente ta
									ON ta.disabled = 0
										AND tam.id_agente = ta.id_agente
										$os_filter
										$group_acl
										$agent_search_filter
										$agent_status_filter
								WHERE tam.disabled = 0
									$agent_filter
									$module_search_filter
								ORDER BY $order_fields";
						break;
				}
				break;
			case 'module_group':
				// ACL Group
				$group_acl =  "";
				if (!empty($this->userGroups)) {
					$user_groups_str = implode(",", array_keys($this->userGroups));
					$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
				}
				else {
					$group_acl = "AND ta.id_grupo = -1";
				}

				switch ($type) {
					// Get the agents of a module group
					case 'module_group':
						if (empty($rootID) || $rootID == -1) {
							$columns = 'tmg.id_mg AS id, tmg.name AS name';
							$group_by_fields = 'tmg.id_mg, tmg.name';
							$order_fields = 'tmg.name ASC, tmg.id_mg ASC';

							// Module groups SQL
							if ($item_for_count === false) {
								$sql = "SELECT $columns
										FROM tmodule_group tmg
										INNER JOIN tagente_modulo tam
											ON tam.disabled = 0
												AND tam.id_module_group = tmg.id_mg
												$module_search_filter
										$module_status_join
										INNER JOIN tagente ta
											ON ta.disabled = 0
												AND tam.id_agente = ta.id_agente
												$group_acl
												$agent_search_filter
												$agent_status_filter
										GROUP BY $group_by_fields
										ORDER BY $order_fields";
							}
							// Counters SQL
							else {
								$agent_table = "SELECT COUNT(DISTINCT(ta.id_agente))
												FROM tagente ta
												INNER JOIN tagente_modulo tam
													ON tam.disabled = 0
														AND ta.id_agente = tam.id_agente
														AND tam.id_module_group = $item_for_count
														$module_search_filter
												$module_status_join
												WHERE ta.disabled = 0
													$group_acl
													$agent_search_filter
													$agent_status_filter";
								$sql = $this->getAgentCountersSql($agent_table);
							}
						}
						else {
							$columns = 'ta.id_agente AS id, ta.nombre AS name, ta.alias,
								ta.fired_count, ta.normal_count, ta.warning_count,
								ta.critical_count, ta.unknown_count, ta.notinit_count,
								ta.total_count, ta.quiet';
							$search_module_jj = "";
							$columns .=",
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_CRITICAL_ALERT) . ", 1, 0)) as state_critical,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_WARNING_ALERT) . ", 1, 0)) as state_warning,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_UNKNOWN) . ", 1, 0)) as state_unknown,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_NO_DATA) . ", 1, 0)) as state_notinit,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_NORMAL) . ", 1, 0)) as state_normal,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado() . ",1,0)) as state_total
							";
							$search_module_jj = "INNER JOIN tagente_estado tae
									ON tae.id_agente_modulo = tam.id_agente_modulo";

							$order_fields = 'ta.alias ASC, ta.id_agente ASC';
							$inner_or_left = $this->filter['show_not_init_agents']
								? "LEFT"
								: "INNER";
							$sql = "SELECT $columns
									FROM tagente ta
									LEFT JOIN tagent_secondary_group tasg
										ON tasg.id_agent = ta.id_agente
									$inner_or_left JOIN tagente_modulo tam
										ON tam.disabled = 0
											AND ta.id_agente = tam.id_agente
									$search_module_jj
									WHERE ta.disabled = 0
										AND tam.id_module_group = $rootID
										$module_status_from_agent
										$group_filter
										$agent_search_filter
										$agent_status_filter
										$module_search_filter
									GROUP BY ta.id_agente
									HAVING state_total > 0
									ORDER BY $order_fields";
						}
						break;
					// Get the modules of an agent
					case 'agent':
						$columns = 'tam.id_agente_modulo AS id, tam.nombre AS name,
							tam.id_tipo_modulo, tam.id_modulo, tae.estado, tae.datos';
						$order_fields = 'tam.nombre ASC, tam.id_agente_modulo ASC';

						$module_group_filter = "AND tam.id_module_group = $rootID";
						$agent_filter = "AND tam.id_agente = $parent";

						// Set for the common ACL only. The strict ACL case is different (groups and tags divided).
						// The modules only have visibility in two cases:
						// 1. The user has access to the group of its agent and this group hasn't tags.
						// 2. The user has access to the group of its agent, this group has tags and the module
						// has any of this tags.
						$tag_join = '';
						if (!$this->strictACL) {
							// $parent is the agent id
							$group_id = (int) db_get_value('id_grupo', 'tagente', 'id_agente', $parent);
							if (empty($group_id)) {
								// ACL error, this will restrict (fuck) the module search
								$tag_join = 'INNER JOIN ttag_module tta
												ON 1=0';
							}
							else if (!empty($this->acltags) && isset($this->acltags[$group_id])) {
								$tags_str = $this->acltags[$group_id];

								if (!empty($tags_str)) {
									$tag_join = sprintf('INNER JOIN ttag_module ttm
																ON tam.id_agente_modulo = ttm.id_agente_modulo
																	AND ttm.id_tag IN (%s)', $tags_str);
								}
							}
						}

						$sql = "SELECT $columns
								FROM tagente_modulo tam
								$tag_join
								$module_status_join
								INNER JOIN tagente ta
									ON ta.disabled = 0
										AND tam.id_agente = ta.id_agente
										$group_acl
										$agent_search_filter
										$agent_status_filter
								WHERE tam.disabled = 0
									$agent_filter
									$module_group_filter
									$module_search_filter
								ORDER BY $order_fields";
						break;
				}
				break;
			case 'module':
				// ACL Group
				//FIXME
				$group_acl =  "";
				if (!empty($this->userGroups)) {
					$user_groups_str = implode(",", array_keys($this->userGroups));
					$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
				}
				else {
					$group_acl = "AND ta.id_grupo = -1";
				}

				switch ($type) {
					// Get the agents of a module
					case 'module':
						if (empty($rootID) || $rootID == -1) {
							$columns = 'tam.nombre AS name';
							$order_fields = 'tam.nombre ASC';

							// Modules SQL
							if ($item_for_count === false) {
								//FIXME This group ACL should be the same in all modules view
								$group_acl = " AND (ta.id_grupo IN ($user_groups_str) OR tasg.id_group IN ($user_groups_str))";
								$sql = "SELECT $columns
										FROM tagente_modulo tam
										INNER JOIN tagente ta
											ON ta.disabled = 0
												AND tam.id_agente = ta.id_agente
										LEFT JOIN tagent_secondary_group tasg
											ON tasg.id_agent = ta.id_agente
												$agent_search_filter
												$agent_status_filter
										$module_status_join
										WHERE tam.disabled = 0
											$group_acl
											$module_search_filter
										GROUP BY tam.nombre
										ORDER BY $order_fields";
							}
							// Counters SQL
							else {
								$agent_table = "SELECT COUNT(DISTINCT(ta.id_agente))
												FROM tagente ta
												INNER JOIN tagente_modulo tam
													ON tam.disabled = 0
														AND ta.id_agente = tam.id_agente
														AND tam.nombre = '$item_for_count'
														$module_group_filter
														$module_search_filter
												$module_status_join
												WHERE ta.disabled = 0
													$group_acl
													$agent_search_filter
													$agent_status_filter";
								$sql = $this->getAgentCountersSql($agent_table);
							}
						}
						else {
							$columns = 'ta.id_agente AS id, ta.nombre AS name, ta.alias,
								ta.fired_count, ta.normal_count, ta.warning_count,
								ta.critical_count, ta.unknown_count, ta.notinit_count,
								ta.total_count, ta.quiet';
							$symbols = ' !"#$%&\'()*+,./:;<=>?@[\\]^{|}~';
							$name = $rootID;
							for ($i = 0; $i < strlen($symbols); $i++) {
								$name = str_replace('_articapandora_' .
									ord(substr($symbols, $i, 1)) .'_pandoraartica_',
									substr($symbols, $i, 1), $name);
							}
							$this->filter['searchModule'] = $name = io_safe_input($name);
							$columns .=",
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_CRITICAL_ALERT) . ", 1, 0)) as state_critical,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_WARNING_ALERT) . ", 1, 0)) as state_warning,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_UNKNOWN) . ", 1, 0)) as state_unknown,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_NO_DATA) . ", 1, 0)) as state_notinit,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado(AGENT_MODULE_STATUS_NORMAL) . ", 1, 0)) as state_normal,
								SUM(if(1=1 " . $this->getModuleStatusFilterFromTestado() . ",1,0)) as state_total
							";
							$search_module_jj = "INNER JOIN tagente_estado tae
								ON tae.id_agente_modulo = tam.id_agente_modulo";

							$order_fields = 'ta.alias ASC, ta.id_agente ASC';
							$inner_or_left = $this->filter['show_not_init_agents']
								? "LEFT"
								: "INNER";
							$sql = "SELECT $columns
								FROM tagente ta
								LEFT JOIN tagent_secondary_group tasg
									ON tasg.id_agent = ta.id_agente
								$inner_or_left JOIN tagente_modulo tam
									ON tam.disabled = 0
										AND ta.id_agente = tam.id_agente
								$search_module_jj
								WHERE ta.disabled = 0
									AND tam.nombre = '$name'
									$module_status_from_agent
									$group_filter
									$agent_search_filter
									$agent_status_filter
									$module_search_filter
								GROUP BY ta.id_agente
								HAVING state_total > 0
								ORDER BY $order_fields";
						}
						break;
					// Get the modules of an agent
					case 'agent':
						$columns = 'tam.id_agente_modulo AS id, tam.nombre AS name,
							tam.id_tipo_modulo, tam.id_modulo, tae.estado, tae.datos';
						$order_fields = 'tam.nombre ASC, tam.id_agente_modulo ASC';

						$symbols = ' !"#$%&\'()*+,./:;<=>?@[\\]^{|}~';
						$name = $rootID;
						for ($i = 0; $i < strlen($symbols); $i++) {
							$name = str_replace('_articapandora_' .
								ord(substr($symbols, $i, 1)) .'_pandoraartica_',
								substr($symbols, $i, 1), $name);
						}
						$name = io_safe_input($name);

						$module_name_filter = "AND tam.nombre = '$name'";
						$agent_filter = "AND tam.id_agente = $parent";

						// We need the agents table
						if (empty($agents_join)) {
							$agents_join = "INNER JOIN tagente ta
												ON ta.disabled = 0
													AND tam.id_agente = ta.id_agente
													$group_acl";
						}
						else {
							$agents_join .= " $group_acl";
						}

						$tag_join = '';
						// $parent is the agent id
						$group_id = (int) db_get_value('id_grupo', 'tagente', 'id_agente', $parent);
						if (empty($group_id)) {
							// ACL error, this will restrict (fuck) the module search
							$tag_join = 'INNER JOIN ttag_module tta
											ON 1=0';
						}
						else if (!empty($this->acltags) && isset($this->acltags[$group_id])) {
							$tags_str = $this->acltags[$group_id];

							if (!empty($tags_str)) {
								$tag_join = sprintf('INNER JOIN ttag_module ttm
															ON tam.id_agente_modulo = ttm.id_agente_modulo
																AND ttm.id_tag IN (%s)', $tags_str);
							}
						}

						$sql = "SELECT $columns
								FROM tagente_modulo tam
								$tag_join
								$module_status_join
								INNER JOIN tagente ta
									ON ta.disabled = 0
										AND tam.id_agente = ta.id_agente
										$group_acl
								WHERE tam.disabled = 0
									$agent_filter
									$module_name_filter
									$module_group_filter
									$module_search_filter
								ORDER BY $order_fields";
						break;
				}
				break;
			default:
				$sql = $this->getSqlExtended($item_for_count, $type, $rootType, $parent, $rootID,
									$agent_search_filter, $agent_status_filter, $agents_join,
									$module_search_filter, $module_status_filter, $modules_join,
									$module_status_join);
		}

		return $sql;
	}

	// Override this method
	protected function getSqlExtended ($item_for_count, $type, $rootType, $parent, $rootID,
										$agent_search_filter, $agent_status_filter, $agents_join,
										$module_search_filter, $module_status_filter, $modules_join,
										$module_status_join) {
		return false;
	}

	protected function getItems ($item_for_count = false) {
		$sql = $this->getSql($item_for_count);
		if (empty($sql))
			return array();
		$data = db_process_sql($sql);
		if (empty($data))
			return array();

		// [26/10/2017] It seems the module hierarchy should be only available into the tree by group
		if ($this->rootType == 'group' && $this->type == 'agent') {
			$data = $this->getProcessedModules($data);
		}

		return $data;
	}

	protected function getCounters ($id) {
		$counters = $this->getItems($id);
		if (!empty($counters)) {
			$counters = array_pop($counters);
		}
		return $counters;
	}

	static function cmpSortNames($a, $b) {
		return strcmp($a["name"], $b["name"]);
	}

	protected function getProcessedGroups () {
		$processed_groups = array();
		// Index and process the groups
		$groups = $this->getGroupCounters(0);

		// If user have not permissions in parent, set parent node to 0 (all)
		// Avoid to do foreach for admins
		if (!users_can_manage_group_all("AR")) {
			foreach ($groups as $id => $group) {
				if (!isset($this->userGroups[$groups[$id]['parent']])) {
					$groups[$id]['parent'] = 0;
				}
			}
		}
		// Build the group hierarchy
		foreach ($groups as $id => $group) {
			if (isset($groups[$id]['parent']) && ($groups[$id]['parent'] != 0)) {
				$parent = $groups[$id]['parent'];
				// Parent exists
				if (!isset($groups[$parent]['children'])) {
					$groups[$parent]['children'] = array();
				}
				// Store a reference to the group into the parent
				$groups[$parent]['children'][] = &$groups[$id];
				// This group was introduced into a parent
				$groups[$id]['have_parent'] = true;
			}
		}
		// Sort the children groups
		foreach ($groups as $id => $group) {
			if (isset($groups[$id]['children'])) {
				usort($groups[$id]['children'], array("Tree", "cmpSortNames"));
			}
		}
		//Filter groups and eliminates the reference to children groups out of her parent
		$groups = array_filter($groups, function ($group) {
			return !$group['have_parent'];
		});
		// Propagate child counters to her parents
		Tree::processCounters($groups);
		// Filter groups and eliminates the reference to empty groups
		$groups = Tree::deleteEmptyGroups($groups);

		usort($groups, array("Tree", "cmpSortNames"));
		return $groups;
	}

	protected function getProcessedItem ($item, $server = false) {

		if (isset($processed_item['is_processed']) && $processed_item['is_processed'])
			return $item;

		$processed_item = array();
		$processed_item['id'] = $item['id'];
		$processed_item['name'] = $item['name'];
		$processed_item['rootID'] = $item['id'];
		$processed_item['rootType'] = $this->rootType;
		$processed_item['searchChildren'] = 1;

		if (isset($item['type']))
			$processed_item['type'] = $item['type'];
		else
			$processed_item['type'] = $this->type;

		if (isset($item['rootType']))
			$processed_item['rootType'] = $item['rootType'];
		else
			$processed_item['rootType'] = $this->rootType;

		if ($processed_item['type'] == 'group') {
			$processed_item['parent'] = $item['parent'];

			$processed_item['icon'] = empty($item['icon'])
				? "without_group.png"
				: $item['icon'].".png";
		}
		if (isset($item['iconHTML'])) {
			$processed_item['icon'] = $item['iconHTML'];
		}

		if (is_metaconsole() && !empty($server)) {
			$processed_item['serverID'] = $server['id'];
		}

		$counters = array();
		if (isset($item['total_unknown_count']))
			$counters['unknown'] = $item['total_unknown_count'];
		if (isset($item['total_critical_count']))
			$counters['critical'] = $item['total_critical_count'];
		if (isset($item['total_warning_count']))
			$counters['warning'] = $item['total_warning_count'];
		if (isset($item['total_not_init_count']))
			$counters['not_init'] = $item['total_not_init_count'];
		if (isset($item['total_normal_count']))
			$counters['ok'] = $item['total_normal_count'];
		if (isset($item['total_count']))
			$counters['total'] = $item['total_count'];
		if (isset($item['total_fired_count']))
			$counters['alerts'] = $item['total_fired_count'];

		if (!empty($counters))
			$processed_item['counters'] = $counters;

		if (!empty($processed_item))
			$processed_item['is_processed'] = true;

		return $processed_item;
	}

	// This function should be used only when retrieving the data of the metaconsole's nodes
	protected function getMergedItems ($items) {
		// This variable holds the result
		$mergedItems = array();

		foreach ($items as $key => $item) {
			// Avoid the deleted items
			if (!isset($items[$key]) || empty($item))
				continue;

			// Store the item in a temporary element
			$resultItem = $item;

			// The 'id' parameter will be stored as 'server_id' => 'id'
			$resultItem['id'] = array();
			$resultItem['id'][$item['serverID']] = $item['id'];
			$resultItem['rootID'] = array();
			$resultItem['rootID'][$item['serverID']] = $item['rootID'];
			$resultItem['serverID'] = array();
			$resultItem['serverID'][$item['serverID']] = $item['rootID'];

			// Initialize counters if any of it don't exist
			if (!isset($resultItem['counters']))
				$resultItem['counters'] = array();
			if (!isset($resultItem['counters']['unknown']))
				$resultItem['counters']['unknown'] = 0;
			if (!isset($resultItem['counters']['critical']))
				$resultItem['counters']['critical'] = 0;
			if (!isset($resultItem['counters']['warning']))
				$resultItem['counters']['warning'] = 0;
			if (!isset($resultItem['counters']['not_init']))
				$resultItem['counters']['not_init'] = 0;
			if (!isset($resultItem['counters']['ok']))
				$resultItem['counters']['ok'] = 0;
			if (!isset($resultItem['counters']['total']))
				$resultItem['counters']['total'] = 0;
			if (!isset($resultItem['counters']['alerts']))
				$resultItem['counters']['alerts'] = 0;

			if ($item['type'] == 'group') {
				// Add the children
				if (!isset($resultItem['children']))
					$resultItem['children'] = array();
			}

			// Iterate over the list to search items that match the actual item
			foreach ($items as $key2 => $item2) {
				// Skip the actual or empty items
				if ($key == $key2 || !isset($items[$key2]))
					continue;

				// Match with the name and type
				if ($item['name'] == $item2['name'] && $item['type'] == $item2['type']) {
					// Add the matched ids
					$resultItem['id'][$item2['serverID']] = $item2['id'];
					$resultItem['rootID'][$item2['serverID']] = $item2['rootID'];
					$resultItem['serverID'][$item2['serverID']] = $item2['rootID'];

					// Add the matched counters
					if (isset($item2['counters']) && !empty($item2['counters'])) {
						foreach ($item2['counters'] as $type => $value) {
							if (isset($resultItem['counters'][$type]))
								$resultItem['counters'][$type] += $value;
						}
					}

					if ($item['type'] == 'group') {
						// Add the matched children
						if (isset($item2['children']))
							$resultItem['children'] = array_merge($resultItem['children'], $item2['children']);
					}

					// Remove the item
					unset($items[$key2]);
				}
			}

			if ($item['type'] == 'group') {
				// Get the merged children (recursion)
				if (!empty($resultItem['children']))
					$resultItem['children'] = $this->getMergedItems($resultItem['children']);

			}

			// Add the resulting item
			if (!empty($resultItem) && !empty($resultItem['counters']['total']))
				$mergedItems[] = $resultItem;

			// Remove the item
			unset($items[$key]);
		}

		usort($mergedItems, array("Tree", "cmpSortNames"));

		return $mergedItems;
	}

	protected function processModule (&$module, $server = false, $all_groups) {
		global $config;
		
		if (isset($module['children'])) {
			foreach ($module['children'] as $i => $children) {
				$this->processModule($module['children'][$i], $server, $all_groups);
			}
		}

		$module['type'] = 'module';
		$module['id'] = (int) $module['id'];
		$module['name'] = io_safe_output($module['name']);
		$module['id_module_type'] = (int) $module['id_tipo_modulo'];
		$module['server_type'] = (int) $module['id_modulo'];
		$module['status'] = $module['estado'];
		$module['value'] = $module['datos'];

		if (is_metaconsole() && !empty($server)) {
			$module['serverID'] = $server['id'];
			$module['serverName'] = $server['server_name'];
		}
		else {
			$module['serverName'] = false;
			$module['serverID'] = false;
		}

		if (!isset($module['value']))
			$module['value'] = modules_get_last_value($module['id']);

		// Status
		switch ($module['status']) {
			case AGENT_MODULE_STATUS_CRITICAL_ALERT:
				$module['alert'] = 1;
			case AGENT_MODULE_STATUS_CRITICAL_BAD:
				$statusType = STATUS_MODULE_CRITICAL_BALL;
				$statusTitle = __('CRITICAL');
				$module['statusText'] = "critical";
				break;
			case AGENT_MODULE_STATUS_WARNING_ALERT:
				$module['alert'] = 1;
			case AGENT_MODULE_STATUS_WARNING:
				$statusType = STATUS_MODULE_WARNING_BALL;
				$statusTitle = __('WARNING');
				$module['statusText'] = "warning";
				break;
			case AGENT_MODULE_STATUS_UNKNOWN:
				$statusType = STATUS_MODULE_UNKNOWN_BALL;
				$statusTitle = __('UNKNOWN');
				$module['statusText'] = "unknown";
				break;
			case AGENT_MODULE_STATUS_NO_DATA:
			case AGENT_MODULE_STATUS_NOT_INIT:
				$statusType = STATUS_MODULE_NO_DATA_BALL;
				$statusTitle = __('NO DATA');
				$module['statusText'] = "not_init";
				break;
			case AGENT_MODULE_STATUS_NORMAL_ALERT:
				$module['alert'] = 1;
			case AGENT_MODULE_STATUS_NORMAL:
			default:
				$statusType = STATUS_MODULE_OK_BALL;
				$statusTitle = __('NORMAL');
				$module['statusText'] = "ok";
				break;
		}

		if ($statusType !== STATUS_MODULE_UNKNOWN_BALL
				&& $statusType !== STATUS_MODULE_NO_DATA_BALL) {
			if (is_numeric($module["value"])) {
				$statusTitle .= " : " . format_for_graph($module["value"]);
			}
			else {
				$statusTitle .= " : " . substr(io_safe_output($module["value"]),0,42);
			}
		}

		$module['statusImageHTML'] = ui_print_status_image($statusType, $statusTitle, true);

		// HTML of the server type image
		$module['serverTypeHTML'] = servers_show_type($module['server_type']);

		// Link to the Module graph

		// ACL
		$acl_graphs = false;
		$module["showGraphs"] = 0;

		// Avoid the check on the metaconsole. Too slow to show/hide an icon depending on the permissions
		if (!empty($group_id) && !is_metaconsole()) {
			$acl_graphs = check_acl_one_of_groups($config['id_user'], $all_groups, "RR");
		}
		else if (!empty($all_groups)) {
			$acl_graphs = true;
		}

		if ($acl_graphs) {
			$module["showGraphs"] = 1;
		}

		if ($module["showGraphs"]) {
			$graphType = return_graphtype($module['id_module_type']);
			$url = ui_get_full_url("operation/agentes/stat_win.php", false, false, false);
			$winHandle = dechex(crc32($module['id'].$module['name']));

			$graph_params = array(
					"type" => $graphType,
					"period" => SECONDS_1DAY,
					"id" => $module['id'],
					"label" => base64_encode($module['name']),
					"refresh" => SECONDS_10MINUTES
				);

			if (is_metaconsole() && !empty($server)) {
				// Set the server id
				$graph_params["server"] = $module['serverID'];
			}

			$graph_params_str = http_build_query($graph_params);
			$moduleGraphURL = "$url?$graph_params_str";

			$module['moduleGraph'] = array(
					'url' => $moduleGraphURL,
					'handle' => $winHandle
				);

			// Info to be able to open the snapshot image new page
			$module['snapshot'] = ui_get_snapshot_link(array(
				'id_module' => $module['id'],
				'interval' => $module['current_interval'],
				'module_name' => $module['name'],
				'id_node' => $module['serverID'] ? $module['serverID'] : 0,
			), true);
		}

		// Alerts fired image
		$has_alerts = (bool) db_get_value(
			'id_agent_module',
			'talert_template_modules', 'id_agent_module', $module['id']);

		if ($has_alerts) {
			$module['alertsImageHTML'] = html_print_image("images/bell.png", true, array("title" => __('Module alerts')));
		}
	}

	protected function processModules (&$modules, $server = false) {
		if (!empty($modules)) {
			$all_groups = modules_get_agent_groups($modules[0]['id']);
		}
		foreach ($modules as $iterator => $module) {
			$this->processModule($modules[$iterator], $server, $all_groups);
		}
	}

	protected function processAgent (&$agent, $server = false) {
		global $config;

		$agent['type'] = 'agent';
		$agent['id'] = (int) $agent['id'];
		$agent['name'] = $agent['name'];

		$agent['rootID'] = $this->rootID;
		$agent['rootType'] = $this->rootType;

		if (is_metaconsole()) {
			if (isset($agent['server_id']))
				$agent['serverID'] = $agent['server_id'];
			else if (!empty($server))
				$agent['serverID'] = $server['id'];
		}
		// Counters
		if (empty($agent['counters'])) {
			$agent['counters'] = array();

			$agent['counters']['unknown'] = isset($agent['unknown_count']) ? $agent['unknown_count'] : 0;
			$agent['counters']['critical'] = isset($agent['critical_count']) ? $agent['critical_count'] : 0;
			$agent['counters']['warning'] = isset($agent['warning_count']) ? $agent['warning_count'] : 0;
			$agent['counters']['not_init'] = isset($agent['notinit_count']) ? $agent['notinit_count'] : 0;
			$agent['counters']['ok'] = isset($agent['normal_count']) ? $agent['normal_count'] : 0;
			$agent['counters']['total'] = isset($agent['total_count']) ? $agent['total_count'] : 0;
			$agent['counters']['alerts'] = isset($agent['fired_count']) ? $agent['fired_count'] : 0;
		}

		// Status image
		$agent['statusImageHTML'] = agents_tree_view_status_img_ball(
				$agent['counters']['critical'],
				$agent['counters']['warning'],
				$agent['counters']['unknown'],
				$agent['counters']['total'],
				$agent['counters']['not_init']);

		// Alerts fired image
		$agent["alertImageHTML"] = agents_tree_view_alert_img_ball($agent['counters']['alerts']);

		// search module recalculate counters
		if(array_key_exists('state_normal', $agent)){
			$agent['counters']['unknown'] = $agent['state_unknown'];
			$agent['counters']['critical'] = $agent['state_critical'];
			$agent['counters']['warning'] = $agent['state_warning'];
			$agent['counters']['not_init'] = $agent['state_notinit'];
			$agent['counters']['ok'] = $agent['state_normal'];
			$agent['counters']['total'] = $agent['state_total'];

			$agent['critical_count'] = $agent['counters']['critical'];
			$agent['warning_count'] = $agent['counters']['warning'];
			$agent['unknown_count'] = $agent['counters']['unknown'];
			$agent['notinit_count'] = $agent['counters']['not_init'];
			$agent['normal_count'] = $agent['counters']['ok'];
			$agent['total_count'] = $agent['counters']['total'];
		}

		if (!$this->getEmptyModuleFilterStatus()) {
			$agent['counters']['unknown'] = 0;
			$agent['counters']['critical'] = 0;
			$agent['counters']['warning'] = 0;
			$agent['counters']['not_init'] = 0;
			$agent['counters']['ok'] = 0;
			$agent['counters']['total'] = 0;
			switch($this->filter['statusModule']) {
				case AGENT_MODULE_STATUS_CRITICAL_ALERT:
				case AGENT_MODULE_STATUS_CRITICAL_BAD:
					$agent['counters']['critical'] = $agent['critical_count'];
					$agent['counters']['total'] = $agent['critical_count'];
					break;
				case AGENT_MODULE_STATUS_WARNING_ALERT:
				case AGENT_MODULE_STATUS_WARNING:
					$agent['counters']['warning'] = $agent['warning_count'];
					$agent['counters']['total'] = $agent['warning_count'];
					break;
				case AGENT_MODULE_STATUS_UNKNOWN:
					$agent['counters']['unknown'] = $agent['unknown_count'];
					$agent['counters']['total'] = $agent['unknown_count'];
					break;
				case AGENT_MODULE_STATUS_NO_DATA:
				case AGENT_MODULE_STATUS_NOT_INIT:
					$agent['counters']['not_init'] = $agent['notinit_count'];
					$agent['counters']['total'] = $agent['notinit_count'];
					break;
				case AGENT_MODULE_STATUS_NORMAL_ALERT:
				case AGENT_MODULE_STATUS_NORMAL:
					$agent['counters']['ok'] = $agent['normal_count'];
					$agent['counters']['total'] = $agent['normal_count'];
					break;
			}
		}

		if (!$this->filter['show_not_init_modules']) {
			$agent['counters']['total'] -= $agent['counters']['not_init'];
			$agent['counters']['not_init'] = 0;
		}

		// Quiet image
		if (isset($agent['quiet']) && $agent['quiet'])
			$agent['quietImageHTML'] = html_print_image("/images/dot_blue.png", true, array("title" => __('Quiet')));

		// Children
		if (empty($agent['children'])) {
			$agent['children'] = array();
			if ($agent['counters']['total'] > 0) {
				switch ($this->childrenMethod) {
					case 'on_demand':
						$agent['searchChildren'] = 1;
						break;
					case 'live':
						$agent['searchChildren'] = 0;
						break;
				}
			}
			else {
				switch ($this->childrenMethod) {
					case 'on_demand':
						$agent['searchChildren'] = 0;
						break;
					case 'live':
						$agent['searchChildren'] = 0;
						break;
				}
			}
		}
	}

	protected function processAgents (&$agents, $server = false) {
		if (!empty($agents)) {
			foreach ($agents as $iterator => $agent) {
				$this->processAgent($agents[$iterator], $server);
			}
		}
	}

	/**
	 * @brief Recursive function to remove the empty groups
	 * 
	 * @param groups All groups structure
	 * 
	 * @return new_groups A new groups structure without empty groups
	 */
	protected static function deleteEmptyGroups ($groups) {
		$new_groups = array();
		foreach ($groups as $group) {
			// If a group is empty, do not add to new_groups.
			if (!isset($group['counters']['total']) || $group['counters']['total'] == 0) {
				continue;
			}
			// Tray to remove the children groups
			if (!empty($group['children'])) {
				$children = Tree::deleteEmptyGroups ($group['children']);
				if (empty($children)) unset($group['children']);
				else $group['children'] = $children;
			}
			$new_groups[] = $group;
		}
		return $new_groups;
	}

	private static function extractGroupsWithIDs ($groups, $ids_hash) {
		$result_groups = array();
		foreach ($groups as $group) {
			if (isset($ids_hash[$group['id']])) {
				$result_groups[] = $group;
			}
			else if (!empty($group['children'])) {
				$result = self::extractGroupsWithIDs($group['children'], $ids_hash);

				// Item found on children
				if (!empty($result)) {
					$result_groups = array_merge($result_groups, $result);
				}
			}
		}

		return $result_groups;
	}

	private static function extractItemWithID ($items, $item_id, $item_type = "group", $strictACL = false) {
		foreach ($items as $item) {
			if ($item["type"] != $item_type)
				continue;

			// Item found
			if ($item["id"] == $item_id)
				return $item;

			if ($item["type"] == "group" && !empty($item["children"])) {
				$result = self::extractItemWithID($item["children"], $item_id, $item_type, $strictACL);

				// Item found on children
				if ($result !== false)
					return $result;
			}
		}

		// Item not found
		return false;
	}

	public function getData() {
		if (! is_metaconsole()) {
			switch ($this->type) {
				case 'os':
					$this->getDataOS();
					break;
				case 'group':
					$this->getDataGroup();
					break;
				case 'module_group':
					$this->getDataModuleGroup();
					break;
				case 'module':
					$this->getDataModules();
					break;
				case 'tag':
					$this->getDataTag();
					break;
				case 'agent':
					$this->getDataAgent();
					break;
				default:
					$this->getDataExtended();
				}
		} else {
			if ($this->type == 'agent') {
				$this->getDataAgent();
			}
			else {
				$this->getDataGroup();
			}
		}
	}

	protected function getDataExtended () {
		// Override this method to add new types
	}

	private function getDataAgent () {
		$processed_items = array();

		// Module names
		if ($this->id == -1) {

		}
		// Agents
		else {
			if (! is_metaconsole()) {
				$items = $this->getItems();
				$this->processModules($items);
				$processed_items = $items;
				
				/*if(!$this->filter['show_not_init_modules']){
					
					foreach ($items as $key => $value) {
						if($items[$key]['total_count'] != $items[$key]['notinit_count']){
							$items[$key]['total_count'] = $items[$key]['total_count'] - $items[$key]['notinit_count'];
							$items[$key]['notinit_count'] = 0;
						}
						
					}
					
				}*/
			}
			else {
				$items = array();

				if ($this->serverID !== false) {

					$server = metaconsole_get_servers($this->serverID);
					if (metaconsole_connect($server) == NOERR) {
						$items = $this->getItems();
						$this->processModules($items, $server);

						metaconsole_restore_db();
					}
				}

				$processed_items = $items;
			}
		}

		$this->tree = $processed_items;
	}

	private function getDataGroup() {
		$processed_items = array();

		// Groups
		if ($this->id == -1) {

			//$items = $this->getItems();
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
		}
		// Agents
		else {
			$items = $this->getItems();
			$this->processAgents($items);
			$processed_items = $items;
		}

		$this->tree = $processed_items;
	}

	private function getDataTag() {
		$processed_items = array();

		// Tags
		if ($this->id == -1) {
			if (! is_metaconsole()) {
				$items = $this->getItems();

				foreach ($items as $key => $item) {

					$counters = $this->getCounters($item['id']);
					if (!empty($counters)) {
						foreach ($counters as $type => $value) {
							$item[$type] = $value;
						}
					}

					$processed_item = $this->getProcessedItem($item);
					$processed_items[] = $processed_item;
				}
			}
			else {
				$servers = metaconsole_get_servers();

				$item_list = array();
				foreach ($servers as $server) {
					if (metaconsole_connect($server) != NOERR)
						continue;
					db_clean_cache();

					$items = $this->getItems();

					$processed_items = array();
					foreach ($items as $key => $item) {

						$counters = $this->getCounters($item['id']);
						if (!empty($counters)) {
							foreach ($counters as $type => $value) {
								$item[$type] = $value;
							}
						}

						$processed_item = $this->getProcessedItem($item, $server);
						$processed_items[] = $processed_item;
					}
					$item_list = array_merge($item_list, $processed_items);

					metaconsole_restore_db();
				}

				$processed_items = $this->getMergedItems($item_list);
			}
		}
		// Agents
		else {
			if (! is_metaconsole()) {
				$items = $this->getItems();
				$this->processAgents($items);
				$processed_items = $items;
			}
			else {
				$rootIDs = $this->rootID;

				$items = array();
				foreach ($rootIDs as $serverID => $rootID) {
					$server = metaconsole_get_servers($serverID);
					if (metaconsole_connect($server) != NOERR)
						continue;
					db_clean_cache();

					$this->rootID = $rootID;
					$newItems = $this->getItems();
					$this->processAgents($newItems, $server);
					$items = array_merge($items, $newItems);

					metaconsole_restore_db();
				}
				$this->rootID = $rootIDs;

				if (!empty($items))
					usort($items, array("Tree", "cmpSortNames"));

				$processed_items = $items;
			}
		}

		$this->tree = $processed_items;
	}

	private function getDataModules() {
		$processed_items = array();
		// Module names
		if ($this->id == -1) {
			if (! is_metaconsole()) {
				//FIXME REFACTOR ME, PLEASE

				$fields = array (
					"g AS nombre",
					"SUM(x_critical) AS total_critical_count",
					"SUM(x_warning) AS total_warning_count",
					"SUM(x_normal) AS total_normal_count",
					"SUM(x_unknown) AS total_unknown_count",
					"SUM(x_not_init) AS total_not_init_count",
					"SUM(x_alerts) AS total_alerts_count",
					"SUM(x_total) AS total_count"
				);
				$fields = implode(", ", $fields);
				$array_array = array(
					'warning' => array(
						'header' => "0 AS x_critical, SUM(total) AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.warning_count > 0 AND ta.critical_count = 0"
					),
					'critical' => array(
						'header' => "SUM(total) AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.critical_count > 0"
					),
					'normal' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, SUM(total) AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count = 0 AND ta.normal_count > 0"
					),
					'unknown' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, SUM(total) AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count > 0"
					),
					'not_init' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, SUM(total) AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => $this->filter['show_not_init_agents'] ? "AND ta.total_count = ta.notinit_count" : " AND 1=0"
					),
					'alerts' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, SUM(total) AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.fired_count > 0"
					),
					'total' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, SUM(total) AS x_total, g",
						'condition' => $this->filter['show_not_init_agents'] ? "" : "AND ta.total_count <> ta.notinit_count"
					)
				);
				$filters = array(
					'agent_alias' => '',
					'agent_status' => '',
					'module_status' => '',
					'module_search_condition' => '',
					'module_status_inner' => '',
					'group_search_condition' => '',
					'group_search_inner' => ''

				);
				if (!empty($this->filter['searchAgent'])) {
					$filters['agent_alias'] = "AND LOWER(ta.alias) LIKE LOWER('%".$this->filter['searchAgent']."%')";
				}
				if ($this->filter['statusAgent'] >= 0) {
					$filters['agent_status'] = $this->getAgentStatusFilter();
				}
				if ($this->filter['statusModule'] >= 0) {
					$filters['module_status_inner'] = "
						INNER JOIN tagente_estado tae
							ON tae.id_agente_modulo = tam.id_agente_modulo";
					$filters['module_status'] = $this->getModuleStatusFilterFromTestado();
				}
				if (!empty($this->filter['searchGroup'])) {
					$filters['group_search_inner'] = "
						INNER JOIN tgrupo tg
							ON ta.id_grupo = tg.id_grupo
							OR tasg.id_group = tg.id_grupo";
					$filters['group_search_condition'] = "AND tg.nombre LIKE '%" . $this->filter['searchGroup'] . "%'";
				}
				if (!empty($this->filter['searchModule'])) {
					$filters['module_search_condition'] = " AND tam.nombre LIKE '%" . $this->filter['searchModule'] . "%' ";
				}

				$group_acl = "";
				if (!users_can_manage_group_all("AR")) {
					$user_groups_str = implode(",", $this->userGroupsArray);
					$group_acl = " AND (ta.id_grupo IN ($user_groups_str) OR tasg.id_group IN ($user_groups_str))";
				}

				$sql_model = "SELECT %s FROM
					(
						SELECT COUNT(DISTINCT(ta.id_agente)) AS total, tam.nombre AS g
							FROM tagente ta
							LEFT JOIN tagent_secondary_group tasg
								ON ta.id_agente = tasg.id_agent
							INNER JOIN tagente_modulo tam
								ON ta.id_agente = tam.id_agente
							%s %s
							WHERE ta.disabled = 0
								AND tam.disabled = 0
								%s %s %s
								%s %s
								%s %s
							GROUP BY tam.nombre
					) x GROUP BY g";
				$sql_array = array();
				foreach ($array_array as $s_array) {
					$sql_array[] = sprintf(
						$sql_model,
						$s_array['header'],
						$filters['module_status_inner'], $filters['group_search_inner'],
						$s_array['condition'], $filters['agent_alias'], $filters['agent_status'],
						$filters['module_status'], $filters['module_search_condition'],
						$filters['group_search_condition'], $group_acl
					);
				}
				$sql = "SELECT $fields FROM (" . implode(" UNION ALL ", $sql_array) . ") x2 GROUP BY g";
				$items = db_get_all_rows_sql($sql);


				//END REFACTOR ME, PLEASE

				foreach ($items as $key => $item) {
					$item['name'] = $item['nombre'];
					$name = str_replace(array(' ','#','/','.','(',')','¿','?','¡','!'),
								array(  '_articapandora_'.ord(' ').'_pandoraartica_',
										'_articapandora_'.ord('#').'_pandoraartica_',
										'_articapandora_'.ord('/').'_pandoraartica_',
										'_articapandora_'.ord('.').'_pandoraartica_',
										'_articapandora_'.ord('(').'_pandoraartica_',
										'_articapandora_'.ord(')').'_pandoraartica_',
										'_articapandora_'.ord('¿').'_pandoraartica_',
										'_articapandora_'.ord('?').'_pandoraartica_',
										'_articapandora_'.ord('¡').'_pandoraartica_',
										'_articapandora_'.ord('!').'_pandoraartica_'),
								io_safe_output($item['name']));

					$processed_item = $this->getProcessedItem($item);
					$processed_item['id'] = $name;
					$processed_item['rootID'] = $name;

					$processed_items[] = $processed_item;
				}
			}
			else {
				$servers = metaconsole_get_servers();

				$item_list = array();
				foreach ($servers as $server) {
					if (metaconsole_connect($server) != NOERR)
						continue;
					db_clean_cache();

					$items = $this->getItems();

					$processed_items = array();
					foreach ($items as $key => $item) {

						$counters = $this->getCounters($item['name']);
						if (!empty($counters)) {
							foreach ($counters as $type => $value) {
								$item[$type] = $value;
							}
						}

						$name = str_replace(array(' ','#','/','.','(',')','¿','?','¡','!'),
									array(  '_articapandora_'.ord(' ').'_pandoraartica_',
											'_articapandora_'.ord('#').'_pandoraartica_',
											'_articapandora_'.ord('/').'_pandoraartica_',
											'_articapandora_'.ord('.').'_pandoraartica_',
											'_articapandora_'.ord('(').'_pandoraartica_',
											'_articapandora_'.ord(')').'_pandoraartica_',
											'_articapandora_'.ord('¿').'_pandoraartica_',
											'_articapandora_'.ord('?').'_pandoraartica_',
											'_articapandora_'.ord('¡').'_pandoraartica_',
											'_articapandora_'.ord('!').'_pandoraartica_'),
									io_safe_output($item['name']));

						$processed_item = $this->getProcessedItem($item, $server);
						$processed_item['id'] = $name;
						$processed_item['rootID'] = $name;

						$processed_items[] = $processed_item;
					}
					$item_list = array_merge($item_list, $processed_items);

					metaconsole_restore_db();
				}

				$processed_items = $this->getMergedItems($item_list);
			}
		}
		// Agents
		else {
			if (! is_metaconsole()) {
				$items = $this->getItems();
				$this->processAgents($items);
				$processed_items = $items;
			}
			else {
				$rootIDs = $this->rootID;

				$items = array();
				foreach ($rootIDs as $serverID => $rootID) {
					$server = metaconsole_get_servers($serverID);
					if (metaconsole_connect($server) != NOERR)
						continue;
					db_clean_cache();

					$this->rootID = $rootID;
					$newItems = $this->getItems();
					$this->processAgents($newItems, $server);
					$items = array_merge($items, $newItems);

					metaconsole_restore_db();
				}
				$this->rootID = $rootIDs;

				if (!empty($items))
					usort($items, array("Tree", "cmpSortNames"));

				$processed_items = $items;
			}
		}

		$this->tree = $processed_items;
	}

	private function getDataModuleGroup() {
		$processed_items = array();
		// Module groups
		if ($this->id == -1) {
			if (! is_metaconsole()) {
				//FIXME REFACTOR ME, PLEASE

				$fields = array (
					"g AS id_module_group",
					"SUM(x_critical) AS total_critical_count",
					"SUM(x_warning) AS total_warning_count",
					"SUM(x_normal) AS total_normal_count",
					"SUM(x_unknown) AS total_unknown_count",
					"SUM(x_not_init) AS total_not_init_count",
					"SUM(x_alerts) AS total_alerts_count",
					"SUM(x_total) AS total_count"
				);
				$fields = implode(", ", $fields);
				$array_array = array(
					'warning' => array(
						'header' => "0 AS x_critical, SUM(total) AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.warning_count > 0 AND ta.critical_count = 0"
					),
					'critical' => array(
						'header' => "SUM(total) AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.critical_count > 0"
					),
					'normal' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, SUM(total) AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count = 0 AND ta.normal_count > 0"
					),
					'unknown' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, SUM(total) AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count > 0"
					),
					'not_init' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, SUM(total) AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => $this->filter['show_not_init_agents'] ? "AND ta.total_count = ta.notinit_count" : " AND 1=0"
					),
					'alerts' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, SUM(total) AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.fired_count > 0"
					),
					'total' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, SUM(total) AS x_total, g",
						'condition' => $this->filter['show_not_init_agents'] ? "" : "AND ta.total_count <> ta.notinit_count"
					)
				);
				$filters = array(
					'agent_alias' => '',
					'agent_status' => '',
					'module_status' => '',
					'module_search_condition' => '',
					'module_status_inner' => '',
					'group_search_condition' => '',
					'group_search_inner' => ''

				);
				if (!empty($this->filter['searchAgent'])) {
					$filters['agent_alias'] = "AND LOWER(ta.alias) LIKE LOWER('%".$this->filter['searchAgent']."%')";
				}
				if ($this->filter['statusAgent'] >= 0) {
					$filters['agent_status'] = $this->getAgentStatusFilter();
				}
				if ($this->filter['statusModule'] >= 0) {
					$filters['module_status_inner'] = "
						INNER JOIN tagente_estado tae
							ON tae.id_agente_modulo = tam.id_agente_modulo";
					$filters['module_status'] = $this->getModuleStatusFilterFromTestado();
				}
				if (!empty($this->filter['searchGroup'])) {
					$filters['group_search_inner'] = "
						INNER JOIN tgrupo tg
							ON ta.id_grupo = tg.id_grupo
							OR tasg.id_group = tg.id_grupo";
					$filters['group_search_condition'] = "AND tg.nombre LIKE '%" . $this->filter['searchGroup'] . "%'";
				}
				if (!empty($this->filter['searchModule'])) {
					$filters['module_search_condition'] = " AND tam.nombre LIKE '%" . $this->filter['searchModule'] . "%' ";
				}

				$group_acl = "";
				if (!users_can_manage_group_all("AR")) {
					$user_groups_str = implode(",", $this->userGroupsArray);
					$group_acl = " AND (ta.id_grupo IN ($user_groups_str) OR tasg.id_group IN ($user_groups_str))";
				}

				$sql_model = "SELECT %s FROM
					(
						SELECT COUNT(DISTINCT(ta.id_agente)) AS total, tam.id_module_group AS g
							FROM tagente ta
							LEFT JOIN tagent_secondary_group tasg
								ON ta.id_agente = tasg.id_agent
							INNER JOIN tagente_modulo tam
								ON ta.id_agente = tam.id_agente
							%s %s
							WHERE ta.disabled = 0
								AND tam.disabled = 0
								%s %s %s
								%s %s
								%s %s
							GROUP BY tam.id_module_group
					) x GROUP BY g";
				$sql_array = array();
				foreach ($array_array as $s_array) {
					$sql_array[] = sprintf(
						$sql_model,
						$s_array['header'],
						$filters['module_status_inner'], $filters['group_search_inner'],
						$s_array['condition'], $filters['agent_alias'], $filters['agent_status'],
						$filters['module_status'], $filters['module_search_condition'],
						$filters['group_search_condition'], $group_acl
					);
				}
				$sql = "SELECT $fields, tmg.name, tmg.id_mg AS id FROM (" . implode(" UNION ALL ", $sql_array) . ") x2
					INNER JOIN tmodule_group tmg
						ON tmg.id_mg = x2.g
					GROUP BY g
					ORDER BY tmg.name";
				$items = db_get_all_rows_sql($sql);


				//END REFACTOR ME, PLEASE
				foreach ($items as $item) {
					$processed_item = $this->getProcessedItem($item);
					$processed_items[] = $processed_item;
				}
			}
			else {
				$servers = metaconsole_get_servers();

				$item_list = array();
				foreach ($servers as $server) {
					if (metaconsole_connect($server) != NOERR)
						continue;
					db_clean_cache();

					$items = $this->getItems();

					$processed_items = array();
					foreach ($items as $key => $item) {

						$counters = $this->getCounters($item['id']);
						if (!empty($counters)) {
							foreach ($counters as $type => $value) {
								$item[$type] = $value;
							}
						}

						$processed_item = $this->getProcessedItem($item, $server);
						$processed_items[] = $processed_item;
					}
					$item_list = array_merge($item_list, $processed_items);

					metaconsole_restore_db();
				}

				$processed_items = $this->getMergedItems($item_list);
			}
		}
		// Agents
		else {
			if (! is_metaconsole()) {
				$items = $this->getItems();
				$this->processAgents($items);
				$processed_items = $items;
			}
			else {
				$rootIDs = $this->rootID;

				$items = array();
				foreach ($rootIDs as $serverID => $rootID) {
					$server = metaconsole_get_servers($serverID);
					if (metaconsole_connect($server) != NOERR)
						continue;
					db_clean_cache();

					$this->rootID = $rootID;
					$newItems = $this->getItems();
					$this->processAgents($newItems, $server);
					$items = array_merge($items, $newItems);

					metaconsole_restore_db();
				}
				$this->rootID = $rootIDs;

				if (!empty($items))
					usort($items, array("Tree", "cmpSortNames"));

				$processed_items = $items;
			}
		}

		$this->tree = $processed_items;
	}

	private function getDataOS() {
		$processed_items = array();

		// OS
		if ($this->id == -1) {
			if (! is_metaconsole()) {
				//FIXME REFACTOR ME, PLEASE

				$fields = array (
					"g AS id_os",
					"SUM(x_critical) AS total_critical_count",
					"SUM(x_warning) AS total_warning_count",
					"SUM(x_normal) AS total_normal_count",
					"SUM(x_unknown) AS total_unknown_count",
					"SUM(x_not_init) AS total_not_init_count",
					"SUM(x_alerts) AS total_alerts_count",
					"SUM(x_total) AS total_count"
				);
				$fields = implode(", ", $fields);
				$array_array = array(
					'warning' => array(
						'header' => "0 AS x_critical, SUM(total) AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.warning_count > 0 AND ta.critical_count = 0"
					),
					'critical' => array(
						'header' => "SUM(total) AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.critical_count > 0"
					),
					'normal' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, SUM(total) AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count = 0 AND ta.normal_count > 0"
					),
					'unknown' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, SUM(total) AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count > 0"
					),
					'not_init' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, SUM(total) AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
						'condition' => $this->filter['show_not_init_agents'] ? "AND ta.total_count = ta.notinit_count" : " AND 1=0"
					),
					'alerts' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, SUM(total) AS x_alerts, 0 AS x_total, g",
						'condition' => "AND ta.fired_count > 0"
					),
					'total' => array(
						'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, SUM(total) AS x_total, g",
						'condition' => $this->filter['show_not_init_agents'] ? "" : "AND ta.total_count <> ta.notinit_count"
					)
				);
				$filters = array(
					'agent_alias' => '',
					'agent_status' => '',
					'module_status' => '',
					'module_search_condition' => '',
					'module_status_inner' => '',
					'group_search_condition' => '',
					'group_search_inner' => ''

				);
				if (!empty($this->filter['searchAgent'])) {
					$filters['agent_alias'] = "AND LOWER(ta.alias) LIKE LOWER('%".$this->filter['searchAgent']."%')";
				}
				if ($this->filter['statusAgent'] >= 0) {
					$filters['agent_status'] = $this->getAgentStatusFilter();
				}
				if ($this->filter['statusModule'] >= 0) {
					$filters['module_status_inner'] = "
						INNER JOIN tagente_estado tae
							ON tae.id_agente_modulo = tam.id_agente_modulo";
					$filters['module_status'] = $this->getModuleStatusFilterFromTestado();
				}
				if (!empty($this->filter['searchGroup'])) {
					$filters['group_search_inner'] = "
						INNER JOIN tgrupo tg
							ON ta.id_grupo = tg.id_grupo
							OR tasg.id_group = tg.id_grupo";
					$filters['group_search_condition'] = "AND tg.nombre LIKE '%" . $this->filter['searchGroup'] . "%'";
				}
				if (!empty($this->filter['searchModule'])) {
					$filters['module_search_condition'] = " AND tam.nombre LIKE '%" . $this->filter['searchModule'] . "%' ";
				}

				$group_acl = "";
				if (!users_can_manage_group_all("AR")) {
					$user_groups_str = implode(",", $this->userGroupsArray);
					$group_acl = " AND (ta.id_grupo IN ($user_groups_str) OR tasg.id_group IN ($user_groups_str))";
				}

				$sql_model = "SELECT %s FROM
					(
						SELECT COUNT(DISTINCT(ta.id_agente)) AS total, ta.id_os AS g
							FROM tagente ta
							LEFT JOIN tagent_secondary_group tasg
								ON ta.id_agente = tasg.id_agent
							INNER JOIN tagente_modulo tam
								ON ta.id_agente = tam.id_agente
							%s %s
							WHERE ta.disabled = 0
								AND tam.disabled = 0
								%s %s %s
								%s %s
								%s %s
							GROUP BY ta.id_os
					) x GROUP BY g";
				$sql_array = array();
				foreach ($array_array as $s_array) {
					$sql_array[] = sprintf(
						$sql_model,
						$s_array['header'],
						$filters['module_status_inner'], $filters['group_search_inner'],
						$s_array['condition'], $filters['agent_alias'], $filters['agent_status'],
						$filters['module_status'], $filters['module_search_condition'],
						$filters['group_search_condition'], $group_acl
					);
				}
				$sql = "SELECT $fields, tco.name, tco.id_os AS id, tco.icon_name AS iconHTML FROM (" . implode(" UNION ALL ", $sql_array) . ") x2
					INNER JOIN tconfig_os tco
						ON tco.id_os = x2.g
					GROUP BY g
					ORDER BY tco.name";
				$items = db_get_all_rows_sql($sql);


				//END REFACTOR ME, PLEASE

				foreach ($items as $key => $item) {
					$processed_item = $this->getProcessedItem($item);
					$processed_items[] = $processed_item;
				}
			}
			else {
				$servers = metaconsole_get_servers();

				$item_list = array();
				foreach ($servers as $server) {
					if (metaconsole_connect($server) != NOERR)
						continue;
					db_clean_cache();

					$items = $this->getItems();

					$processed_items = array();
					foreach ($items as $key => $item) {

						$counters = $this->getCounters($item['id']);
						if (!empty($counters)) {
							foreach ($counters as $type => $value) {
								$item[$type] = $value;
							}
						}

						$processed_item = $this->getProcessedItem($item, $server);
						$processed_item['icon'] = $item['os_icon'];
						$processed_items[] = $processed_item;
					}
					$item_list = array_merge($item_list, $processed_items);

					metaconsole_restore_db();
				}

				$processed_items = $this->getMergedItems($item_list);
			}
		}
		// Agents
		else {
			if (! is_metaconsole()) {
				$items = $this->getItems();
				$this->processAgents($items);
				$processed_items = $items;
			}
			else {
				$rootIDs = $this->rootID;

				$items = array();
				foreach ($rootIDs as $serverID => $rootID) {
					$server = metaconsole_get_servers($serverID);
					if (metaconsole_connect($server) != NOERR)
						continue;
					db_clean_cache();

					$this->rootID = $rootID;
					$newItems = $this->getItems();
					$this->processAgents($newItems, $server);
					$items = array_merge($items, $newItems);

					metaconsole_restore_db();
				}
				$this->rootID = $rootIDs;

				if (!empty($items))
					usort($items, array("Tree", "cmpSortNames"));

				$processed_items = $items;
			}
		}

		$this->tree = $processed_items;
	}

	public function getJSON() {
		$this->getData();

		return json_encode($this->tree);
	}

	public function getArray() {
		$this->getData();

		return $this->tree;
	}

	static function processCounters(&$groups) {
		$all_counters = array();
		foreach ($groups as $id => $group) {
			$child_counters = array();
			if (!empty($groups[$id]['children'])) {
				$child_counters = Tree::processCounters($groups[$id]['children']);
			}
			if (!empty($child_counters)) {
				foreach($child_counters as $type => $value) {
					$groups[$id]['counters'][$type] += $value;
				}
			}
			foreach($groups[$id]['counters'] as $type => $value) {
				$all_counters[$type] += $value;
			}
		}
		return $all_counters;
	}

	protected function getProcessedModules($modules_tree) {
		$tree_modules = array();
		$new_modules_root = array_filter($modules_tree, function ($module) {
			return (isset($module['parent']) && ($module['parent'] == 0));
		});

		$new_modules_child = array_filter($modules_tree, function ($module) {
			return (isset($module['parent']) && ($module['parent'] != 0));
		});
		
		while (!empty($new_modules_child)) {
			foreach ($new_modules_child as $i => $child) {
				Tree::recursive_modules_tree_view($new_modules_root, $new_modules_child, $i, $child);
			}
		}

		foreach ($new_modules_root as $m) {
			$tree_modules[] = $m;
		}
		
		return $tree_modules;
	}

	protected function getGroupCounters($group_id) {
		global $config;
		static $group_stats = false;
		# Do not use the group stat cache when using tags or real time group stats.

		if ( $group_stats !== false) {
			return isset($group_stats[$group_id])
				? $group_stats[$group_id]
				: array(
					'total_count' => 0,
					'total_critical_count' => 0,
					'total_unknown_count' => 0,
					'total_warning_count' => 0,
					'total_not_init_count' => 0,
					'total_normal_count' => 0,
					'total_fired_count' => 0
				);
		}

		if ($config['realtimestats'] == 1 || 
			(isset($this->userGroups[$group_id]['tags']) && $this->userGroups[$group_id]['tags'] != "") || 
			!empty($this->filter['searchAgent']) ) {
			$fields = array (
				"g AS id_group",
				"SUM(x_critical) AS critical",
				"SUM(x_warning) AS warning",
				"SUM(x_normal) AS normal",
				"SUM(x_unknown) AS unknown",
				"SUM(x_not_init) AS `non-init`",
				"SUM(x_alerts) AS alerts_fired",
				"SUM(x_total) AS agents"
			);
			$fields = implode(", ", $fields);
			$array_array = array(
				'warning' => array(
					'header' => "0 AS x_critical, SUM(total) AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
					'condition' => "AND ta.warning_count > 0 AND ta.critical_count = 0"
				),
				'critical' => array(
					'header' => "SUM(total) AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
					'condition' => "AND ta.critical_count > 0"
				),
				'normal' => array(
					'header' => "0 AS x_critical, 0 AS x_warning, SUM(total) AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
					'condition' => "AND ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count = 0 AND ta.normal_count > 0"
				),
				'unknown' => array(
					'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, SUM(total) AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
					'condition' => "AND ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count > 0"
				),
				'not_init' => array(
					'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, SUM(total) AS x_not_init, 0 AS x_alerts, 0 AS x_total, g",
					'condition' => $this->filter['show_not_init_agents'] ? "AND ta.total_count = ta.notinit_count" : " AND 1=0"
				),
				'alerts' => array(
					'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, SUM(total) AS x_alerts, 0 AS x_total, g",
					'condition' => "AND ta.fired_count > 0"
				),
				'total' => array(
					'header' => "0 AS x_critical, 0 AS x_warning, 0 AS x_normal, 0 AS x_unknown, 0 AS x_not_init, 0 AS x_alerts, SUM(total) AS x_total, g",
					'condition' => $this->filter['show_not_init_agents'] ? "" : "AND ta.total_count <> ta.notinit_count"
				)
			);
			$filters = array(
				'agent_alias' => '',
				'agent_status' => '',
				'module_status' => '',
				'module_search' => ''
			);
			if (!empty($this->filter['searchAgent'])) {
				$filters['agent_alias'] = "AND LOWER(ta.alias) LIKE LOWER('%".$this->filter['searchAgent']."%')";
			}
			if ($this->filter['statusAgent'] >= 0) {
				$filters['agent_status'] = $this->getAgentStatusFilter();
			}
			if ($this->filter['statusModule'] >= 0) {
				$filters['module_status'] = $this->getModuleStatusFilter();
			}
			if (!empty($this->filter['searchModule'])) {
				$filters['module_search_inner'] = "INNER JOIN tagente_modulo tam
						ON ta.id_agente = tam.id_agente
					INNER JOIN tagente_estado tae
						ON tae.id_agente_modulo = tam.id_agente_modulo";
				$filters['module_search_condition'] = " AND tam.disabled = 0 AND tam.nombre LIKE '%" . $this->filter['searchModule'] . "%' " . $this->getModuleStatusFilterFromTestado();
			}

			$group_acl = "";
			$secondary_group_acl = "";
			if (!users_can_manage_group_all("AR")) {
				$user_groups_str = implode(",", $this->userGroupsArray);
				$group_acl = " AND ta.id_grupo IN ($user_groups_str)";
				$secondary_group_acl = " AND tasg.id_group IN ($user_groups_str)";
			}

			$table = is_metaconsole() ? "tmetaconsole_agent" : "tagente";
			$table_sec = is_metaconsole() ? "tmetaconsole_agent_secondary_group" : "tagent_secondary_group";
			$sql_model = "SELECT %s FROM
				(
					SELECT COUNT(DISTINCT(ta.id_agente)) AS total, id_group AS g
						FROM $table ta INNER JOIN $table_sec tasg
							ON ta.id_agente = tasg.id_agent
						%s
						WHERE ta.disabled = 0
							%s %s %s
							%s %s %s
						GROUP BY id_group
					UNION ALL
					SELECT COUNT(DISTINCT(ta.id_agente)) AS total, id_grupo AS g
						FROM $table ta
						%s
						WHERE ta.disabled = 0
							%s %s %s
							%s %s %s
						GROUP BY id_grupo
				) x GROUP BY g";
			$sql_array = array();
			foreach ($array_array as $s_array) {
				$sql_array[] = sprintf(
					$sql_model,
					$s_array['header'],
					$filters['module_search_inner'],
					$s_array['condition'], $filters['agent_alias'], $filters['agent_status'],
					$filters['module_status'], $filters['module_search_condition'], $secondary_group_acl,
					$filters['module_search_inner'],
					$s_array['condition'], $filters['agent_alias'], $filters['agent_status'],
					$filters['module_status'], $filters['module_search_condition'], $group_acl
				);
			}
			$hierarchy = $this->getDisplayHierarchy()
				? 'tg.parent'
				: '0 as parent';
			$sql = "SELECT $fields, tg.nombre AS `name`, $hierarchy, tg.icon, tg.id_grupo AS gid FROM (" . implode(" UNION ALL ", $sql_array) . ") x2 RIGHT JOIN tgrupo tg ON x2.g = tg.id_grupo GROUP BY tg.id_grupo";
			$stats = db_get_all_rows_sql($sql);
		}
		else{
			$stats = db_get_all_rows_sql('SELECT * FROM tgroup_stat');
		}


		# Update the group cache (from db or calculated).
		$group_stats = array();
		foreach ($stats as $group) {
//			$group_stats[$group['id_group']]['total_count'] = $group['modules'] > 0 ? $group['agents'] : 0;
			$group_stats[$group['gid']]['total_count'] = (bool)$group['agents'] ? $group['agents'] : 0;
			$group_stats[$group['gid']]['total_critical_count'] = $group['critical'] ? $group['critical'] : 0;
			$group_stats[$group['gid']]['total_unknown_count'] = $group['unknown'] ? $group['unknown'] : 0;
			$group_stats[$group['gid']]['total_warning_count'] = $group['warning'] ? $group['warning'] : 0;
			$group_stats[$group['gid']]['total_not_init_count'] = $group['non-init'] ? $group['non-init'] : 0;
			$group_stats[$group['gid']]['total_normal_count'] = $group['normal'] ? $group['normal'] : 0;
			$group_stats[$group['gid']]['total_fired_count'] = $group['alerts_fired'] ? $group['alerts_fired'] : 0;
			$group_stats[$group['gid']]['name'] = $group['name'];
			$group_stats[$group['gid']]['parent'] = $group['parent'];
			$group_stats[$group['gid']]['icon'] = $group['icon'];
			$group_stats[$group['gid']]['id'] = $group['gid'];
			$group_stats[$group['gid']] = $this->getProcessedItem($group_stats[$group['gid']]);
		}

		if ($group_stats !== false && isset($group_stats[$group_id])) {
			return $group_stats[$group_id];
		}
		if ($group_stats !== false && $group_id === 0) {
			return $group_stats;
		}
		return $this->getCounters($group_id);
	}

	static function recursive_modules_tree_view (&$new_modules, &$new_modules_child, $i, $child) {
		foreach ($new_modules as $index => $module) {
			if ($module['id'] == $child['parent']) {
				$new_modules[$index]['children'][] = $child;
				unset($new_modules_child[$i]);
				break;
			}
			else if (isset($new_modules[$index]['children'])) {
				Tree::recursive_modules_tree_view ($new_modules[$index]['children'], $new_modules_child, $i, $child);
			}
		}
	}

}
?>
