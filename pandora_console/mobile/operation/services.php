<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
use PandoraFMS\Enterprise\Service;

require_once '../include/functions_users.php';
enterprise_include_once('meta/include/functions_ui_meta.php');
require_once '../include/functions_groupview.php';

class Services
{

    private $correct_acl = false;

    private $enterprise = false;

    private $acl = 'AR';

    private $services = [];

    private $idTable = '';

    private $serviceId = '';

    private $rows = [];


    function __construct()
    {
        $system = System::getInstance();

        if ($system->checkACL($this->acl)) {
            $this->correct_acl = true;

            $this->services = enterprise_hook('services_get_services');
        } else {
            $this->correct_acl = false;
        }

        if ($system->checkEnterprise() === false) {
            $this->show_fail_enterprise();
        }
    }


    public function show()
    {
        if (!$this->correct_acl) {
            $this->show_fail_acl();
        } else {
            $this->show_services();
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


    private function show_fail_enterprise()
    {
        $error['type'] = 'onStart';
        $error['title_text'] = __('You don\'t have access to this page');
        $error['content_text'] = System::getDefaultLicenseFailText();
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

        if (!$this->correct_acl) {
            return;
        } else {
            switch ($parameter2) {
                case 'get_services':
                    $this->serviceId = $system->getRequest('service_id', 0);
                    $rows = $this->getListServices();

                    $this->rows = $rows;
                    $table = $this->getTable();

                    echo $table;
                break;
            }
        }
    }


    private function show_services()
    {
        $ui = Ui::getInstance();

        $ui->createPage();
        $ui->createDefaultHeader(
            __('Services'),
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

        if (empty($this->services)) {
            $ui->contentAddHtml(
                '<span class="no-data">'.__('No services found').'</span>'
            );
        }

        $ui->contentAddHtml('<div class="list_services" data-role="collapsible-set" data-theme="a" data-content-theme="d">');
            $count = 0;
            $url_agent = 'index.php?page=agents&group=%s&status=%s';
            $url_modules = 'index.php?page=modules&group=%s&status=%s';

        foreach ($this->services as $service) {
            switch ($service['status']) {
                case SERVICE_STATUS_NORMAL:
                    $color = COL_NORMAL;
                break;

                case SERVICE_STATUS_CRITICAL:
                    $color = COL_CRITICAL;
                break;

                case SERVICE_STATUS_WARNING:
                    $color = COL_WARNING;
                break;

                case SERVICE_STATUS_UNKNOWN:
                default:
                    $color = COL_UNKNOWN;
                break;
            }

            $group_icon = ui_print_group_icon($service['id_group'], true, '../images/groups_small_white', '', false);

            $ui->contentAddHtml(
                '
                <style type="text/css">
                    .ui-icon-group_'.$count.' {
                        background-color: '.$color.' !important;
                    }
                </style>
                '
            );
            $ui->contentAddHtml('<div class="border-collapsible" data-collapsed-icon="group_'.$count.'" data-expanded-icon="group_'.$count.'" data-iconpos="right" data-role="collapsible" data-collapsed="true" data-content-theme="d">');
            $arrow = '<span class="ui-icon ui-icon-arrow-d"></span>';
            $ui->contentAddHtml('<h4 id="service-'.$service['id'].'" onclick="loadTable(\''.$service['id'].'\')">'.$arrow.$group_icon.'&nbsp;'.$service['name'].'</h4>');

            $spinner = '
                <div class="spinner mt15px" id="spinner-'.$service['id'].'">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            ';

            $ui->contentAddHtml($spinner);
            $ui->contentAddHtml('</div>');

            $count++;
        }

            $ui->contentAddHtml('</div>');
            $this->addJavascriptAddBottom();

            $ui->endContent();
            $ui->showPage();
    }


    public function getListServices()
    {
        $ui = Ui::getInstance();
        $this->idTable = 'service-table-'.$this->serviceId;
        $rows = [];

        $elements_list = new Service($this->serviceId);
        $elements = $elements_list->getElements(true);

        if (empty($elements) === false) {
            foreach ($elements as $item) {
                $name = '';
                // Icon.
                switch ($item->type()) {
                    case SERVICE_ELEMENT_AGENT:
                        $element_icon = html_print_image(
                            'images/agents@svg.svg',
                            true,
                            [
                                'title' => __('Agent'),
                                'class' => 'main_menu_icon',
                            ]
                        );

                        if ($item->exists() !== true) {
                            $name .= '<strong class="no-data">'.__('Nonexistent. This element should be deleted').'</strong>';
                        } else {
                            $url = ui_get_full_url('mobile/index.php?page=agent&id='.$item->agent()->id_agente());
                            $name = '<a href="'.$url.'">';
                            if (((bool) $item->agent()->disabled()) === true) {
                                $disabled_element = true;
                                if (is_metaconsole() === true) {
                                    $name .= '<em class="grey_disabled">';
                                } else {
                                    $name .= '<em class="disabled_module">';
                                }
                            }

                            if (is_metaconsole()
                                && ((int) $item->id_server_meta()) !== 0
                            ) {
                                $name .= $item->nodeName().' » ';
                            }

                            $name .= $item->agent()->alias();
                            $name .= '</a>';

                            if (((bool) $item->agent()->disabled()) === true) {
                                $name .= ui_print_help_tip(
                                    __('This element does not affect service weigth because is disabled.'),
                                    true
                                ).'</em>';
                            }
                        }
                    break;

                    case SERVICE_ELEMENT_SERVICE:
                        $element_icon = html_print_image(
                            'images/item-service.svg',
                            true,
                            [
                                'title' => __('Service'),
                                'class' => 'main_menu_icon',
                            ]
                        );
                        if ($item->exists() !== true) {
                            $name .= '<strong class="no-data">'.__('Nonexistent. This element should be deleted').'</strong>';
                        } else {
                            if (is_metaconsole()
                                && (((int) $item->id_server_meta()) !== 0 )
                            ) {
                                $server = db_get_row(
                                    'tmetaconsole_setup',
                                    'id',
                                    $item->id_server_meta()
                                );

                                $url = ui_meta_get_url_console_child(
                                    $server,
                                    'estado',
                                    'enterprise/operation/services/services',
                                    [
                                        'tab'        => 'service',
                                        'action'     => 'view',
                                        'id_service' => $item->id_service_child(),
                                    ]
                                );
                            } else {
                                $url = ui_get_full_url(
                                    'index.php?sec=network&sec2=enterprise/operation/services/services&tab=service&action=view&id_service='.$item->id_service_child()
                                );
                            }

                            // $name = '<a target="_blank" href="'.$url.'">';
                            $name = '';
                            if (((bool) $item->service()->disabled()) === true) {
                                $disabled_element = true;
                                if (is_metaconsole() === true) {
                                    $name .= '<em class="grey_disabled">';
                                } else {
                                    $name .= '<em class="disabled_module">';
                                }
                            }

                            if (is_metaconsole()
                                && ((int) $item->id_server_meta()) !== 0
                            ) {
                                $name .= $item->nodeName().' » ';
                            }

                            $name .= $item->service()->name();
                            // $name .= '</a>';
                            if (((bool) $item->service()->disabled()) === true) {
                                $name .= ui_print_help_tip(
                                    __('This element does not affect service weigth because is disabled.'),
                                    true
                                ).'</em>';
                            }
                        }
                    break;

                    case SERVICE_ELEMENT_MODULE:
                        $element_icon = html_print_image(
                            'images/modules@svg.svg',
                            true,
                            [
                                'title' => __('Module'),
                                'class' => 'main_menu_icon',
                            ]
                        );

                        if ($item->exists() !== true) {
                            $name .= '<strong class="no-data">'.__('Nonexistent. This element should be deleted').'</strong>';
                        } else {
                            $url = ui_get_full_url('mobile/index.php?page=agent&id='.$item->agent()->id_agente());
                            $name = '<a href="'.$url.'">';

                            if (((bool) $item->module()->disabled()) === true) {
                                $disabled_element = true;
                                if (is_metaconsole()) {
                                    $name .= '<em class="grey_disabled">';
                                } else {
                                    $name .= '<em class="disabled_module">';
                                }
                            }

                            if (is_metaconsole()
                                && ((int) $item->id_server_meta()) !== 0
                            ) {
                                $name .= $item->nodeName().' » ';
                            }

                            $name .= $item->module()->agent()->alias();
                            $name .= ' » '.$item->module()->nombre();

                            if (((bool) $item->module()->disabled()) === true) {
                                $name .= ui_print_help_tip(
                                    __('This element does not affect service weigth because is disabled.'),
                                    true
                                ).'</em>';
                            }

                            $name .= '</a>';
                        }
                    break;

                    case SERVICE_ELEMENT_DYNAMIC:
                        $element_icon = html_print_image(
                            'images/modules-group@svg.svg',
                            true,
                            [
                                'title' => __('Dynamic element'),
                                'class' => 'main_menu_icon',
                            ]
                        );

                        try {
                            if (empty($item->getMatches(true)) === true) {
                                ui_print_warning_message(
                                    __(
                                        'Dynamic element (%d) \'%s\' does not match any target',
                                        $item->id(),
                                        $item->description()
                                    )
                                );
                            }
                        } catch (Exception $e) {
                            ui_print_warning_message(
                                __(
                                    'Dynamic element (%d) \'%s\' causes an error: %s',
                                    $item->id(),
                                    $item->description(),
                                    $e->getMessage()
                                )
                            );
                        }

                        if ($item->rules()->dynamic_type === 'agent') {
                            $name = '<i>'.__(
                                'agents like "%s"',
                                $item->rules()->agent_name
                            ).'</i>';
                        } else if ($item->rules()->dynamic_type === 'module') {
                            $name = '<i>'.__(
                                'modules like "%s"',
                                $item->rules()->module_name
                            ).'</i>';
                        }
                    break;

                    default:
                        $element_icon = '';
                    break;
                }

                // Status.
                switch ($item->lastStatus(true)) {
                    case SERVICE_STATUS_NORMAL:
                    case AGENT_STATUS_NORMAL:
                    case AGENT_MODULE_STATUS_NORMAL:
                        $status_element = STATUS_MODULE_OK;
                        $title_element = __('NORMAL');
                    break;

                    case SERVICE_STATUS_CRITICAL:
                    case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                    case AGENT_MODULE_STATUS_CRITICAL_BAD:
                    case AGENT_STATUS_CRITICAL:
                        $status_element = STATUS_MODULE_CRITICAL;
                        $title_element = __('CRITICAL');
                    break;

                    case SERVICE_STATUS_WARNING:
                    case AGENT_MODULE_STATUS_WARNING:
                    case AGENT_MODULE_STATUS_WARNING_ALERT:
                    case AGENT_STATUS_WARNING:
                        $status_element = STATUS_MODULE_WARNING;
                        $title_element = __('WARNING');
                    break;

                    case SERVICE_STATUS_ALERT:
                    case AGENT_MODULE_STATUS_NOT_INIT:
                    case AGENT_STATUS_NOT_INIT:
                        $status_element = STATUS_MODULE_NO_DATA;
                        $title_element = __('NOT INITIALIZED');
                    break;

                    case AGENT_MODULE_STATUS_UNKNOWN:
                    case SERVICE_STATUS_UNKNOWN:
                    case AGENT_STATUS_UNKNOWN:
                    default:
                        $status_element = STATUS_MODULE_UNKNOWN;
                        $title_element = __('UNKNOWN');
                    break;
                }

                $row = [];
                $row[0] = $element_icon;
                $row[1] = $name;
                $row[2] = ui_print_status_image($status_element, $title_element, true);

                array_push($rows, $row);
            }
        }

        return $rows;
    }


    public function getTable()
    {
        $html = '';

        $html = "<table data-role='table' id=service-table-".$this->serviceId."' data-mode='reflow' class='ui-responsive table-stroke'>";

            // $html .= '<thead>';
            // $html .= '<tr>';
            // $html .= '<th class="head_horizontal">'.__('Type').'</th>';
            // $html .= '<th class="head_horizontal">'.__('Name').'</th>';
            // $html .= '<th class="head_horizontal">'.__('Status').'</th>';
            // $html .= '</tr>';
            // $html .= '</thead>';
        $html .= '<tbody>';
        foreach ($this->rows as $key => $row) {
            $html .= "<tr class=''>";

            foreach ($row as $key_cell => $cell) {
                $html .= "<td class='cell_".$key_cell."'>".$cell.'</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }


    private function addJavascriptAddBottom()
    {
        $ui = Ui::getInstance();

        $ui->contentAddHtml(
            "<script type=\"text/javascript\">				
				function loadTable(id) {
							
                    postvars = {};
                    postvars[\"action\"] = \"ajax\";
                    postvars[\"parameter1\"] = \"services\";
                    postvars[\"parameter2\"] = \"get_services\";
                    postvars[\"service_id\"] = id;
                    
                    $.post(
                        \"index.php\",
                        postvars,
                        function (data) {
                            $('h4#service-'+id+' + div.ui-collapsible-content').html(data);
                        },
                        \"html\");

                    var arrow = document.querySelector('h4#service-'+id+' > a > span.ui-icon.ui-icon-arrow-d');
                    
                    if (arrow.style.transform == 'rotate(180deg)') {
                        arrow.style.transform = '';
                    } else {
                        arrow.style.transform = 'rotate(180deg)';
                    }
				}
			</script>"
        );
    }


}
