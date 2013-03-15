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

class Home {
	private $global_search = '';
	
	function __construct() {
		$this->global_search = '';
	}
	
	public function show() {
		global $config;
		
		require_once ($config["homedir"] . '/include/functions_graph.php');
		
		$ui = Ui::getInstance();
		
		$ui->createPage();
		$ui->createDefaultHeader(__("PandoraFMS: Home"));
		$ui->showFooter(false);
		$ui->beginContent();
			$ui->beginForm();
			$options = array(
				'name' => 'global_search',
				'value' => $this->global_search,
				'placeholder' => __('Global search')
				);
			$ui->formAddInputSearch($options);
			$ui->endForm();
			
			//List of buttons
			$options = array('icon' => 'gear',
					'pos' => 'right',
					'text' => __('Tactical'),
					'href' => 'index.php?page=tactical');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'info',
					'pos' => 'right',
					'text' => __('Events'),
					'href' => 'index.php?page=events');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'arrow-u',
					'pos' => 'right',
					'text' => __('Groups'),
					'href' => 'index.php?page=groups');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'alert',
					'pos' => 'right',
					'text' => __('Alerts'),
					'href' => 'index.php?page=alerts');
			$ui->contentAddHtml($ui->createButton($options));
			//~ 
			//~ $ui->contentAddHtml('
			//~ <style>
//~ /* Basic styles */
//~ .container div {
    //~ text-align: left;
    //~ border-color: #ddd;
//~ }
  //~ .container p {
    //~ color: #777;
    //~ line-height: 140%
//~ }
//~ /* Stack all blocks to start */
//~ .container .ui-block-a,
//~ .container .ui-block-b,
//~ .container .ui-block-c {
    //~ width: 100%;
    //~ float: none;
//~ }
//~ /* 1st breakpoint - Float B and C, leave A full width on top */
//~ @media all and (min-width: 42em){
    //~ .container div {
       //~ min-height:14em;
    //~ }
    //~ .container .ui-block-b,
    //~ .container .ui-block-c {
      //~ float:left;
      //~ width: 49.95%;
    //~ }
    //~ .container .ui-block-b p,
    //~ .container .ui-block-c p {
      //~ font-size:.8em;
    //~ }
//~ }
//~ /* 2nd breakpoint - Float all, 50/25/25 */
//~ @media all and (min-width: 55em){
    //~ .container div {
       //~ min-height:17em;
    //~ }
    //~ .container .ui-block-a,
    //~ .container .ui-block-c {
      //~ float:left;
      //~ width: 49.95%;
    //~ }
    //~ .container .ui-block-b,
    //~ .container .ui-block-c {
      //~ float:left;
      //~ width: 24.925%;
    //~ }
//~ }
//~ /* 3rd breakpoint - Bump up font size at very wide screens */
//~ @media all and (min-width: 75em){
    //~ .container {
      //~ font-size:125%;
    //~ }
    //~ .container .ui-block-a,
    //~ .container .ui-block-c {
      //~ float:left;
      //~ width: 49.95%;
    //~ }
    //~ .container .ui-block-b,
    //~ .container .ui-block-c {
      //~ float:left;
      //~ width: 24.925%;
    //~ }
//~ }
			//~ </style>
			//~ <div class="container">
    //~ <!-- Lead story block -->
    //~ <div class="ui-block-a">
      //~ <div class="ui-body ui-body-d">
        //~ <h2>Apple schedules iPad Mini event for October 23</h2>
        //~ <p>One of the worst-kept secrets in tech has been confirmed: Apple will hold an event October 23 in San Jose, California, at which the company is widely expected to unveil a smaller, cheaper version of its popular iPad called "Mini".</p>
      //~ </div>
    //~ </div>
    //~ <!-- secondary story block #1 -->
    //~ <div class="ui-block-b">
      //~ <div class="ui-body ui-body-d">
        //~ <h4>Microsoft Surface tablet goes on sale for $499</h4>
        //~ <p>The Microsoft Surface tablet picture has come into focus. The Redmond giant filled in the blanks on the new tablets availability and specs.</p>
      //~ </div>
     //~ </div>
    //~ <!-- secondary story block #2 -->
    //~ <div class="ui-block-c">
      //~ <div class="ui-body ui-body-d">
        //~ <h4>AOL unveils Alto, an email service that syncs 5 accounts</h4>
        //~ <p>AOL, struggling to shed its outdated image, is reimagining one of the most visibly aging parts of its platform: Its email service. </p>
      //~ </div>
     //~ </div>
//~ </div>');
		
		$ui->endContent();
		$ui->showPage();
		return;
	}
}
?>