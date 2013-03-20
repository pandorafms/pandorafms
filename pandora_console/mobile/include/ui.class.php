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

//Singleton
class Ui {
	private static $instance;
	
	private $title;
	
	private $endHeader = false;
	private $header = array();
	private $endContent = false;
	private $content = array();
	private $endFooter = false;
	private $footer = array();
	private $form = array();
	private $grid = array();
	private $collapsible = true;
	private $endForm = true;
	private $endGrid = true;
	private $endCollapsible = true;
	private $dialog = '';
	
	public function __construct() {
	}
	
	public static function getInstance() {
		if (!(self::$instance instanceof self)) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public function debug($var, $file = false) {
		$more_info = '';
		if (is_string($var)) {
			$more_info = 'size: ' . strlen($var);
		}
		elseif (is_bool($var)) {
			$more_info = 'val: ' . 
				($var ? 'true' : 'false');
		}
		elseif (is_null($var)) {
			$more_info = 'is null';
		}
		elseif (is_array($var)) {
			$more_info = count($var);
		}
		
		if ($file === true)
			$file = '/tmp/logDebug';
		
		if (strlen($file) > 0) {
			$f = fopen($file, "a");
			ob_start();
			echo date("Y/m/d H:i:s") . " (" . gettype($var) . ") " . $more_info . "\n";
			print_r($var);
			echo "\n\n";
			$output = ob_get_clean();
			fprintf($f,"%s",$output);
			fclose($f);
		}
		else {
			echo "<pre>" .
				date("Y/m/d H:i:s") . " (" . gettype($var) . ") " . $more_info .
				"</pre>";
			echo "<pre>";print_r($var);echo "</pre>";
		}
	}
	
	public function createPage($title = null) {
		if (!isset($title)) {
			$this->title = __('Pandora FMS mobile');
		}
		
		$this->html = '';
		$this->endHeader = false;
		$this->header = array();
		$this->endContent = false;
		$this->content = array();
		$this->noFooter = false;
		$this->endFooter = false;
		$this->footer = array();
		$this->form = array();
		$this->grid = array();
		$this->collapsible = array();
		$this->endForm = true;
		$this->endGrid = true;
		$this->endCollapsible = true;
		$this->dialog = '';
	}
	
	public function showFooter($show = true) {
		$this->noFooter = !$show;
	}
	
	public function beginHeader() {
		$this->header = array();
		$this->header['button_left'] = '';
		$this->header['button_right'] = '';
		$this->header['title'] = __('Pandora FMS mobile');
		$this->endHeader = false;
	}
	
	public function endHeader() {
		$this->endHeader = true;
	}
	
	public function createHeader($title = null, $buttonLeft = null, $buttonRight = null) {
		$this->beginHeader();
		
		$this->headerTitle($title);
		$this->headerAddButtonLeft($buttonLeft);
		$this->headerAddButtonRight($buttonRight);
		
		$this->endHeader();
	}
	
	public function headerTitle($title = null) {
		if (isset($title)) {
			$this->header['title'] = $title;
		}
	}
	
	public function headerAddButtonLeft($button = null) {
		if (isset($button)) {
			$this->header['button_left'] = $button;
		}
	}
	
	public function headerAddButtonRight($button = null) {
		if (isset($button)) {
			$this->header['button_right'] = $button;
		}
	}
	
	public function createHeaderButton($options) {
		return $this->createButton($options);
	}
	
	public function createDefaultHeader($title = false) {
		if ($title === false) {
			$title = __('Pandora FMS mobile');
		}
		
		$this->createHeader(
			$title,
			$this->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Logout'),
					'href' => 'index.php?action=logout')),
			$this->createHeaderButton(
				array('icon' => 'home',
					'pos' => 'right',
					'text' => __('Home'),
					'href' => 'index.php?page=home')));
	}
	
	public function createButton($options) {
		$return = '<a data-role="button" ';
		
		if (isset($options['icon'])) {
			$return .= 'data-icon="' . $options['icon'] . '" ';
		}
		
		if (isset($options['icon_pos'])) {
			$return .= 'data-iconpos="' . $options['pos'] . '" ';
		}
		
		if (isset($options['href'])) {
			$return .= 'href="' . $options['href'] . '" ';
		}
		else {
			$return .= 'href="#" ';
		}
		
		$return .= '>';
		
		if (isset($options['text'])) {
			$return .= $options['text'];
		}
		
		$return .= '</a>';
		
		return $return;
	}
	
	public function beginFooter() {
		$this->footer = array();
		$this->endFooter = false;
	}
	
	public function endFooter() {
		$this->endFooter = true;
	}
	
	public function createFooter($text = "") {
		$this->footerText($text);
	}
	
	public function footerText($text = null) {
		if (!isset($text)) {
			$this->footer['text'] = '';
		}
		else {
			$this->footer['text'] = $text;
		}
		
		$this->endFooter();
	}
	
	public function defaultFooter() {
		global $pandora_version, $build_version;
		
		if (isset($_SERVER['REQUEST_TIME'])) {
			$time = $_SERVER['REQUEST_TIME'];
		}
		else {
			$time = get_system_time ();
		}
		
		return "<div id='footer' style='text-align: center;'>\n"
			. sprintf(__('Pandora FMS %s - Build %s', $pandora_version, $build_version)) . "<br />\n"
			. __('Generated at') . ' '. ui_print_timestamp ($time, true, array ("prominent" => "timestamp")) . "\n"
			. "</div>";
	}
	
	public function beginContent() {
		$this->content = array();
		$this->endContent = false;
	}
	
	public function endContent() {
		$this->endContent = true;
	}
	
	public function contentAddHtml($html) {
		$this->content[] = $html;
	}
	
	public function contentBeginGrid($mode = 'responsive') {
		$this->endGrid = false;
		
		$this->grid = array();
		$this->grid['mode'] = $mode;
		$this->grid['cells'] = array();
	}
	
	public function contentGridAddCell($html) {
		$this->grid['cells'][] = $html;
	}
	
	public function contentEndGrid() {
		$this->endGrid = true;
		
		//TODO Make others modes, only responsible mode
		$convert_columns_jquery_grid = array(
			2 => 'a', 3 => 'b', 4 => 'c', 5 => 'd');
		$convert_cells_jquery_grid = array('a', 'b', 'c', 'd', 'e');
		
		$html = "<div class='ui-grid-" .
			$convert_columns_jquery_grid[count($this->grid['cells'])] . " ui-responsive'>\n";
		
		reset($convert_cells_jquery_grid);
		foreach ($this->grid['cells'] as $cell) {
			switch ($this->grid['mode']) {
				default:
				case 'responsive':
					$html .= "<div class='ui-block-" .
						current($convert_cells_jquery_grid) . "'>\n";
					break;
			}
			next($convert_cells_jquery_grid);
			$html .= "<div class='ui-body ui-body-d'>\n";
			$html .= $cell;
			$html .= "</div>\n";
			
			$html .= "</div>\n";
		}
		
		$html .= "</div>\n";
		
		$this->contentAddHtml($html);
		$this->grid = array();
	}
	
	public function contentBeginCollapsible($title = "&nbsp;") {
		$this->endCollapsible = false;
		$this->collapsible = array();
		$this->collapsible['items'] = array();
		$this->collapsible['title'] = $title;
	}
	
	public function contentCollapsibleAddItem($html) {
		$this->collapsible['items'][] = $html;
	}
	
	public function contentEndCollapsible() {
		$this->endCollapsible = true;
		
		$html = "<div data-role='collapsible' " .
			" data-collapsed-icon='arrow-d' " .
			" data-expanded-icon='arrow-u' data-mini='true' ".
			" data-theme='a' data-content-theme='c'>\n";
		$html .= "<h4>" . $this->collapsible['title'] . "</h4>\n";
		
		$html .= "<ul data-role='listview' data-theme='c'>\n";
		foreach ($this->collapsible['items'] as $item) {
			$html .= "<li>" . $item . "</li>";
		}
		$html .= "</ul>\n";
		
		$html .= "</div>\n";
		
		
		$this->contentAddHtml($html);
		$this->collapsible = array();
	}
	
	public function beginForm($action = "index.php", $method = "post") {
		$this->form = array();
		$this->endForm = false;
		
		$this->form['action'] = $action;
		$this->form['method'] = $method;
	}
	
	public function endForm() {
		$this->contentAddHtml($this->getEndForm());
		
	}
	
	public function getEndForm() {
		$this->endForm = true;
		
		$html = "<form action='" . $this->form['action'] . "' " .
			"method='" . $this->form['method'] . "'>\n";
		foreach ($this->form['fields'] as $field) {
			$html .= $field . "\n";
		}
		$html .= "</form>\n";
		
		$this->form = array();
		
		return $html;
	}
	
	public function formAddHtml($html) {
		$this->form['fields'][] = $html;
	}
	
	public function formAddInput($options) {
		//$label = '', $name = '', $id = '', $value = '') {
		
		if (empty($options['name'])) {
			$options['name'] = uniqid('input');
		}
		
		if (empty($options['id'])) {
			$options['id'] = 'text-' . $options['name'];
		}
		
		$html = "<div>\n";
		$html .= "<fieldset data-role='controlgroup'>\n";
		if (!empty($options['label'])) {
			$html .= "<label for='" . $options['id'] . "'>" . $options['label'] . "</label>\n";
		}
		
		//Erase other options and only for the input
		unset($options['label']);
		
		$html .= "<input "; 
		foreach ($options as $option => $value) {
			$html .= " " . $option  . "='" . $value . "' ";
		}
		$html .= ">\n";
		
		$html .= "</fieldset>\n";
		$html .= "</div>\n";
		
		$this->formAddHtml($html);
	}
	
	public function formAddInputPassword($options) {
		$options['type'] = 'password';
		
		$this->formAddInput($options);
	}
	
	public function formAddInputText($options) {
		$options['type'] = 'text';
		
		$this->formAddInput($options);
	}
	
	public function formAddInputSearch($options) {
		$options['type'] = 'search';
		
		$this->formAddInput($options);
	}
	
	public function formAddSubmitButton($options) {
		$options['type'] = 'submit';
		
		if (isset($options['icon'])) {
			$options['data-icon'] = $options['icon'];
			unset($options['icon']);
		}
		
		if (isset($options['icon_pos'])) {
			$options['data-iconpos'] = $options['icon_pos'];
			unset($options['icon_pos']);
		}
		
		if (isset($options['text'])) {
			$options['value'] = $options['text'];
			unset($options['text']);
		}
		
		$this->formAddInput($options);
	}
	
	public function formAddSelectBox($options) {
		$html = '';
		
		if (empty($options['name'])) {
			$options['name'] = uniqid('input');
		}
		
		if (empty($options['id'])) {
			$options['id'] = 'select-' . $options['name'];
		}
		
		$html = "<div data-role='fieldcontain'>\n";
		$html .= "<fieldset>\n";
		if (!empty($options['label'])) {
			$html .= "<label for='" . $options['id'] . "'>" . $options['label'] . "</label>\n";
		}
		
		$html .= "<select name='" . $options['name'] . "' " .
			"id='" . $options['id'] . "' data-native-menu='false'>\n";
		
		//Hack of jquery mobile
		$html .= "<option>" . $options['title'] . "</option>\n";
		if (empty($options['items']))
			$options['items'] = array();
		foreach ($options['items'] as $id => $item) {
			if (!empty($options['item_id'])) {
				$item_id = $item[$options['item_id']];
			}
			else {
				$item_id = $id;
			}
			
			if (!empty($options['item_value'])) {
				$item_value = $item[$options['item_value']];
			}
			else {
				$item_value = $item;
			}
			
			$selected = '';
			if (isset($options['selected'])) {
				if ($options['selected'] == $item_id) {
					$selected = "selected = 'selected'";
				}
			}
			
			$html .= "<option " . $selected . " value='" . $item_id . "'>" . $item_value . "</option>\n";
		}
		$html .= "</select>\n";
		
		$html .= "</fieldset>\n";
		$html .= "</div>\n";
		
		$this->formAddHtml($html);
	}
	
	public function formAddSlider($options) {
		$options['type'] = 'range';
		
		$this->formAddInput($options);
		//<input type="range" name="slider-fill" id="slider-fill" value="60" min="0" max="1000" step="50" data-highlight="true">
	}
	
	public function addDialog($title = '', $content = '', $button_text = '') {
		$this->dialog = "<div data-role='dialog' data-close-btn='none'>\n";
		$this->dialog .= "<div data-role='header'>\n";
		$this->dialog .= "<h1>" . $title . "</h1>\n";
		$this->dialog .= "</div>\n";
		$this->dialog .= "<div data-role='content'>\n";
		$this->dialog .= $content;
		$this->dialog .= "<a data-role='button' href='#main_page'>";
		if (empty($button_text)) {
			$this->dialog .= __('Close');
		}
		else {
			$this->dialog .= $button_text;
		}
		$this->dialog .= "</a></p>\n";
		$this->dialog .= "</div>\n";
		$this->dialog .= "</div>\n";
	}
	
	public function showError($msg) {
		echo $msg;
	}
	
	
	public function showPage() {
		if (!$this->endHeader) {
			$this->showError(__('Not found header.'));
		}
		else if (!$this->endContent) {
			$this->showError(__('Not found content.'));
		}
		else if ((!$this->endFooter) && (!$this->noFooter)) {
			$this->showError(__('Not found footer.'));
		}
		else if (!$this->endForm) {
			$this->showError(__('Incorrect form.'));
		}
		else if (!$this->endGrid) {
			$this->showError(__('Incorrect grid.'));
		}
		else if (!$this->endCollapsible) {
			$this->showError(__('Incorrect collapsible.'));
		}
		
		ob_start ();
		echo "<!DOCTYPE html>\n";
		echo "<html>\n";
		echo "	<head>\n";
		echo "		<title>" . $this->title . "</title>\n";
		echo "		<meta name='viewport' content='width=device-width, initial-scale=1'>\n";
		echo "		<link rel='stylesheet' href='include/style/main.css' />\n";
		
		echo "		<link rel='stylesheet' href='http://code.jquery.com/mobile/1.3.0/jquery.mobile-1.3.0.css' />\n";
		echo "		<script src='http://code.jquery.com/jquery-1.9.1.js'></script>\n";
		echo "		<script src='http://code.jquery.com/mobile/1.3.0/jquery.mobile-1.3.0.js'></script>\n";
		
		echo "	</head>\n";
		echo "	<body>\n";
		if (!empty($this->dialog)) {
			echo "		" . $this->dialog . "\n";
		}
		echo "		<div data-role='page' id='main_page'>\n";
		echo "			<div data-role='header' data-position='fixed' >\n";
		echo "				<h1>" . $this->header['title'] . "</h1>\n";
		echo "				" . $this->header['button_left'] . "\n";
		echo "				" . $this->header['button_right'] . "\n";
		echo "			</div>\n";
		echo "			<div data-role='content'>\n";
		foreach ($this->content as $content) {
			echo "				" . $content . "\n";
		}
		echo "			</div>\n";
		if (!$this->noFooter) {
			echo "			<div data-role='footer' role='contentinfo'>\n";
			if (!empty($this->footer['text'])) {
				echo "				" . $this->footer['text'] . "\n";
			}
			else {
				echo "				" . $this->defaultFooter() . "\n";
			}
		}
		echo "			</div>\n";
		echo "		</div>\n";
		echo "	</body>\n";
		echo "</html>";
		ob_end_flush();
	}
	//
	//
}


class Table {
	private $head = array();
	private $rows = array();
	private $id = array();
	private $rowClass = array();
	private $class_table = '';
	
	public function __construct() {
		$this->init();
	}
	
	public function init() {
		$this->id = uniqid();
		$this->head = array();
		$this->rows = array();
		$this->rowClass = array();
		$this->class_table = '';
	}
	
	public function addHeader($head) {
		$this->head = $head;
	}
	
	public function importFromHash($data) {
		foreach ($data as $id => $row) {
			$table_row = array();
			foreach ($row as $key => $value) {
				if (!in_array($key, $this->head)) {
					$this->head[] = $key;
				}
				
				$cell_key = array_search($key, $this->head);
				
				$table_row[$cell_key] = $value;
			}
			
			$this->rows[] = $table_row;
		}
	}
	
	public function setClass($class = '') {
		$this->class_table = $class;
	}
	
	public function setRowClass($class = '', $pos = false) {
		if (is_array($class)) {
			$this->rowClass = $class;
		}
		else {
			if ($pos !== false) {
				$this->rowClass[$pos] = $class;
			}
			else {
				$this->rowClass = array_fill(0, count($this->rows), $class);
			}
		}
	}
	
	public function getHTML() {
		$html = '';
		
		$html = "<table data-role='table' id='" . $this->id . "' " .
			"data-mode='reflow' class='" . $this->class_table . " ui-responsive table-stroke'>\n";
		
		
		$html .= "<thead>\n";
		$html .= "<tr>\n";
		//Empty head for white space between rows in the responsive vertical layout
		$html .= "<th></th>\n";
		foreach ($this->head as $head) {
			$html .= "<th>" . $head . "</th>\n";
		}
		$html .= "</tr>\n";
		$html .= "</thead>\n";
		
		
		$html .= "<tbody>\n";
		foreach ($this->rows as $key => $row) {
			$class = '';
			if (isset($this->rowClass[$key])) {
				$class = $this->rowClass[$key];
			}
			
			$html .= "<tr class='" . $class . "'>\n";
			//Empty head for white space between rows in the responsive vertical layout
			$html .= "<th></th>\n";
			foreach ($row as $cell) {
				$html .= "<td>" . $cell . "</td>\n";
			}
			$html .= "</tr>\n";
		}
		
		$html .= "</table>\n";
		$html .= "</tbody>\n";
		
		return $html;
	}
}

?>
