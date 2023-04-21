<?php
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
/**
 * Modules list view for mobile
 *
 * @category   Mobile
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.
class ModuleData
{

    private $correct_acl = false;

    private $acl = 'AR';

    private $default = true;

    private $default_filters = [];

    private $moduleId = null;

    private $agentAlias = null;

    private $moduleName = null;

    private $columns = null;


    function __construct()
    {
        $system = System::getInstance();

        if ($system->checkACL($this->acl)) {
            $this->correct_acl = true;
        } else {
            $this->correct_acl = false;
        }

        $this->moduleId = $system->getRequest('module_id');
    }


    public function ajax($parameter2=false)
    {
        $system = System::getInstance();

        if (!$this->correct_acl) {
            return;
        } else {
            switch ($parameter2) {
                case 'get_module_data':
                    $this->getFilters();
                    $page = $system->getRequest('page', 0);
                    $module_id = $system->getRequest('module_id');
                    $servers = [];
                    $end = 1;

                    $listData = $this->getListData($page, true);

                    if (!empty($listData['data'])) {
                        $end = 0;
                        $servers = $listData['data'];
                    }

                    echo json_encode(['end' => $end, 'servers' => $servers]);
                break;
            }
        }
    }


    public function show()
    {
        if (!$this->correct_acl) {
            $this->show_fail_acl();
        } else {
            $this->show_module_data();
        }
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


    private function show_module_data()
    {
        $ui = Ui::getInstance();

        $ui->createPage();
        $ui->createDefaultHeader(
            __('Module data'),
            $ui->createHeaderButton(
                [
                    'icon'  => 'ui-icon-back',
                    'pos'   => 'left',
                    'text'  => __('Back'),
                    'href'  => 'index.php?page=modules',
                    'class' => 'header-button-left',
                ]
            )
        );
        $ui->showFooter(false);
        $ui->beginContent();
        $this->listDataHtml();
        $ui->endContent();
        $ui->showPage();
    }


    private function getListData($page=0, $ajax=false)
    {
        global $config;
        $system = System::getInstance();

        $total = 0;
        $data = [];

        $module_data = modules_get_agentmodule_data(
            $this->moduleId,
            604800,
            0,
            false,
            false,
            'DESC'
        );

        $total = (int) count($module_data);

        foreach ($module_data as $module) {
            $row[__('Data')] = '<span class="data">'.$module['data'].'</span>';
            $row[__('Timestamp')] = '<span class="data">'.human_time_comparation($module['utimestamp'], 'tiny').'</span>';

            array_push($data, $row);
        }

        return [
            'data'  => $data,
            'total' => $total,
        ];
    }


    public function listDataHtml($page=0, $return=false)
    {
        $system = System::getInstance();
        $ui = Ui::getInstance();

        $listData = $this->getListData($page);
        if ($listData['total'] == 0) {
            $html = '<p class="no-data">'.__('No data').'</p>';
            if (!$return) {
                $ui->contentAddHtml($html);
            } else {
                return $html;
            }
        } else {
            if (!$return) {
                $table = new Table();
                $table->id = 'list_module_data';
                $table->importFromHash($listData['data']);

                $ui->contentAddHtml('<div class="white-card">');

                $agent_id = agents_get_agent_id_by_module_id($this->moduleId);
                $agent_name = agents_get_name($agent_id);
                $module_name = modules_get_agentmodule_name($this->moduleId);
                $ui->contentAddHtml('<h1 class="center font-10pt">'.$module_name.'</h1>');
                $ui->contentAddHtml('<p class="center truncate muted">'.$agent_name.'</p>');

                $ui->contentAddHtml($table->getHTML());

                $ui->contentAddHtml('</div>');
            } else {
                $table = new Table();
                $table->id = 'list_module_data';

                $table->importFromHash($listData['data']);

                $html = $table->getHTML();

                return $html;
            }

            // if ($system->getPageSize() < $listData['total']) {
            // $ui->contentAddHtml(
            // '<div id="loading_rows">'.html_print_image('images/spinner.gif', true, false, false, false, false, true).' '.__('Loading...').'</div>'
            // );
            // $this->addJavascriptAddBottom();
            // }
        }

        $ui->contentAddLinkListener('list_servers');
    }


    private function addJavascriptAddBottom()
    {
        $ui = Ui::getInstance();

        $ui->contentAddHtml(
            "<script type=\"text/javascript\">
				var load_more_rows = 1;
				var page = 1;
				
				function custom_scroll() {
                    if (load_more_rows) {
                        if ($(this).scrollTop() + $(this).height()
                        >= ($(document).height() - 100)) {
                            load_more_rows = 0;
							
							postvars = {};
							postvars[\"action\"] = \"ajax\";
							postvars[\"parameter1\"] = \"module_data\";
							postvars[\"parameter2\"] = \"get_module_data\";
							postvars[\"page\"] = page;
							page++;
							
                            $.post(
                                \"index.php\",
								postvars,
								function (data) {
									if (data.end) {
										$(\"#loading_rows\").hide();
									}
									else {
										$.each(data.servers, function(key, server) {;
                                            $(\"table#list_servers tbody\").append(\"<tr>\" +
                                                    \"<td class='cell_0'><b class='ui-table-cell-label'>".__('Status')."</b>\" + server['Status'] + \"</td>\" +
                                                    \"<td class='cell_1'><b class='ui-table-cell-label'>".__('Image')."</b>\" + server['Image'] + \"</td>\" +
                                                    \"<td class='cell_2'><b class='ui-table-cell-label'>".__('Name')."</b>\" + server['Name'] + \"</td>\" +
                                                \"</tr>\");
                                            });
										
										load_more_rows = 1;
										refresh_link_listener_list_servers()
									}
									
									
								},
								\"json\");

                            
						}
					}
				}
				
				$(document).ready(function() {
                    
					$(window).bind(\"scroll\", function () {
						custom_scroll();
					});
					
					$(window).on(\"touchmove\", function(event) {
						custom_scroll();
					});
				});
			</script>"
        );
    }


    private function filterServersGetString()
    {
        if ($this->default) {
            return __('(Default)');
        } else {
            $filters_to_serialize = [];

            if (!$this->default_filters['group']) {
                $filters_to_serialize[] = sprintf(
                    __('Group: %s'),
                    groups_get_name($this->group, true)
                );
            }

            if (!$this->default_filters['module_group']) {
                $module_group = db_get_value(
                    'name',
                    'tmodule_group',
                    'id_mg',
                    $this->module_group
                );
                $module_group = io_safe_output($module_group);

                $filters_to_serialize[] = sprintf(
                    __('Module group: %s'),
                    $module_group
                );
            }

            if (!$this->default_filters['status']) {
                $filters_to_serialize[] = sprintf(
                    __('Status: %s'),
                    $this->list_status[$this->status]
                );
            }

            if (!$this->default_filters['free_search']) {
                $filters_to_serialize[] = sprintf(
                    __('Free Search: %s'),
                    $this->free_search
                );
            }

            if (!$this->default_filters['tag']) {
                $tag_name = tags_get_name($this->tag);
                    $filters_to_serialize[] = sprintf(
                        __('Tag: %s'),
                        $tag_name
                    );
            }

            $string = '('.implode(' - ', $filters_to_serialize).')';

            return $string;
        }
    }


}
