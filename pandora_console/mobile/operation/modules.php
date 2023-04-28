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
class Modules
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

    private $all_modules = false;

    private $list_status = null;

    private $columns = null;


    function __construct()
    {
        $system = System::getInstance();

        $this->list_status = [
            -1                               => __('All'),
            AGENT_MODULE_STATUS_NORMAL       => __('Normal'),
            AGENT_MODULE_STATUS_WARNING      => __('Warning'),
            AGENT_MODULE_STATUS_CRITICAL_BAD => __('Critical'),
            AGENT_MODULE_STATUS_UNKNOWN      => __('Unknown'),
            AGENT_MODULE_STATUS_NOT_NORMAL   => __('Not normal'),
        // default
            AGENT_MODULE_STATUS_NOT_INIT     => __('Not init'),
        ];

        $this->columns = ['agent' => 1];

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
                case 'get_modules':
                    $this->getFilters();
                    $page = $system->getRequest('page', 0);
                    $modules = [];
                    $end = 1;

                    $listModules = $this->getListModules($page, true);

                    if (!empty($listModules['modules'])) {
                        $end = 0;
                        $modules = $listModules['modules'];
                    }

                    echo json_encode(['end' => $end, 'modules' => $modules]);
                break;
            }
        }
    }


    public function setFilters($filters)
    {
        if (isset($filters['id_agent'])) {
            $this->id_agent = $filters['id_agent'];
        }

        if (isset($filters['all_modules'])) {
            $this->all_modules = $filters['all_modules'];
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
            $this->show_modules();
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


    private function show_modules()
    {
        $ui = Ui::getInstance();

        $ui->createPage();
        $ui->createDefaultHeader(
            __('Modules'),
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
                __('Filter Modules by %s'),
                $this->filterEventsGetString()
            );
            $ui->contentBeginCollapsible($filter_title, 'filter-collapsible');
                $ui->beginForm('index.php?page=modules');
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

                    $module_groups = db_get_all_rows_sql(
                        'SELECT *
						FROM tmodule_group
						ORDER BY name'
                    );
                    $module_groups = io_safe_output($module_groups);

                    array_unshift($module_groups, ['id_mg' => 0, 'name' => __('All')]);

                    $options = [
                        'name'       => 'module_group',
                        'title'      => __('Module group'),
                        'label'      => __('Module group'),
                        'item_id'    => 'id_mg',
                        'item_value' => 'name',
                        'items'      => $module_groups,
                        'selected'   => $this->module_group,
                    ];
                    $ui->formAddSelectBox($options);

                    $tags = tags_get_user_tags();

                    array_unshift($tags, __('All'));

                    $options = [
                        'name'     => 'tag',
                        'title'    => __('Tag'),
                        'label'    => __('Tag'),
                        'items'    => $tags,
                        'selected' => $this->tag,
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
                    $this->listModulesHtml();
                    $ui->endContent();
                    $ui->showPage();
    }


    private function getListModules($page=0, $ajax=false)
    {
        global $config;
        $system = System::getInstance();
        $user = User::getInstance();

        $id_type_web_content_string = db_get_value(
            'id_tipo',
            'ttipo_modulo',
            'nombre',
            'web_content_string'
        );

        $total = 0;
        $modules = [];
        $modules_db = [];

        $sql_conditions_base = ' WHERE 1=1';

        // Part SQL for the id_agent
        $sql_conditions_agent = '';
        if ($this->id_agent != 0) {
            $sql_conditions_agent = ' AND tagente_modulo.id_agente = '.$this->id_agent;
        }

        // Part SQL for the Group
        if ($this->group != 0) {
            $sql_conditions_group = ' AND tagente.id_grupo = '.$this->group;
        } else {
            $user_groups = implode(',', $user->getIdGroups($this->acl));
            $sql_conditions_group = ' AND tagente.id_grupo IN ('.$user_groups.')';
        }

        // Part SQL for the Tag
        $sql_conditions_tags = tags_get_acl_tags(
            $user->getIdUser(),
            $user->getIdGroups($this->acl),
            $this->acl,
            'module_condition',
            'AND',
            'tagente_modulo'
        );

        $sql_conditions = ' AND tagente_modulo.disabled = 0 AND tagente.disabled = 0';

        // Part SQL for the module_group
        if ($this->module_group > -1) {
            $sql_conditions .= sprintf(
                " AND tagente_modulo.id_module_group = '%d'",
                $this->module_group
            );
        }

        // Part SQL for the free search
        if ($this->free_search != '') {
            $sql_conditions .= sprintf(
                " AND (tagente.nombre LIKE '%%%s%%'
				OR tagente_modulo.nombre LIKE '%%%s%%'
				OR tagente_modulo.descripcion LIKE '%%%s%%')",
                $this->free_search,
                $this->free_search,
                $this->free_search
            );
        }

        // Search by module name.
        if ($this->name != '') {
            $sql_conditions .= sprintf(
                " AND lower(tagente_modulo.nombre) LIKE lower('%%%s%%') ",
                $this->name
            );
        }

        // Part SQL fro Status
        if ((int) $this->status == AGENT_MODULE_STATUS_NORMAL) {
            // Normal.
            $sql_conditions .= ' AND tagente_estado.estado = 0
			AND (utimestamp > 0 OR (tagente_modulo.id_tipo_modulo IN(21,22,23,100))) ';
        } else if ((int) $this->status === AGENT_MODULE_STATUS_CRITICAL_BAD) {
            // Critical.
            $sql_conditions .= ' AND tagente_estado.estado = 1 AND utimestamp > 0';
        } else if ((int) $this->status === AGENT_MODULE_STATUS_WARNING) {
            // Warning.
            $sql_conditions .= ' AND tagente_estado.estado = 2 AND utimestamp > 0';
        } else if ((int) $this->status === AGENT_MODULE_STATUS_NOT_NORMAL) {
            // Not normal.
            $sql_conditions .= ' AND tagente_estado.estado <> 0';
        } else if ((int) $this->status === AGENT_MODULE_STATUS_UNKNOWN) {
            // Unknown.
            $sql_conditions .= ' AND tagente_estado.estado = 3 AND tagente_estado.utimestamp <> 0';
        } else if ((int) $this->status === AGENT_MODULE_STATUS_NOT_INIT) {
            // Not init.
            $sql_conditions .= ' AND tagente_estado.utimestamp = 0
				AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100)';
        }

        if ($this->tag > 0) {
            $sql_conditions .= ' AND tagente_modulo.id_agente_modulo IN (
				SELECT ttag_module.id_agente_modulo
				FROM ttag_module
				WHERE ttag_module.id_tag = '.$this->tag.')';
        }

        $sql_conditions_all = $sql_conditions_base.$sql_conditions_agent.$sql_conditions.$sql_conditions_group.$sql_conditions_tags;

        $sql_select = "SELECT
			(SELECT GROUP_CONCAT(ttag.name SEPARATOR ',')
				FROM ttag
				WHERE ttag.id_tag IN (
					SELECT ttag_module.id_tag
					FROM ttag_module
					WHERE ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo))
			AS tags,
			tagente_modulo.id_agente_modulo,
			tagente.intervalo AS agent_interval,
			tagente.nombre AS agent_name,
			tagente.alias AS agent_alias,
			tagente_modulo.nombre AS module_name,
			tagente_modulo.history_data,
			tagente_modulo.flag AS flag,
			tagente.id_grupo AS id_group,
			tagente.id_agente AS id_agent,
			tagente_modulo.id_tipo_modulo AS module_type,
			tagente_modulo.module_interval,
			tagente_estado.datos,
			tagente_estado.estado,
			tagente_modulo.min_warning,
			tagente_modulo.max_warning,
			tagente_modulo.str_warning,
			tagente_modulo.unit,
			tagente_modulo.min_critical,
			tagente_modulo.max_critical,
			tagente_modulo.str_critical,
			tagente_modulo.extended_info,
			tagente_modulo.critical_inverse,
			tagente_modulo.warning_inverse,
			tagente_modulo.critical_instructions,
			tagente_modulo.warning_instructions,
			tagente_modulo.unknown_instructions,
			tagente_estado.utimestamp AS utimestamp";

        $sql_total = 'SELECT count(*)';

        $sql = ' FROM tagente INNER JOIN tagente_modulo
				ON tagente_modulo.id_agente = tagente.id_agente
			INNER JOIN tagente_estado 
				ON tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			LEFT JOIN tagent_secondary_group tasg
				ON tagente.id_agente = tasg.id_agent
			LEFT JOIN ttag_module
				ON ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo'.$sql_conditions_all;

        $sql_limit = 'ORDER BY tagente.nombre ASC ';

        if (!$this->all_modules) {
            $sql_limit = ' LIMIT '.(int) ($page * $system->getPageSize()).','.(int) $system->getPageSize();
        }

        if ($system->getConfig('metaconsole')) {
            $servers = db_get_all_rows_sql(
                'SELECT *
				FROM tmetaconsole_setup
				WHERE disabled = 0'
            );
            if ($servers === false) {
                $servers = [];
            }

            // $modules_db = array();
            $total = 0;
            foreach ($servers as $server) {
                if (metaconsole_connect($server) != NOERR) {
                    continue;
                }

                $temp_modules = db_get_all_rows_sql($sql_select.$sql.$sql_limit);

                foreach ($temp_modules as $result_element_key => $result_element_value) {
                    $result_element_value['server_id'] = $server['id'];
                    $result_element_value['server_name'] = $server['server_name'];
                    array_push($modules_db, $result_element_value);
                }

                $total += db_get_value_sql($sql_total.$sql);

                metaconsole_restore_db();
            }
        } else {
            $total = db_get_value_sql($sql_total.$sql);
            $modules_db = db_get_all_rows_sql($sql_select.$sql.$sql_limit);
        }

        if (empty($modules_db)) {
            $modules_db = [];
        } else {
            $modules = [];
            foreach ($modules_db as $module) {
                $row = [];

                $image_status = '';
                if ($module['utimestamp'] == 0 && (($module['module_type'] < 21
                    || $module['module_type'] > 23) && $module['module_type'] != 100)
                ) {
                    $image_status = ui_print_status_image(
                        STATUS_MODULE_NO_DATA,
                        __('NOT INIT'),
                        true
                    );
                } else if ($module['estado'] == 0) {
                    $image_status = ui_print_status_image(
                        STATUS_MODULE_OK,
                        __('NORMAL').': '.$module['datos'],
                        true
                    );
                } else if ($module['estado'] == 1) {
                    $image_status = ui_print_status_image(
                        STATUS_MODULE_CRITICAL,
                        __('CRITICAL').': '.$module['datos'],
                        true
                    );
                } else if ($module['estado'] == 2) {
                    $image_status = ui_print_status_image(
                        STATUS_MODULE_WARNING,
                        __('WARNING').': '.$module['datos'],
                        true
                    );
                } else {
                    $last_status = modules_get_agentmodule_last_status(
                        $module['id_agente_modulo']
                    );
                    switch ($last_status) {
                        case 0:
                            $image_status = ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('NORMAL').': '.$module['datos'],
                                true
                            );
                        break;

                        case 1:
                            $image_status = ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('CRITICAL').': '.$module['datos'],
                                true
                            );
                        break;

                        case 2:
                            $image_status = ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('WARNING').': '.$module['datos'],
                                true
                            );
                        break;
                    }
                }

                $script = '';
                if ($system->getConfig('metaconsole')) {
                    $script = 'onclick="openDialog('.$module['id_agente_modulo'].', '.$this->id_agent.' ,'.$module['server_id'].');"';
                } else {
                    $script = 'onclick="openDialog('.$module['id_agente_modulo'].', '.$this->id_agent.', \'node\');"';
                }

                if ($system->getRequest('page') === 'modules') {
                    $row[0] = $row[__('Module name')] = '<span '.$script.'><span class="tiny module-status">'.$image_status.'</span>'.'<span class="data module_name">'.ui_print_truncate_text($module['module_name'], 30, false).'</span></span>';
                } else {
                    $row[0] = $row[__('Module name')] = '<span class="tiny module-status">'.$image_status.'</span>'.'<span '.$script.' class="data module_name">'.ui_print_truncate_text($module['module_name'], 30, false).'</span>';
                }

                if ($this->columns['agent']) {
                    $row[1] = $row[__('Agent name')] = '<span class="data"><span class="show_collapside bolder invisible">'.__('Agent').' </span>'.ui_print_truncate_text($module['agent_alias'], 50, false).'</span>';
                }

                if ($module['utimestamp'] == 0 && (($module['module_type'] < 21
                    || $module['module_type'] > 23) && $module['module_type'] != 100)
                ) {
                    $row[5] = $row[__('Status')] = ui_print_status_image(
                        STATUS_MODULE_NO_DATA,
                        __('NOT INIT'),
                        true
                    );
                } else if ($module['estado'] == 0) {
                    $row[5] = $row[__('Status')] = ui_print_status_image(
                        STATUS_MODULE_OK,
                        __('NORMAL').': '.$module['datos'],
                        true
                    );
                } else if ($module['estado'] == 1) {
                    $row[5] = $row[__('Status')] = ui_print_status_image(
                        STATUS_MODULE_CRITICAL,
                        __('CRITICAL').': '.$module['datos'],
                        true
                    );
                } else if ($module['estado'] == 2) {
                    $row[5] = $row[__('Status')] = ui_print_status_image(
                        STATUS_MODULE_WARNING,
                        __('WARNING').': '.$module['datos'],
                        true
                    );
                } else {
                    $last_status = modules_get_agentmodule_last_status(
                        $module['id_agente_modulo']
                    );
                    switch ($last_status) {
                        case 0:
                            $row[5] = $row[__('Status')] = ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('NORMAL').': '.$module['datos'],
                                true
                            );
                        break;

                        case 1:
                            $row[5] = $row[__('Status')] = ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('CRITICAL').': '.$module['datos'],
                                true
                            );
                        break;

                        case 2:
                            $row[5] = $row[__('Status')] = ui_print_status_image(
                                STATUS_MODULE_UNKNOWN,
                                __('UNKNOWN').' - '.__('Last status').' '.__('WARNING').': '.$module['datos'],
                                true
                            );
                        break;
                    }
                }

                $row[__('Interval')] = ($module['module_interval'] == 0) ? human_time_description_raw($module['agent_interval'], false, 'tiny') : human_time_description_raw($module['module_interval'], false, 'tiny');

                $row[4] = $row[__('Interval')] = '<span class="data"><span class="show_collapside bolder invisible">'.__('Interval.').' </span>'.$row[__('Interval')].'</span>';

                $row[6] = $row[__('Timestamp')] = '<span class="data"><span class="show_collapside bolder invisible">'.__('Last update.').' </span>'.human_time_comparation($module['utimestamp'], 'tiny').'</span>';
                if (is_numeric($module['datos'])) {
                    $output = format_numeric($module['datos']);

                    // Show units ONLY in numeric data types
                    if (isset($module['unit'])) {
                        $output .= '&nbsp;'.'<i>'.io_safe_output($module['unit']).'</i>';
                    }
                } else {
                    $is_web_content_string = (bool) db_get_value_filter(
                        'id_agente_modulo',
                        'tagente_modulo',
                        [
                            'id_agente_modulo' => $module['id_agente_modulo'],
                            'id_tipo_modulo'   => $id_type_web_content_string,
                        ]
                    );

                    // Fixed the goliat sends the strings from web
                    // without HTML entities
                    if ($is_web_content_string) {
                        $module['datos'] = io_safe_input($module['datos']);
                    }

                    // Fixed the data from Selenium Plugin
                    if ($module['datos'] != strip_tags($module['datos'])) {
                        $module['datos'] = io_safe_input($module['datos']);
                    }

                    if ($is_web_content_string) {
                        $module_value = $module['datos'];
                    } else {
                        $module_value = io_safe_output($module['datos']);
                    }

                    $sub_string = substr(io_safe_output($module['datos']), 0, 12);
                    if ($module_value == $sub_string) {
                        $output = $module_value;
                    } else {
                        $output = $sub_string;
                    }
                }

                $is_snapshot = is_snapshot_data($module['datos']);
                $is_large_image = is_text_to_black_string($module['datos']);
                if (($config['command_snapshot']) && ($is_snapshot || $is_large_image)) {
                    $link = ui_get_snapshot_link(
                        [
                            'id_module'   => $module['id_agente_modulo'],
                            'module_name' => $module['module_name'],
                        ]
                    );

                    // Row 7.
                    $row[7] = $row[__('Data')] = ui_get_snapshot_image($link, $is_snapshot).'&nbsp;&nbsp;';
                } else {
                    if ($system->getRequest('page') === 'modules') {
                        if ($system->getConfig('metaconsole')) {
                            $row[7] = $row[__('Data')] = '<span class="nowrap">';
                            $row[7] = $row[__('Data')] .= '<span class="show_collapside invisible">';
                            $row[7] = $row[__('Data')] .= $row[__('Status')].'&nbsp;&nbsp;</span>';
                            $row[7] = $row[__('Data')] .= '<span data-ajax="false" class="ui-link" ';
                            $row[7] = $row[__('Data')] .= 'href="#">';
                            $row[7] = $row[__('Data')] .= $output.'</span></span>';
                            // Row 7.
                            $row[7] = $row[__('Data')];
                        } else {
                            // Row 7.
                            $row[7] = $row[__('Data')] = '<span class="nowrap">';
                            $row[7] = $row[__('Data')] .= '<span class="show_collapside invisible">';
                            $row[7] = $row[__('Data')] .= $row[__('Status')].'&nbsp;&nbsp;</span>';
                            $row[7] = $row[__('Data')] .= '<span data-ajax="false" class="ui-link" ';
                            $row[7] = $row[__('Data')] .= 'href="#">';
                            $row[7] = $row[__('Data')] .= $output.'</span></span>';
                        }
                    } else {
                        if ($system->getConfig('metaconsole')) {
                            $row[__('Data')] = '<span class="nowrap">';
                            $row[__('Data')] .= '<span class="show_collapside invisible">';
                            $row[__('Data')] .= $row[__('Status')].'&nbsp;&nbsp;</span>';
                            $row[__('Data')] .= '<a data-ajax="false" class="ui-link" ';
                            $row[__('Data')] .= 'href="index.php?page=module_graph&id='.$module['id_agente_modulo'];
                            $row[__('Data')] .= '&server_id='.$module['server_id'];
                            $row[__('Data')] .= '&id_agent='.$this->id_agent.'">';
                            $row[__('Data')] .= $output.'</a></span>';
                            // Row 7.
                            $row[__('Data')];
                        } else {
                            // Row 7.
                            $row[__('Data')] = '<span class="nowrap">';
                            $row[__('Data')] .= '<span class="show_collapside invisible">';
                            $row[__('Data')] .= $row[__('Status')].'&nbsp;&nbsp;</span>';
                            $row[__('Data')] .= '<a data-ajax="false" class="ui-link" ';
                            $row[__('Data')] .= 'href="index.php?page=module_graph&id=';
                            $row[__('Data')] .= $module['id_agente_modulo'];
                            $row[__('Data')] .= '&id_agent='.$this->id_agent.'">';
                            $row[__('Data')] .= $output.'</a></span>';
                        }
                    }
                }

                if (!$ajax) {
                    unset($row[0]);
                    if ($this->columns['agent']) {
                        unset($row[1]);
                    }

                    unset($row[2]);
                    unset($row[4]);
                    unset($row[5]);
                    unset($row[6]);
                    unset($row[7]);
                }

                $modules[$module['id_agente_modulo']] = $row;
            }
        }

        return [
            'modules' => $modules,
            'total'   => $total,
        ];
    }


    public function listModulesHtml($page=0, $return=false)
    {
        $system = System::getInstance();
        $ui = Ui::getInstance();

        $listModules = $this->getListModules($page);
        if ($listModules['total'] == 0) {
            $html = '<p class="no-data">'.__('No modules').'</p>';
            if (!$return) {
                $ui->contentAddHtml($html);
            } else {
                return $html;
            }
        } else {
            if (!$return) {
                $table = new Table();
                $table->id = 'list_Modules';
                $table->importFromHash($listModules['modules']);

                $ui->contentAddHtml('<div class="hr-full"></div>');
                $ui->contentAddHtml('<div class="white-card p-lr-0px">');
                $ui->contentAddHtml($table->getHTML());

                if (!$this->all_modules) {
                    if ($system->getPageSize() < $listModules['total']) {
                        $ui->contentAddHtml(
                            '<br><div id="loading_rows">'.html_print_image('images/spinner.gif', true, false, false, false, false, true).' '.__('Loading...').'</div>'
                        );

                        $this->addJavascriptAddBottom();
                    }
                }

                $ui->contentAddHtml('</div>');
            } else {
                $table = new Table();
                $table->id = 'list_agent_Modules';

                $table->importFromHash($listModules['modules']);

                $html = $table->getHTML();

                return $html;
            }
        }

        $ui->contentAddHtml(
            '<a id="module-dialog-button" href="#module-dialog" data-rel="popup" data-position-to="window" 
            data-transition="pop" class="ui-btn ui-corner-all ui-btn-inline ui-icon-delete ui-btn-icon-left ui-btn-b">
            </a>
            
            <div data-role="popup" id="module-dialog" data-overlay-theme="b" data-dismissible="false">
                <div data-role="header" data-theme="a" class="flex align-items-center space-between">
                    <h1 style="margin-left: 10px;" class="font-10pt"> '.__('Choose option').'</h1>
                    <a href="#" id="close-dialog-btn" data-role="button" class="ui-corner-all close-button-dialog" data-rel="back"></a>
                </div>
                
                <div role="main" class="ui-content">                    
                    <a data-role="button" id="graph-option" href="#" class="ui-btn ui-corner-all ui-btn-inline ui-btn-b">
                        '.__('Graph').'
                    </a>

                    <a data-role="button" id="historical-option" href="#" class="ui-btn ui-corner-all ui-btn-inline ui-btn-b">
                        '.__('Historical data').'
                    </a>
                </div>
            </div>'
        );

        $ui->contentAddLinkListener('list_Modules');
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
							postvars[\"parameter1\"] = \"modules\";
							postvars[\"parameter2\"] = \"get_modules\";
							postvars[\"group\"] = $(\"select[name='group']\").val();
							postvars[\"status\"] = $(\"select[name='status']\").val();
							postvars[\"type\"] = $(\"select[name='module_group']\").val();
							postvars[\"tag\"] = $(\"select[name='tag']\").val();
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
										$.each(data.modules, function(key, module) {
											$(\"table#list_Modules tbody\").append(\"<tr>\" +
													\"<td class='cell_0'><b class='ui-table-cell-label'>".__('Module name')."</b>\" + module[0] + \"</td>\" +
													\"<td class='cell_1'><b class='ui-table-cell-label'>".__('Agent name')."</b>\" + module[1] + \"</td>\" +
													\"<td class='cell_2'><b class='ui-table-cell-label'>".__('Status')."</b>\" + module[5] + \"</td>\" +
													\"<td class='cell_3'><b class='ui-table-cell-label'>".__('Interval')."</b>\" + module[4] + \"</td>\" +
													\"<td class='cell_4'><b class='ui-table-cell-label'>".__('Timestamp')."</b>\" + module[6] + \"</td>\" +
													\"<td class='cell_5'><b class='ui-table-cell-label'>".__('Data')."</b>\" + module[7] + \"</td>\" +
												\"</tr>\");
											});
										
										load_more_rows = 1;
										refresh_link_listener_list_Modules()
									}
									
									
								},
								\"json\");
						}
					}
				}

                function openDialog(moduleId, agentId, serverId) {
                    var graph = '';
                    var historical = '';
                    if (serverId === 'node') {
                        graph = 'index.php?page=module_graph&id='+moduleId+'&id_agent='+agentId;
                        historical = 'index.php?page=module_data&module_id='+moduleId;
                    } else {
                        graph = 'index.php?page=module_graph&id='+moduleId+'&id_agent='+agentId+'&server_id='+serverId;
                        historical = 'index.php?page=module_data&module_id='+moduleId;
                    }
                    
                    $('#graph-option').attr('href', graph);
                    $('#historical-option').attr('href', historical);

                    $('#module-dialog-button').click();
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
