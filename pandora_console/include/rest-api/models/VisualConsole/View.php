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

/**
 * Global HTML generic class.
 */
class View extends \HTML
{


    public function loadTabs()
    {
        $url = ui_get_full_url(false, false, false, false);
        $url .= 'ajax.php?page=include/rest-api/index';
        $url .= '&loadtabs=1';
        $url .= '&item='.get_parameter('item', null);

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
                                "Couldn\'t load Data. Plz Reload Page or Try Again Later.");
                            });
                        }

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
        $item = null;
        $item_json = get_parameter('item', null);
        $item = json_decode(io_safe_output($item_json));

        $type = null;
        if (isset($item) === true) {
            $values = $item->itemProps;
            $values->tabSelected = get_parameter('tabSelected', 'label');
            $type = $values->type;
        }

        hd($values->tabSelected, true);

        $itemClass = VisualConsole::getItemClass($type);

        if (!isset($itemClass)) {
            throw new \Exception(__('Item type not valid ['.$type.']'));
        }

        if (\method_exists($itemClass, 'getFormInputs') === false) {
            throw new \Exception(__('Item type has no getFormInputs method ['.$type.']'));
        }

        $form = [
            'action' => '#',
            'method' => 'POST',
            'id'     => 'itemForm-'.$values->tabSelected,
            'class'  => 'discovery modal',
        ];

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

        return $form;

    }


    /**
     * Process a form.
     *
     * @return string JSON response.
     */
    public function processForm()
    {
        global $config;

        hd('++++++++++++++++++++++++++++++++++++', true);
        hd($_POST, true);
        hd('++++++++++++++++++++++++++++++++++++', true);

        $item = json_decode(io_safe_output(\get_parameter('item')), true);

        // Inserted data in new item.
        $vCId = \get_parameter('vCId', 0);

        $data['type'] = $item['itemProps']['type'];

        // Page Label.
        $data['label'] = \get_parameter('label');

        // Page general.
        $data['width'] = \get_parameter('width');
        $data['height'] = \get_parameter('height');
        $data['x'] = \get_parameter('x');
        $data['y'] = \get_parameter('y');
        $data['isLinkEnabled'] = \get_parameter('isLinkEnabled');
        $data['isOnTop'] = \get_parameter('isOnTop');
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

        // Page specific.
        $data['imageSrc'] = \get_parameter('imageSrc');
        $data['agentId'] = \get_parameter('agentId');
        $data['metaconsoleId'] = \get_parameter('metaconsoleId');
        $data['agentAlias'] = \get_parameter('agentAlias');
        $data['showLastValueTooltip'] = \get_parameter('showLastValueTooltip');

        if (isset($item['itemProps']['id']) === false) {
            // CreateVC.
            $class = VisualConsole::getItemClass((int) $data['type']);
            try {
                // Save the new item.
                $data['id_layout'] = $vCId;
                $itemId = $class::save($data);
                hd('he creado:'.$itemId, true);
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
            $itemId = $item['itemProps']['id'];

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
                    hd('he actualizado: '.$itemId, true);
                    $result = $item->toArray();
                } catch (\Throwable $th) {
                    // There is no item in the database.
                    echo false;
                    return false;
                }
            }
        }

        hd($result, true);
        return json_encode($result);
    }


}
