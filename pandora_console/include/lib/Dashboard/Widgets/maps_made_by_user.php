<?php
/**
 * Widget Maps by users Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Maps by users
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

namespace PandoraFMS\Dashboard;
// Load Visual Console.
use Models\VisualConsole\Container as VisualConsole;
use PandoraFMS\Enterprise\Metaconsole\Node;
use PandoraFMS\User;
/**
 * Maps by users Widgets.
 */
class MapsMadeByUser extends Widget
{

    /**
     * Name widget.
     *
     * @var string
     */
    protected $name;

    /**
     * Title widget.
     *
     * @var string
     */
    protected $title;

    /**
     * Page widget;
     *
     * @var string
     */
    protected $page;

    /**
     * Class name widget.
     *
     * @var [type]
     */
    protected $className;

    /**
     * Values options for each widget.
     *
     * @var [type]
     */
    protected $values;

    /**
     * Configuration required.
     *
     * @var boolean
     */
    protected $configurationRequired;

    /**
     * Error load widget.
     *
     * @var boolean
     */
    protected $loadError;

    /**
     * Width.
     *
     * @var integer
     */
    protected $width;

    /**
     * Heigth.
     *
     * @var integer
     */
    protected $height;

    /**
     * Grid Width.
     *
     * @var integer
     */
    protected $gridWidth;

    /**
     * Cell Id.
     *
     * @var integer
     */
    protected $cellId;


    /**
     * Construct.
     *
     * @param integer      $cellId      Cell ID.
     * @param integer      $dashboardId Dashboard ID.
     * @param integer      $widgetId    Widget ID.
     * @param integer|null $width       New width.
     * @param integer|null $height      New height.
     * @param integer|null $gridWidth   Grid width.
     */
    public function __construct(
        int $cellId,
        int $dashboardId=0,
        int $widgetId=0,
        ?int $width=0,
        ?int $height=0,
        ?int $gridWidth=0
    ) {
        global $config;

        // Include.
        include_once $config['homedir'].'/include/graphs/functions_d3.php';
        include_once $config['homedir'].'/include/functions_visual_map.php';

        // WARNING: Do not edit. This chunk must be in the constructor.
        parent::__construct(
            $cellId,
            $dashboardId,
            $widgetId
        );

        // Width.
        $this->width = $width;

        // Height.
        $this->height = $height;

        // Grid Width.
        $this->gridWidth = $gridWidth;

        // Cell Id.
        $this->cellId = $cellId;

        // Options.
        $this->values = $this->decoders($this->getOptionsWidget());

        // Positions.
        $this->position = $this->getPositionWidget();

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Visual Console');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'maps_made_by_user';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['vcId']) === true) {
            $this->configurationRequired = true;
        } else {
            try {
                if (is_metaconsole() === true
                    && $this->values['node'] > 0
                ) {
                    $node = new Node($this->values['node']);
                    $node->connect();
                }

                $check_exist = db_get_value(
                    'id',
                    'tlayout',
                    'id',
                    $this->values['vcId']
                );
            } catch (\Exception $e) {
                // Unexistent agent.
                if (is_metaconsole() === true
                    && $this->values['node'] > 0
                ) {
                    $node->disconnect();
                }

                $check_exist = false;
            } finally {
                if (is_metaconsole() === true
                    && $this->values['node'] > 0
                ) {
                    $node->disconnect();
                }
            }

            if ($check_exist === false) {
                $this->loadError = true;
            }
        }

        $this->overflow_scrollbars = false;
    }


    /**
     * Decoders hack for retrocompability.
     *
     * @param array $decoder Values.
     *
     * @return array Returns the values ​​with the correct key.
     */
    public function decoders(array $decoder): array
    {
        $values = [];
        // Retrieve global - common inputs.
        $values = parent::decoders($decoder);

        if (isset($decoder['id_layout']) === true) {
            $values['vcId'] = $decoder['id_layout'];
        }

        if (isset($decoder['vcId']) === true) {
            $values['vcId'] = $decoder['vcId'];
        }

        return $values;
    }


    /**
     * Dumps consoles list in json to fullfill select for consoles.
     *
     * @return void
     */
    public function getVisualConsolesList(): void
    {
        $node_id = \get_parameter('nodeId', $this->nodeId);
        if (\is_metaconsole() === true && $node_id > 0) {
            if (\metaconsole_connect(null, $node_id) !== NOERR) {
                echo json_encode(
                    ['error' => __('Failed to connect to node %d', $node_id) ]
                );
            }
        }

        echo json_encode(
            $this->getVisualConsoles(),
            1
        );

        if (\is_metaconsole() === true && $node_id > 0) {
            \metaconsole_restore_db();
        }
    }


    /**
     * Retrieve visual consoles.
     *
     * @return array
     */
    private function getVisualConsoles()
    {
        global $config;

        $return_all_group = false;

        if (users_can_manage_group_all('RM')) {
            $return_all_group = true;
        }

        $fields = \visual_map_get_user_layouts(
            $config['id_user'],
            true,
            ['can_manage_group_all' => $return_all_group],
            $return_all_group
        );

        foreach ($fields as $k => $v) {
            $fields[$k] = \io_safe_output($v);
        }

        // If currently selected graph is not included in fields array
        // (it belongs to a group over which user has no permissions), then add
        // it to fields array.
        // This is aimed to avoid overriding this value when a user with
        // narrower permissions edits widget configuration.
        if ($this->values['vcId'] !== null
            && array_key_exists($this->values['vcId'], $fields) === false
        ) {
            $selected_vc = db_get_value(
                'name',
                'tlayout',
                'id',
                $this->values['vcId']
            );

            $fields[$this->values['vcId']] = $selected_vc;
        }

        return $fields;
    }


    /**
     * Generates inputs for form (specific).
     *
     * @return array Of inputs.
     *
     * @throws Exception On error.
     */
    public function getFormInputs(): array
    {
        global $config;

        $values = $this->values;

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        $node_id = $this->nodeId;
        if (\is_metaconsole() === true && $node_id > 0) {
            if (\metaconsole_connect(null, $node_id) !== NOERR) {
                echo json_encode(
                    ['error' => __('Failed to connect to node %d', $node_id) ]
                );
            }
        }

        $fields = $this->getVisualConsoles();

        if (\is_metaconsole() === true && $node_id > 0) {
            \metaconsole_restore_db();
        }

        // Visual console.
        $inputs[] = [
            'label'     => __('Visual console'),
            'arguments' => [
                'id'            => 'vcId',
                'type'          => 'select',
                'fields'        => $fields,
                'name'          => 'vcId',
                'selected'      => $values['vcId'],
                'return'        => true,
                'nothing'       => __('None'),
                'nothing_value' => 0,
            ],
        ];

        return $inputs;
    }


    /**
     * Get Post for widget.
     *
     * @return array
     */
    public function getPost():array
    {
        // Retrieve global - common inputs.
        $values = parent::getPost();

        $values['vcId'] = \get_parameter('vcId', 0);

        return $values;
    }


    /**
     * Draw widget.
     *
     * @return string;
     */
    public function load()
    {
        global $config;

        $size = parent::getSize();

        $visualConsole = null;
        try {
            $visualConsole = VisualConsole::fromDB(
                ['id' => $this->values['vcId']]
            );
        } catch (\Throwable $e) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access visual console without Id'
            );
            include 'general/noaccess.php';
            exit;
        }

        $size['width'] = ($size['width'] + 30);

        $ratio = $visualConsole->adjustToViewport($size, 'dashboard');
        $visualConsoleData = $visualConsole->toArray();

        $uniq = uniqid();

        $output = '<div class="container-center">';
        // Style.
        $style = 'width:'.$visualConsoleData['width'].'px;';
        // Class.
        $class = 'visual-console-container-dashboard c-'.$uniq;
        // Id.
        $id = 'visual-console-container-'.$this->cellId;
        $output .= '<div style="'.$style.'" class="'.$class.'" id="'.$id.'">';
        $output .= '</div>';
        $output .= '</div>';

        // Check groups can access user.
        $aclUserGroups = [];
        if (users_can_manage_group_all('AR') === true) {
            $aclUserGroups = array_keys(
                users_get_groups(false, 'AR')
            );
        }

        $ignored_params['refr'] = '';
        \ui_require_javascript_file(
            'tiny_mce',
            'include/javascript/tiny_mce/'
        );
        \ui_require_javascript_file(
            'pandora_visual_console',
            'include/javascript/',
            true
        );
        \include_javascript_d3();
        \visual_map_load_client_resources();

        // Load Visual Console Items.
        $visualConsoleItems = VisualConsole::getItemsFromDB(
            $this->values['vcId'],
            $aclUserGroups,
            $ratio
        );

        $output .= '<style id="css_cv_'.$uniq.'" type="text/css">';
        $output .= css_label_styles_visual_console($uniq, $ratio);
        $output .= '</style>';

        $visualConsoleItems = array_reduce(
            $visualConsoleItems,
            function ($carry, $item) {
                $carry[] = $item->toArray();
                return $carry;
            },
            []
        );

        $settings = \json_encode(
            [
                'props'                      => $visualConsoleData,
                'items'                      => $visualConsoleItems,
                'baseUrl'                    => ui_get_full_url(
                    '/',
                    false,
                    false,
                    false
                ),
                'ratio'                      => $ratio,
                'size'                       => $size,
                'cellId'                     => $this->cellId,
                'hash'                       => User::generatePublicHash(),
                'id_user'                    => $config['id_user'],
                'page'                       => 'include/ajax/visual_console.ajax',
                'uniq'                       => $uniq,
                'mobile_view_orientation_vc' => false,
            ]
        );

        $output .= '<script type="text/javascript">';
        $output .= '$(document).ready(function () {';
        $output .= 'dashboardLoadVC('.$settings.')';
        $output .= '});';
        $output .= '</script>';

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Visual Console');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'maps_made_by_user';
    }


    /**
     * Return aux javascript code for forms.
     *
     * @return string
     */
    public function getFormJS()
    {
        ob_start();
        ?>
            $('#node').on('change', function() { 
                $.ajax({
                    method: "POST",
                    url: '<?php echo \ui_get_full_url('ajax.php'); ?>',
                    data: {
                        page: 'operation/dashboard/dashboard',
                        dashboardId: '<?php echo $this->dashboardId; ?>',
                        widgetId: '<?php echo $this->widgetId; ?>',
                        cellId: '<?php echo $this->cellId; ?>',
                        class: '<?php echo __CLASS__; ?>',
                        method: 'getVisualConsolesList',
                        nodeId: $('#node').val()
                    },
                    dataType: 'JSON',
                    success: function(data) {
                        $('#vcId').empty();
                        Object.entries(data).forEach(e => {
                            key = e[0];
                            value = e[1];
                            $('#vcId').append($('<option>').val(key).text(value))
                        });
                        if (Object.entries(data).length == 0) {
                            $('#vcId').append(
                                $('<option>')
                                    .val(-1)
                                    .text("<?php echo __('None'); ?>")
                            );
                        }
                    }
                })
            });
        <?php
        $js = ob_get_clean();
        return $js;
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => 400,
            'height' => (is_metaconsole() === true) ? 330 : 270,
        ];

        return $size;
    }


}
