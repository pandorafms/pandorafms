<?php
/**
 * Class to handle visual console interface (modals and any stuff).
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Visual Console - View
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
namespace Models\VisualConsole;
use Models\VisualConsole\Container as VisualConsole;

global $config;
require_once $config['homedir'].'/include/class/HTML.class.php';
enterprise_include_once('include/functions_metaconsole.php');

/**
 * Global HTML generic class.
 */
class View extends \HTML
{


    /**
     * Tabs.
     *
     * @return string
     */
    public function loadTabs()
    {
        $type = (int) \get_parameter('type', 0);
        $itemId = (int) \get_parameter('itemId', 0);
        $vCId = (int) \get_parameter('vCId', 0);

        $url = ui_get_full_url(false, false, false, false);
        $url .= 'ajax.php?page=include/rest-api/index';
        $url .= '&loadtabs=2';
        $url .= '&type='.$type;
        $url .= '&itemId='.$itemId;
        $url .= '&vCId='.$vCId;

        $tabs = [
            [
                'name' => __('Label settings'),
                'id'   => 'tab-label',
                'href' => $url.'&tabSelected=label',
                'img'  => 'zoom.png',
            ],[
                'name' => __('General settings'),
                'id'   => 'tab-general',
                'href' => $url.'&tabSelected=general',
                'img'  => 'pencil.png',
            ],[
                'name' => __('Specific settings'),
                'id'   => 'tab-specific',
                'href' => $url.'&tabSelected=specific',
                'img'  => 'event_responses_col.png',
            ],
        ];

        if ($type === LABEL) {
            $tabs = [
                [
                    'name' => __('Label settings'),
                    'id'   => 'tab-label',
                    'href' => $url.'&tabSelected=label',
                    'img'  => 'zoom.png',
                ],[
                    'name' => __('General settings'),
                    'id'   => 'tab-general',
                    'href' => $url.'&tabSelected=general',
                    'img'  => 'pencil.png',
                ],
            ];
        }

        $result = html_print_tabs($tabs);

        // TODO:Change other place.
        $js = '<script>
	            $(function() {
                    $tabs = $( "#html-tabs" ).tabs({
                        beforeLoad: function (event, ui) {
                            if (ui.tab.data("loaded")) {
                                event.preventDefault();
                                return;
                            }
                            ui.ajaxSettings.cache = false;
                            ui.jqXHR.done(function() {
                                ui.tab.data( "loaded", true );
                            });
                            ui.jqXHR.fail(function () {
                                ui.panel.html(
                                    "Couldn\'t load Data. Plz Reload Page or Try Again Later."
                                );
                            });
                        },
                        active: 2
                    });';
        $js .= '});';
        $js .= '</script>';

        return $result.$js;
    }


    /**
     * Generates a form for you <3
     *
     * @return string HTML code for Form.
     *
     * @throws \Exception On error.
     */
    public function loadForm()
    {
        // Load desired form based on item type.
        $values = [];
        $type = get_parameter('type', null);
        $tabSelected = get_parameter('tabSelected', 'label');
        $itemId = (int) get_parameter('itemId', 0);
        $vCId = (int) \get_parameter('vCId', 0);

        $itemClass = VisualConsole::getItemClass($type);

        if (isset($itemClass) === false) {
            throw new \Exception(__('Item type not valid ['.$type.']'));
        }

        if (\method_exists($itemClass, 'getFormInputs') === false) {
            throw new \Exception(
                __('Item type has no getFormInputs method ['.$type.']')
            );
        }

        $form = [
            'action' => '#',
            'method' => 'POST',
            'id'     => 'itemForm-'.$tabSelected,
            'class'  => 'discovery modal',
        ];

        if ($itemId !== 0) {
            $item = VisualConsole::getItemFromDB($itemId);
            $values = $item->toArray();
        } else {
            $values['type'] = $type;
            $values['isLinkEnabled'] = true;
            $values['isOnTop'] = true;
        }

        $values['tabSelected'] = $tabSelected;
        $values['vCId'] = $vCId;

        // Retrieve inputs.
        $inputs = $itemClass::getFormInputs($values);

        // Generate Form.
        $form = $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );

        return $form.$jsforms;

    }


    /**
     * Process a form.
     *
     * @return string JSON response.
     */
    public function processForm()
    {
        hd($_POST, true);

        global $config;
        // Inserted data in new item.
        $vCId = \get_parameter('vCId', 0);
        $type = get_parameter('type', null);
        $itemId = (int) get_parameter('itemId', 0);

        // Type.
        $data['type'] = $type;

        // Page Label for each item.
        $data['label'] = \get_parameter('label');

        // Page general for each item.
        $tabGeneral = (bool) \get_parameter('tabGeneral', false);
        $data['width'] = \get_parameter('width');
        $data['height'] = \get_parameter('height');
        $data['x'] = \get_parameter('x');
        $data['y'] = \get_parameter('y');

        if ($tabGeneral === true) {
            $data['isLinkEnabled'] = \get_parameter_switch('isLinkEnabled');
            $data['isOnTop'] = \get_parameter_switch('isOnTop');
        } else {
            if ($itemId === 0) {
                $data['isLinkEnabled'] = true;
                $data['isOnTop'] = true;
            }
        }

        $data['parentId'] = \get_parameter('parentId');
        $data['aclGroupId'] = \get_parameter('aclGroupId');
        $data['cacheExpiration_select'] = \get_parameter(
            'cacheExpiration_select'
        );
        $data['cacheExpiration_text'] = \get_parameter('cacheExpiration_text');
        $data['cacheExpiration'] = \get_parameter('cacheExpiration');
        $data['cacheExpiration_units'] = \get_parameter(
            'cacheExpiration_units'
        );

        // Linked other VC.
        $data['linkedLayoutId'] = \get_parameter(
            'linkedLayoutId',
            0
        );
        $data['linkedLayoutNodeId'] = \get_parameter(
            'linkedLayoutNodeId',
            0
        );
        $data['linkedLayoutStatusType'] = \get_parameter(
            'linkedLayoutStatusType',
            'default'
        );
        $data['linkedLayoutStatusTypeWeight'] = \get_parameter(
            'linkedLayoutStatusTypeWeight'
        );
        $data['linkedLayoutStatusTypeCriticalThreshold'] = \get_parameter(
            'linkedLayoutStatusTypeCriticalThreshold'
        );
        $data['linkedLayoutStatusTypeWarningThreshold'] = \get_parameter(
            'linkedLayoutStatusTypeWarningThreshold'
        );

        // Page specific data for each item.
        switch ($type) {
            case STATIC_GRAPH:
                $data['imageSrc'] = \get_parameter('imageSrc');
                $data['agentId'] = \get_parameter('agentId');
                $data['metaconsoleId'] = \get_parameter('metaconsoleId');
                $data['showLastValueTooltip'] = \get_parameter(
                    'showLastValueTooltip'
                );
            break;

            case MODULE_GRAPH:
                $data['backgroundType'] = \get_parameter('backgroundType');
                $data['agentId'] = \get_parameter('agentId');
                $data['metaconsoleId'] = \get_parameter('metaconsoleId');
                $data['moduleId'] = \get_parameter('moduleId');
                $data['customGraphId'] = \get_parameter('customGraphId');
                $data['graphType'] = \get_parameter('graphType');
            break;

            case SIMPLE_VALUE:
            case SIMPLE_VALUE_MAX:
            case SIMPLE_VALUE_MIN:
            case SIMPLE_VALUE_AVG:
                $data['agentId'] = \get_parameter('agentId');
                $data['metaconsoleId'] = \get_parameter('metaconsoleId');
                $data['moduleId'] = \get_parameter('moduleId');
                $data['processValue'] = \get_parameter('processValue');
            break;

            case PERCENTILE_BAR:
            case PERCENTILE_BUBBLE:
            case CIRCULAR_PROGRESS_BAR:
            case CIRCULAR_INTERIOR_PROGRESS_BAR:
                $data['percentileType'] = \get_parameter('percentileType');
                $data['width'] = \get_parameter('width');
                $data['minValue'] = \get_parameter('minValue');
                $data['maxValue'] = \get_parameter('maxValue');
                $data['valueType'] = \get_parameter('valueType');
                $data['color'] = \get_parameter('color');
                $data['labelColor'] = \get_parameter('labelColor');
                $data['agentId'] = \get_parameter('agentId');
                $data['metaconsoleId'] = \get_parameter('metaconsoleId');
                $data['moduleId'] = \get_parameter('moduleId');
            break;

            case LABEL:
                // Nothing. no specific items.
            break;

            case ICON:
                $data['imageSrc'] = \get_parameter('imageSrc');
            break;

            case SERVICE:
                // TODO:Enterprise item. It may not exist.
            break;

            case GROUP_ITEM:
                $data['imageSrc'] = \get_parameter('imageSrc');
                $data['showStatistics'] = \get_parameter_switch(
                    'showStatistics',
                    0
                );
            break;

            case BOX_ITEM:
                $data['borderColor'] = \get_parameter('borderColor');
                $data['borderWidth'] = \get_parameter('borderWidth');
                $data['fillColor'] = \get_parameter('fillColor');
            break;

            case LINE_ITEM:
                // Nothing. no specific items.
            break;

            case AUTO_SLA_GRAPH:
                $data['agentId'] = \get_parameter('agentId');
                $data['metaconsoleId'] = \get_parameter('metaconsoleId');
                $data['agentAlias'] = \get_parameter('agentAlias');
                $data['moduleId'] = \get_parameter('moduleId');
                $data['maxTime'] = \get_parameter('maxTime');
            break;

            case DONUT_GRAPH:
                $data['agentId'] = \get_parameter('agentId');
                $data['metaconsoleId'] = \get_parameter('metaconsoleId');
                $data['moduleId'] = \get_parameter('moduleId');
                $data['legendBackgroundColor'] = \get_parameter(
                    'legendBackgroundColor'
                );
            break;

            case BARS_GRAPH:
                $data['backgroundColor'] = \get_parameter('backgroundColor');
                $data['typeGraph'] = \get_parameter('typeGraph');
                $data['gridColor'] = \get_parameter('gridColor');
                $data['agentId'] = \get_parameter('agentId');
                $data['metaconsoleId'] = \get_parameter('metaconsoleId');
                $data['moduleId'] = \get_parameter('moduleId');
            break;

            case CLOCK:
                $data['clockType'] = \get_parameter('clockType');
                $data['clockFormat'] = \get_parameter('clockFormat');
                $data['width'] = \get_parameter('width');
                $data['clockTimezone'] = \get_parameter('clockTimezone');
                $data['color'] = \get_parameter('color');
            break;

            case COLOR_CLOUD:
                // TODO:XXX.
            break;

            default:
                // Not posible.
            break;
        }

        if (isset($itemId) === false || $itemId === 0) {
            // TODO: ACL.
            // CreateVC.
            $class = VisualConsole::getItemClass((int) $data['type']);
            try {
                // Save the new item.
                $data['id_layout'] = $vCId;
                $itemId = $class::save($data);
            } catch (\Throwable $th) {
                // Bad params.
                http_response_code(400);
                return false;
            }

            // Extract data new item inserted.
            try {
                $item = VisualConsole::getItemFromDB($itemId);
                $result = $item->toArray();
            } catch (Throwable $e) {
                // Bad params.
                http_response_code(400);
                return false;
            }
        } else {
            // UpdateVC.
            try {
                $item = VisualConsole::getItemFromDB($itemId);
            } catch (Throwable $e) {
                // Bad params.
                http_response_code(400);
                return false;
            }

            $itemData = $item->toArray();
            $itemType = $itemData['type'];
            $itemAclGroupId = $itemData['aclGroupId'];

            // ACL.
            $aclRead = check_acl($config['id_user'], $itemAclGroupId, 'VR');
            $aclWrite = check_acl($config['id_user'], $itemAclGroupId, 'VW');
            $aclManage = check_acl($config['id_user'], $itemAclGroupId, 'VM');

            if (!$aclRead && !$aclWrite && !$aclManage) {
                db_pandora_audit(
                    'ACL Violation',
                    'Trying to access visual console without group access'
                );
                http_response_code(403);
                return false;
            }

            // Check also the group Id for the group item.
            if ($itemType === GROUP_ITEM) {
                $itemGroupId = $itemData['groupId'];
                // ACL.
                $aclRead = check_acl($config['id_user'], $itemGroupId, 'VR');
                $aclWrite = check_acl($config['id_user'], $itemGroupId, 'VW');
                $aclManage = check_acl($config['id_user'], $itemGroupId, 'VM');

                if (!$aclRead && !$aclWrite && !$aclManage) {
                    db_pandora_audit(
                        'ACL Violation',
                        'Trying to access visual console without group access'
                    );
                    http_response_code(403);
                    return false;
                }
            }

            if (is_array($data) === true && empty($data) === false) {
                try {
                    // Save the new item.
                    $data['id_layout'] = $vCId;
                    $data['id'] = $itemId;
                    $item->save($data);
                    $result = $item->toArray();
                } catch (\Throwable $th) {
                    // There is no item in the database.
                    echo false;
                    return false;
                }
            }
        }

        return json_encode($result);
    }


}
