<?php
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
/**
 * Agents list view for mobile
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
class Agents
{

    private $correct_acl = false;

    private $acl = 'AR';

    private $default = true;

    private $default_filters = [];

    private $group = 0;

    private $status = -1;

    private $free_search = '';

    private $list_status = null;


    function __construct()
    {
        $system = System::getInstance();

        $this->list_status = [
            -1                               => __('All'),
            AGENT_MODULE_STATUS_CRITICAL_BAD => __('Critical'),
            AGENT_MODULE_STATUS_NORMAL       => __('Normal'),
            AGENT_MODULE_STATUS_WARNING      => __('Warning'),
            AGENT_MODULE_STATUS_UNKNOWN      => __('Unknown'),
        ];

        if ($system->checkACL($this->acl)) {
            $this->correct_acl = true;
        } else {
            $this->correct_acl = false;
        }
    }


    public function ajax($parameter2=false)
    {
        $system = System::getInstance();

        if (!$this->correct_acl) {
            return;
        } else {
            switch ($parameter2) {
                case 'get_agents':
                    $this->getFilters();
                    $page = $system->getRequest('page', 0);

                    $agents = [];
                    $end = 1;

                    $listAgents = $this->getListAgents($page, true);

                    if (empty($listAgents['agents']) === false) {
                        $end = 0;

                        $agents = [];
                        foreach ($listAgents['agents'] as $key => $agent) {
                            $agent[0] = '<b class="ui-table-cell-label">'.__('Agent').'</b>'.$agent[0];
                            $agent[2] = '<b class="ui-table-cell-label">'.__('OS').'</b>'.$agent[2];
                            $agent[3] = '<b class="ui-table-cell-label">'.__('Group').'</b>'.$agent[3];
                            $agent[5] = '<b class="ui-table-cell-label">'.__('Modules').'</b>'.$agent[5];
                            $agent[6] = '<b class="ui-table-cell-label">'.__('Status').'</b>'.$agent[6];
                            $agent[7] = '<b class="ui-table-cell-label">'.__('Alerts').'</b>'.$agent[7];
                            $agent[8] = '<b class="ui-table-cell-label">'.__('Last contact').'</b>'.$agent[8];
                            $agent[9] = '<b class="ui-table-cell-label">'.__('Last status change').'</b>'.$agent[9];

                            $agents[$key] = $agent;
                        }
                    }

                    echo json_encode(['end' => $end, 'agents' => $agents]);
                break;
            }
        }
    }


    private function getFilters()
    {
        $system = System::getInstance();
        $user = User::getInstance();

        // Default.
        $filters = [
            'free_search' => '',
            'status'      => -1,
            'group'       => 0,
        ];

        $serialized_filters = (string) $system->getRequest('agents_filter');
        if (empty($serialized_filters) === true) {
            $filters_unsafe = json_decode(base64_decode($serialized_filters, true), true);
            if ($filters_unsafe) {
                $filters = $system->safeInput($filters_unsafe);
            }
        }

        $this->default_filters['group'] = true;
        $this->default_filters['status'] = true;
        $this->default_filters['free_search'] = true;

        $this->free_search = $system->getRequest('free_search', $filters['free_search']);
        if ($this->free_search != '') {
            $this->default = false;
            $this->default_filters['free_search'] = false;
            $filters['free_search'] = $this->free_search;
        }

        $this->status = $system->getRequest('status', $filters['status']);
        if (($this->status === __('Status')) || ($this->status == -1)) {
            $this->status = -1;
        } else {
            $this->default = false;
            $this->default_filters['status'] = false;
            $filters['status'] = (int) $this->status;
        }

        $this->group = (int) $system->getRequest('group', $filters['group']);
        if (!$user->isInGroup($this->acl, $this->group)) {
            $this->group = 0;
        }

        if (($this->group === __('Group')) || ($this->group == 0)) {
            $this->group = 0;
        } else {
            $this->default = false;
            $this->default_filters['group'] = false;
            $filters['group'] = $this->group;
        }

        if (empty($filters) === false) {
            // Store the filter.
            $this->serializedFilters = base64_encode(json_encode($system->safeOutput($filters)));
        }
    }


    public function show()
    {
        if (!$this->correct_acl) {
            $this->show_fail_acl();
        } else {
            $this->getFilters();
            $this->show_agents();
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


    private function show_agents()
    {
        $ui = Ui::getInstance();

        $ui->createPage();
        $ui->createDefaultHeader(
            __('Agents'),
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
            $filter_title = sprintf(
                __('Filter Agents by %s'),
                $this->filterEventsGetString()
            );
            $ui->contentBeginCollapsible($filter_title, 'filter-collapsible');
                $ui->beginForm('index.php?page=agents');
                    $system = System::getInstance();
                    $groups = users_get_groups_for_select(
                        $system->getConfig('id_user'),
                        'AR',
                        true,
                        true,
                        false,
                        'id_grupo'
                    );
                    $options = [
                        'name'     => 'group',
                        'title'    => __('Group'),
                        'label'    => __('Group'),
                        'items'    => $groups,
                        'selected' => $this->group,
                    ];
                    $ui->formAddSelectBox($options);

                    $options = [
                        'name'     => 'status',
                        'title'    => __('Status'),
                        'label'    => __('Status'),
                        'items'    => $this->list_status,
                        'selected' => $this->status,
                    ];
                    $ui->formAddSelectBox($options);

                    $options = [
                        'name'        => 'free_search',
                        'value'       => $this->free_search,
                        'placeholder' => __('Free search'),
                    ];
                    $ui->formAddInputSearch($options);

                    $options = [
                        'icon'     => 'refresh',
                        'icon_pos' => 'right',
                        'text'     => __('Apply Filter'),
                    ];
                    $ui->formAddSubmitButton($options);
                    $html = $ui->getEndForm();
                    $ui->contentCollapsibleAddItem($html);
                    $ui->contentEndCollapsible();
                    $this->listAgentsHtml();
                    $ui->endContent();
                    $ui->showPage();
    }


    private function getListAgents($page=0, $ajax=false)
    {
        $system = System::getInstance();

        $total = 0;
        $agents = [];

        $search_sql = '';

        if (empty($this->free_search) === false) {
            $search_sql = " AND (
				alias LIKE '%".$this->free_search."%'
				OR nombre LIKE '%".$this->free_search."%'
				OR direccion LIKE '%".$this->free_search."%'
				OR comentarios LIKE '%".$this->free_search."%') ";
        }

        if (!$system->getConfig('metaconsole')) {
            $total = agents_get_agents(
                [
                    'disabled' => 0,
                    'id_grupo' => $this->group,
                    'search'   => $search_sql,
                    'status'   => $this->status,
                ],
                ['COUNT(*) AS total'],
                'AR',
                false
            );
        } else {
            $total = agents_get_meta_agents(
                [
                    'disabled' => 0,
                    'id_grupo' => $this->group,
                    'search'   => $search_sql,
                    'status'   => $this->status,
                ],
                ['COUNT(*) AS total'],
                'AR',
                false
            );
        }

        $total = isset($total[0]['total']) ? $total[0]['total'] : 0;

        $order = [
            'field'  => 'alias',
            'field2' => 'nombre',
            'order'  => 'ASC',
        ];
        if (!$system->getConfig('metaconsole')) {
            $agents_db = agents_get_agents(
                [
                    'disabled' => 0,
                    'id_grupo' => $this->group,
                    'search'   => $search_sql,
                    'status'   => $this->status,
                    'offset'   => ((int) $page * $system->getPageSize()),
                    'limit'    => (int) $system->getPageSize(),
                ],
                [
                    'id_agente',
                    'id_grupo',
                    'id_os',
                    'alias',
                    'ultimo_contacto',
                    'intervalo',
                    'comentarios description',
                    'quiet',
                    'normal_count',
                    'warning_count',
                    'critical_count',
                    'unknown_count',
                    'notinit_count',
                    'total_count',
                    'fired_count',
                ],
                'AR',
                $order
            );
        } else {
            $agents_db = agents_get_meta_agents(
                [
                    'disabled' => 0,
                    'id_grupo' => $this->group,
                    'search'   => $search_sql,
                    'status'   => $this->status,
                    'offset'   => ((int) $page * $system->getPageSize()),
                    'limit'    => (int) $system->getPageSize(),
                ],
                [
                    'id_agente',
                    'id_grupo',
                    'id_os',
                    'alias',
                    'ultimo_contacto',
                    'intervalo',
                    'comentarios description',
                    'quiet',
                    'normal_count',
                    'warning_count',
                    'critical_count',
                    'unknown_count',
                    'notinit_count',
                    'total_count',
                    'fired_count',
                ],
                'AR',
                $order
            );
        }

        if (empty($agents_db)) {
            $agents_db = [];
        }

        foreach ($agents_db as $agent) {
            $row = [];

            $img_status = agents_tree_view_status_img(
                $agent['critical_count'],
                $agent['warning_count'],
                $agent['unknown_count'],
                $agent['total_count'],
                $agent['notinit_count']
            );

            $img_alert = agents_tree_view_alert_img($agent['fired_count']);

            $serialized_filters_q_param = empty($this->serializedFilters) ? '' : '&agents_filter='.$this->serializedFilters;

            $row[0] = $row[__('Agent')] = '<span class="tiny agent-status">'.$img_status.'</span>'.'<a class="ui-link" data-ajax="false" href="index.php?page=agent&id='.$agent['id_agente'].$serialized_filters_q_param.'">'.ui_print_truncate_text($agent['alias'], 30, false).'</a>';
            $row[2] = $row[__('OS')] = ui_print_os_icon($agent['id_os'], false, true);
            $row[3] = $row[__('Group')] = ui_print_group_icon($agent['id_grupo'], true, 'groups_small', '', false);
            $row[5] = $row[__('Status')] = '<span class="show_collapside align-none-10p">'.__('S.').' </span>'.$img_status;
            $row[6] = $row[__('Alerts')] = '<span class="show_collapside align-none-10p">&nbsp;&nbsp;'.__('A.').' </span>'.$img_alert;

            $row[7] = $row[__('Modules')] = '<span class="agents_tiny_stats">'.reporting_tiny_stats($agent, true, 'agent', ':').' </span>';

            $last_time = time_w_fixed_tz($agent['ultimo_contacto']);
            $now = get_system_time();
            $diferencia = ($now - $last_time);
            $time = human_time_comparation($agent['ultimo_contacto'], 'tiny');
            $style = '';
            if ($diferencia > ($agent['intervalo'] * 2)) {
                $row[8] = $row[__('Last contact')] = '<b><span class="color_ff0">'.$time.'</span></b>';
            } else {
                $row[8] = $row[__('Last contact')] = $time;
            }

            $row[8] = $row[__('Last contact')] = '<span class="agents_last_contact">'.$row[__('Last contact')].'</span>';

            $last_status_change = human_time_comparation(agents_get_last_status_change($agent['id_agente']), 'tiny');
            $row[9] = $row[__('Last status change')] = '<span class="agents_last_contact">'.$last_status_change.'</span>';

            if (!$ajax) {
                unset($row[0]);
                unset($row[1]);
                unset($row[2]);
                unset($row[3]);
                unset($row[4]);
                unset($row[5]);
                unset($row[6]);
                unset($row[7]);
                unset($row[8]);
                unset($row[9]);
            }

            $agents[$agent['id_agente']] = $row;
        }

        return [
            'agents' => $agents,
            'total'  => $total,
        ];
    }


    private function listAgentsHtml($page=0)
    {
        $system = System::getInstance();
        $ui = Ui::getInstance();

        $listAgents = $this->getListAgents($page);

        if ($listAgents['total'] == 0) {
            $ui->contentAddHtml('<p class="no-data">'.__('No agents').'</p>');
        } else {
            $table = new Table();
            $table->id = 'list_agents';
            $table->importFromHash($listAgents['agents']);

            $ui->contentAddHtml('<div class="hr-full"></div>');
            $ui->contentAddHtml('<div class="white-card p-lr-0px">');
            $ui->contentAddHtml($table->getHTML());

            if ($system->getPageSize() < $listAgents['total']) {
                $ui->contentAddHtml(
                    '<br><div id="loading_rows">'.html_print_image('images/spinner.gif', true, false, false, false, false, true).' '.__('Loading...').'</div>'
                );

                $this->addJavascriptAddBottom();
            }

            $ui->contentAddHtml('</div>');
        }

        $ui->contentAddLinkListener('list_agents');
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
								postvars[\"parameter1\"] = \"agents\";
								postvars[\"parameter2\"] = \"get_agents\";
								postvars[\"group\"] = $(\"select[name='group']\").val();
								postvars[\"status\"] = $(\"select[name='status']\").val();
								postvars[\"free_search\"] = $(\"input[name='free_search']\").val();
								postvars[\"page\"] = page;
								page++;

								$.post(\"index.php\",
									postvars,
									function (data) {
										if (data.end) {
											$(\"#loading_rows\").hide();
										}
										else {
											$.each(data.agents, function(key, agent) {
												$(\"table#list_agents tbody\")
													.append(\"<tr class=''>\" +
														\"<td class='cell_0'>\" + agent[0] + \"</td>\" +
														\"<td class='cell_1'>\" + agent[2] + \"</td>\" +
														\"<td class='cell_2'>\" + agent[3] + \"</td>\" +
														\"<td class='cell_3'>\" + agent[5] + \"</td>\" +
														\"<td class='cell_4'>\" + agent[6] + \"</td>\" +
														\"<td class='cell_5'>\" + agent[7] + \"</td>\" +
														\"<td class='cell_6'>\" + agent[8] + \"</td>\" +
														\"<td class='cell_7'>\" + agent[9] + \"</td>\" +
													\"</tr>\");
												});

											load_more_rows = 1;
											refresh_link_listener_list_agents();
										}
									},
									\"json\");
                                // Clean
                                // $('#loading_rows').remove();
							}
						}
				}

				$(document).ready(function() {
                    // Be sure of fill all of screen first.
                    custom_scroll();

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


    private function filterEventsGetString()
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

            $string = '('.implode(' - ', $filters_to_serialize).')';

            return $string;
        }
    }


}
