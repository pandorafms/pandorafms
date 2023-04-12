<?php


namespace PandoraFMS\Dashboard;

use PandoraFMS\View;
use PandoraFMS\Dashboard\Cell;
use PandoraFMS\PublicLogin;

/**
 * Dashboard manager.
 */
class Manager implements PublicLogin
{

    /**
     * Ajax controller.
     *
     * @var string
     */
    protected $ajaxController;

    /**
     * User is admin.
     *
     * @var integer
     */
    private $isAdmin;

    /**
     * Groups for user ACL.
     *
     * @var array
     */
    private $groups;

    /**
     * Groups for user ACL string for IN clauses query.
     *
     * @var string
     */
    private $stringGroups;

    /**
     * Acl write report.
     *
     * @var integer
     */
    private $writeDashboards;

    /**
     * Acl manage report.
     *
     * @var integer
     */
    private $manageDashboards;

    /**
     * ID dashboard
     *
     * @var integer
     */
    private $dashboardId;

    /**
     * Dashboard data.
     *
     * @var array
     */
    private $dashboardFields;

    /**
     * Operations delete dashboard.
     *
     * @var integer
     */
    private $deleteDashboard;

    /**
     * Operations copy dashboard.
     *
     * @var integer
     */
    private $copyDashboard;

    /**
     * Operations create dashboard.
     *
     * @var integer
     */
    private $createDashboard;

    /**
     * Operations update dashboard.
     *
     * @var integer
     */
    private $updateDashboard;

    /**
     * Cells for this dashboard.
     *
     * @var array
     */
    private $cells;

    /**
     * Id Cell.
     *
     * @var integer
     */
    private $cellId;

    /**
     * Id Widget.
     *
     * @var integer
     */
    private $widgetId;

    /**
     * Offset.
     *
     * @var integer
     */
    private $offset;

    /**
     * Slides.
     *
     * @var integer
     */
    private $slides;

    /**
     * Cell Mode Slides.
     *
     * @var integer
     */
    private $cellModeSlides;

    /**
     * Slides Ids.
     *
     * @var array
     */
    private $slidesIds;

    /**
     * Refr.
     *
     * @var integer
     */
    private $refr;

    /**
     * Public Link.
     *
     * @var boolean
     */
    private $publicLink;

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = [
        'getCellsLayout',
        'insertCellLayout',
        'drawCell',
        'drawWidget',
        'saveLayout',
        'deleteCell',
        'drawAddWidget',
        'drawConfiguration',
        'drawFormDashboard',
        'updateDashboard',
        'saveWidgetIntoCell',
        'imageIconDashboardAjax',
        'formSlides',
        'callWidgetMethod',
        'getSizeModalConfiguration',
    ];


    /**
     * Constructor
     *
     * @param string $page For ajax controller.
     */
    public function __construct(string $page='operation/dashboard/dashboard')
    {
        global $config;

        // Check ACL.
        $hash = get_parameter('hash', false);
        $this->publicLink = false;
        // Check user access.
        if ($hash === false) {
            check_login();
            if (check_acl($config['id_user'], 0, 'RR') === 0) {
                include 'general/noaccess.php';
                return;
            }
        } else {
            if (self::validatePublicHash($hash) === false) {
                db_pandora_audit(
                    AUDIT_LOG_HACK_ATTEMPT,
                    'Trying to access public dashboard'
                );
                include 'general/noaccess.php';
                exit;
            }

            $this->publicLink = true;
        }

        if (empty(get_parameter('auth_hash', '')) === false) {
            $this->publicLink = true;
        }

        // User is admin.
        $this->isAdmin = (bool) \is_user_admin($config['id_user']);

        // Groups user access.
        $this->groups = array_keys(
            \users_get_groups(
                $config['id_user'],
                'RR',
                true
            )
        );

        // String groups for query.
        $this->stringGroups = \io_safe_output(
            implode(
                ', ',
                array_values($this->groups)
            )
        );

        // Urls.
        $this->url = \ui_get_full_url(
            'index.php?sec=reporting&sec2=operation/dashboard/dashboard'
        );
        $this->ajaxController = $page;

        // ACL Dashboards.
        $this->writeDashboards = \check_acl($config['id_user'], 0, 'RW');
        $this->manageDashboards = \check_acl($config['id_user'], 0, 'RM');

        // Operations Dashboards.
        $this->deleteDashboard = (bool) \get_parameter('deleteDashboard', 0);
        $this->copyDashboard = (bool) \get_parameter('copyDashboard', 0);
        $this->createDashboard = \get_parameter('createDashboard', null);
        $this->updateDashboard = \get_parameter('updateDashboard', null);

        $this->slides = (int) \get_parameter('slides', 0);
        $extradata = \get_parameter('extradata', '');
        if (empty($extradata) === false) {
            $extradata = json_decode(\io_safe_output($extradata), true);

            if (isset($extradata['dashboardId']) === false) {
                $extradata['dashboardId'] = null;
            }

            if (isset($extradata['cellId']) === false) {
                $extradata['cellId'] = null;
            }

            if (isset($extradata['offset']) === false) {
                $extradata['offset'] = null;
            }

            if (isset($extradata['widgetId']) === false) {
                $extradata['widgetId'] = null;
            }

            $this->dashboardId = (int) $extradata['dashboardId'];
            $this->cellId = (int) $extradata['cellId'];
            $this->offset = (int) $extradata['offset'];
            $this->widgetId = (int) $extradata['widgetId'];
        } else {
            $this->cellId = (int) \get_parameter('cellId', []);
            $this->offset = (int) \get_parameter('offset', 0);

            $this->dashboardId = (int) \get_parameter('dashboardId', 0);
            if ($this->slides === 1) {
                $this->slidesIds = (array) \get_parameter('slidesIds');
                $this->cellModeSlides = (int) \get_parameter(
                    'cellModeSlides',
                    0
                );
                if ($this->dashboardId === 0) {
                    $this->dashboardId = (int) $this->slidesIds[0];
                }
            }

            $this->widgetId = (int) \get_parameter('widgetId', 0);
        }

        if ($this->dashboardId !== 0) {
            $this->dashboardFields = $this->get();
            $this->cells = Cell::getCells($this->dashboardId);
        }

        $this->refr = (int) get_parameter('refr', 0);
        $this->refr = (empty($this->refr) === false) ? $this->refr : $config['vc_refr'];
    }


    /**
     * Generates a hash to authenticate in public dashboards.
     *
     * @param string|null $other_secret To authenticate some parts
     * of public dashboards (like visual consoles or wux widgets)
     * another hash is needed. Other secret avoid
     * to reuse the main hash to view other components.
     *
     * @return string Returns a hash with the authenticaction.
     */
    public static function generatePublicHash(?string $other_secret=''):string
    {
        global $config;

        $str = $config['dbpass'];
        $str .= $config['id_user'];
        $str .= $other_secret;
        return hash('sha256', $str);
    }


    /**
     * Validates a hash to authenticate in public dashboards.
     *
     * @param string $hash         Hash to be checked.
     * @param string $other_secret Yo need to provide it to
     * authenticate some parts of widgets.
     *
     * @return boolean Returns true if hash is valid.
     */
    public static function validatePublicHash(
        string $hash,
        string $other_secret=''
    ):bool {
        global $config;

        if (isset($config['id_user']) === true) {
            // Already logged in.
            return true;
        }

        $userFromParams = false;
        // Try to get id_user from parameters if it is missing.
        if (isset($config['id_user']) === false) {
            $userFromParams = true;
            $config['id_user'] = get_parameter('id_user', false);
            // It is impossible to authenticate without an id user.
            if ($config['id_user'] === false) {
                unset($config['id_user']);
                return false;
            }
        } else {
            $config['public_dashboard'] = false;
        }

        // Build a hash to check.
        $hashCheck = self::generatePublicHash($other_secret);
        if ($hashCheck === $hash) {
            // "Log" user in.
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $_SESSION['id_usuario'] = $config['id_user'];
            session_write_close();

            $config['public_dashboard'] = true;
            $config['force_instant_logout'] = true;
            return true;
        }

        // Remove id user from config array if authentication has failed.
        if ($userFromParams === true) {
            unset($config['id_user']);
        }

        return false;
    }


    /**
     * Instance Widget.
     *
     * @param integer|null $width     Width.
     * @param integer|null $height    Height.
     * @param integer|null $gridWidth Width Grid.
     *
     * @return object
     */
    private function instanceWidget(
        ?int $width=0,
        ?int $height=0,
        ?int $gridWidth=0
    ):object {
        global $config;

        if ($this->widgetId === 0) {
            $cellClass = new Cell($this->cellId, $this->dashboardId);
            $dataCell = $cellClass->get();
            $this->widgetId = $dataCell['id_widget'];
        }

        $this->cWidget = new Widget(
            $this->cellId,
            $this->dashboardId,
            $this->widgetId
        );

        $widgetData = $this->cWidget->get();

        // Include widget if necesary.
        $urlWidget = $config['homedir'];
        $urlWidget .= '/include/lib/Dashboard/Widgets/';
        $urlWidget .= $widgetData['page'];
        include_once $urlWidget;

        $class = new \ReflectionClass($this);
        $nameSpace = $class->getNamespaceName();

        // Rename Old bad name class.
        if ($widgetData['class_name'] === 'TopN_event_by_group') {
            $widgetData['class_name'] = 'TopNEventByGroupWidget';
        } else if ($widgetData['class_name'] === 'TopN_event_by_module') {
            $widgetData['class_name'] = 'TopNEventByModuleWidget';
        }

        $className = $nameSpace.'\\'.$widgetData['class_name'];

        // Hack: class_name is the name of the widget class to create.
        $instance = new $className(
            $this->cellId,
            $this->dashboardId,
            $this->widgetId,
            $width,
            $height,
            $gridWidth,
            $this->publicLink
        );

        return $instance;
    }


    /**
     * Retrieve a dashboard definition.
     *
     * @return array dashboard data.
     */
    private function get()
    {
        global $config;

        $sql = sprintf(
            'SELECT *
            FROM tdashboard
            WHERE id = %d',
            $this->dashboardId
        );

        if ($this->isAdmin !== true) {
            $sql = sprintf(
                "SELECT *
                FROM tdashboard
                WHERE id = %d
                AND ((id_group IN (%s) AND id_user = '') OR id_user = '%s')",
                $this->dashboardId,
                $this->stringGroups,
                $config['id_user']
            );
        }

        $data = \db_get_row_sql($sql);

        if ($data === false) {
            return [];
        }

        return $data;
    }


    /**
     * Insert dashboard item.
     *
     * @param array $data Array data Insert.
     *
     * @return integer
     */
    public function set(array $data):int
    {
        global $config;

        // Insert.
        $result = \db_process_sql_insert(
            'tdashboard',
            $data
        );

        if ($result === false) {
            $result = 0;
        }

        return $result;
    }


    /**
     * Update local object attributes and updates the DB.
     *
     * @param array $values Values to be updated.
     *
     * @return array Result of set operation.
     */
    public function put(array $values) : array
    {
        global $config;

        $result = [
            'error'     => false,
            'msg_error' => '',
            'result'    => 0,
        ];

        // Update.
        $res = \db_process_sql_update(
            'tdashboard',
            $values,
            ['id' => $this->dashboardId]
        );

        $result['result'] = $res;
        if ($res === false) {
            $result = [
                'error'     => true,
                'msg_error' => $config['dbconnection']->error,
                'result'    => $res,
            ];
        }

        return $result;
    }


    /**
     * Delete Dashboard.
     *
     * @return boolean
     */
    public function delete()
    {
        global $config;

        $result = 0;
        if ($this->manageDashboards === 1) {
            $result = \db_process_sql_delete(
                'tdashboard',
                ['id' => $this->dashboardId]
            );

            // Delete dashboard from fav menu.
            \db_process_sql_delete(
                'tfavmenu_user',
                [
                    'id_element' => $this->dashboardId,
                    'section'    => 'Dashboard_',
                    'id_user'    => $config['id_user'],
                ]
            );
        }

        // Audit.
        if ($result !== 0) {
            \db_pandora_audit(
                AUDIT_LOG_DASHBOARD_MANAGEMENT,
                'Delete dashboard #'.$this->dashboardId
            );
        } else {
            \db_pandora_audit(
                AUDIT_LOG_DASHBOARD_MANAGEMENT,
                'Fail try to delete dashboard #'.$this->dashboardId
            );
        }

        return $result;
    }


    /**
     * Copy Dashboard and asociate widgets.
     *
     * @return integer
     */
    public function copy():int
    {
        $result = true;

        // Name copy change.
        $name = __('Copy of %s', $this->dashboardFields['name']);
        $i = 1;
        while (true) {
            $exists = db_get_value(
                'name',
                'tdashboard',
                'name',
                $name
            );
            if (empty($exists) === true) {
                break;
            } else {
                $name = $name.' ('.$i.')';
            }

            $i++;
        }

        $values = [
            'name'            => $name,
            'id_user'         => $this->dashboardFields['id_user'],
            'id_group'        => $this->dashboardFields['id_group'],
            'active'          => $this->dashboardFields['active'],
            'cells'           => $this->dashboardFields['cells'],
            'cells_slideshow' => $this->dashboardFields['cells_slideshow'],
        ];

        $id = $this->set($values);

        if (empty($id) === true) {
            $result = 0;
        } else {
            $cells = Cell::getCells($this->dashboardId);
            foreach ($cells as $cell) {
                // Remove Id.
                unset($cell['id']);
                // Change Id dashboard.
                $cell['id_dashboard'] = $id;

                $result = db_process_sql_insert('twidget_dashboard', $cell);

                if (empty($result) === true) {
                    $result = 0;
                    break;
                }
            }

            // Clean database.
            if ($result === 0) {
                db_process_sql_delete(
                    'tdashboard',
                    ['id' => $id]
                );
                db_process_sql_delete(
                    'twidget_dashboard',
                    ['id_dashboard' => $id]
                );
            }
        }

        return $result;
    }


    /**
     * Get all dashboard user can you see.
     *
     * @param integer $offset    Offset query.
     * @param integer $limit     Limit query.
     * @param boolean $favourite Fovorite dashboard.
     * @param boolean $slideshow Slideshow Mode.
     *
     * @return array
     */
    static public function getDashboards(
        int $offset=-1,
        int $limit=-1,
        bool $favourite=false,
        bool $slideshow=false,
        string $id_user=''
    ):array {
        global $config;

        $sql_limit = '';
        if ($offset !== -1 && $limit !== -1) {
            $sql_limit = ' LIMIT '.$offset.','.$limit;
        }

        $sql_where = '';
        if ($favourite === true) {
            $sql_where .= 'AND td.active = 1';
        }

        if ($slideshow === true) {
            $sql_where .= 'AND td.cells_slideshow = 1';
        }

        if (empty($id_user) === true) {
            $id_user = $config['id_user'];
        }

        // Check ACl.
        if (\is_user_admin($id_user) !== true) {
            // Non-admin user can see dashboards of his groups and 'AR' profile.
            $group_list = \users_get_groups(
                $id_user,
                'RR',
                true
            );

            if ($group_list === false) {
                $group_list = [];
            }

            if (empty($group_list) === false) {
                $string_groups = implode(', ', array_keys($group_list));
                $string_groups = \io_safe_output($string_groups);

                // Select user's dashboards.
                $sql_dashboard = sprintf(
                    "SELECT td.id,
                    td.name,
                    td.id_user,
                    td.id_group,
                    td.active,
                    count(twd.id) as cells,
                    td.cells_slideshow
                FROM tdashboard td
                LEFT JOIN twidget_dashboard twd
                    ON td.id = twd.id_dashboard
				WHERE ((td.id_group IN (%s) AND td.id_user = '') OR
					td.id_user = '%s') %s
                GROUP BY td.id
				ORDER BY name%s",
                    $string_groups,
                    $id_user,
                    $sql_where,
                    $sql_limit
                );
            } else {
                $sql_dashboard = sprintf(
                    "SELECT td.id,
                        td.name,
                        td.id_user,
                        td.id_group,
                        td.active,
                        count(twd.id) as cells,
                        td.cells_slideshow
				    FROM tdashboard td
                    LEFT JOIN twidget_dashboard twd
                        ON td.id = twd.id_dashboard
				    WHERE td.id_group = 0 AND td.id_user = '%s' %s
                    GROUP BY td.id
				    ORDER BY name%s",
                    $id_user,
                    $sql_where,
                    $sql_limit
                );
            }
        } else {
            // User admin view all dashboards.
            $sql_dashboard = sprintf(
                'SELECT td.id,
                    td.name,
                    td.id_user,
                    td.id_group,
                    td.active,
                    count(twd.id) as cells,
                    td.cells_slideshow
                FROM tdashboard td
                LEFT JOIN twidget_dashboard twd
                    ON td.id = twd.id_dashboard
                WHERE 1=1 %s
                GROUP BY td.id
                ORDER BY name%s',
                $sql_where,
                $sql_limit
            );
        }

        $dashboards = \db_get_all_rows_sql($sql_dashboard);

        if ($favourite === true && $dashboards !== false && count($dashboards) > 0) {
            $dashboards = array_map(
                function ($dashboard) {
                    $dashboard['name'] = io_safe_input($dashboard['name']);
                    return $dashboard;
                },
                $dashboards
            );
        }

        if ($dashboards === false) {
            $dashboards = [];
        }

        return $dashboards;
    }


    /**
     * Get all dashboard user can you see.
     *
     * @return array Return counts.
     */
    static public function getDashboardsCount()
    {
        global $config;

        if (is_user_admin($config['id_user']) !== false) {
            // Non-admin user can see dashboards of his groups and 'AR' profile.
            $group_list = \users_get_groups(
                $config['id_user'],
                'RR',
                true
            );

            if ($group_list === false) {
                $group_list = [];
            }

            if (empty($group_list) === false) {
                $string_groups = implode(', ', array_keys($group_list));
                $string_groups = io_safe_output($string_groups);

                $sql_dashboard = sprintf(
                    'SELECT COUNT(*)
                    FROM tdashboard
                    WHERE (id_group IN (%s))',
                    $string_groups
                );
            } else {
                $sql_dashboard = 'SELECT COUNT(*) FROM tdashboard WHERE id_group = 0';
            }
        } else {
            $sql_dashboard = 'SELECT COUNT(*) FROM tdashboard';
        }

        $count_dashboards = db_get_all_rows_sql($sql_dashboard);

        if ($count_dashboards === false) {
            $count_dashboards = [];
        }

        return $count_dashboards[0]['COUNT(*)'];
    }


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public function ajaxMethod(string $method):bool
    {
        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Init manager dashboard.
     *
     * @return void
     */
    public function run()
    {
        global $config;

        ui_require_css_file('modal');
        ui_require_css_file('form');

        if ($this->dashboardId === 0
            || $this->deleteDashboard === true
            || $this->copyDashboard === true
        ) {
            $this->showList();
        } else {
            $this->drawLayout();
        }
    }


    /**
     * Draw list dashboards.
     *
     * @return void
     */
    private function showList()
    {
        global $config;

        $limit_sql = $config['block_size'];

        $resultDelete = null;
        if ($this->deleteDashboard === true) {
            $resultDelete = $this->delete();
        }

        $resultCopy = null;
        if ($this->copyDashboard === true) {
            $resultCopy = $this->copy();
        }

        $dashboards = $this->getDashboards($this->offset, $limit_sql);
        $count = $this->getDashboardsCount();

        View::render(
            'dashboard/list',
            [
                'dashboards'       => $dashboards,
                'count'            => $count,
                'offset'           => $this->offset,
                'urlDashboard'     => $this->url,
                'manageDashboards' => $this->manageDashboards,
                'writeDashboards'  => $this->writeDashboards,
                'resultDelete'     => $resultDelete,
                'resultCopy'       => $resultCopy,
                'ajaxController'   => $this->ajaxController,
                'urlAjax'          => \ui_get_full_url('ajax.php'),

            ]
        );
    }


    /**
     * Form Upadte dashboards.
     *
     * @return void
     */
    public function drawFormDashboard()
    {
        View::render(
            'dashboard/formDashboard',
            [
                'dashboardId'    => $this->dashboardId,
                'arrayDashboard' => $this->dashboardFields,
            ]
        );
    }


    /**
     * Update dashboards.
     *
     * @return mixed
     */
    public function updateDashboard()
    {
        global $config;

        if (check_acl($config['id_user'], 0, 'RW') === 0) {
            include 'general/noaccess.php';
            return;
        }

        $name = \get_parameter('name', '');
        $private = \get_parameter_switch('private');
        $id_group = \get_parameter('id_group');
        $slideshow = \get_parameter_switch('slideshow');
        $favourite = \get_parameter_switch('favourite');

        $id_user = (empty($private) === false) ? $config['id_user'] : '';

        $values = [
            'name'            => $name,
            'id_user'         => $id_user,
            'id_group'        => $id_group,
            'cells_slideshow' => $slideshow,
            'active'          => $favourite,
        ];

        if ($this->dashboardId === 0) {
            $this->dashboardId = $this->set($values);
            $res = $this->dashboardId;
            if ($res !== false) {
                // Create Initial Widget.
                $values = [
                    'x'      => '0',
                    'y'      => '0',
                    'width'  => '4',
                    'height' => '4',
                ];
                $cellClass = new Cell($values, $this->dashboardId);
                $dataCell = $cellClass->get();
            }

            $type = '&createDashboard=1';
            $type .= '&cellIdCreate='.$dataCell['id'];
        } else {
            $res = $this->put($values);
        }

        if ($res !== false) {
            ui_update_name_fav_element($this->dashboardId, 'Dashboard_', $name);
        }

        $result = [
            'error'        => ($res === false) ? 1 : 0,
            'error_mesage' => __('Error create or update dashboard'),
            'url'          => $this->url.$type,
            'dashboardId'  => $this->dashboardId,
        ];

        echo json_encode($result);

    }


    /**
     * Draw layout.
     *
     * @return mixed
     */
    public function drawLayout()
    {
        global $config;

        $dashboards = $this->getDashboards();
        $dashboards = array_reduce(
            $dashboards,
            function ($carry, $item) {
                $carry[$item['id']] = $item['name'];
                return $carry;
            },
            []
        );

        // Header.
        if ($this->slides === 0) {
            View::render(
                'dashboard/header',
                [
                    'dashboards'     => $dashboards,
                    'ajaxController' => $this->ajaxController,
                    'dashboardId'    => $this->dashboardId,
                    'refr'           => $this->refr,
                    'url'            => $this->url,
                    'dashboardName'  => $this->dashboardFields['name'],
                    'hash'           => self::generatePublicHash(),
                    'publicLink'     => $this->publicLink,
                    'dashboardGroup' => $this->dashboardFields['id_group'],
                    'dashboardUser'  => $this->dashboardFields['id_user'],
                ]
            );
        } else {
            View::render(
                'dashboard/slides',
                [
                    'dashboard'      => $this->dashboardFields,
                    'ajaxController' => $this->ajaxController,
                    'dashboardId'    => $this->dashboardId,
                    'refr'           => $this->refr,
                    'url'            => $this->url,
                    'dashboardName'  => $this->dashboardFields['name'],
                    'slides'         => $this->slides,
                    'slidesIds'      => $this->slidesIds,
                    'cells'          => $this->cells,
                    'cellModeSlides' => $this->cellModeSlides,
                    'cellId'         => ($this->cellId === 0) ? $this->cells[0]['id'] : $this->cellId,
                    'dashboardGroup' => $this->dashboardFields['id_group'],
                ]
            );
        }

        if (isset($config['public_dashboard']) === false) {
            $config['public_dashboard'] = false;
        }

        // View.
        if ($this->slides === 0 || $this->cellModeSlides === 0) {
            View::render(
                'dashboard/layout',
                [
                    'ajaxController'  => $this->ajaxController,
                    'dashboardId'     => $this->dashboardId,
                    'url'             => \ui_get_full_url('ajax.php'),
                    'createDashboard' => $this->createDashboard,
                    'updateDashboard' => $this->updateDashboard,
                    'cellIdCreate'    => \get_parameter('cellIdCreate', 0),
                    'class'           => (($config['public_dashboard'] === true) ? quotemeta(__CLASS__) : ''),
                    'hash'            => (($config['public_dashboard'] === true) ? self::generatePublicHash() : ''),
                ]
            );
        } else {
            $this->cellId = ($this->cellId === 0) ? $this->cells[0]['id'] : $this->cellId;

            $cellClass = new Cell($this->cellId, $this->dashboardId);
            $cellData = $cellClass->get();

            $instance = '';
            if ((int) $cellData['id_widget'] !== 0 || $this->widgetId !== 0) {
                $settings = [
                    'page'        => $this->ajaxController,
                    'url'         => \ui_get_full_url('ajax.php'),
                    'dashboardId' => $this->dashboardId,
                    'widgetId'    => $cellData['id_widget'],
                    'cellId'      => $this->cellId,
                ];
            }

            View::render(
                'dashboard/slidesWidget',
                [
                    'options'  => \json_decode($cellData['options'], true),
                    'settings' => $settings,
                ]
            );
        }

        // Js countdown for mode slice.
        View::render(
            'dashboard/jsLayout',
            ['dashboardId' => $this->dashboardId]
        );

        return null;
    }


    /**
     * Get cells for layout draw
     *
     * @return void
     */
    public function getCellsLayout()
    {
        global $config;

        $result = [];
        $cells = $this->cells;

        if ($cells === false) {
            $cells = [];
        }

        if (empty($cells) === false) {
            $result = array_reduce(
                $cells,
                function ($carry, $item) {
                    $carry[$item['order']]['id'] = $item['id'];
                    $carry[$item['order']]['position'] = $item['position'];
                    $carry[$item['order']]['widgetId'] = $item['id_widget'];

                    return $carry;
                },
                []
            );
        }

        echo json_encode($result);
    }


    /**
     * Insert new cell layout.
     *
     * @return void
     */
    public function insertCellLayout():void
    {
        global $config;

        $position = [
            'x'      => 0,
            'y'      => 0,
            'width'  => 4,
            'height' => 4,
        ];

        $cellClass = new Cell($position, $this->dashboardId);
        $dataCell = $cellClass->get();

        $result = ['cellId' => $dataCell['id']];

        echo json_encode($result);
    }


    /**
     * Draw Cell.
     *
     * @return mixed
     */
    public function drawCell()
    {
        global $config;

        $redraw = (bool) \get_parameter('redraw', 0);

        $cellClass = new Cell($this->cellId, $this->dashboardId);
        $cellData = $cellClass->get();

        if ((int) $cellData['id_widget'] !== 0 || $this->widgetId !== 0) {
            if ((int) $cellData['id_widget'] === 0 && $this->widgetId !== 0) {
                // Insert in cell widget ID.
                $res = $cellClass->put([], $this->widgetId);
                if ($res === 1) {
                    $cellData['id_widget'] = $this->widgetId;
                }
            }

            $instance = $this->instanceWidget();
            $cellData['options'] = $instance->decoders(
                $instance->getOptionsWidget()
            );

            if (isset($cellData['options']['title']) === false) {
                $cellData['options']['title'] = $instance->getDescription();
            }

            $cellData['options'] = json_encode($cellData['options']);
        }

        View::render(
            'dashboard/cell',
            [
                'redraw'           => $redraw,
                'cellData'         => $cellData,
                'manageDashboards' => $this->manageDashboards,
            ]
        );

        return null;
    }


    /**
     * Draw widget.
     *
     * @return mixed
     */
    public function drawWidget()
    {
        $newWidth = (int) \get_parameter('newWidth', 0);
        $newHeight = (int) \get_parameter('newHeight', 0);
        $gridWidth = (int) \get_parameter('gridWidth', 0);

        $cellClass = new Cell($this->cellId, $this->dashboardId);
        $cellData = $cellClass->get();

        $instance = '';

        if ((int) $cellData['id_widget'] !== 0 || $this->widgetId !== 0) {
            $instance = $this->instanceWidget(
                $newWidth,
                $newHeight,
                $gridWidth
            );
        }

        View::render(
            'dashboard/widget',
            [
                'widgetId' => $this->widgetId,
                'cellData' => $cellData,
                'instance' => $instance,
            ]
        );

        return null;
    }


    /**
     * Save layout.
     * Update, Insert and delete widgets.
     * More important order.
     *
     * @return mixed
     */
    public function saveLayout()
    {
        global $config;

        $items = \get_parameter('items', []);

        // Class Dashboard.
        if (empty($items) === false) {
            // Order for position Y and X.
            usort(
                $items,
                function ($a, $b) {
                    // First order by position `y`.
                    // if `y` is the same order by position `x`.
                    $retval = ($a['y'] <=> $b['y']);
                    if ($retval === 0) {
                        $retval = ($a['x'] <=> $b['x']);
                    }

                    return $retval;
                }
            );

            $result = false;
            foreach ($items as $order => $item) {
                $item['order'] = $order;
                $id = $item['id'];
                unset($item['id']);
                // Update cells.
                $cellClass = new Cell($id, $this->dashboardId);
                $result = $cellClass->put($item);

                if ($result === false) {
                    return false;
                }
            }
        }

        echo json_encode($result);
    }


    /**
     * Ajax layout delete Cell.
     *
     * @return void
     */
    public function deleteCell():void
    {
        global $config;

        $res = 0;
        if ($this->cellId !== 0) {
            // Remove cells.
            $cellClass = new Cell($this->cellId, $this->dashboardId);
            $res = $cellClass->delete();
        }

        $result = ['result' => $res];

        echo json_encode($result);
    }


    /**
     * Draw list widgets.
     *
     * @return mixed
     */
    public function drawAddWidget()
    {
        global $config;

        Widget::dashboardInstallWidgets($this->cellId);

        $search = \io_safe_output(\get_parameter('search', ''));

        // The limit is fixed here.
        $total = count(Widget::getWidgets(-1, -1, $search));
        $widgets = Widget::getWidgets($this->offset, 9, $search);

        View::render(
            'dashboard/listWidgets',
            [
                'widgets'        => $widgets,
                'total'          => $total,
                'offset'         => $this->offset,
                'dashboardId'    => $this->dashboardId,
                'cellId'         => $this->cellId,
                'search'         => $search,
                'ajaxController' => $this->ajaxController,
            ]
        );

        return null;
    }


    /**
     * Form configuration widget.
     *
     * @return mixed
     */
    public function drawConfiguration()
    {
        global $config;

        $instance = $this->instanceWidget();
        $blocks = [];
        $htmlInputs = $instance->getFormInputs([]);

        if (isset($htmlInputs['blocks']) === true) {
            $blocks = $htmlInputs['blocks'];
            $htmlInputs = $htmlInputs['inputs'];
        }

        $js = $instance->getFormJS();

        View::render(
            'dashboard/configurationWidgets',
            [
                'dashboardId' => $this->dashboardId,
                'cellId'      => $this->cellId,
                'blocks'      => $blocks,
                'htmlInputs'  => $htmlInputs,
                'js'          => $js,
            ]
        );

        return null;
    }


    /**
     * Save widget into cell.
     *
     * @return void
     */
    public function saveWidgetIntoCell()
    {
        global $config;

        // Init result.
        $result = ['result' => false];
        if ($this->widgetId !== 0) {
            // Instance widget for get Post.
            $instance = $this->instanceWidget();
            $values = $instance->getPost();

            // Add new configuration for widget into cell.
            $cellClass = new Cell($this->cellId, $this->dashboardId);
            $res = $cellClass->put([], $this->widgetId, $values);

            $result = [
                'result'      => $res,
                'page'        => $this->ajaxController,
                'url'         => ui_get_full_url('ajax.php'),
                'dashboardId' => $this->dashboardId,
                'cellId'      => $this->cellId,
                'widgetId'    => $this->widgetId,
            ];
        }

        echo json_encode($result);
    }


    /**
     * Image icon Dashboard ajax change.
     *
     * @return mixed
     */
    public function imageIconDashboardAjax()
    {
        $nameImg = \get_parameter('nameImg', '');

        $output = $this->imageIconDashboard($nameImg);

        echo $output;
        return null;
    }


    /**
     * Return image php.
     *
     * @param string|null $nameImg Path image.
     *
     * @return string Image.
     */
    public static function imageIconDashboard(?string $nameImg):string
    {
        if (empty($nameImg) === true) {
            $nameImg = 'appliance';
        }

        $output = html_print_image(
            'images/console/icons/'.$nameImg.'.png',
            true,
            [
                'alt'   => __('Icon image dashboard'),
                'style' => 'max-width:70px; max-height:70px;',
            ]
        );
        return $output;
    }


    /**
     * Draw form slides.
     *
     * @return mixed
     */
    public function formSlides()
    {
        $dashboards = $this->getDashboards(-1, -1, false, false);

        View::render(
            'dashboard/formSlides',
            [
                'url'        => $this->url,
                'dashboards' => $dashboards,
            ]
        );
        return null;
    }


    /**
     * Prints error.
     *
     * @param string $msg Message.
     *
     * @return void
     */
    public function error(string $msg)
    {
        if ((bool) \is_ajax() === true) {
            echo json_encode(['error' => $msg]);
        } else {
            \ui_print_error_message($msg);
        }
    }


    /**
     * Call widget method (ajax only).
     *
     * @param string $method Method to be invoked.
     *
     * @return boolean Executed or not.
     */
    public function callWidgetMethod(string $method):bool
    {
        $widget = $this->instanceWidget();

        if (method_exists($widget, $method) === true) {
            $widget->$method();
            return true;
        }

        return false;

    }


    /**
     * Size configuration modal (ajax only).
     *
     * @return void.
     */
    public function getSizeModalConfiguration():void
    {
        $result = [];
        $widget = $this->instanceWidget();
        $result = $widget->getSizeModalConfiguration();

        echo json_encode($result);

        return;

    }


}
