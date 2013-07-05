<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

class Events {
	private $correct_acl = false;
	private $acl = "IR";
	
	private $default = true;
	private $free_search = '';
	private $hours_old = 8;
	private $status = 3;
	private $type = "";
	private $severity = -1;
	private $group = 0;
	private $id_agent = 0;
	private $all_events = false;
	
	private $columns = null;
	
	private $readOnly = false;
	
	function __construct() {
		$system = System::getInstance();
		
		$this->columns = array('agent' => 1);
		
		if ($system->checkACL($this->acl)) {
			$this->correct_acl = true;
		}
		else {
			$this->correct_acl = false;
		}
	}
	
	public function setReadOnly() {
		$this->readOnly = true;
	}
	
	public function ajax($parameter2 = false) {
		$system = System::getInstance();
		
		if (!$this->correct_acl) {
			return;
		}
		else {
			switch ($parameter2) {
				case 'get_events':
					$this->eventsGetFilters();
					$page = $system->getRequest('page', 0);
					
					$system = System::getInstance();
					
					$listEvents = $this->getListEvents($page);
					$events_db = $listEvents['events'];
					$total_events = $listEvents['total'];
					
					$events = array();
					$end = 1;
					foreach ($events_db as $event) {
						$end = 0;
						$row = array();
						$row[] = '
						<b class="ui-table-cell-label">' . __('Event Name') . '</b>
						<a class="ui-link" href="javascript: openDetails(' . $event['id_evento'] . ')">' . 
							io_safe_output($event['evento']) . '</a>';
						/*
						switch ($event['estado']) {
							case 0:
								$img_st = "images/star.png";
								$title_st = __('New event');
								break;
							case 1:
								$img_st = "images/tick.png";
								$title_st = __('Event validated');
								break;
							case 2:
								$img_st = "images/hourglass.png";
								$title_st = __('Event in process');
								break;
						}
						$row[] = html_print_image ($img_st, true, 
							array ("class" => "image_status",
								"width" => 16,
								"height" => 16,
								"title" => $title_st,
								"id" => 'status_img_' . $event["id_evento"]));
						*/
						$row[] = '<b class="ui-table-cell-label">' . __('Timestamp') . '</b>' .
							ui_print_timestamp ($event['timestamp_rep'], true);
						
						if ($event["id_agente"] == 0) {
							$agent_name = __('System');
						}
						else {
							$agent_name = ui_print_agent_name ($event["id_agente"], true);
						}
						$row[] = '<b class="ui-table-cell-label">' . __('Agent') . '</b>' .
							$agent_name;
						
						$status =
							html_print_image ("mobile/images/" .
								get_priority_class($event['criticity']) . ".png", true);
						$status .= "&nbsp;";
						if ($event['estado'] == 1) {
							$img_st = "images/tick.png";
							$title_st = __('Event validated');
							
							$status .= html_print_image ($img_st, true, 
								array ("class" => "image_status",
									"width" => 16,
									"height" => 16,
									"title" => $title_st,
									"id" => 'status_img_' . $event["id_evento"]));
						}
						else {
							$status .= '';
						}
						
						$row[] = '<b class="ui-table-cell-label">' . __('Status') . '</b>' .
							$status;
						
						
						$events[$event['id_evento']] = $row;
					}
					
					echo json_encode(array('end' => $end, 'events' => $events));
					
					break;
				case 'get_detail_event':
					$system = System::getInstance();
					
					$id_event = $system->getRequest('id_event', 0);
					
					$event = events_get_event($id_event);
					if ($event) {
						//Check if it is a event from module.
						if ($event['id_agentmodule'] > 0) {
							$event['module_graph_link'] =
								'<a data-ajax="false" href="index.php?page=module_graph&id=' . $event['id_agentmodule'] . '">' .
								html_print_image('images/chart_curve.png', true, array ("style" => 'vertical-align: middle;')) .
								'</a>';
						}
						
						
						$event['evento'] = io_safe_output($event['evento']);
						
						$event["timestamp"] = date($system->getConfig("date_format"), strtotime($event["timestamp"]));
						if(empty($event["owner_user"])) {
							$event["owner_user"] = '<i>'.__('N/A').'</i>';
						}
						else {
							$user_owner = db_get_value('fullname', 'tusuario', 'id_user', $event["owner_user"]);
							if (empty($user_owner)) {
								$user_owner = $event['owner_user'];
							}
							$event["owner_user"] = $user_owner;
						}
						
						$event["event_type"] = events_print_type_img ($event["event_type"], true).' '.events_print_type_description($event["event_type"], true);
						
						if (!isset($group_rep))
							$group_rep = 0;
						
						if ($group_rep != 0) {
							if($event["event_rep"] <= 1) {
								$event["event_repeated"] = '<i>'.__('No').'</i>';
							}
							else {
								$event["event_repeated"] = sprintf("%d Times",$event["event_rep"]);
							}
						}
						else {
							$event["event_repeated"] = '<i>'.__('No').'</i>';
						}
						
						$event_criticity = get_priority_name ($event["criticity"]);
						
						switch ($event["criticity"]) {
							default:
							case 0:
								$img_sev = "images/status_sets/default/severity_maintenance.png";
								break;
							case 1:
								$img_sev = "images/status_sets/default/severity_informational.png";
								break;
							case 2:
								$img_sev = "images/status_sets/default/severity_normal.png";
								break;
							case 3:
								$img_sev = "images/status_sets/default/severity_warning.png";
								break;
							case 4:
								$img_sev = "images/status_sets/default/severity_critical.png";
								break;
							case 5:
								$img_sev = "images/status_sets/default/severity_minor.png";
								break;
							case 6:
								$img_sev = "images/status_sets/default/severity_major.png";
								break;
						}
						
						$event["criticity"] = html_print_image ($img_sev, true, 
							array ("class" => "image_status",
								"width" => 12,
								"height" => 12,
								"title" => $event_criticity));
						$event["criticity"] .= ' ' . $event_criticity;
						
						
						if ($event['estado'] == 1) {
							$user_ack = db_get_value('fullname', 'tusuario', 'id_user', $event['id_usuario']);
							if (empty($user_ack)) {
								$user_ack = $event['id_usuario'];
							}
							$event["acknowledged_by"] = $user_ack;
						}
						else {
							$event["acknowledged_by"] = '<i>'.__('N/A').'</i>';
						}
						
						// Get Status
						switch($event['estado']) {
							case 0:
								$img_st = "images/star.png";
								$title_st = __('New event');
								break;
							case 1:
								$img_st = "images/tick.png";
								$title_st = __('Event validated');
								break;
							case 2:
								$img_st = "images/hourglass.png";
								$title_st = __('Event in process');
								break;
						}
						$event["status"] = html_print_image($img_st,true).' '.$title_st;
						
						$event["group"] = ui_print_group_icon ($event["id_grupo"], true);
						$event["group"] .= groups_get_name ($event["id_grupo"]);
						
						$event["tags"] = tags_get_tags_formatted($event["tags"]);
						if (empty($event["tags"])) {
							$event["tags"] = '<i>'.__('N/A').'</i>';
						}
						
						echo json_encode(array('correct' => 1, 'event' => $event));
					}
					else {
						echo json_encode(array('correct' => 0, 'event' => array()));
					}
					break;
				case 'validate_event':
					$system = System::getInstance();
					
					$id_event = $system->getRequest('id_event', 0);
					
					if (events_validate_event($id_event)) {
						echo json_encode(array('correct' => 1));
					}
					else {
						echo json_encode(array('correct' => 0));
					}
					break;
			}
		}
	}
	
	public function disabledColumns($columns = null) {
		if (!empty($columns)) {
			foreach ($columns as $column) {
				$this->columns[$column] = 0;
			}
		}
	}
	
	private function eventsGetFilters() {
		$system = System::getInstance();
		$user = User::getInstance();
		
		$this->hours_old = $system->getRequest('hours_old', 8);
		if ($this->hours_old != 8) {
			$this->default = false;
		}
		
		$this->free_search = $system->getRequest('free_search', '');
		if ($this->free_search != '') {
			$this->default = false;
		}
		
		$this->status = $system->getRequest('status', __("Status"));
		if (($this->status === __("Status")) || ($this->status == 3)) {
			$this->status = 3;
		}
		else {
			$this->status = (int)$this->status;
			$this->default = false;
		}
		
		$this->type = $system->getRequest('type', __("Type"));
		if ($this->type === __("Type")) {
			$this->type = "";
		}
		else {
			$this->default = false;
		}
		
		$this->group = $system->getRequest('group', __("Group"));
		if (!$user->isInGroup($this->acl, $this->group)) {
			$this->group = 0;
		}
		if (($this->group === __("Group")) || ($this->group == 0)) {
			$this->group = 0;
		}
		else {
			$this->default = false;
		}
		
		$this->severity = $system->getRequest('severity', __("Severity"));
		if ($this->severity === __("Severity")) {
			$this->severity = -1;
		}
		else {
			$this->default = false;
		}
		
	}
	
	public function setFilters($filters) {
		if (isset($filters['id_agent'])) {
			$this->id_agent = $filters['id_agent'];
		}
		if (isset($filters['all_events'])) {
			$this->all_events = $filters['all_events'];
		}
	}
	
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->eventsGetFilters();
			$this->show_events();
		}
	}
	
	private function show_fail_acl() {
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');
		$home = new Home();
		$home->show($error);
	}
	
	private function show_events() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		
			$options['type'] = 'hidden';
			
			$options['dialog_id'] = 'detail_event_dialog';
			
			$options['title_close_button'] = true;
			$options['title_text'] = __('Event detail');
			
			//Content
			ob_start();
			?>
			<table class="pandora_responsive">
				<tbody>
					<tr class="event_name">
						<td class="cell_event_name" colspan="2"></td>
					</tr>
					<tr class="event_id">
						<th><?php echo __('Event ID');?></th>
						<td class="cell_event_id"></td>
					</tr>
					<tr class="event_timestamp">
						<th><?php echo __('Timestamp');?></th>
						<td class="cell_event_timestamp"></td>
					</tr>
					<tr class="event_owner">
						<th><?php echo __('Owner');?></th>
						<td class="cell_event_owner"></td>
					</tr>
					<tr class="event_type">
						<th><?php echo __('Type');?></th>
						<td class="cell_event_type"></td>
					</tr>
					<tr class="event_repeated">
						<th><?php echo __('Repeated');?></th>
						<td class="cell_event_repeated"></td>
					</tr>
					<tr class="event_severity">
						<th><?php echo __('Severity');?></th>
						<td class="cell_event_severity"></td>
					</tr>
					<tr class="event_status">
						<th><?php echo __('Status');?></th>
						<td class="cell_event_status"></td>
					</tr>
					<tr class="event_acknowledged_by">
						<th><?php echo __('Acknowledged by');?></th>
						<td class="cell_event_acknowledged_by"></td>
					</tr>
					<tr class="event_group">
						<th><?php echo __('Group');?></th>
						<td class="cell_event_group"></td>
					</tr>
					</tr>
					<tr class="event_module_graph">
						<th><?php echo __('Module Graph');?></th>
						<td class="cell_module_graph"></td>
					</tr>
					<tr class="event_tags">
						<th><?php echo __('Tags');?></th>
						<td class="cell_event_tags"></td>
					</tr>
				</tbody>
			</table>
			<?php
			$options['content_text'] = ob_get_clean();
			$options_button = array(
				'text' => __('Validate'),
				'id' => 'validate_button',
				'href' => 'javascript: validateEvent();');
			$options['content_text'] .= $ui->createButton($options_button);
			$options_hidden = array(
				'id' => 'event_id',
				'value' => 0,
				'type' => 'hidden'
				);
			$options['content_text'] .= $ui->getInput($options_hidden);
			$options['content_text'] .= '<div id="validate_button_loading" style="display: none; text-align: center;">
				<img src="images/ajax-loader.gif" /></div>';
			$options['content_text'] .= '<div id="validate_button_correct" style="display: none; text-align: center;">
				<h3>' . __('Sucessful validate') . '</h3></div>';
			$options['content_text'] .= '<div id="validate_button_fail" style="display: none; text-align: center;">
				<h3 style="color: #ff0000;">' . __('Fail validate') . '</h3></div>';
			
			$options['button_close'] = false;
			
			
			
		$ui->addDialog($options);
			$options['type'] = 'hidden';
			
			$options['dialog_id'] = 'detail_event_dialog_error';
			$options['title_text'] = __('ERROR: Event detail');
			$options['content_text'] = '<span style="color: #ff0000;">' .
				__('Error connecting to DB pandora.') . '</span>';
		$ui->addDialog($options);
		
		
		$ui->createDefaultHeader(__("PandoraFMS: Events"),
				$ui->createHeaderButton(
					array('icon' => 'back',
						'pos' => 'left',
						'text' => __('Back'),
						'href' => 'index.php')));
		$ui->showFooter(false);
		$ui->beginContent();
			$ui->contentAddHtml("<a id='detail_event_dialog_hook' href='#detail_event_dialog' style='display:none;'>detail_event_hook</a>");
			$ui->contentAddHtml("<a id='detail_event_dialog_error_hook' href='#detail_event_dialog_error' style='display:none;'>detail_event_dialog_error_hook</a>");
			
			$filter_title = sprintf(__('Filter Events by %s'), $this->filterEventsGetString());
			$ui->contentBeginCollapsible($filter_title);
				$ui->beginForm("index.php?page=events");
				/*
					$options = array(
						'name' => 'page',
						'type' => 'hidden',
						'value' => 'events'
						);
					$ui->formAddInput($options);*/
					
					
					$system = System::getInstance();
					$groups = users_get_groups_for_select(
						$system->getConfig('id_user'), "ER", true, true, false, 'id_grupo');
					$options = array(
						'name' => 'group',
						'title' => __('Group'),
						'label' => __('Group'),
						'items' => $groups,
						'selected' => $this->group
						);
					$ui->formAddSelectBox($options);
					
					$options = array(
						'name' => 'status',
						'title' => __('Status'),
						'label' => __('Status'),
						'items' => events_get_all_status(),
						'selected' => $this->status
						);
					$ui->formAddSelectBox($options);
					
					$options = array(
						'name' => 'type',
						'title' => __('Type'),
						'label' => __('Type'),
						'items' => get_event_types(),
						'selected' => $this->type
						);
					$ui->formAddSelectBox($options);
					
					$options = array(
						'name' => 'severity',
						'title' => __('Severity'),
						'label' => __('Severity'),
						'items' => get_priorities(),
						'selected' => $this->severity
						);
					$ui->formAddSelectBox($options);
					
					$options = array(
						'name' => 'free_search',
						'value' => $this->free_search,
						'placeholder' => __('Free search')
						);
					$ui->formAddInputSearch($options);
					
					$options = array(
						'label' => __('Max. hours old'),
						'name' => 'hours_old',
						'value' => $this->hours_old,
						'min' => 0,
						'max' => 24 * 7,
						'step' => 8
						);
					$ui->formAddSlider($options);
					
					$options = array(
						'icon' => 'refresh',
						'icon_pos' => 'right',
						'text' => __('Apply Filter')
						);
					$ui->formAddSubmitButton($options);
				$html = $ui->getEndForm();
				$ui->contentCollapsibleAddItem($html);
			$ui->contentEndCollapsible();
			$this->listEventsHtml();
		$ui->endContent();
		$ui->showPage();
	}
	
	private function getListEvents($page = 0) {
		$system = System::getInstance();
		
		//--------------Fill the SQL POST-------------------------------
		$sql_post = '';
		
		switch ($this->status) {
			case 0:
			case 1:
			case 2:
				$sql_post .= " AND estado = " . $this->status;
				break;
			case 3:
				$sql_post .= " AND (estado = 0 OR estado = 2)";
				break;
		}
		
		if ($this->free_search != "") {
			$sql_post .= " AND evento LIKE '%" . io_safe_input($this->free_search) . "%'";
		}
		
		if ($this->severity != -1) {
			switch ($this->severity) {
				case EVENT_CRIT_WARNING_OR_CRITICAL:
					$sql_post .= " AND (criticity = " . EVENT_CRIT_WARNING . " OR 
						criticity = " . EVENT_CRIT_CRITICAL . ")";
					break;
				case EVENT_CRIT_NOT_NORMAL:
					$sql_post .= " AND criticity != " . EVENT_CRIT_NORMAL;
					break;
				default:
					$sql_post .= " AND criticity = " . $this->severity;
					break;
			}
		}
		
		if ($this->hours_old > 0) {
			$unixtime = get_system_time () - ($this->hours_old * SECONDS_1HOUR);
			$sql_post .= " AND (utimestamp > " . $unixtime . " OR estado = 2)";
		}
		
		if ($this->type != "") {
			// If normal, warning, could be several (going_up_warning, going_down_warning... too complex 
			// for the user so for him is presented only "warning, critical and normal"
			if ($this->type == "warning" || $this->type == "critical"
				|| $this->type == "normal") {
				$sql_post .= " AND event_type LIKE '%" . $this->type . "%' ";
			}
			elseif ($this->type == "not_normal") {
				$sql_post .= " AND event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ";
			}
			elseif ($this->type != "all") {
				$sql_post .= " AND event_type = '" . $this->type."'";
			}
			
		}
		
		$system = System::getInstance();
		$groups = users_get_groups($system->getConfig('id_user'), 'IR');
		
		//Group selection
		if ($this->group > 0 && in_array ($this->group, array_keys ($groups))) {
			//If a group is selected and it's in the groups allowed
			$sql_post .= " AND id_grupo = " . $this->group;
		}
		else {
			if (is_user_admin ($system->getConfig('id_user'))) {
				//Do nothing if you're admin, you get full access
				$sql_post .= "";
			}
			else {
				//Otherwise select all groups the user has rights to.
				$sql_post .= " AND id_grupo IN (" .
					implode (",", array_keys ($groups)) . ")";
			}
		}
		
		if ($this->id_agent > 0) {
			$sql_post .= " AND id_agente = " . $this->id_agent;
		}
		
		// Skip system messages if user is not PM
		if (!check_acl($system->getConfig('id_user'), 0, "PM")) {
			$sql_post .= " AND id_grupo != 0";
		}
		//--------------------------------------------------------------
		
		
		
		$events_db = events_get_events_grouped($sql_post,
			$page * $system->getPageSize(), $system->getPageSize(), false, false);
		if (empty($events_db)) {
			$events_db = array();
		}
		
		$total_events = events_get_total_events_grouped($sql_post);
		
		return array('events' => $events_db, 'total' => $total_events);
	}
	
	public function listEventsHtml($page = 0, $return = false) {
		$system = System::getInstance();
		
		$listEvents = $this->getListEvents($page);
		$events_db = $listEvents['events'];
		$total_events = $listEvents['total'];
		
		if (empty($events_db))
			$events_db = array();
		
		$events = array();
		$field_event_name = __('Event Name');
		$field_timestamp = __('Timestamp');
		$field_agent = __('Agent');
		$field_status = __('Status');
		$row_class = array();
		foreach ($events_db as $event) {
			$row_class[$event['id_evento']] = "events";
			// . get_priority_class($event['criticity']);
			
			$row = array();
			if ($this->readOnly) {
				$row[$field_event_name] = io_safe_output($event['evento']);
			}
			else {
				$row[$field_event_name] = '<a href="javascript: openDetails(' . $event['id_evento'] . ')">' . 
					io_safe_output($event['evento']) . '</a>';
			}
			/*
			switch ($event['estado']) {
				case 0:
					$img_st = "images/star.png";
					$title_st = __('New event');
					break;
				case 1:
					$img_st = "images/tick.png";
					$title_st = __('Event validated');
					break;
				case 2:
					$img_st = "images/hourglass.png";
					$title_st = __('Event in process');
					break;
			}
			*/
			
			$row[$field_timestamp] = ui_print_timestamp ($event['timestamp_rep'], true);
			
			if ($this->columns['agent']) {
				$row[$field_agent] = '<a class="ui-link" data-ajax="false" href="index.php?page=agent&id=' . $event["id_agente"] . '">' .
					(string) agents_get_name($event["id_agente"]) . '</a>';
			}
			
			$row[$field_status] =
				html_print_image ("mobile/images/" .
					get_priority_class($event['criticity']) . ".png", true);
			$row[$field_status] .= "&nbsp;";
			if ($event['estado'] == 1) {
				$img_st = "images/tick.png";
				$title_st = __('Event validated');
				
				$row[$field_status] .= html_print_image ($img_st, true, 
					array ("class" => "image_status",
						"width" => 16,
						"height" => 16,
						"title" => $title_st,
						"id" => 'status_img_' . $event["id_evento"]));
			}
			else {
				$row[$field_status] .= '';
			}
			
			
			
			$events[$event['id_evento']] = $row;
		}
		
		$ui = Ui::getInstance();
		if (empty($events)) {
			if (!$return) {
				$ui->contentAddHtml('<p style="color: #ff0000;">' . __('No events') . '</p>');
			}
			else {
				return '<p style="color: #ff0000;">' . __('No events') . '</p>';
			}
		}
		else {
			$table = new Table();
			$table->id = 'list_events';
			$table->setRowClass($row_class);
			$table->importFromHash($events);
			if (!$return) {
				$ui->contentAddHtml($table->getHTML());
				
				if ($system->getPageSize() < $total_events) {
					$ui->contentAddHtml('<div id="loading_rows">' .
							html_print_image('images/spinner.gif', true) .
							' ' . __('Loading...') .
						'</div>');
					
					$this->addJavascriptAddBottom();
				}
				
				$this->addJavascriptDialog();
			}
			else {
				return $table->getHTML();
			}
		}
	}
	
	private function addJavascriptDialog() {
		$ui = Ui::getInstance();
		
		$ui->contentAddHtml("
			<script type=\"text/javascript\">
				function openDetails(id_event) {
					$.mobile.showPageLoadingMsg();
					
					postvars = {};
					postvars[\"action\"] = \"ajax\";
					postvars[\"parameter1\"] = \"events\";
					postvars[\"parameter2\"] = \"get_detail_event\";
					postvars[\"id_event\"] = id_event;
					
					$.ajax ({
						type: \"POST\",
						url: \"index.php\",
						dataType: \"json\",
						data: postvars,
						success: 
							function (data) {
								if (data.correct) {
									event = data.event;
									
									//Fill the dialog
									$(\"#detail_event_dialog h1.dialog_title\")
										.html(event[\"evento\"]);
									$(\"#detail_event_dialog .cell_event_name\")
										.html(event[\"evento\"]);
									$(\"#detail_event_dialog .cell_event_id\")
										.html(id_event);
									$(\"#detail_event_dialog .cell_event_timestamp\")
										.html(event[\"timestamp\"]);
									$(\"#detail_event_dialog .cell_event_owner\")
										.html(event[\"owner_user\"]);
									$(\"#detail_event_dialog .cell_event_type\")
										.html(event[\"event_type\"]);
									$(\"#detail_event_dialog .cell_event_repeated\")
										.html(event[\"event_repeated\"]);
									$(\"#detail_event_dialog .cell_event_severity\")
										.html(event[\"criticity\"]);
									$(\"#detail_event_dialog .cell_event_status\")
										.html(event[\"status\"]);
									$(\"#detail_event_dialog .cell_event_acknowledged_by\")
										.html(event[\"acknowledged_by\"]);
									$(\"#detail_event_dialog .cell_event_group\")
										.html(event[\"group\"]);
									$(\"#detail_event_dialog .cell_event_tags\")
										.html(event[\"tags\"]);
									
									//The link to module graph
									$(\".event_module_graph\").hide();
									if (event[\"id_agentmodule\"] != \"0\") {
										$(\".event_module_graph\").show();
										$(\".cell_module_graph\").html(event[\"module_graph_link\"]);
									}
									
									$(\"#event_id\").val(id_event);
									
									if (event[\"estado\"] != 1) {
										$(\"#validate_button\").show();
									}
									else {
										//The event is validated.
										$(\"#validate_button\").hide();
									}
									$(\"#validate_button_loading\").hide();
									$(\"#validate_button_fail\").hide();
									$(\"#validate_button_correct\").hide();
									
									$.mobile.hidePageLoadingMsg();
									
									$(\"#detail_event_dialog_hook\").click();
								}
								else {
									$.mobile.hidePageLoadingMsg();
									$(\"#detail_event_dialog_error_hook\").click();
								}
							},
						error:
							function (jqXHR, textStatus, errorThrown) {
								$.mobile.hidePageLoadingMsg();
								$(\"#detail_event_dialog_error_hook\").click();
							}
						});
				}
				
				
				function validateEvent() {
					id_event = $(\"#event_id\").val();
					
					$(\"#validate_button\").hide();
					$(\"#validate_button_loading\").show();
					
					//Hide the button to close
					$(\"#detail_event_dialog div.ui-header a.ui-btn-right\")
						.hide();
					
					postvars = {};
					postvars[\"action\"] = \"ajax\";
					postvars[\"parameter1\"] = \"events\";
					postvars[\"parameter2\"] = \"validate_event\";
					postvars[\"id_event\"] = id_event;
					
					$.ajax ({
						type: \"POST\",
						url: \"index.php\",
						dataType: \"json\",
						data: postvars,
						success: 
							function (data) {
								$(\"#validate_button_loading\").hide();
								
								if (data.correct) {
									$(\"#validate_button_correct\").show();
								}
								else {
									$(\"#validate_button_fail\").show();
								}
								
								$(\"#detail_event_dialog div.ui-header a.ui-btn-right\")
									.show();
							},
						error:
							function (jqXHR, textStatus, errorThrown) {
								$(\"#validate_button_loading\").hide();
								$(\"#validate_button_fail\").show();
								$(\"#detail_event_dialog div.ui-header a.ui-btn-right\")
									.show();
							}
						});
				}
			</script>");
	}
	
	private function addJavascriptAddBottom() {
		$ui = Ui::getInstance();
		
		$ui->contentAddHtml("<script type=\"text/javascript\">
				var load_more_rows = 1;
				var page = 1;
				
				function add_rows(data) {
					if (data.end) {
						$(\"#loading_rows\").hide();
					}
					else {
						$.each(data.events, function(key, event) {
							$(\"table#list_events tbody\").append(
								\"<tr class='events'>\" +
									\"<th class='head_vertical'></th>\" +
									\"<td class='cell_0'>\" +
										event[0] +
									\"</td>\" +
									\"<td>\" + event[1] + \"</td>\" +
									\"<td>\" + event[2] + \"</td>\" +
									\"<td>\" + event[3] + \"</td>\" +
								\"</tr>\");
							});
						
						//$.mobile.changePage();
						//$(document).page();
						//$(\"table#list_events\").table().table('refresh');
						
						load_more_rows = 1;
					}
				}
				
				function ajax_load_rows() {
					if (load_more_rows) {
						
						load_more_rows = 0;
						
						postvars = {};
						postvars[\"action\"] = \"ajax\";
						postvars[\"parameter1\"] = \"events\";
						postvars[\"parameter2\"] = \"get_events\";
						postvars[\"filter\"] = $(\"select[name='filter']\").val();
						postvars[\"group\"] = $(\"select[name='group']\").val();
						postvars[\"status\"] = $(\"select[name='status']\").val();
						postvars[\"type\"] = $(\"select[name='type']\").val();
						postvars[\"severity\"] = $(\"select[name='severity']\").val();
						postvars[\"free_search\"] = $(\"input[name='free_search']\").val();
						postvars[\"hours_old\"] = $(\"input[name='hours_old']\").val();
						postvars[\"page\"] = page;
						page++;
						
						$.post(\"index.php\",
							postvars,
							function (data) {
								add_rows(data);
								
								//For large screens load the new events
								if (document.documentElement.scrollHeight == document.documentElement.clientHeight) {
									ajax_load_rows();
								}
							},
							\"json\");
					}
				}
				
				$(document).ready(function() {
					$(window).bind(\"scroll\", function () {
						if ($(this).scrollTop() + $(this).height()
							>= ($(document).height() - 100)) {
							
							ajax_load_rows();
						}
					});
				});
			</script>");
	}
	
	private function filterEventsGetString() {
		if ($this->default) {
			return __("(Default)");
		}
		else {
			$status = "";
			if (!empty($this->status))
				$status = events_get_status($this->status);
			$type = "";
			if (!empty($this->type))
				$type = get_event_types($this->type);
			$severity = "";
			if ($this->severity != -1)
				$severity = get_priorities($this->severity);
			
			
			$string = sprintf(
				__("(Status: %s - Hours: %s - Type: %s - Severity: %s - Free Search: %s)"),
				$status, $this->hours_old, $type, $severity,
				$this->free_search);
			
			return $string;
		}
	}
}

?>