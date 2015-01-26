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
	protected $tree = array();
	protected $filter = array();
	protected $childrenMethod = "on_demand";

	protected $userGroups;
	
	protected $strictACL = false;
	protected $acltags = false;
	
	public function  __construct($type, $rootType = '', $id = -1, $rootID = -1, $childrenMethod = "on_demand") {
		
		$this->type = $type;
		$this->rootType = !empty($rootType) ? $rootType : $type;
		$this->id = $id;
		$this->rootID = !empty($rootID) ? $rootID : $id;
		$this->childrenMethod = $childrenMethod;
		
		$userGroups = users_get_groups();

		if (empty($userGroups))
			$this->userGroups = false;
		else
			$this->userGroups = $userGroups;

		global $config;
		include_once($config['homedir']."/include/functions_servers.php");

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

	protected function getAgentStatusFilter ($status) {
		$agent_status_filter = "";
		switch ($this->filter['statusAgent']) {
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
		// Critical
		$agent_critical_filter = $this->getAgentStatusFilter(AGENT_STATUS_CRITICAL);
		$agents_critical_count = "COUNT($agent_table
											$agent_critical_filter) AS total_critical_count";
		// Warning
		$agent_warning_filter = $this->getAgentStatusFilter(AGENT_STATUS_WARNING);
		$agents_warning_count = "COUNT($agent_table
											$agent_warning_filter) AS total_warning_count";
		// Unknown
		$agent_unknown_filter = $this->getAgentStatusFilter(AGENT_STATUS_UNKNOWN);
		$agents_unknown_count = "COUNT($agent_table
											$agent_unknown_filter) AS total_unknown_count";
		// Normal
		$agent_normal_filter = $this->getAgentStatusFilter(AGENT_STATUS_NORMAL);
		$agents_normal_count = "COUNT($agent_table
											$agent_normal_filter) AS total_normal_count";
		// Not init
		$agent_not_init_filter = $this->getAgentStatusFilter(AGENT_STATUS_NOT_INIT);
		$agents_not_init_count = "COUNT($agent_table
											$agent_not_init_filter) AS total_not_init_count";
		// Alerts fired
		$agents_fired_count = "COUNT($agent_table
											AND ta.fired_count > 0) AS total_fired_count";
		// Total
		$agents_total_count = "COUNT($agent_table) AS total_count";

		$columns = "$agents_critical_count, $agents_warning_count, "
			. "$agents_unknown_count, $agents_normal_count, $agents_not_init_count, "
			. "$agents_fired_count, $agents_total_count";

		return $columns;
	}

	protected function getSql () {
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
			
			if (!empty($module_search_filter)) {
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
				// if ($rootID == -1)
				// 	return array();

				// ACL Groups
				// if (isset($this->userGroups) && $this->userGroups === false)
				// 	return array();

				// if (!empty($this->userGroups) && $rootID != -1) {
				// 	if (!isset($this->userGroups[$rootID]))
				// 		return array();
				// }
				// TODO: Check ACL

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
							if (empty($tags_str))
								$groups[] = $group_id;
						}
						if (!empty($groups)) {
							$user_groups_str = implode(",", $groups);
							$group_acl = "AND ta.id_grupo IN ($user_groups_str)";
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

							$columns = 'tg.id_grupo AS id, tg.nombre AS name, tg.parent, tg.icon, COUNT(ta.id_agente) AS num_agents';
							$order_fields = 'tg.nombre ASC, tg.id_grupo ASC';

							// Add the agent counters to the columns
							$agent_table = "SELECT tac.id_agente
											FROM tagente AS tac
											$modules_join
											WHERE tac.disabled = 0
												$group_acl
												$agent_search_filter
												$agent_status_filter
												AND tac.id_os = tos.id_os";
							//$counter_columns = $this->getAgentCounterColumnsSql($agent_table);
							if (!empty($counter_columns))
								$columns .= ", $counter_columns";

							// WARNING: THE AGENTS JOIN ARE NOT FILTERING BY tg.id_grupo = ta.id_grupo

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
							$columns = 'ta.id_agente AS id, ta.nombre AS name, ta.fired_count,
								ta.normal_count, ta.warning_count, ta.critical_count,
								ta.unknown_count, ta.notinit_count, ta.total_count';
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
				// if ($rootID == -1)
				// 	return array();

				// $groups_clause = "";
				// if (!empty($this->acltags)) {
				// 	$i = 0;
				// 	$groups = array();
				// 	foreach ($this->acltags as $group_id => $tags) {
				// 		if (!empty($tags)) {
				// 			$tags_arr = explode(',', $tags);

				// 			if (in_array($id_tag, $tags_arr))
				// 				$groups[] = $group_id;
				// 		}
				// 	}
				// 	if (!empty($groups)) {
				// 		$groups_str = implode(",", $groups);
				// 		$groups_clause = " AND ta.id_grupo IN ($groups_str)"; 
				// 	}
				// }

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

								if (in_array($rootID, $tags))
									$groups[] = $group_id;
							}
						}
						if (!empty($groups)) {
							$user_groups_str = implode(",", $groups);
							$group_acl = " AND ta.id_grupo IN ($user_groups_str) ";
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

							$columns = 'tt.id_tag AS id, tt.name AS name';
							$order_fields = 'tt.name ASC, tt.id_tag ASC';

							// Add the agent counters to the columns
							$agent_table = "SELECT tac.id_agente
											FROM tagente AS tac
											$modules_join
											WHERE tac.disabled = 0
												$group_acl
												$agent_search_filter
												$agent_status_filter
												AND tac.id_os = tos.id_os";
							//$counter_columns = $this->getAgentCounterColumnsSql($agent_table);
							if (!empty($counter_columns))
								$columns .= ", $counter_columns";

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
									GROUP BY tt.id_tag
									ORDER BY $order_fields";
						}
						else {
							$columns = 'ta.id_agente AS id, ta.nombre AS name, ta.fired_count,
								ta.normal_count, ta.warning_count, ta.critical_count,
								ta.unknown_count, ta.notinit_count, ta.total_count';
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

							// Add the agent counters to the columns
							$agent_table = "SELECT tac.id_agente
											FROM tagente AS tac
											$modules_join
											WHERE tac.disabled = 0
												$group_acl
												$agent_search_filter
												$agent_status_filter
												AND tac.id_os = tos.id_os";
							//$counter_columns = $this->getAgentCounterColumnsSql($agent_table);
							if (!empty($counter_columns))
								$columns .= ", $counter_columns";

							// We need the agents table
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
						else {
							$columns = 'ta.id_agente AS id, ta.nombre AS name, ta.fired_count,
								ta.normal_count, ta.warning_count, ta.critical_count,
								ta.unknown_count, ta.notinit_count, ta.total_count';
							$order_fields = 'ta.nombre ASC, ta.id_agente ASC';

							$os_filter = "AND ta.id_os = $rootID";

							$sql = "SELECT $columns
									FROM tagente AS ta
									$modules_join
									WHERE ta.disabled = 0
										$os_filter
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

							// Add the agent counters to the columns
							$agent_table = "SELECT ta.id_agente
											FROM tagente AS ta
											INNER JOIN tagente_modulo AS tam
												ON tam.disabled = 0
													AND ta.id_agente = tam.id_agente
													AND tam.id_module_group = tmg.id_mg
													$module_search_filter
											$module_status_join
											WHERE ta.disabled = 0
												$group_acl
												$agent_search_filter
												$agent_status_filter";
							//$counter_columns = $this->getAgentCounterColumnsSql($agent_table);
							if (!empty($counter_columns))
								$columns .= ", $counter_columns";

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
									GROUP BY tmg.name
									ORDER BY $order_fields";
						}
						else {
							$columns = 'ta.id_agente AS id, ta.nombre AS name, ta.fired_count,
								ta.normal_count, ta.warning_count, ta.critical_count,
								ta.unknown_count, ta.notinit_count, ta.total_count';
							$order_fields = 'ta.nombre ASC, ta.id_agente ASC';

							$module_group_filter = "AND tam.id_module_group = $rootID";

							$sql = "SELECT $columns
									FROM tagente AS ta
									INNER JOIN tagente_modulo AS tam
										ON tam.disabled = 0
											AND ta.id_agente = tam.id_agente
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

							// Add the agent counters to the columns
							$agent_table = "SELECT ta.id_agente
											FROM tagente AS ta
											INNER JOIN tagente_modulo AS tam
												ON tam.disabled = 0
													AND ta.id_agente = tam.id_agente
													AND tam.nombre = name
													$module_group_filter
													$module_search_filter
											$module_status_join
											WHERE ta.disabled = 0
												$group_acl
												$agent_search_filter
												$agent_status_filter";
							//$counter_columns = $this->getAgentCounterColumnsSql($agent_table);
							if (!empty($counter_columns))
								$columns .= ", $counter_columns";

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
						else {
							$columns = 'ta.id_agente AS id, ta.nombre AS name, ta.fired_count,
								ta.normal_count, ta.warning_count, ta.critical_count,
								ta.unknown_count, ta.notinit_count, ta.total_count';
							$order_fields = 'ta.nombre ASC, ta.id_agente ASC';

							$symbols = ' !"#$%&\'()*+,./:;<=>?@[\\]^{|}~';
							$name = $rootID;
							for ($i = 0; $i < strlen($symbols); $i++) {
								$name = str_replace('_articapandora_' .
									ord(substr($symbols, $i, 1)) .'_pandoraartica_',
									substr($symbols, $i, 1), $name);
							}
							$name = io_safe_input($name);

							$module_name_filter = "AND tam.nombre = '$name'";

							$sql = "SELECT $columns
									FROM tagente AS ta
									INNER JOIN tagente_modulo AS tam
										ON tam.disabled = 0
											AND ta.id_agente = tam.id_agente
											$module_name_filter
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
					$sql = $this->getSqlExtended($type, $rootType, $parent, $rootID,
										$agent_search_filter, $agent_status_filter,
										$agents_join, $module_search_filter,
										$module_status_filter, $modules_join,
										$module_status_join);
		}
		
		html_debug_print($sql, true);
		return $sql;
	}

	// Override this method
	protected function getSqlExtended ($type, $rootType, $parent, $rootID,
										$agent_search_filter, $agent_status_filter,
										$agents_join, $module_search_filter,
										$module_status_filter, $modules_join,
										$module_status_join) {
		return false;
	}

	protected function getItems ($server_id = false) {
		$sql = $this->getSql();

		if (empty($sql))
			return array();

		$data = db_process_sql($sql);

		if (empty($data))
			return array();
		
		return $data;
	}

	protected function processModule (&$module) {
		global $config;

		$module['type'] = 'module';
		$module['id'] = (int) $module['id'];
		$module['name'] = $module['name'];
		$module['id_module_type'] = (int) $module['id_tipo_modulo'];
		$module['server_type'] = (int) $module['id_modulo'];
		$module['status'] = $module['estado'];
		$module['value'] = $module['datos'];
		// $module['icon'] = modules_get_type_icon($module['id_tipo_modulo']);

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
		$graphType = return_graphtype($module['id']);
		$winHandle = dechex(crc32($module['id'] . $module['name']));
		
		$moduleGraphURL = $config['homeurl'] .
			"/operation/agentes/stat_win.php?" .
			"type=$graphType&" .
			"period=86400&" .
			"id=" . $module['id'] . "&" .
			"label=" . rawurlencode(urlencode(base64_encode($module['name']))) . "&" .
			"refresh=600";

		$module['moduleGraph'] = array(
				'url' => $moduleGraphURL,
				'handle' => $winHandle
			);
	}

	protected function processModules (&$modules) {
		foreach ($modules as $iterator => $module) {
			$this->processModule($modules[$iterator]);
		}
	}

	protected function getModules ($parent = 0, $filter = array()) {
		$modules = array();

		$modules_aux = agents_get_modules($parent,
			array('id_agente_modulo', 'nombre', 'id_tipo_modulo', 'id_modulo'), $filter);
		
		if (empty($modules_aux))
			$modules_aux = array();
		
		// Process the modules
		$this->processModules($modules_aux, $modules);

		return $modules;
	}
	
	protected function processAgent (&$agent, $modulesFilter = array(), $searchChildren = true) {
		$agent['type'] = 'agent';
		$agent['id'] = (int) $agent['id'];
		$agent['name'] = $agent['name'];

		$agent['rootID'] = $this->rootID;
		$agent['rootType'] = $this->rootType;
		
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

						if ($searchChildren)
							$agent['children'] = $this->getModules($agent['id'], $modulesFilter);
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

	protected function processAgents (&$agents) {
		if (!empty($agents)) {
			foreach ($agents as $iterator => $agent) {
				$this->processAgent($agents[$iterator]);
			}
		}
	}

	protected function getAgents ($parent = 0, $parentType = '', $server_id = false) {
		// Agent name filter
		$agent_search = "";
		if (!empty($this->filter['searchAgent'])) {
			$agent_search = " AND ta.nombre LIKE '%".$this->filter['searchAgent']."%' ";
		}
		
		// Module name filter
		$module_search = "";
		if (!empty($this->filter['searchModule'])) {
			$module_search = " AND tam.nombre LIKE '%".$this->filter['searchModule']."%' ";
		}

		switch ($parentType) {
			case 'group':
				// ACL Groups
				if (isset($this->userGroups) && $this->userGroups === false)
					return array();

				if (!empty($this->userGroups) && !empty($parent)) {
					if (!isset($this->userGroups[$parent]))
						return array();
				}
				// TODO: Check ACL
				
				// Get the agents. The modules are optional (LEFT JOIN), like their status
				$sql = "SELECT ta.id_agente, ta.nombre AS agent_name, ta.fired_count,
							ta.normal_count, ta.warning_count, ta.critical_count,
							ta.unknown_count, ta.notinit_count, ta.total_count,
							tam.id_agente_modulo, tam.nombre AS module_name,
							tam.id_tipo_modulo, tam.id_modulo, tae.estado, tae.datos
						FROM tagente AS ta
						LEFT JOIN tagente_modulo AS tam
								LEFT JOIN tagente_estado AS tae
									ON tam.id_agente_modulo IS NOT NULL
										AND tam.id_agente_modulo = tae.id_agente_modulo
							ON tam.disabled = 0
								AND ta.id_agente = tam.id_agente
								$module_search
						WHERE ta.id_grupo = $parent
							AND ta.disabled = 0
							$agent_search
						ORDER BY ta.nombre ASC, ta.id_agente ASC, tam.nombre ASC, tam.id_agente_modulo ASC";
				break;
			case 'tag':
				$groups_clause = "";
				if (!empty($this->acltags)) {
					$i = 0;
					$groups = array();
					foreach ($this->acltags as $group_id => $tags) {
						if (!empty($tags)) {
							$tags_arr = explode(',', $tags);

							if (in_array($id_tag, $tags_arr))
								$groups[] = $group_id;
						}
					}
					if (!empty($groups)) {
						$groups_str = implode(",", $groups);
						$groups_clause = " AND ta.id_grupo IN ($groups_str)"; 
					}
				}

				// Get the agents. The modules are required (INNER JOIN), although their status
				$sql = "SELECT ta.id_agente, ta.nombre AS agent_name, ta.fired_count,
							ta.normal_count, ta.warning_count, ta.critical_count,
							ta.unknown_count, ta.notinit_count, ta.total_count,
							tam.id_agente_modulo, tam.nombre AS module_name,
							tam.id_tipo_modulo, tam.id_modulo, tae.estado, tae.datos
						FROM tagente AS ta
						INNER JOIN tagente_modulo AS tam
							ON tam.disabled = 0
								AND ta.id_agente = tam.id_agente
								$module_search
						INNER JOIN ttag_module AS ttm
							ON ttm.id_tag = $parent
								AND tam.id_agente_modulo = ttm.id_agente_modulo
						LEFT JOIN tagente_estado AS tae
							ON tam.id_agente_modulo = tae.id_agente_modulo
						WHERE ta.disabled = 0
							$groups_clause
							$agent_search
						ORDER BY ta.nombre ASC, ta.id_agente ASC, tam.nombre ASC, tam.id_agente_modulo ASC";
				break;
			default:
				return array();
				break;
		}
		if (! defined ('METACONSOLE')) {
			$data = db_process_sql($sql);
		}
		else if ($server_id) {
			$server = metaconsole_get_servers($server_id);
			if (metaconsole_connect($server) != NOERR) {
				$data = db_process_sql($sql);
				metaconsole_restore_db();
			}
		}

		if (empty($data))
			return array();
		
		$agents = array();
		$actual_agent = array();
		foreach ($data as $key => $value) {

			if (empty($actual_agent) || $actual_agent['id_agente'] != (int)$value['id_agente']) {
				if (!empty($actual_agent)) {
					$this->processAgent($actual_agent, array(), false);
					$agents[] = $actual_agent;
				}

				$actual_agent = array();
				$actual_agent['id_agente'] = (int) $value['id_agente'];
				$actual_agent['nombre'] = $value['agent_name'];

				$actual_agent['children'] = array();

				// Initialize counters
				$actual_agent['counters'] = array();
				$actual_agent['counters']['total'] = 0;
				$actual_agent['counters']['alerts'] = 0;
				$actual_agent['counters']['critical'] = 0;
				$actual_agent['counters']['warning'] = 0;
				$actual_agent['counters']['unknown'] = 0;
				$actual_agent['counters']['not_init'] = 0;
				$actual_agent['counters']['ok'] = 0;

				// $actual_agent['counters'] = array();
				// $actual_agent['counters']['total'] = (int) $value['total_count'];
				// $actual_agent['counters']['alerts'] = (int) $value['fired_count_count'];
				// $actual_agent['counters']['critical'] = (int) $value['critical_count'];
				// $actual_agent['counters']['warning'] = (int) $value['warning_count'];
				// $actual_agent['counters']['unknown'] = (int) $value['unknown_count'];
				// $actual_agent['counters']['not_init'] = (int) $value['notinit_count'];
				// $actual_agent['counters']['ok'] = (int) $value['normal_count'];
			}

			if (empty($value['id_agente_modulo']))
				continue;

			$module = array();
			$module['id_agente_modulo'] = (int) $value['id_agente_modulo'];
			$module['nombre'] = $value['module_name'];
			$module['id_tipo_modulo'] = (int) $value['id_tipo_modulo'];
			$module['server_type'] = (int) $value['id_modulo'];
			$module['status'] = (int) $value['estado'];
			$module['value'] = $value['data'];

			$this->processModule($module);

			$actual_agent['children'][] = $module;
			$actual_agent['counters']['total']++;

			if (isset($actual_agent['counters'][$module['statusText']]))
				$actual_agent['counters'][$module['statusText']]++;

			if ($module['alert'])
				$actual_agent['counters']['alerts']++;
		}
		if (!empty($actual_agent)) {
			$this->processAgent($actual_agent, array(), false);
			$agents[] = $actual_agent;
		}
		
		return $agents;
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
		$items = $this->getItems();
		$processed_items = array();

		// Module names
		if ($this->id == -1) {

		}
		// Agents
		else {
			$this->processModules($items);
			$processed_items = $items;
		}

		$this->tree = $processed_items;
	}

	private function getDataStrict () {
		global $config;

		require_once($config['homedir']."/include/functions_groups.php");

		function cmpSortNames($a, $b) {
			return strcmp($a["name"], $b["name"]);
		}

		// Return all the children groups
		function __searchChildren(&$groups, $id, $server_id = false) {
			$children = array();
			foreach ($groups as $key => $group) {
				if (isset($group['_parent_id_']) && $group['_parent_id_'] == $id) {
					$children_aux = __getProcessedItem($key, $groups, $server_id);
					if (!empty($children_aux))
						$children[] = $children_aux;
				}
			}
			return $children;
		}

		function __getProcessedItem($itemKey, &$items, $server_id = false) {
			if (!isset($items[$itemKey])) {
				return false;
			}
			else {
				$item = $items[$itemKey];
				unset($items[$itemKey]);
			}
			$processed_item = array();
			$processed_item['id'] = $item['_id_'];
			$processed_item['rootID'] = $item['_id_'];
			$processed_item['name'] = $item['_name_'];
			$processed_item['searchChildren'] = 1;

			//$processed_item['agentsNum'] = (int) $item['num_agents'];

			if (defined ('METACONSOLE') && $server_id) {
				$processed_item['server_id'] = $server_id;
			}
			if (isset($item['_is_tag_']) && $item['_is_tag_']) {
				$processed_item['type'] = 'tag';
				$processed_item['rootType'] = 'tag';
			}
			else {
				$processed_item['type'] = 'group';
				$processed_item['rootType'] = 'group';
				$processed_item['parentID'] = $item['_parent_id_'];

				if (!empty($item['_iconImg_']))
					$processed_item['iconHTML'] = $item['_iconImg_'];
				else
					$processed_item['icon'] = "without_group.png";
			}

			$counters = array();
			if (isset($item['_agents_unknown_']))
				$counters['unknown'] = $item['_agents_unknown_'];
			if (isset($item['_agents_critical_']))
				$counters['critical'] = $item['_agents_critical_'];
			if (isset($item['_agents_warning_']))
				$counters['warning'] = $item['_agents_warning_'];
			if (isset($item['_agents_not_init_']))
				$counters['not_init'] = $item['_agents_not_init_'];
			if (isset($item['_agents_ok_']))
				$counters['ok'] = $item['_agents_ok_'];
			if (isset($item['_total_agents_']))
				$counters['total'] = $item['_total_agents_'];
			if (isset($item['_monitors_alerts_fired_']))
				$counters['alerts'] = $item['_monitors_alerts_fired_'];

			$children = __searchChildren($items, $item['_id_'], $server_id);
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

			if (!empty($counters))
				$processed_item['counters'] = $counters;

			return $processed_item;
		}

		function __getMergedItems($items) {
			// This variable holds the result
			$mergedItems = array();
			foreach ($items as $key => $child) {
				// Store the item in a temporary element
				$resultItem = $child;

				// Remove the item
				unset($items[$key]);

				// The 'id' parameter will be stored as 'server_id' => 'id'
				$resultItem['id'] = array();
				$resultItem['id'][$child['server_id']] = $child['id'];
				$resultItem['rootID'] = array();
				$resultItem['rootID'][$child['server_id']] = $child['rootID'];

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

				// Add the children
				if (!isset($resultItem['children']))
					$resultItem['children'] = array();

				// Iterate over the list to search items that match the actual item
				foreach ($items as $key2 => $child2) {
					// Skip the actual or empty items
					if (!isset($key) || !isset($key2) || $key == $key2)
						continue;

					// Match with the name
					if ($child['name'] == $child2['name'] && $child['type'] == $child2['type']) {
						// Add the matched ids
						$resultItem['id'][$child2['server_id']] = $child2['id'];
						$resultItem['rootID'][$child2['server_id']] = $child2['rootID'];

						// Add the matched counters
						if (isset($child2['counters']) && !child2($item['counters'])) {
							foreach ($child2['counters'] as $type => $value) {
								if (isset($resultItem['counters'][$type]))
									$resultItem['counters'][$type] += $value;
							}
						}
						// Add the matched children
						if (isset($child2['children']))
							$resultItem['children'] += $child2['children'];

						// Remove the item
						unset($items[$key2]);
					}
				}
				// Get the merged children (recursion)
				if (!empty($resultItem['children']))
					$resultItem['children'] = __getMergedItems($resultItem['children']);

				// Add the resulting item
				if (!empty($resultItem) && !empty($resultItem['counters']['total']))
					$mergedItems[] = $resultItem;
			}

			usort($mergedItems, "cmpSortNames");

			return $mergedItems;
		}
		
		// Data retrieving

		$processed_items = array();

		// Groups and tags
		if ($this->id == -1) {
			if (! defined ('METACONSOLE')) {
				$items = group_get_data($config['id_user'], $this->strictACL, $this->acltags, false, 'tree');

				// Build the group and tag hierarchy
				$processed_items = array();
				foreach ($items as $key => $item) {
					if (empty($item['_parent_id_'])) {
						$processed_item = __getProcessedItem($key, $items);

						if (!empty($processed_item)
								&& isset($processed_item['counters'])
								&& isset($processed_item['counters']['total'])
								&& !empty($processed_item['counters']['total'])) {
							$processed_items[] = $processed_item;
						}
					}
				}
			}
			else {
				$unmerged_items = array();

				$servers = metaconsole_get_servers();
				foreach ($servers as $server) {
					if (metaconsole_connect($server) != NOERR)
						continue;

					$items = group_get_data($config['id_user'], $this->strictACL, $this->acltags, false, 'tree');

					// Build the group hierarchy
					$processed_items = array();
					foreach ($items as $key => $item) {
						if (empty($item['_parent_id_']))
							$processed_items[] = __getProcessedItem($key, $items, $server['id']);
					}
					$unmerged_items += $processed_items;

					metaconsole_restore_db();
				}
				
				$processed_items = __getMergedItems($unmerged_items);
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
					if (metaconsole_connect($server) == NOERR)
						continue;

					$this->rootID = $rootID;
					$newItems = $this->getItems();
					$this->processAgents($newItems);
					$items += $newItems;

					metaconsole_restore_db();
				}
				$this->rootID = $rootIDs;

				if (!empty($items))
					usort($items, "cmpSortTagNames");

				$processed_items = $items;
			}
		}
		
		$this->tree = $processed_items;
	}
	
	private function getDataGroup() {
		global $config;

		function cmpSortNames($a, $b) {
			return strcmp($a["name"], $b["name"]);
		}

		function __searchChildren(&$groups, $id, $server_id = false) {
			$children = array();
			foreach ($groups as $key => $group) {
				if (isset($group['parent']) && $group['parent'] == $id) {
					$children_aux = __getProcessedItem($key, $groups, $server_id);
					if (!empty($children_aux))
						$children[] = $children_aux;
				}
			}
			return $children;
		}

		function __getProcessedItem($itemKey, &$items, $server_id = false) {
			if (!isset($items[$itemKey])) {
				return false;
			}
			else {
				$item = $items[$itemKey];
				unset($items[$itemKey]);
			}

			$processed_item = array();
			$processed_item['id'] = $item['id'];
			$processed_item['rootID'] = $item['id'];
			$processed_item['name'] = $item['name'];
			$processed_item['agentsNum'] = (int) $item['num_agents'];
			$processed_item['searchChildren'] = 1;

			$processed_item['type'] = 'group';
			$processed_item['rootType'] = 'group';
			$processed_item['parentID'] = $item['parent'];

			if (!empty($item['icon']))
				$processed_item['icon'] = $item['icon'].".png";
			else
				$processed_item['icon'] = "without_group.png";
			if (defined ('METACONSOLE') && $server_id) {
				$processed_item['server_id'] = $server_id;
			}

			// $counters = array();
			// if (isset($item['_agents_unknown_']))
			// 	$counters['unknown'] = $item['_agents_unknown_'];
			// if (isset($item['_agents_critical_']))
			// 	$counters['critical'] = $item['_agents_critical_'];
			// if (isset($item['_agents_warning_']))
			// 	$counters['warning'] = $item['_agents_warning_'];
			// if (isset($item['_agents_not_init_']))
			// 	$counters['not_init'] = $item['_agents_not_init_'];
			// if (isset($item['_agents_ok_']))
			// 	$counters['ok'] = $item['_agents_ok_'];
			// if (isset($item['_total_agents_']))
			// 	$counters['total'] = $item['_total_agents_'];
			// if (isset($item['_monitors_alerts_fired_']))
			// 	$counters['alerts'] = $item['_monitors_alerts_fired_'];

			$children = __searchChildren($items, $item['id'], $server_id);
			if (!empty($children)) {
				$processed_item['children'] = $children;

				foreach ($children as $key => $child) {
					if (isset($child['counters'])) {
						foreach ($child['counters'] as $type => $value) {
							if (isset($counters[$type]))
								$counters[$type] += $value;
						}
					}
					if (isset($child['agentsNum']))
						$processed_item['agentsNum'] += $child['agentsNum'];
				}
			}

			if (!empty($counters))
				$processed_item['counters'] = $counters;

			return $processed_item;
		}

		function __getMergedItems($items) {
			// This variable holds the result
			$mergedItems = array();

			foreach ($items as $key => $child) {

				// Store the item in a temporary element
				$resultItem = $child;
				// Remove the item
				unset($items[$key]);

				// The 'id' parameter will be stored as 'server_id' => 'id'
				// $resultItem['id'] = array();
				// $resultItem['id'][$child['server_id']] = $child['id'];
				$resultItem['rootID'] = array();
				$resultItem['rootID'][$child['server_id']] = $child['rootID'];

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

				// Add the children
				if (!isset($resultItem['children']))
					$resultItem['children'] = array();

				// Iterate over the list to search items that match the actual item
				foreach ($items as $key2 => $child2) {
					// Skip the actual or empty items
					if (!isset($key) || !isset($key2) || $key == $key2)
						continue;

					// Match with the name
					if ($child['name'] == $child2['name'] && $child['type'] == $child2['type']) {
						// Add the matched ids
						// $resultItem['id'][$child2['server_id']] = $child2['id'];
						$resultItem['rootID'][$child2['server_id']] = $child2['rootID'];

						// Add the matched counters
						if (isset($child2['counters']) && !child2($item['counters'])) {
							foreach ($child2['counters'] as $type => $value) {
								if (isset($resultItem['counters'][$type]))
									$resultItem['counters'][$type] += $value;
							}
						}

						// Add the matched children
						if (isset($child2['children']))
							$resultItem['children'] += $child2['children'];

						// Sum the agents number
						if (isset($child2['agentsNum']))
							$resultItem['agentsNum'] += $child2['agentsNum'];

						// Remove the item
						unset($items[$key2]);
					}
				}
				// Get the merged children (recursion)
				if (!empty($resultItem['children']))
					$resultItem['children'] = __getMergedItems($resultItem['children']);

				// Add the resulting item
				if (!empty($resultItem) && !empty($resultItem['agentsNum']))
					$mergedItems[] = $resultItem;
			}
			
			//usort($mergedItems, "cmpSortNames");

			return $mergedItems;
		}

		$processed_items = array();

		// Groups
		if ($this->id == -1) {
			if (! defined ('METACONSOLE')) {
				$items = $this->getItems();

				// Build the group hierarchy
				foreach ($items as $key => $item) {
					if (empty($item['parent'])) {
						$processed_item = __getProcessedItem($key, $items);

						if (!empty($processed_item) && !empty($processed_item['agentsNum']))
							$processed_items[] = $processed_item;
					}
				}
			}
			else {
				$servers = metaconsole_get_servers();

				$item_list = array();
				foreach ($servers as $server) {
					if (metaconsole_connect($server) == NOERR)
						continue;

					$items = $this->getItems();

					// Build the group hierarchy
					$processed_items = array();
					foreach ($items as $key => $item) {
						if (empty($item['parent']))
							$processed_items[] = __getProcessedItem($key, $items, $server['id']);
					}
					$item_list += $processed_items;

					metaconsole_restore_db();
				}
				
				if (!empty($item_list))
					usort($item_list, "cmpSortNames");

				$processed_items = __getMergedItems($item_list);
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
					if (metaconsole_connect($server) == NOERR)
						continue;

					$this->rootID = $rootID;
					$newItems = $this->getItems();
					$this->processAgents($newItems);
					$items += $newItems;

					metaconsole_restore_db();
				}
				$this->rootID = $rootIDs;
				
				if (!empty($items))
					usort($items, "cmpSortNames");
				
				$processed_items = $items;
			}
		}
		
		$this->tree = $processed_items;
	}

	private function getDataTag() {

		function cmpSortTagNames($a, $b) {
			return strcmp($a["name"], $b["name"]);
		}

		$processed_items = array();

		// Tags
		if ($this->id == -1) {
			$items = $this->getItems();

			foreach ($items as $key => $item) {
				$processed_item = array();
				$processed_item['id'] = $item['id'];
				$processed_item['name'] = $item['name'];
				$processed_item['type'] = $this->type;
				$processed_item['rootID'] = $item['id'];
				$processed_item['rootType'] = $this->rootType;
				$processed_item['searchChildren'] = 1;

				$processed_items[] = $processed_item;
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
					if (metaconsole_connect($server) == NOERR)
						continue;

					$this->rootID = $rootID;
					$newItems = $this->getItems();
					$this->processAgents($newItems);
					$items += $newItems;

					metaconsole_restore_db();
				}
				$this->rootID = $rootIDs;

				if (!empty($items))
					usort($items, "cmpSortTagNames");

				$processed_items = $items;
			}
		}
		
		$this->tree = $processed_items;

			// if (! defined ('METACONSOLE')) {
			// 	$this->tree = $this->getAgents($parent, $this->type);
			// }
			// else {
			// 	function cmpSortAgentNames($a, $b) {
			// 		return strcmp($a["name"], $b["name"]);
			// 	}

			// 	$agents = array();
			// 	foreach ($parent as $server_id => $tag_id) {
			// 		$server = metaconsole_get_servers($server_id);

			// 		if (!empty($server)) {
			// 			if (metaconsole_connect($server) != NOERR)
			// 				continue;

			// 			$agents += $this->tree = $this->getAgents($tag_id, $this->type, $server_id);

			// 			metaconsole_restore_db();
			// 		}
			// 	}
			// 	if (!empty($agents))
			// 		usort($agents, "cmpSortAgentNames");

			// 	$this->tree = $agents;
			// }
	}
	
	private function getDataModules() {
		$items = $this->getItems();
		$processed_items = array();

		// Module names
		if ($this->id == -1) {
			$processed_items = array();

			foreach ($items as $key => $item) {
				$processed_item = array();

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
									'_articapandora_'.ord('!').'_pandoraartica_'), io_safe_output($item['name']));

				$processed_item['id'] = $name;
				$processed_item['name'] = $item['name'];
				$processed_item['type'] = $this->type;
				$processed_item['rootID'] = $name;
				$processed_item['rootType'] = $this->rootType;
				$processed_item['searchChildren'] = 1;

				$processed_items[] = $processed_item;
			}
		}
		// Agents
		else {
			$this->processAgents($items);
			$processed_items = $items;
		}

		$this->tree = $processed_items;
	}

	private function getDataModuleGroup() {
		$items = $this->getItems();
		$processed_items = array();

		// Module groups
		if ($this->id == -1) {
			$processed_items = array();

			foreach ($items as $key => $item) {
				$processed_item = array();
				$processed_item['id'] = $item['id'];
				$processed_item['name'] = $item['name'];
				$processed_item['type'] = $this->type;
				$processed_item['rootID'] = $item['id'];
				$processed_item['rootType'] = $this->rootType;
				$processed_item['searchChildren'] = 1;

				$processed_items[] = $processed_item;
			}
		}
		// Agents
		else {
			$this->processAgents($items);
			$processed_items = $items;
		}

		$this->tree = $processed_items;

		// TODO: NOT ASSIGNED!!
	}
	
	private function getDataOS() {
		$items = $this->getItems();
		$processed_items = array();

		// OS
		if ($this->id == -1) {
			$processed_items = array();

			foreach ($items as $key => $item) {
				$processed_item = array();
				$processed_item['id'] = $item['id'];
				$processed_item['name'] = $item['name'];
				$processed_item['icon'] = $item['os_icon'];
				$processed_item['type'] = $this->type;
				$processed_item['rootID'] = $item['id'];
				$processed_item['rootType'] = $this->rootType;
				$processed_item['searchChildren'] = 1;

				$processed_items[] = $processed_item;
			}
		}
		// Agents
		else {
			$this->processAgents($items);
			$processed_items = $items;
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
