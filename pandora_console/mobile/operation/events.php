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
	
	private $default = true;
	private $free_search = '';
	private $hours_old = 8;
	private $status = 3;
	private $type = "";
	private $severity = -1;
	private $filter = 0;
	private $group = 0;
	
	function __construct() {
		$system = System::getInstance();
		
		if ($system->checkACL("ER")) {
			$this->correct_acl = true;
		}
		else {
			$this->correct_acl = false;
		}
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
						$row[] = $event['evento'];
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
						$row[] = ui_print_timestamp ($event['timestamp_rep'], true);
						$row[] = ui_print_agent_name ($event["id_agente"], true);
						
						
						$events[$event['id_evento']] = $row;
					}
					
					echo json_encode(array('end' => $end, 'events' => $events));
					
					break;
			}
		}
	}
	
	private function eventsGetFilters() {
		$system = System::getInstance();
		
		$this->hours_old = $system->getRequest('hours_old', 8);
		if ($this->hours_old != 8) {
			$this->default = false;
		}
		
		$this->free_search = $system->getRequest('free_search', '');
		if ($this->free_search != '') {
			$this->default = false;
		}
		
		$this->status = $system->getRequest('status', __("Status"));
		if ($this->status === __("Status")) {
			$this->status = 3;
		}
		else {
			$this->default = false;
		}
		
		$this->type = $system->getRequest('type', __("Type"));
		if ($this->type === __("Type")) {
			$this->type = "";
		}
		else {
			$this->default = false;
		}
		
		$this->severity = $system->getRequest('group', __("Group"));
		if ($this->severity === __("Group")) {
			$this->severity = 0;
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
		
		$this->filter = $system->getRequest('filter', __('Preset Filters'));
		if ($this->filter === __("Preset Filters")) {
			$this->filter = 0;
		}
		else {
			$this->default = false;
		}
		
		///The user set a preset filter
		if ($this->filter > 0) {
			$this->loadPresetFilter();
		}
	}
	
	private function loadPresetFilter() {
		$filter = db_get_row('tevent_filter', 'id_filter', $this->filter);
		
		$this->free_search = $filter['search'];
		$this->hours_old = $filter['event_view_hr'];
		$this->status = $filter['status'];
		$this->type = $filter['type'];
		$this->severity = $filter['severity'];
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
		$ui = Ui::getInstance();
		
		$ui->createPage();
		$ui->addDialog(__('You don\'t have access to this page'),
			__('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database'));
		$ui->showPage();
	}
	
	private function show_events() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		$ui->createDefaultHeader(__("PandoraFMS: Events"));
		$ui->showFooter(false);
		$ui->beginContent();
			$filter_title = sprintf(__('Filter Events by %s'), $this->filterEventsGetString());
			$ui->contentBeginCollapsible($filter_title);
				$ui->beginForm();
					$options = array(
						'name' => 'page',
						'type' => 'hidden',
						'value' => 'events'
						);
					$ui->formAddInput($options);
					
					$items = db_get_all_rows_in_table('tevent_filter');
					$items[] = array('id_filter' => 0, 'id_name' => __('None'));
					$options = array(
						'name' => 'filter',
						'title' => __('Preset Filters'),
						'label' => __('Preset Filters'),
						'items' => $items,
						'item_id' => 'id_filter',
						'item_value' => 'id_name',
						'selected' => $this->filter
						);
					$ui->formAddSelectBox($options);
					
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
			$sql_post .= " AND (utimestamp > " . $unixtime . ")";
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
		
		if ($this->group > 0) {
			//If a group is selected and it's in the groups allowed
			$sql_post = " AND id_grupo = " . $this->group;
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
	
	private function listEventsHtml($page = 0) {
		$system = System::getInstance();
		
		$listEvents = $this->getListEvents($page);
		$events_db = $listEvents['events'];
		$total_events = $listEvents['total'];
		
		if (empty($events_db))
			$events_db = array();
		
		$events = array();
		$field_event_name = __('Event Name');
		$field_status = __('Status');
		$field_timestamp = __('Timestamp');
		$field_agent = __('Agent');
		foreach ($events_db as $event) {
			$row = array();
			$row[$field_event_name] = io_safe_output($event['evento']);
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
			$row[$field_status] = html_print_image ($img_st, true, 
				array ("class" => "image_status",
					"width" => 16,
					"height" => 16,
					"title" => $title_st,
					"id" => 'status_img_' . $event["id_evento"]));
			$row[$field_timestamp] = ui_print_timestamp ($event['timestamp_rep'], true);
			$row[$field_agent] = ui_print_agent_name ($event["id_agente"], true);
			
			
			$events[$event['id_evento']] = $row;
		}
		
		$ui = Ui::getInstance();
		if (empty($events)) {
			$ui->contentAddHtml('<p style="color: #ff0000;">' . __('No events') . '</p>');
		}
		else {
			$table = new Table();
			$table->importFromHash($events);
			$ui->contentAddHtml($table->getHTML());
			
			if ($system->getPageSize() < $total_events) {
				$ui->contentAddHtml('<div id="loading_rows">' .
						html_print_image('images/spinner.gif', true) .
						' ' . __('Loading...') .
					'</div>');
				
				$this->addJavascriptAddBottom();
			}
		}
	}
	
	private function addJavascriptAddBottom() {
		$ui = Ui::getInstance();
		
		$ui->contentAddHtml("<script type=\"text/javascript\">
				var load_more_rows = 1;
				var page = 1;
				$(document).ready(function() {
					$(window).bind(\"scroll\", function () {
						
						if (load_more_rows) {
							if ($(this).scrollTop() + $(this).height()
								>= ($(document).height() - 100)) {
								
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
										if (data.end) {
											$(\"#loading_rows\").hide();
										}
										else {
											$.each(data.events, function(key, event) {
												$(\"table tbody\").append(\"<tr>\" +
														\"<th></th>\" +
														\"<td>\" + event[0] + \"</td>\" +
														\"<td>\" + event[1] + \"</td>\" +
														\"<td>\" + event[2] + \"</td>\" +
														\"<td>\" + event[3] + \"</td>\" +
													\"</tr>\");
												});
											
											load_more_rows = 1;
										}
										
										
									},
									\"json\");
							}
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
			if ($this->filter) {
				$filter = db_get_row('tevent_filter', 'id_filter', $this->filter);
				
				return sprintf(__('Filter: %s'), $filter['id_name']);
			}
			else {
				$status = "";
				if (!empty($this->status))
					$status = events_get_status($this->status);
				$type = "";
				if (!empty($this->empty))
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
}

?>