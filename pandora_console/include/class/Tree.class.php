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

	protected $userGroups;
	
	protected $strictACL = false;
	protected $acltags = false;
	
	public function  __construct($type, $rootType = '', $id = -1, $rootID = -1, $serverID = false, $childrenMethod = "on_demand") {
		
		$this->type = $type;
		$this->rootType = !empty($rootType) ? $rootType : $type;
		$this->id = $id;
		$this->rootID = !empty($rootID) ? $rootID : $id;
		$this->serverID = $serverID;
		$this->childrenMethod = $childrenMethod;
		
		$userGroups = users_get_groups();

		if (empty($userGroups))
			$this->userGroups = false;
		else
			$this->userGroups = $userGroups;

		global $config;
		include_once($config['homedir']."/include/functions_servers.php");

		if (defined("METACONSOLE"))
			enterprise_include_once("meta/include/functions_ui_meta.php");

		$this->strictACL = (bool) db_get_value("strict_acl", "tusuario", "id_user", $config['id_user']);

		if ($this->strictACL) {
			require_once($config['homedir']."/include/functions_tags.php");
			$this->acltags = tags_get_user_module_and_tags($config['id_user'], 'AR');
		}
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function setFilter($filter) {
		$this->filter = $filter;
	}

	public function isStrict () {
		return $this->strictACL;
	}

	public function setStrict ($value) {
		$this->strictACL = (bool) $value;
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
			$agent_not_init_filter = $this->getAgentStatusFilter(AGENT_STATUS_NOT_INIT);
			$agents_not_init_count = "($agent_table
										$agent_not_init_filter) AS total_not_init_count";
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
			$agent_search_filter = " AND ta.nombre LIKE '%".$this->filter['searchAgent']."%' ";
		}

		// Agent status filter
		$agent_status_filter = "";
		if (isset($this->filter['statusAgent'])
				&& $this->filter['statusAgent'] != AGENT_STATUS_ALL) {
			$agent_status_filter = $this->getAgentStatusFilter($this->filter['statusAgent']);
		}

		// Agents join
		$agents_join = "";
		if (!empty($agent_search_filter) || !empty($agent_status_filter)) {
			$agents_join = "INNER JOIN tagente AS ta
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

		// Module status filter
		$module_status_filter = "";
		if (isset($this->filter['statusModule'])
				&& $this->filter['statusModule'] != -1) {

			switch ($this->filter['statusModule']) {
				case AGENT_MODULE_STATUS_CRITICAL_ALERT:
				case AGENT_MODULE_STATUS_CRITICAL_BAD:
					$module_status_filter = " AND (tae.estado = ".AGENT_MODULE_STATUS_CRITICAL_ALERT."
												OR tae.estado = ".AGENT_MODULE_STATUS_CRITICAL_BAD.") ";
					break;
				case AGENT_MODULE_STATUS_WARNING_ALERT:
				case AGENT_MODULE_STATUS_WARNING:
					$module_status_filter = " AND (tae.estado = ".AGENT_MODULE_STATUS_WARNING_ALERT."
												OR tae.estado = ".AGENT_MODULE_STATUS_WARNING.") ";
					break;
				case AGENT_MODULE_STATUS_UNKNOWN:
					$module_status_filter = " AND tae.estado = ".AGENT_MODULE_STATUS_UNKNOWN." ";
					break;
				case AGENT_MODULE_STATUS_NO_DATA:
				case AGENT_MODULE_STATUS_NOT_INIT:
					$module_status_filter = " AND (tae.estado = ".AGENT_MODULE_STATUS_NO_DATA."
												OR tae.estado = ".AGENT_MODULE_STATUS_NOT_INIT.") ";
					break;
				case AGENT_MODULE_STATUS_NORMAL_ALERT:
				case AGENT_MODULE_STATUS_NORMAL:
					$module_status_filter = " AND (tae.estado = ".AGENT_MODULE_STATUS_NORMAL_ALERT."
												OR tae.estado = ".AGENT_MODULE_STATUS_NORMAL.") ";
					break;
			}
		}

		// Modules join
		$modules_join = "";
		$module_status_join = "";
		if (!empty($module_search_filter) || !empty($module_status_filter)) {
			
			if (!empty($module_status_filter)) {
				$module_status_join = "INNER JOIN tagente_estado AS tae
										ON tam.id_agente_modulo IS NOT NULL
											AND tam.id_agente_modulo = tae.id_agente_modulo
											$module_status_filter";
			}

			$modules_join = "INNER JOIN tagente_modulo AS tam
								ON tam.disabled = 0
									AND ta.id_agente = tam.id_agente
									$module_search_filter
							$module_status_join";
		}
		
		if (empty($module_status_join)) {
			$module_status_join = "LEFT JOIN tagente_estado AS tae
									ON tam.id_agente_modulo = tae.id_agente_modulo";
		}

		$sql = false;

		switch ($rootType) {
			case 'group':
				// ACL Group
				$group_acl =  "";
				if (!$this->strictACL) {
					if (!empty($this->userGroups)) {
						$user_groups_str = implode(",", array_keys($this->userGroups));
						$group_acl = "AND ta.id_grupo IN ($user_groups_str)";
					}
					else {
						$group_acl = "AND ta.id_grupo = -1";
					}
				}
				else {
					if (!empty($this->acltags)) {
						$groups = array();
						foreach ($this->acltags as $group_id => $tags_str) {
							if (empty($tags_str)) {
								$hierarchy_groups = groups_get_id_recursive($group_id);
								$groups = array_merge($groups, $hierarchy_groups);
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
					// Get the agents of a group
					case 'group':
						if (empty($rootID) || $rootID == -1) {
							if ($this->strictACL)
								return false;

							$columns = 'tg.id_grupo AS id, tg.nombre AS name, tg.parent, tg.icon, COUNT(DISTINCT(ta.id_agente)) AS total_count';
							$order_fields = 'tg.nombre ASC, tg.id_grupo ASC';

							// Add the agent counters to the columns
							$agent_table = "SELECT COUNT(DISTINCT(ta.id_agente))
											FROM tagente AS ta
											LEFT JOIN tagente_modulo AS tam
												ON tam.disabled = 0
													AND ta.id_agente = tam.id_agente
													$module_search_filter
											$module_status_join
											WHERE ta.disabled = 0
												AND ta.id_grupo = tg.id_grupo
												$group_acl
												$agent_search_filter
												$agent_status_filter";
							$counter_columns = $this->getAgentCounterColumnsSql($agent_table);
							if (!empty($counter_columns))
								$columns .= ", $counter_columns";

							$sql = "SELECT $columns
									FROM tgrupo AS tg
									LEFT JOIN tagente AS ta
											LEFT JOIN tagente_modulo AS tam
												ON tam.disabled = 0
													AND ta.id_agente = tam.id_agente
													$module_search_filter
											$module_status_join
										ON ta.disabled = 0
											AND tg.id_grupo = ta.id_grupo
											$group_acl
											$agent_search_filter
											$agent_status_filter
									GROUP BY tg.id_grupo
									ORDER BY $order_fields";
						}
						else {
							$columns = 'ta.id_agente AS id, ta.nombre AS name, 
								ta.fired_count, ta.normal_count, ta.warning_count,
								ta.critical_count, ta.unknown_count, ta.notinit_count,
								ta.total_count, ta.quiet';
							$order_fields = 'ta.nombre ASC, ta.id_agente ASC';

							$sql = "SELECT $columns
									FROM tagente AS ta
									LEFT JOIN tagente_modulo AS tam
										ON tam.disabled = 0
											AND ta.id_agente = tam.id_agente
											$module_search_filter
									$module_status_join
									WHERE ta.disabled = 0
										AND ta.id_grupo = $rootID
										$group_acl
										$agent_search_filter
										$agent_status_filter
									GROUP BY ta.id_agente
									ORDER BY $order_fields";
						}
						break;
					// Get the modules of an agent
					case 'agent':
						$columns = 'tam.id_agente_modulo AS id, tam.nombre AS name,
							tam.id_tipo_modulo, tam.id_modulo, tae.estado, tae.datos';
						$order_fields = 'tam.nombre ASC, tam.id_agente_modulo ASC';

						$sql = "SELECT $columns
								FROM tagente_modulo AS tam
								$module_status_join
								INNER JOIN tagente AS ta
									ON ta.disabled = 0
										AND tam.id_agente = ta.id_agente
										AND ta.id_grupo = $rootID
										$group_acl
										$agent_search_filter
										$agent_status_filter
								WHERE tam.disabled = 0
									AND tam.id_agente = $parent
									$module_search_filter
								GROUP BY tam.id_agente_modulo
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
							$order_fields = 'tt.name ASC, tt.id_tag ASC';
							
							// Tags SQL
							if ($item_for_count === false) {
								$sql = "SELECT $columns
										FROM ttag AS tt
										INNER JOIN ttag_module AS ttm
											ON tt.id_tag = ttm.id_tag
										INNER JOIN tagente_modulo AS tam
											ON tam.disabled = 0
												AND ttm.id_agente_modulo = tam.id_agente_modulo
												$module_search_filter
										$module_status_join
										INNER JOIN tagente AS ta
											ON ta.disabled = 0
											AND tam.id_agente = ta.id_agente
											$group_acl
											$agent_search_filter
											$agent_status_filter
										$tag_filter
										GROUP BY tt.id_tag
										ORDER BY $order_fields";
							}
							// Counters SQL
							else {
								$agent_table = "SELECT COUNT(DISTINCT(ta.id_agente))
												FROM tagente AS ta
												INNER JOIN tagente_modulo AS tam
													ON tam.disabled = 0
														AND ta.id_agente = tam.id_agente
														$module_search_filter
												$module_status_join
												INNER JOIN ttag_module AS ttm
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
							$columns = 'ta.id_agente AS id, ta.nombre AS name, 
								ta.fired_count, ta.normal_count, ta.warning_count,
								ta.critical_count, ta.unknown_count, ta.notinit_count,
								ta.total_count, ta.quiet';
							$order_fields = 'ta.nombre ASC, ta.id_agente ASC';

							$sql = "SELECT $columns
									FROM tagente AS ta
									INNER JOIN tagente_modulo AS tam
										ON tam.disabled = 0
											AND ta.id_agente = tam.id_agente
											$module_search_filter
									$module_status_join
									INNER JOIN ttag_module AS ttm
										ON tam.id_agente_modulo = ttm.id_agente_modulo
											AND ttm.id_tag = $rootID
									WHERE ta.disabled = 0
										$group_acl
										$agent_search_filter
										$agent_status_filter
									GROUP BY ta.id_agente
									ORDER BY $order_fields";
						}
						break;
					// Get the modules of an agent
					case 'agent':
						$columns = 'tam.id_agente_modulo AS id, tam.nombre AS name,
							tam.id_tipo_modulo, tam.id_modulo, tae.estado, tae.datos';
						$order_fields = 'tam.nombre ASC, tam.id_agente_modulo ASC';

						$sql = "SELECT $columns
								FROM tagente_modulo AS tam
								INNER JOIN ttag_module AS ttm
									ON tam.id_agente_modulo = ttm.id_agente_modulo
										AND ttm.id_tag = $rootID
								$module_status_join
								INNER JOIN tagente AS ta
									ON ta.disabled = 0
										AND tam.id_agente = ta.id_agente
										$group_acl
										$agent_search_filter
										$agent_status_filter
								WHERE tam.disabled = 0
									AND tam.id_agente = $parent
									$module_search_filter
								GROUP BY tam.id_agente_modulo
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
							$order_fields = 'tos.icon_name ASC, tos.id_os ASC';

							// OS SQL
							if ($item_for_count === false) {
								$sql = "SELECT $columns
										FROM tconfig_os AS tos
										INNER JOIN tagente AS ta
											ON ta.disabled = 0
												AND ta.id_os = tos.id_os
												$agent_search_filter
												$agent_status_filter
												$group_acl
										$modules_join
										GROUP BY tos.id_os
										ORDER BY $order_fields";
							}
							// Counters SQL
							else {
								$agent_table = "SELECT COUNT(DISTINCT(ta.id_agente))
												FROM tagente AS ta
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
							$columns = 'ta.id_agente AS id, ta.nombre AS name, 
								ta.fired_count, ta.normal_count, ta.warning_count,
								ta.critical_count, ta.unknown_count, ta.notinit_count,
								ta.total_count, ta.quiet';
							$order_fields = 'ta.nombre ASC, ta.id_agente ASC';

							$sql = "SELECT $columns
									FROM tagente AS ta
									$modules_join
									WHERE ta.disabled = 0
										AND ta.id_os = $rootID
										$group_acl
										$agent_search_filter
										$agent_status_filter
									GROUP BY ta.id_agente
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

						$sql = "SELECT $columns
								FROM tagente_modulo AS tam
								$module_status_join
								INNER JOIN tagente AS ta
									ON ta.disabled = 0
										AND tam.id_agente = ta.id_agente
										$os_filter
										$group_acl
										$agent_search_filter
										$agent_status_filter
								WHERE tam.disabled = 0
									$agent_filter
									$module_search_filter
								GROUP BY tam.id_agente_modulo
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
							$order_fields = 'tmg.name ASC, tmg.id_mg ASC';

							// Module groups SQL
							if ($item_for_count === false) {
								$sql = "SELECT $columns
										FROM tmodule_group AS tmg
										INNER JOIN tagente_modulo AS tam
											ON tam.disabled = 0
												AND tam.id_module_group = tmg.id_mg
												$module_search_filter
										$module_status_join
										INNER JOIN tagente AS ta
											ON ta.disabled = 0
												AND tam.id_agente = ta.id_agente
												$group_acl
												$agent_search_filter
												$agent_status_filter
										GROUP BY tmg.id_mg
										ORDER BY $order_fields";
							}
							// Counters SQL
							else {
								$agent_table = "SELECT COUNT(DISTINCT(ta.id_agente))
												FROM tagente AS ta
												INNER JOIN tagente_modulo AS tam
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
							$columns = 'ta.id_agente AS id, ta.nombre AS name, 
								ta.fired_count, ta.normal_count, ta.warning_count,
								ta.critical_count, ta.unknown_count, ta.notinit_count,
								ta.total_count, ta.quiet';
							$order_fields = 'ta.nombre ASC, ta.id_agente ASC';

							$sql = "SELECT $columns
									FROM tagente AS ta
									INNER JOIN tagente_modulo AS tam
										ON tam.disabled = 0
											AND ta.id_agente = tam.id_agente
											AND tam.id_module_group = $rootID
											$module_search_filter
									$module_status_join
									WHERE ta.disabled = 0
										$group_acl
										$agent_search_filter
										$agent_status_filter
									GROUP BY ta.id_agente
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

						$sql = "SELECT $columns
								FROM tagente_modulo AS tam
								$module_status_join
								INNER JOIN tagente AS ta
									ON ta.disabled = 0
										AND tam.id_agente = ta.id_agente
										$group_acl
										$agent_search_filter
										$agent_status_filter
								WHERE tam.disabled = 0
									$agent_filter
									$module_group_filter
									$module_search_filter
								GROUP BY tam.id_agente_modulo
								ORDER BY $order_fields";
						break;
				}
				break;
			case 'module':
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
					// Get the agents of a module
					case 'module':
						if (empty($rootID) || $rootID == -1) {
							$columns = 'tam.nombre AS name';
							$order_fields = 'tam.nombre ASC';
								
							// Modules SQL
							if ($item_for_count === false) {
								$sql = "SELECT $columns
										FROM tagente_modulo AS tam
										INNER JOIN tagente AS ta
											ON ta.disabled = 0
												AND tam.id_agente = ta.id_agente
												$group_acl
												$agent_search_filter
												$agent_status_filter
										$module_status_join
										WHERE tam.disabled = 0
											$module_search_filter
										GROUP BY tam.nombre
										ORDER BY $order_fields";
							}
							// Counters SQL
							else {
								$agent_table = "SELECT COUNT(DISTINCT(ta.id_agente))
												FROM tagente AS ta
												INNER JOIN tagente_modulo AS tam
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
							$columns = 'ta.id_agente AS id, ta.nombre AS name, 
								ta.fired_count, ta.normal_count, ta.warning_count,
								ta.critical_count, ta.unknown_count, ta.notinit_count,
								ta.total_count, ta.quiet';
							$order_fields = 'ta.nombre ASC, ta.id_agente ASC';

							$symbols = ' !"#$%&\'()*+,./:;<=>?@[\\]^{|}~';
							$name = $rootID;
							for ($i = 0; $i < strlen($symbols); $i++) {
								$name = str_replace('_articapandora_' .
									ord(substr($symbols, $i, 1)) .'_pandoraartica_',
									substr($symbols, $i, 1), $name);
							}
							$name = io_safe_input($name);

							$sql = "SELECT $columns
									FROM tagente AS ta
									INNER JOIN tagente_modulo AS tam
										ON tam.disabled = 0
											AND ta.id_agente = tam.id_agente
											AND tam.nombre = '$name'
											$module_group_filter
											$module_search_filter
									$module_status_join
									WHERE ta.disabled = 0
										$group_acl
										$agent_search_filter
										$agent_status_filter
									GROUP BY ta.id_agente
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
							$agents_join = "INNER JOIN tagente AS ta
												ON ta.disabled = 0
													AND tam.id_agente = ta.id_agente
													$group_acl";
						}
						else {
							$agents_join .= " $group_acl";
						}

						$sql = "SELECT $columns
								FROM tagente_modulo AS tam
								$module_status_join
								INNER JOIN tagente AS ta
									ON ta.disabled = 0
										AND tam.id_agente = ta.id_agente
										$group_acl
								WHERE tam.disabled = 0
									$agent_filter
									$module_name_filter
									$module_group_filter
									$module_search_filter
								GROUP BY tam.id_agente_modulo
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
	
	protected function getGroupsChildren(&$groups, &$groups_tmp, $parent_id, $server = false, $remove_empty = false) {
		$children = array();
		foreach ($groups as $key => $group) {
			if (isset($group['parent']) && $group['parent'] == $parent_id) {
				unset($groups[$key]);
				
				$children_aux = $this->getProcessedItem($group, $server, $groups, $groups_tmp, $remove_empty);
				if (!empty($children_aux))
					$children[] = $children_aux;
			}
		}
		foreach ($groups_tmp as $key_tmp => $group_tmp) {
			if (isset($group_tmp['parent']) && $group_tmp['parent'] == $parent_id) {
				unset($groups_tmp[$key_tmp]);
				
				$children[] = $group_tmp;
			}
		}
		usort($children, array("Tree", "cmpSortNames"));
		$children = array_filter($children);
		
		return $children;
	}

	protected function getProcessedItem ($item, $server = false, &$items = array(), &$items_tmp = array(), $remove_empty = false) {
		// For strict items
		if (isset($item['_id_'])) {
			$item['id'] = $item['_id_'];
			$item['name'] = $item['_name_'];
			
			if (isset($item['_is_tag_']) && $item['_is_tag_']) {
				$item['type'] = 'tag';
				$item['rootType'] = 'tag';
			}
			else {
				$item['type'] = 'group';
				$item['rootType'] = 'group';
				$item['parent'] = $item['_parent_id_'];
				
				if (!empty($item['_iconImg_']))
					$item['iconHTML'] = $item['_iconImg_'];
			}
			
			if (isset($item['_agents_unknown_']))
				$item['total_unknown_count'] = $item['_agents_unknown_'];
			if (isset($item['_agents_critical_']))
				$item['total_critical_count'] = $item['_agents_critical_'];
			if (isset($item['_agents_warning_']))
				$item['total_warning_count'] = $item['_agents_warning_'];
			if (isset($item['_agents_not_init_']))
				$item['total_not_init_count'] = $item['_agents_not_init_'];
			if (isset($item['_agents_ok_']))
				$item['total_normal_count'] = $item['_agents_ok_'];
			if (isset($item['_total_agents_']))
				$item['total_count'] = $item['_total_agents_'];
			if (isset($item['_monitors_alerts_fired_']))
				$item['total_fired_count'] = $item['_monitors_alerts_fired_'];
			
			// Agent filter for Strict ACL users
			if ($this->filter["statusAgent"] != -1) {
				switch ($this->filter["statusAgent"]) {
					case AGENT_STATUS_NOT_INIT:
						$item['total_count'] = $item['total_not_init_count'];
						
						$item['total_unknown_count'] = 0;
						$item['total_critical_count'] = 0;
						$item['total_warning_count'] = 0;
						$item['total_normal_count'] = 0;
						break;
					case AGENT_STATUS_CRITICAL:
						$item['total_count'] = $item['total_critical_count'];
						
						$item['total_unknown_count'] = 0;
						$item['total_warning_count'] = 0;
						$item['total_not_init_count'] = 0;
						$item['total_normal_count'] = 0;
						break;
					case AGENT_STATUS_WARNING:
						$item['total_count'] = $item['total_warning_count'];
						
						$item['total_unknown_count'] = 0;
						$item['total_critical_count'] = 0;
						$item['total_not_init_count'] = 0;
						$item['total_normal_count'] = 0;
						break;
					case AGENT_STATUS_UNKNOWN:
						$item['total_count'] = $item['total_unknown_count'];
						
						$item['total_critical_count'] = 0;
						$item['total_warning_count'] = 0;
						$item['total_not_init_count'] = 0;
						$item['total_normal_count'] = 0;
						break;
					case AGENT_STATUS_NORMAL:
						$item['total_count'] = $item['total_normal_count'];
						
						$item['total_unknown_count'] = 0;
						$item['total_critical_count'] = 0;
						$item['total_warning_count'] = 0;
						$item['total_not_init_count'] = 0;
						break;
				}
			}
		}
		
		
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
			
			if (!empty($item['iconHTML']))
				$processed_item['iconHTML'] = $item['iconHTML'];
			else if (!empty($item['icon']))
				$processed_item['icon'] = $item['icon'].".png";
			else
				$processed_item['icon'] = "without_group.png";
		}

		if (defined("METACONSOLE") && !empty($server)) {
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
		
		if ($processed_item['type'] == 'group') {

			$children = $this->getGroupsChildren($items, $items_tmp, $item['id'], $server, $remove_empty);
			if (!empty($children)) {
				$processed_item['children'] = $children;

				foreach ($children as $key => $child) {
					if (isset($child['counters'])) {
						foreach ($child['counters'] as $type => $value) {
							if (isset($counters[$type]))
								$counters[$type] += $value;
						}
					}
				}
			}
		}

		if (!empty($counters))
			$processed_item['counters'] = $counters;
		
		if ($remove_empty && $processed_item['type'] == 'group'
				&& (!isset($processed_item['counters']['total'])
					|| empty($processed_item['counters']['total']))) {
			$processed_item = array();
		}

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

	protected function processModule (&$module, $server = false) {
		global $config;

		$module['type'] = 'module';
		$module['id'] = (int) $module['id'];
		$module['name'] = $module['name'];
		$module['id_module_type'] = (int) $module['id_tipo_modulo'];
		$module['server_type'] = (int) $module['id_modulo'];
		$module['status'] = $module['estado'];
		$module['value'] = $module['datos'];

		if (defined("METACONSOLE") && !empty($server)) {
			$module['serverID'] = $server['id'];
			$module['serverName'] = $server['server_name'];
		}
		else{
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
		$graphType = return_graphtype($module['id_module_type']);
		$winHandle = dechex(crc32($module['id'] . $module['name']));
		
		if (!defined('METACONSOLE')) {
			$moduleGraphURL = $config['homeurl'] .
				"/operation/agentes/stat_win.php?" .
				"type=$graphType&" .
				"period=" . SECONDS_1DAY . "&" .
				"id=" . $module['id'] . "&" .
				"label=" . rawurlencode(urlencode(base64_encode($module['name']))) . "&" .
				"refresh=" . SECONDS_10MINUTES;
		}
		else if (!empty($server)) {
			$moduleGraphURL = ui_meta_get_url_console_child(
				$server, null, null, null, null,
				"operation/agentes/stat_win.php?" .
				"type=$graphType&" .
				"period=" . SECONDS_1DAY . "&" .
				"id=" . $module["id"] . "&" .
				"label=" . rawurlencode(urlencode(base64_encode($module['name']))) . "&" .
				"refresh=" . SECONDS_10MINUTES);
		}
		
		if (!empty($moduleGraphURL)) {
			$module['moduleGraph'] = array(
					'url' => $moduleGraphURL,
					'handle' => $winHandle
				);
		}
		
		// Alerts fired image
		$has_alerts = (bool) db_get_value(
			'COUNT(DISTINCT(id_agent_module))',
			'talert_template_modules', 'id_agent_module', $module['id']);
		
		if ($has_alerts) {
			$module['alertsImageHTML'] = html_print_image("images/bell.png", true, array("title" => __('Module alerts')));
		}
	}
	
	protected function processModules (&$modules, $server = false) {
		foreach ($modules as $iterator => $module) {
			$this->processModule($modules[$iterator], $server);
		}
	}
	
	protected function processAgent (&$agent, $server = false) {
		global $config;
		
		$agent['type'] = 'agent';
		$agent['id'] = (int) $agent['id'];
		$agent['name'] = $agent['name'];
		
		$agent['rootID'] = $this->rootID;
		$agent['rootType'] = $this->rootType;
		
		if (defined("METACONSOLE") && !empty($server))
			$agent['serverID'] = $server['id'];

		// Realtime counters for Strict ACL
		if ($this->strictACL) {
			$agent_filter = array("id" => $agent['id']);
			$module_filter = array();

			if (isset($this->filter["statusModule"]))
				$module_filter["status"] = $this->filter["statusModule"];
			if (isset($this->filter["searchModule"]))
				$module_filter["name"] = $this->filter["searchModule"];

			if ($agent['rootType'] == "group") {
				$agent['counters'] = array();
				$agent['counters']['unknown'] = (int) groups_get_unknown_monitors ($agent['rootID'], $agent_filter, $module_filter, true, $this->acltags);
				$agent['counters']['critical'] = (int) groups_get_critical_monitors ($agent['rootID'], $agent_filter, $module_filter, true, $this->acltags);
				$agent['counters']['warning'] = (int) groups_get_warning_monitors ($agent['rootID'], $agent_filter, $module_filter, true, $this->acltags);
				$agent['counters']['not_init'] = (int) groups_get_not_init_monitors ($agent['rootID'], $agent_filter, $module_filter, true, $this->acltags);
				$agent['counters']['ok'] = (int) groups_get_normal_monitors ($agent['rootID'], $agent_filter, $module_filter, true, $this->acltags);
				$agent['counters']['total'] = (int) groups_get_total_monitors ($agent['rootID'], $agent_filter, $module_filter, true, $this->acltags);
				$agent['counters']['alerts'] = agents_get_alerts_fired($agent['id']);
			}
			else if ($agent['rootType'] == "tag") {
				$agent['counters'] = array();
				$agent['counters']['unknown'] = (int) tags_get_unknown_monitors($agent['rootID'], $this->acltags, $agent_filter, $module_filter);
				$agent['counters']['critical'] = (int) tags_get_critical_monitors($agent['rootID'], $this->acltags, $agent_filter, $module_filter);
				$agent['counters']['warning'] = (int) tags_get_warning_monitors($agent['rootID'], $this->acltags, $agent_filter, $module_filter);
				$agent['counters']['not_init'] = (int) tags_get_not_init_monitors($agent['rootID'], $this->acltags, $agent_filter, $module_filter);
				$agent['counters']['ok'] = (int) tags_get_normal_monitors($agent['rootID'], $this->acltags, $agent_filter, $module_filter);
				$agent['counters']['total'] = (int) tags_get_total_monitors($agent['rootID'], $this->acltags, $agent_filter, $module_filter);
				$agent['counters']['alerts'] = (int) tags_monitors_fired_alerts($agent['rootID'], $this->acltags, $agent['id']);
			}
		}
		
		// Counters
		if (empty($agent['counters'])) {
			$agent['counters'] = array();
			
			if (isset($agent['unknown_count']))
				$agent['counters']['unknown'] = $agent['unknown_count'];
			else
				$agent['counters']['unknown'] = agents_monitor_unknown($agent['id']);
			
			if (isset($agent['critical_count']))
				$agent['counters']['critical'] = $agent['critical_count'];
			else
				$agent['counters']['critical'] = agents_monitor_critical($agent['id']);
			
			if (isset($agent['warning_count']))
				$agent['counters']['warning'] = $agent['warning_count'];
			else
				$agent['counters']['warning'] = agents_monitor_warning($agent['id']);
			
			if (isset($agent['notinit_count']))
				$agent['counters']['not_init'] = $agent['notinit_count'];
			else
				$agent['counters']['not_init'] = agents_monitor_notinit($agent['id']);
			
			if (isset($agent['normal_count']))
				$agent['counters']['ok'] = $agent['normal_count'];
			else
				$agent['counters']['ok'] = agents_monitor_ok($agent['id']);
			
			if (isset($agent['total_count']))
				$agent['counters']['total'] = $agent['total_count'];
			else
				$agent['counters']['total'] = agents_monitor_total($agent['id']);
			
			if (isset($agent['fired_count']))
				$agent['counters']['alerts'] = $agent['fired_count'];
			else
				$agent['counters']['alerts'] = agents_get_alerts_fired($agent['id']);
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
		
		// Quiet image
		if (isset($agent['quiet']) && $agent['quiet'])
			$agent['quietImageHTML'] = html_print_image("/images/dot_green.disabled.png", true, array("title" => __('Quiet')));
		
		// Status
		$agent['statusRaw'] = agents_get_status($agent['id']);
		switch ($agent['statusRaw']) {
			case AGENT_STATUS_NORMAL:
				$agent['status'] = "ok";
				break;
			case AGENT_STATUS_WARNING:
				$agent['status'] = "warning";
				break;
			case AGENT_STATUS_CRITICAL:
				$agent['status'] = "critical";
				break;
			case AGENT_STATUS_UNKNOWN:
				$agent['status'] = "unknown";
				break;
			case AGENT_STATUS_NOT_INIT:
				$agent['status'] = "not_init";
				break;
			default:
				$agent['status'] = "none";
				break;
		}
		
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
						
						// if ($searchChildren)
						// 	$agent['children'] = $this->getModules($agent['id'], $modulesFilter);
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
	
	private static function extractItemWithID ($items, $item_id, $item_type = "group") {
		foreach ($items as $item) {
			if ($item["type"] != $item_type)
				continue;
			
			// Item found
			if (! defined("METACONSOLE")) {
				if ($item["id"] == $item_id)
					return $item;
			}
			else {
				foreach ($item["id"] as $server_id => $id) {
					if ($id == $item_id)
						return $item;
				}
			}
			
			if ($item["type"] == "group" && !empty($item["children"])) {
				$result = self::extractItemWithID($item["children"], $item_id, $item_type);
				
				// Item found on children
				if ($result !== false)
					return $result;
			}
		}
		
		// Item not found
		return false;
	}
	
	public function getData() {
		
		if (! $this->strictACL) {
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
		}
		else {
			switch ($this->type) {
				case 'group':
				case 'tag':
					$this->getDataStrict();
					break;
				case 'agent':
					$this->getDataAgent();
					break;
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
			if (! defined ('METACONSOLE')) {
				$items = $this->getItems();
				$this->processModules($items);
				$processed_items = $items;
			}
			else {
				$items = array();
				
				if ($this->serverID !== false) {
					
					$server = metaconsole_get_servers($this->serverID);
					if (metaconsole_connect($server) == NOERR) {
						db_clean_cache();
						
						$newItems = $this->getItems();
						$this->processModules($newItems, $server);
						$items = array_merge($items, $newItems);
						
						metaconsole_restore_db();
					}
				}
				
				if (!empty($items))
					usort($items, array("Tree", "cmpSortNames"));
				
				$processed_items = $items;
			}
		}
		
		$this->tree = $processed_items;
	}
	
	private function getDataStrict () {
		global $config;
		
		require_once($config['homedir']."/include/functions_groups.php");
		
		$processed_items = array();
		
		// Groups and tags
		if ($this->id == -1) {
			$agent_filter = array();
			if (isset($this->filter["statusAgent"]))
				$agent_filter["status"] = $this->filter["statusAgent"];
			if (isset($this->filter["searchAgent"]))
				$agent_filter["name"] = $this->filter["searchAgent"];

			$module_filter = array();
			if (isset($this->filter["statusModule"]))
				$module_filter["status"] = $this->filter["statusModule"];
			if (isset($this->filter["searchModule"]))
				$module_filter["name"] = $this->filter["searchModule"];
			
			if (! defined ('METACONSOLE')) {
				$items = group_get_data($config['id_user'], $this->strictACL, $this->acltags, false, 'tree', $agent_filter, $module_filter);
				
				// Build the group and tag hierarchy
				$processed_items = array();
				$processed_items_tmp = array();
				foreach ($items as $key => $item) {
					unset($items[$key]);
					
					$processed_item = $this->getProcessedItem($item, false, $items, $processed_items_tmp, true);
					if (!empty($processed_item)
							&& isset($processed_item['counters'])
							&& isset($processed_item['counters']['total'])
							&& !empty($processed_item['counters']['total'])) {
						$processed_items_tmp[] = $processed_item;
					}
				}
				if (!empty($processed_items_tmp)) {
					usort($processed_items_tmp, array("Tree", "cmpSortNames"));
					// array_filter clean the empty elements
					$processed_items = array_filter($processed_items_tmp);
				}
			}
			else {
				$unmerged_items = array();
				
				$servers = metaconsole_get_servers();
				foreach ($servers as $server) {
					if (metaconsole_connect($server) != NOERR)
						continue;
					db_clean_cache();
					
					$items = group_get_data($config['id_user'], $this->strictACL, $this->acltags, false, 'tree', $agent_filter, $module_filter);
					
					// Build the group and tag hierarchy
					$processed_items = array();
					$processed_items_tmp = array();
					foreach ($items as $key => $item) {
						unset($items[$key]);
						$processed_items_tmp[] = $this->getProcessedItem($item, $server, $items, $processed_items_tmp);
					}
					if (!empty($processed_items_tmp)) {
						usort($processed_items_tmp, array("Tree", "cmpSortNames"));
						// array_filter clean the empty elements
						$processed_items = array_filter($processed_items_tmp);
					}
					
					$unmerged_items += $processed_items;
					
					metaconsole_restore_db();
				}
				
				$processed_items = $this->getMergedItems($unmerged_items);
			}
			
			if (!empty($processed_items)) {
				if (!empty($this->filter["groupID"])) {
					$result = self::extractItemWithID($processed_items, $this->filter["groupID"], "group");
					
					if ($result === false)
						$processed_items = array();
					else
						$processed_items = array($result);
				}
				else if (!empty($this->filter["tagID"])) {
					$result = self::extractItemWithID($processed_items, $this->filter["tagID"], "tag");
					
					if ($result === false)
						$processed_items = array();
					else
						$processed_items = array($result);
				}
			}
		}
		// Agents
		else {
			if (! defined ('METACONSOLE')) {
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
	
	private function getDataGroup() {
		$processed_items = array();
		
		// Groups
		if ($this->id == -1) {
			if (! defined ('METACONSOLE')) {
				$items = $this->getItems();
				
				// Build the group hierarchy
				foreach ($items as $key => $item) {
					if (empty($item['parent'])) {
						
						unset($items[$key]);
						$items_tmp = array();
						$processed_item = $this->getProcessedItem($item, false, $items, $items_tmp, true);
						
						if (!empty($processed_item)
								&& isset($processed_item['counters'])
								&& isset($processed_item['counters']['total'])
								&& !empty($processed_item['counters']['total']))
							$processed_items[] = $processed_item;
					}
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
					
					// Build the group hierarchy
					$processed_items = array();
					foreach ($items as $key => $item) {
						if (empty($item['parent'])) {
							
							unset($items[$key]);
							$processed_items[] = $this->getProcessedItem($item, $server, $items);
						}
					}
					
					$item_list = array_merge($item_list, $processed_items);
					
					metaconsole_restore_db();
				}
				
				$processed_items = $this->getMergedItems($item_list);
			}
			// groupID filter. To access the view from tactical views f.e.
			if (!empty($processed_items) && !empty($this->filter['groupID'])) {
				$result = self::extractItemWithID($processed_items, $this->filter['groupID'], "group");
				
				if ($result === false)
					$processed_items = array();
				else
					$processed_items = array($result);
			}
		}
		// Agents
		else {
			if (! defined ('METACONSOLE')) {
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
	
	private function getDataTag() {
		$processed_items = array();
		
		// Tags
		if ($this->id == -1) {
			if (! defined ('METACONSOLE')) {
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
			if (! defined ('METACONSOLE')) {
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
			if (! defined ('METACONSOLE')) {
				$items = $this->getItems();
				
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
			if (! defined ('METACONSOLE')) {
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
			if (! defined ('METACONSOLE')) {
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
			if (! defined ('METACONSOLE')) {
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
			if (! defined ('METACONSOLE')) {
				$items = $this->getItems();
				
				foreach ($items as $key => $item) {
					
					$counters = $this->getCounters($item['id']);
					if (!empty($counters)) {
						foreach ($counters as $type => $value) {
							$item[$type] = $value;
						}
					}
					
					$processed_item = $this->getProcessedItem($item);
					$processed_item['icon'] = $item['os_icon'];
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
			if (! defined ('METACONSOLE')) {
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
}
?>
