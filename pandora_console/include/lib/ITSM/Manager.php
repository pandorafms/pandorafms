<?php

namespace PandoraFMS\ITSM;

use PandoraFMS\View;
use Throwable;

/**
 * Dashboard manager.
 */
class Manager
{

    /**
     * Ajax controller.
     *
     * @var string
     */
    protected $ajaxController;

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = [
        'getListTickets',
        'getUserSelect',
        'getInputFieldsIncidenceType',
    ];

    /**
     * Url.
     *
     * @var string
     */
    protected $url;

    /**
     * Operation mode.
     *
     * @var string
     */
    protected $operation;


    /**
     * Constructor
     *
     * @param string $page For ajax controller.
     */
    public function __construct(string $page='operation/ITSM/itsm')
    {
        global $config;

        check_login();
        if (check_acl($config['id_user'], 0, 'RR') === 0) {
            include 'general/noaccess.php';
            return;
        }

        $this->operation = \get_parameter('operation', 0);

        // Urls.
        $this->url = \ui_get_full_url(
            'index.php?sec=reporting&sec2=operation/dashboard/dashboard'
        );

        $this->ajaxController = $page;
    }


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public function ajaxMethod(string $method): bool
    {
        return in_array($method, $this->AJAXMethods);
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
     * Init manager ITSM.
     *
     * @return void
     */
    public function run()
    {
        ui_require_css_file('integriaims');
        switch ($this->operation) {
            case 'list':
                $this->showList();
            break;

            case 'edit':
                \ui_require_javascript_file('ITSM');
                $this->showEdit();
            break;

            case 'dashboard':
            default:
                $this->showDashboard();
            break;
        }
    }


    /**
     * Draw list tickets.
     *
     * @return void
     */
    private function showList()
    {
        View::render(
            'ITSM/ITSMTicketListView',
            [
                'ajaxController' => $this->ajaxController,
                'urlAjax'        => \ui_get_full_url('ajax.php'),
            ]
        );
    }


    /**
     * Draw list tickets.
     *
     * @return void
     */
    private function showEdit()
    {
        $create_incidence = (bool) \get_parameter('create_incidence', 0);
        $update_incidence = (bool) \get_parameter('update_incidence', 0);
        $idIncidence      = \get_parameter('idIncidence', 0);

        // Debugger.
        // hd($_POST);
        $error = '';
        $ITSM = new ITSM();
        try {
            $objectTypes = $this->getObjectypes($ITSM);
            $groups = $this->getGroups($ITSM);
            // $priorities = $ITSM->callApi('listPriorities');
            $resolutions = $this->getResolutions($ITSM);
            $status = $this->getStatus($ITSM);

            if (empty($idIncidence) === false) {
                $incidenceData = $this->getIncidence($ITSM, $idIncidence);
            }
        } catch (\Throwable $th) {
            $error = $th->getMessage();
        }

        // TODO: END POINT priorities.
        // \get_parameter('priority', 'LOW').
        $incidence = [
            'title'           => \get_parameter('title', ($incidenceData['title'] ?? '')),
            'idIncidenceType' => \get_parameter('idIncidenceType', ($incidenceData['idIncidenceType'] ?? 0)),
            'idGroup'         => \get_parameter('idGroup', ($incidenceData['idGroup'] ?? 0)),
            'priority'        => 'LOW',
            'status'          => \get_parameter('status', ($incidenceData['status'] ?? 'NEW')),
            'idCreator'       => \get_parameter('idCreator', ($incidenceData['idCreator'] ?? '')),
            'owner'           => \get_parameter('owner_hidden', ''),
            'resolution'      => \get_parameter('resolution', ($incidenceData['resolution'] ?? null)),
            'description'     => \get_parameter('description', ($incidenceData['description'] ?? '')),
        ];

        $successfullyMsg = '';
        try {
            if (empty($incidence['idIncidenceType']) === false
                && ($create_incidence === true || $update_incidence === true)
            ) {
                $customFields = \get_parameter('custom-fields', []);
                if (empty($customFields) === false) {
                    $typeFieldData = [];
                    foreach ($customFields as $idField => $data) {
                        $typeFieldData[] = [
                            'idIncidenceTypeField' => $idField,
                            'data'                 => $data,
                        ];
                    }
                }

                $incidence['typeFieldData'] = $typeFieldData;
            }

            if ($create_incidence === true) {
                $incidence = $this->createIncidence($ITSM, $incidence);
                $idIncidence = $incidence['idIncidence'];
                $successfullyMsg = __('Successfully create ticket');
            }

            if ($update_incidence === true) {
                $incidence = $this->updateIncidence($ITSM, $incidence, $idIncidence);
                $successfullyMsg = __('Successfully update ticket');
            }
        } catch (\Throwable $th) {
            $error = $th->getMessage();
        }

        View::render(
            'ITSM/ITSMTicketEditView',
            [
                'ajaxController'  => $this->ajaxController,
                'urlAjax'         => \ui_get_full_url('ajax.php'),
                'objectTypes'     => $objectTypes,
                'groups'          => $groups,
                'priorities'      => [],
                'resolutions'     => $resolutions,
                'status'          => $status,
                'error'           => $error,
                'incidence'       => $incidence,
                'idIncidence'     => $idIncidence,
                'successfullyMsg' => $successfullyMsg,
            ]
        );
    }


    /**
     * Get Incidences types.
     *
     * @param ITSM $ITSM Object for callApi.
     *
     * @return array Return mode select.
     */
    private function getObjectypes(ITSM $ITSM): array
    {
        $listObjectTypes = $ITSM->callApi('listObjectTypes');
        $result = [];
        foreach ($listObjectTypes['data'] as $objectType) {
            $result[$objectType['idIncidenceType']] = $objectType['name'];
        }

        return $result;
    }


    /**
     * Get Groups.
     *
     * @param ITSM $ITSM Object for callApi.
     *
     * @return array Return mode select.
     */
    private function getGroups(ITSM $ITSM): array
    {
        $listGroups = $ITSM->callApi('listGroups');
        $result = [];
        foreach ($listGroups['data'] as $group) {
            if ($group['idGroup'] > 1) {
                $result[$group['idGroup']] = $group['name'];
            }
        }

        return $result;
    }


    /**
     * Get Resolutions.
     *
     * @param ITSM $ITSM Object for callApi.
     *
     * @return array Return mode select.
     */
    private function getResolutions(ITSM $ITSM): array
    {
        $listResolutions = $ITSM->callApi('listResolutions');
        $result = [];
        foreach ($listResolutions['data'] as $resolution) {
            $result[$resolution['idIncidenceResolution']] = $resolution['name'];
        }

        return $result;
    }


    /**
     * Create incidence
     *
     * @param ITSM  $ITSM      Object for callApi.
     * @param array $incidence Params insert.
     *
     * @return array
     */
    private function createIncidence(ITSM $ITSM, array $incidence): array
    {
        $incidence = $ITSM->callApi('createIncidence', null, $incidence);
        return $incidence;
    }


    /**
     * Update incidence
     *
     * @param ITSM    $ITSM        Object for callApi.
     * @param array   $incidence   Params insert.
     * @param integer $idIncidence Id incidence.
     *
     * @return array
     */
    private function updateIncidence(ITSM $ITSM, array $incidence, int $idIncidence): array
    {
        $incidence = $ITSM->callApi(
            'updateIncidence',
            null,
            $incidence,
            $idIncidence,
            'PUT'
        );
        return $incidence;
    }


    /**
     * Get Status.
     *
     * @param ITSM $ITSM Object for callApi.
     *
     * @return array Return mode select.
     */
    private function getStatus(ITSM $ITSM): array
    {
        $listStatus = $ITSM->callApi('listStatus');
        $result = [];
        foreach ($listStatus['data'] as $status) {
            $result[$status['idIncidenceStatus']] = $status['name'];
        }

        return $result;
    }


    /**
     * Get Incidence.
     *
     * @param ITSM    $ITSM        Object for callApi.
     * @param integer $idIncidence Incidence ID.
     *
     * @return array Data incidence
     */
    private function getIncidence(ITSM $ITSM, int $idIncidence): array
    {
        $result = $ITSM->callApi(
            'incidence',
            [],
            [],
            $idIncidence,
            'GET'
        );

        return $result;
    }


    /**
     * Get fields incidence type.
     *
     * @param ITSM    $ITSM            Object for callApi.
     * @param integer $idIncidenceType Incidence Type ID.
     *
     * @return array Fields array.
     */
    private function getFieldsIncidenceType(ITSM $ITSM, int $idIncidenceType): array
    {
        $result = $ITSM->callApi(
            'incidenceTypeFields',
            [
                'page'     => 0,
                'sizePage' => 0,
            ],
            [],
            $idIncidenceType
        );

        return $result;
    }


    /**
     * Draw list dashboards.
     *
     * @return void
     */
    private function showDashboard()
    {
        View::render(
            'ITSM/ITSMDashboardView',
            [
                'ajaxController' => $this->ajaxController,
                'urlAjax'        => \ui_get_full_url('ajax.php'),
            ]
        );
    }


    /**
     * Get list tickets and prepare data for datatable.
     *
     * @return void
     */
    public function getListTickets()
    {
        global $config;

        // Init data.
        $data = [];
        // Catch post parameters.
        $start = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $order = get_datatable_order(true);
        $filters = get_parameter('filter', []);

        try {
            ob_start();
            $queryParams = array_merge(
                [
                    'page'     => $start,
                    'sizePage' => $length,
                ],
                $order
            );

            $ITSM = new ITSM();
            $result = $ITSM->callApi(
                'listTickets',
                $queryParams,
                $filters
            );

            $data = array_reduce(
                $result['data'],
                function ($carry, $item) {
                    global $config;
                    // Transforms array of arrays $data into an array
                    // of objects, making a post-process of certain fields.
                    $tmp = (object) $item;
                    $new = (object) [];
                    $new->id = $tmp->idIncidence;
                    $new->title = $tmp->title;
                    $new->groupCompany = $tmp->idGroup.'/'.$tmp->idCompany;
                    $new->statusResolution = $tmp->status.'/'.$tmp->resolution;
                    $new->priority = $tmp->priority;
                    $new->updated = $tmp->updateDate;
                    $new->started = $tmp->startDate;
                    $new->creator = $tmp->idCreator;
                    $new->owner = $tmp->owner;

                    $carry[] = $new;
                    return $carry;
                }
            );

            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $result['paginationData']['totalRegisters'],
                    'recordsFiltered' => $result['paginationData']['totalRegisters'],
                ]
            );
            // Capture output.
            $response = ob_get_clean();
        } catch (Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        // If not valid, show error with issue.
        json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            // If valid dump.
            echo $response;
        } else {
            echo json_encode(
                ['error' => $response]
            );
        }

        exit;
    }


    /**
     * Get list tickets and prepare data for datatable.
     *
     * @return void
     */
    public function getUserSelect()
    {
        global $config;

        try {
            $ITSM = new ITSM();
            $result = $ITSM->callApi(
                'listUsers',
                [
                    'page'     => 0,
                    'sizePage' => 0,
                ],
                ['freeSearch' => \get_parameter('search_term', '')]
            );

            $response = array_reduce(
                $result['data'],
                function ($carry, $user) {
                    $carry[$user['idUser']] = $user['fullName'];
                    return $carry;
                }
            );
        } catch (Throwable $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        echo json_encode($response);
        exit;
    }


    /**
     * Get Input fields of type incidence.
     *
     * @return void
     */
    public function getInputFieldsIncidenceType()
    {
        $idIncidenceType = (int) get_parameter('idIncidenceType', true);
        $fieldsData = json_decode(io_safe_output(get_parameter('fieldsData', [])), true);
        if (empty($fieldsData) === false) {
            $fieldsData = array_reduce(
                $fieldsData,
                function ($carry, $user) {
                    $carry[$user['idIncidenceField']] = $user['data'];
                    return $carry;
                }
            );
        } else {
            $fieldsData = [];
        }

        $error = '';
        try {
            $ITSM = new ITSM();
            $result = $this->getFieldsIncidenceType($ITSM, $idIncidenceType);
            $customFields = $result['data'];
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        View::render(
            'ITSM/ITSMCustomFields',
            [
                'ajaxController' => $this->ajaxController,
                'urlAjax'        => \ui_get_full_url('ajax.php'),
                'customFields'   => $customFields,
                'fieldsData'     => $fieldsData,
                'error'          => $error,
            ]
        );
        exit;
    }


}
