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
require_once '../include/functions_visual_map.php';

class Visualmap
{

    private $correct_acl = false;

    private $acl = 'VR';

    private $id = 0;

    private $visualmap = null;


    function __construct()
    {

    }


    private function checkVisualmapACL($groupID=0)
    {
        $system = System::getInstance();

        if ($system->checkACL($this->acl)) {
            $this->correct_acl = true;
        } else {
            $this->correct_acl = false;
        }
    }


    private function getFilters()
    {
        $system = System::getInstance();

        $this->id = (int) $system->getRequest('id', 0);
    }


    public function show()
    {
        $this->getFilters();

        $this->visualmap = db_get_row(
            'tlayout',
            'id',
            $this->id
        );

        if (empty($this->visualmap)) {
            $this->show_fail_acl();
        }

        $this->checkVisualmapACL($this->visualmap['id_group']);
        if (!$this->correct_acl) {
            $this->show_fail_acl();
        }

        $this->show_visualmap();
    }


    private function show_fail_acl()
    {
        $error['type'] = 'onStart';
        $error['title_text'] = __('You don\'t have access to this page');
        $error['content_text'] = System::getDefaultACLFailText();
        if (class_exists('HomeEnterprise')) {
            $home = new HomeEnterprise();
        } else {
            $home = new Home();
        }

        $home->show($error);
    }


    public function ajax($parameter2=false)
    {
        $system = System::getInstance();
        $this->checkVisualmapACL($this->visualmap['id_group']);
        if (!$this->correct_acl) {
            $this->show_fail_acl();
        } else {
            switch ($parameter2) {
                case 'render_map':
                    $map_id = $system->getRequest('map_id', '0');
                    $width = $system->getRequest('width', '400');
                    $height = $system->getRequest('height', '400');
                    visual_map_print_visual_map($map_id, false, true, $width, $height);
                exit;
            }
        }
    }


    private function show_visualmap()
    {
        $ui = Ui::getInstance();
        $system = System::getInstance();

        $ui->createPage();
        $ui->createDefaultHeader(
            sprintf(
                '%s',
                $this->visualmap['name']
            ),
            $ui->createHeaderButton(
                [
                    'icon' => 'back',
                    'pos'  => 'left',
                    'text' => __('Back'),
                    'href' => 'index.php?page=visualmaps',
                ]
            )
        );
        $ui->showFooter(false);
        $ui->beginContent();

        ob_start();
        $rendered_map = '<div id="rendered_visual_map">';
        $rendered_map .= html_print_image('images/spinner.gif', true);
        $rendered_map .= '</div>';
        ob_clean();

        $ui->contentAddHtml($rendered_map);
        $ui->contentAddHtml(
            "<script type=\"text/javascript\">
				function ajax_load_map() {
					$('#rendered_visual_map').html('<div style=\"text-align: center\"> ".__('Loading...')."<br /><img src=\"images/ajax-loader.gif\" /></div>');
					
					var map_max_width = window.innerWidth * 0.90;
					var map_max_height = (window.innerHeight - 47) * 0.90;
					
					var original_width = ".$this->visualmap['width'].';
					var original_height = '.$this->visualmap['height'].';
					
					var map_width = map_max_width;
					var map_height = original_height / (original_width / map_width);
					
					if(map_height > map_max_height) {
						map_height = map_max_height;
						map_width = original_width / (original_height / map_height);
					}
					
					postvars = {};
					postvars["action"] = "ajax";
					postvars["parameter1"] = "visualmap";
					postvars["parameter2"] = "render_map";
					postvars["map_id"] = "'.$this->id."\";
					postvars[\"width\"] = map_width;
					postvars[\"height\"] = map_height;
					
					$.post(\"index.php\",
						postvars,
						function (data) {
							$('#rendered_visual_map').html(data);
						},
						\"html\");
				}
				
				ajax_load_map();
				
				// Detect orientation change to refresh dinamic content
				$(window).on({
					orientationchange: function(e) {
						ajax_load_map();
					}
				});
			</script>"
        );
        $ui->endContent();
        $ui->showPage();
    }


}
