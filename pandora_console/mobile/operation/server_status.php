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
class ServerStatus
{

    private $correct_acl = false;

    private $acl = 'AR';

    private $default = true;

    private $default_filters = [];

    private $group = 0;

    private $status = AGENT_MODULE_STATUS_NOT_NORMAL;

    private $free_search = '';

    private $name = '';

    private $module_group = -1;

    private $tag = '';

    private $id_agent = 0;

    private $all_servers = false;

    private $list_status = null;

    private $columns = null;


    function __construct()
    {
        $system = System::getInstance();

        if ($system->checkACL($this->acl)) {
            $this->correct_acl = true;
        } else {
            $this->correct_acl = false;
        }

        if ($system->getConfig('metaconsole') === 0) {
            $this->all_servers = true;
        }
    }


    public function ajax($parameter2=false)
    {
        $system = System::getInstance();

        if (!$this->correct_acl) {
            return;
        } else {
            switch ($parameter2) {
                case 'get_server_status':
                    $this->getFilters();
                    $page = $system->getRequest('page', 0);
                    $servers = [];
                    $end = 1;

                    $listServers = $this->getListServers($page, true);

                    if (!empty($listServers['servers'])) {
                        $end = 0;
                        $servers = $listServers['servers'];
                    }

                    echo json_encode(['end' => $end, 'servers' => $servers]);
                break;
            }
        }
    }


    public function setFilters($filters)
    {
        if (isset($filters['id_agent'])) {
            $this->id_agent = $filters['id_agent'];
        }

        if (isset($filters['all_servers'])) {
            $this->all_servers = $filters['all_servers'];
        }

        if (isset($filters['status'])) {
            $this->status = (int) $filters['status'];
        }

        if (isset($filters['name'])) {
            $this->name = $filters['name'];
        }
    }


    public function disabledColumns($columns=null)
    {
        if (!empty($columns)) {
            foreach ($columns as $column) {
                $this->columns[$column] = 0;
            }
        }
    }


    private function getFilters()
    {
        $system = System::getInstance();
        $user = User::getInstance();

        $this->default_filters['module_group'] = true;
        $this->default_filters['group'] = true;
        $this->default_filters['status'] = true;
        $this->default_filters['free_search'] = true;
        $this->default_filters['tag'] = true;

        $this->free_search = $system->getRequest('free_search', '');
        if ($this->free_search != '') {
            $this->default = false;
            $this->default_filters['free_search'] = false;
        }

        $this->status = $system->getRequest('status', __('Status'));
        if (($this->status === __('Status')) || ((int) $this->status === AGENT_MODULE_STATUS_ALL)) {
            $this->status = AGENT_MODULE_STATUS_ALL;
        } else {
            $this->default = false;
            $this->default_filters['status'] = false;
        }

        $this->group = (int) $system->getRequest('group', __('Group'));
        if (!$user->isInGroup($this->acl, $this->group)) {
            $this->group = 0;
        }

        if (($this->group === __('Group')) || ($this->group == 0)) {
            $this->group = 0;
        } else {
            $this->default = false;
            $this->default_filters['group'] = false;
        }

        $this->module_group = (int) $system->getRequest('module_group', __('Module group'));
        if (($this->module_group === __('Module group')) || ($this->module_group === -1)
            || ($this->module_group == 0)
        ) {
            $this->module_group = -1;
        } else {
            $this->default = false;
            $this->module_group = (int) $this->module_group;
            $this->default_filters['module_group'] = false;
        }

        $this->tag = (int) $system->getRequest('tag', __('Tag'));
        if (($this->tag === __('Tag')) || ($this->tag == 0)) {
            $this->tag = 0;
        } else {
            $this->default = false;
            $this->default_filters['tag'] = false;
        }
    }


    public function show()
    {
        if (!$this->correct_acl) {
            $this->show_fail_acl();
        } else {
            $this->getFilters();
            $this->show_servers();
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


    private function show_servers()
    {
        $ui = Ui::getInstance();

        $ui->createPage();
        $ui->createDefaultHeader(
            __('Server status'),
            $ui->createHeaderButton(
                [
                    'icon'  => 'ui-icon-back',
                    'pos'   => 'left',
                    'text'  => __('Back'),
                    'href'  => 'index.php?page=home',
                    'class' => 'header-button-left',
                ]
            )
        );
        $ui->showFooter(false);
        $ui->beginContent();
        $this->listServersHtml();
        $ui->endContent();
        $ui->showPage();
    }


    private function getListServers($page=0, $ajax=false)
    {
        global $config;
        $system = System::getInstance();
        $user = User::getInstance();

        $total = 0;
        $servers = [];
        $servers_db = [];

        if ($this->all_servers === true) {
            $sql_limit = ' LIMIT '.(int) ($page * $system->getPageSize()).','.(int) $system->getPageSize();
        }

        if ($system->getConfig('metaconsole')) {
            $servers_info = servers_get_info();
            $total = (int) count(servers_get_info());

            foreach ($servers_info as $server_value) {
                $image_status = ui_print_status_image(STATUS_SERVER_OK, '', true);
                if ($server_value['status'] == -1) {
                    $image_status = ui_print_status_image(
                        STATUS_SERVER_CRASH,
                        __('Server has crashed.'),
                        true
                    );
                } else if ($server_value['status'] == 0) {
                    $image_status = ui_print_status_image(
                        STATUS_SERVER_DOWN,
                        __('Server is stopped.'),
                        true
                    );
                }

                // $row[__('Status')] = '<span class="data">'.$server_value['status'].'</span>';
                $row[__('Status')] = '<span class="data">'.$image_status.'</span>';
                $row[__('Image')] = '<span class="data">'.$server_value['img'].'</span>';
                $row[__('Name')] = '<span class="data">'.$server_value['name'].'</span>';

                $servers[$server_value['id_server'].'-'.$server_value['name']] = $row;
            }

            $meta_servers = metaconsole_get_servers();

            if ($meta_servers === false) {
                $meta_servers = [];
            }

            foreach ($meta_servers as $server) {
                if (metaconsole_connect($server) != NOERR) {
                    continue;
                }

                $servers_info = servers_get_info();
                $total += (int) count(servers_get_info());

                foreach ($servers_info as $server_value) {
                    $image_status = ui_print_status_image(STATUS_SERVER_OK, '', true);
                    if ($server_value['status'] == -1) {
                        $image_status = ui_print_status_image(
                            STATUS_SERVER_CRASH,
                            __('Server has crashed.'),
                            true
                        );
                    } else if ($server_value['status'] == 0) {
                        $image_status = ui_print_status_image(
                            STATUS_SERVER_DOWN,
                            __('Server is stopped.'),
                            true
                        );
                    }

                    // $row[__('Status')] = '<span class="data">'.$server_value['status'].'</span>';
                    $row[__('Status')] = '<span class="data">'.$image_status.'</span>';
                    $row[__('Image')] = '<span class="data">'.$server_value['img'].'</span>';
                    $row[__('Name')] = '<span class="data">'.$server_value['name'].'</span>';

                    $servers[$server_value['id_server']] = $row;
                }

                metaconsole_restore_db();
            }
        } else {
            $servers_info = servers_get_info(-1, $sql_limit);
            $total = count(servers_get_info());

            foreach ($servers_info as $server_value) {
                $image_status = ui_print_status_image(STATUS_SERVER_OK, '', true);
                if ($server_value['status'] == -1) {
                    $image_status = ui_print_status_image(
                        STATUS_SERVER_CRASH,
                        __('Server has crashed.'),
                        true
                    );
                } else if ($server_value['status'] == 0) {
                    $image_status = ui_print_status_image(
                        STATUS_SERVER_DOWN,
                        __('Server is stopped.'),
                        true
                    );
                }

                // $row[__('Status')] = '<span class="data">'.$server_value['status'].'</span>';
                $row[__('Status')] = '<span class="data">'.$image_status.'</span>';
                $row[__('Image')] = '<span class="data">'.$server_value['img'].'</span>';
                $row[__('Name')] = '<span class="data">'.$server_value['name'].'</span>';

                $servers[$server_value['id_server']] = $row;
            }
        }

        return [
            'servers' => $servers,
            'total'   => $total,
        ];
    }


    public function listServersHtml($page=0, $return=false)
    {
        $system = System::getInstance();
        $ui = Ui::getInstance();

        $listServers = $this->getListServers($page);
        if ($listServers['total'] == 0) {
            $html = '<p class="no-data">'.__('No servers').'</p>';
            if (!$return) {
                $ui->contentAddHtml($html);
            } else {
                return $html;
            }
        } else {
            if (!$return) {
                $table = new Table();
                $table->id = 'list_servers';
                $table->importFromHash($listServers['servers']);

                $ui->contentAddHtml('<div class="white-card">');
                $ui->contentAddHtml($table->getHTML());

                if ($this->all_servers === true) {
                    if ($system->getPageSize() < $listServers['total']) {
                        $ui->contentAddHtml(
                            '<br><div id="loading_rows">'.html_print_image('images/spinner.gif', true, false, false, false, false, true).' '.__('Loading...').'</div>'
                        );

                        $this->addJavascriptAddBottom();
                    }
                }

                $ui->contentAddHtml('</div>');
            } else {
                $table = new Table();
                $table->id = 'list_servers_status';

                $table->importFromHash($listServers['servers']);

                $html = $table->getHTML();

                return $html;
            }
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
							postvars[\"parameter1\"] = \"server_status\";
							postvars[\"parameter2\"] = \"get_server_status\";
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

                let intervalId;
                let count = 0;
                function getFreeSpace() {
                    let headerHeight = $('div[data-role=\"header\"].ui-header').outerHeight();
                    let contentHeight = $('div[data-role=\"content\"].ui-content').outerHeight();
                    let windowHeight = $(window).height();

                    let freeSpace = windowHeight - (headerHeight + contentHeight);

                    if (freeSpace > 0 && count < 50) {
                        custom_scroll();
                    } else {
                        clearInterval(intervalId);
                    }

                    count++;
                }
				
				$(document).ready(function() {
                    intervalId = setInterval(getFreeSpace, 500);
                    
					$(window).bind(\"scroll\", function () {
						custom_scroll();
					});
					
					$(window).on(\"touchmove\", function(event) {
						custom_scroll();
					});

                    window.addEventListener('DOMContentLoaded', (event) => {
                        document.querySelector('table#list_servers span.data a').href = '#';
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
