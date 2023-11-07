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
        'getDownloadIncidenceAttachment',
        'checkConnectionApi',
        'checkConnectionApiITSMToPandora',
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
        \ui_require_css_file('pandoraitsm');
        \ui_require_javascript_file('ITSM');
        switch ($this->operation) {
            case 'list':
                $this->showList();
            break;

            case 'edit':
                $this->showEdit();
            break;

            case 'detail':
                $this->showDetail();
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
        $idIncidence = \get_parameter('idIncidence', 0);
        $error = '';
        $successfullyMsg = '';
        $groups = [];
        $status = [];
        $priorities = [];

        $headerTabs = $this->headersTabs('list');

        try {
            $ITSM = new ITSM();
            $groups = $ITSM->getGroups();
            $status = $ITSM->getStatus();
            $priorities = $ITSM->getPriorities();
            if (empty($idIncidence) === false) {
                $this->deleteIncidence($ITSM, $idIncidence);
                $successfullyMsg = __('Delete ticket successfully');
            }
        } catch (\Throwable $th) {
            $error = $th->getMessage();
        }

        View::render(
            'ITSM/ITSMTicketListView',
            [
                'ajaxController'  => $this->ajaxController,
                'urlAjax'         => \ui_get_full_url('ajax.php'),
                'error'           => $error,
                'successfullyMsg' => $successfullyMsg,
                'groups'          => $groups,
                'status'          => $status,
                'priorities'      => $priorities,
                'headerTabs'      => $headerTabs,
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
        global $config;
        $create_incidence = (bool) \get_parameter('create_incidence', 0);
        $update_incidence = (bool) \get_parameter('update_incidence', 0);
        $idIncidence = (int) \get_parameter('idIncidence', 0);
        $idEvent = (int) \get_parameter('from_event', 0);

        $headerTabs = $this->headersTabs('edit', $idIncidence);

        $error = '';
        try {
            $ITSM = new ITSM();
            $objectTypes = $ITSM->getObjectypes();
            $groups = $ITSM->getGroups();
            $priorities = $ITSM->getPriorities();
            $resolutions = $this->getResolutions($ITSM);
            $status = $ITSM->getStatus();

            if (empty($idIncidence) === false) {
                $incidenceData = $this->getIncidence($ITSM, $idIncidence);
            }
        } catch (\Throwable $th) {
            $error = $th->getMessage();
        }

        $default_values = [
            'title'           => '',
            'idIncidenceType' => 0,
            'idGroup'         => 0,
            'priority'        => 'LOW',
            'status'          => 'NEW',
            'idCreator'       => '',
            'owner'           => '',
            'resolution'      => null,
            'description'     => '',
        ];

        if (empty($idEvent) === false) {
            $default_values = [
                'title'           => $config['cr_incident_title'],
                'idIncidenceType' => $config['cr_incident_type'],
                'idGroup'         => $config['cr_default_group'],
                'priority'        => $config['cr_default_criticity'],
                'status'          => $config['cr_incident_status'],
                'idCreator'       => '',
                'owner'           => $config['cr_default_owner'],
                'resolution'      => null,
                'description'     => $config['cr_incident_content'],
            ];
        }

        $incidence = [
            'title'           => \get_parameter('title', ($incidenceData['title'] ?? $default_values['title'])),
            'idIncidenceType' => \get_parameter('idIncidenceType', ($incidenceData['idIncidenceType'] ?? $default_values['idIncidenceType'])),
            'idGroup'         => \get_parameter('idGroup', ($incidenceData['idGroup'] ?? $default_values['idGroup'])),
            'priority'        => \get_parameter('priority', ($incidenceData['priority'] ?? $default_values['priority'])),
            'status'          => \get_parameter('status', ($incidenceData['status'] ?? $default_values['status'])),
            'idCreator'       => \get_parameter('idCreator', ($incidenceData['idCreator'] ?? $default_values['idCreator'])),
            'owner'           => \get_parameter('owner_hidden', ($incidenceData['owner'] ?? $default_values['owner'])),
            'resolution'      => \get_parameter('resolution', ($incidenceData['resolution'] ?? $default_values['resolution'])),
            'description'     => \get_parameter('description', ($incidenceData['description'] ?? $default_values['description'])),
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
            } else {
                $incidence['typeFieldData'] = $incidenceData['typeFieldData'];
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
                'priorities'      => $priorities,
                'error'           => $error,
                'incidence'       => $incidence,
                'idIncidence'     => $idIncidence,
                'successfullyMsg' => $successfullyMsg,
                'headerTabs'      => $headerTabs,
            ]
        );
    }


    /**
     * Draw list tickets.
     *
     * @return void
     */
    private function showDetail()
    {
        $idIncidence = (int) \get_parameter('idIncidence', 0);
        $uploadFile = (bool) \get_parameter('upload_file', 0);
        $idAttachment = (int) \get_parameter('idAttachment', 0);
        $addComment = (bool) \get_parameter('addComment', 0);

        $headerTabs = $this->headersTabs('detail', $idIncidence);

        $error = '';
        $error_upload = '';
        $error_comment = '';
        $error_delete_attachment = '';
        $successfullyMsg = null;
        $incidence = null;
        $objectTypes = null;
        $groups = null;
        $resolutions = null;
        $status = null;
        $wus = null;
        $files = null;
        $users = null;
        $priorities = null;
        $priorityDiv = null;
        $inventories = null;
        $ITSM = new ITSM();

        try {
            if ($uploadFile === true) {
                $attachment = [
                    'description' => get_parameter('file_description', ''),
                ];

                $incidenceAttachment = $this->createIncidenceAttachment(
                    $ITSM,
                    $idIncidence,
                    $attachment,
                    get_parameter('userfile')
                );

                if ($incidenceAttachment !== false) {
                    $successfullyMsg = __('File added succesfully');
                }
            }
        } catch (\Exception $e) {
            $error_upload = $e->getMessage();
        }

        try {
            if ($addComment === true) {
                $wu = [
                    'description' => get_parameter('comment_description', ''),
                ];

                $incidenceAttachment = $this->createIncidenceWu(
                    $ITSM,
                    $idIncidence,
                    $wu
                );

                if ($incidenceAttachment !== false) {
                    $successfullyMsg = __('Comment added succesfully');
                }
            }
        } catch (\Exception $e) {
            $error_comment = $e->getMessage();
        }

        try {
            if (empty($idAttachment) === false) {
                $this->deleteIncidenceAttachment($ITSM, $idIncidence, $idAttachment);
                $successfullyMsg = __('Delete File successfully');
            }
        } catch (\Exception $e) {
            $error_delete_attachment = $e->getMessage();
        }

        try {
            if (empty($idIncidence) === false) {
                $incidence = $this->getIncidence($ITSM, $idIncidence);
                $objectTypes = $ITSM->getObjectypes();
                $groups = $ITSM->getGroups();
                $resolutions = $this->getResolutions($ITSM);
                $status = $ITSM->getStatus();
                $priorities = $ITSM->getPriorities();
                $wus = $this->getIncidenceWus($ITSM, $idIncidence);
                $files = $this->getIncidenceFiles($ITSM, $idIncidence);

                $usersInvolved = [];
                $usersInvolved[$incidence['idCreator']] = $incidence['idCreator'];
                $usersInvolved[$incidence['owner']] = $incidence['owner'];
                $usersInvolved[$incidence['closedBy']] = $incidence['closedBy'];

                foreach ($wus['data'] as $wu) {
                    $usersInvolved[$wu['idUser']] = $wu['idUser'];
                }

                foreach ($files['data'] as $file) {
                    $usersInvolved[$file['idUser']] = $file['idUser'];
                }

                $users = $this->getUsers($ITSM, $usersInvolved);

                $inventories = [];
                $priorityDiv = $this->priorityDiv($incidence['priority'], $priorities[$incidence['priority']]);
                if (empty($incidence) === false
                    && isset($incidence['inventories']) === true
                    && empty($incidence['inventories']) === false
                ) {
                    foreach ($incidence['inventories'] as $inventory) {
                        $inventories[] = $this->getInventory($ITSM, $inventory['idInventory']);
                    }
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        View::render(
            'ITSM/ITSMTicketDetailView',
            [
                'ajaxController'          => $this->ajaxController,
                'urlAjax'                 => \ui_get_full_url('ajax.php'),
                'error'                   => $error,
                'error_upload'            => $error_upload,
                'error_comment'           => $error_comment,
                'error_delete_attachment' => $error_delete_attachment,
                'successfullyMsg'         => $successfullyMsg,
                'incidence'               => $incidence,
                'objectTypes'             => $objectTypes,
                'groups'                  => $groups,
                'resolutions'             => $resolutions,
                'status'                  => $status,
                'wus'                     => $wus,
                'files'                   => $files,
                'users'                   => $users,
                'priorities'              => $priorities,
                'priorityDiv'             => $priorityDiv,
                'headerTabs'              => $headerTabs,
                'inventories'             => $inventories,
            ]
        );
    }


    /**
     * Draw list dashboards.
     *
     * @return void
     */
    private function showDashboard()
    {
        $error = '';

        $headerTabs = $this->headersTabs('dashboard');

        try {
            $ITSM = new ITSM();
            $status = $ITSM->getStatus();
            $incidencesByStatus = $this->getIncidencesGroupedByStatus($ITSM, $status);
            $priorities = $ITSM->getPriorities();
            $incidencesByPriorities = $this->getIncidencesGroupedByPriorities($ITSM, $priorities);
            $incidencesByGroups = $this->getIncidencesGroupedByGroups($ITSM);
            $incidencesByOwners = $this->getIncidencesGroupedByOwners($ITSM);
        } catch (\Throwable $th) {
            $error = $th->getMessage();
        }

        View::render(
            'ITSM/ITSMDashboardView',
            [
                'ajaxController'         => $this->ajaxController,
                'urlAjax'                => \ui_get_full_url('ajax.php'),
                'incidencesByStatus'     => $incidencesByStatus,
                'incidencesByPriorities' => $incidencesByPriorities,
                'incidencesByGroups'     => $incidencesByGroups,
                'incidencesByOwners'     => $incidencesByOwners,
                'error'                  => $error,
                'headerTabs'             => $headerTabs,
            ]
        );
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
     * Delete Incidence.
     *
     * @param ITSM    $ITSM        Object for callApi.
     * @param integer $idIncidence Incidence ID.
     *
     * @return void
     */
    private function deleteIncidence(ITSM $ITSM, int $idIncidence): void
    {
        $ITSM->callApi(
            'deleteIncidence',
            [],
            [],
            $idIncidence,
            'DELETE'
        );
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
                'page'      => 0,
                'sizePage'  => 0,
                'field'     => 'idIncidenceTypeField',
                'direction' => 'ascending',
            ],
            [],
            $idIncidenceType
        );

        return $result;
    }


    /**
     * Get Incidence Work units.
     *
     * @param ITSM    $ITSM        Object for callApi.
     * @param integer $idIncidence Incidence ID.
     *
     * @return array Data workUnits incidence.
     */
    private function getIncidenceWus(ITSM $ITSM, int $idIncidence): array
    {
        $result = $ITSM->callApi(
            'incidenceWus',
            [
                'page'     => 0,
                'sizePage' => 0,
            ],
            [],
            $idIncidence
        );

        return $result;
    }


    /**
     * Get Incidence Attachments.
     *
     * @param ITSM    $ITSM        Object for callApi.
     * @param integer $idIncidence Incidence ID.
     *
     * @return array Data attachment incidence.
     */
    private function getIncidenceFiles(ITSM $ITSM, int $idIncidence): array
    {
        $result = $ITSM->callApi(
            'incidenceFiles',
            [
                'page'     => 0,
                'sizePage' => 0,
            ],
            [],
            $idIncidence
        );

        return $result;
    }


    /**
     * Get Users.
     *
     * @param ITSM  $ITSM  Object for callApi.
     * @param array $users Users ID.
     *
     * @return array Users.
     */
    private function getUsers(ITSM $ITSM, array $users): array
    {
        $result = $ITSM->callApi(
            'listUsers',
            [
                'page'     => 0,
                'sizePage' => 0,
            ],
            [
                'multipleSearchString' => [
                    'field' => 'idUser',
                    'data'  => $users,
                ],
            ]
        );

        $res = [];
        if (empty($result['data']) === false) {
            $res = array_reduce(
                $result['data'],
                function ($carry, $user) {
                    $carry[$user['idUser']] = [
                        'fullName'  => $user['fullName'],
                        'idCompany' => $user['idCompany'],
                    ];
                    return $carry;
                }
            );
        }

        return $res;
    }


    /**
     * Get Companies.
     *
     * @param ITSM  $ITSM      Object for callApi.
     * @param array $companies Companies ID.
     *
     * @return array Companies.
     */
    private function getCompanies(ITSM $ITSM, array $companies): array
    {
        $result = $ITSM->callApi(
            'listCompanies',
            [
                'page'     => 0,
                'sizePage' => 0,
            ],
            [
                'multipleSearchString' => [
                    'field' => 'idCompany',
                    'data'  => $companies,
                ],
            ]
        );

        $res = [];
        if (empty($result['data']) === false) {
            $res = array_reduce(
                $result['data'],
                function ($carry, $company) {
                    $carry[$company['idCompany']] = $company['name'];
                    return $carry;
                }
            );
        }

        return $res;
    }


    /**
     * Create incidence Attachment.
     *
     * @param ITSM    $ITSM        Object for callApi.
     * @param integer $idIncidence Id incidence.
     * @param array   $attachment  Params insert.
     * @param array   $file        Info file.
     *
     * @return array
     */
    private function createIncidenceAttachment(ITSM $ITSM, int $idIncidence, array $attachment, array $file): array
    {
        $incidenceAttachment = $ITSM->callApi(
            'createIncidenceAttachment',
            null,
            $attachment,
            $idIncidence,
            'POST',
            $file
        );
        return $incidenceAttachment;
    }


    /**
     * Create incidence Wu.
     *
     * @param ITSM    $ITSM        Object for callApi.
     * @param integer $idIncidence Id incidence.
     * @param array   $wu          Params insert.
     *
     * @return array
     */
    private function createIncidenceWu(ITSM $ITSM, int $idIncidence, array $wu): array
    {
        $incidenceWu = $ITSM->callApi(
            'createIncidenceWu',
            null,
            $wu,
            $idIncidence,
            'POST'
        );
        return $incidenceWu;
    }


    /**
     * Delete Attachment.
     *
     * @param ITSM    $ITSM         Object for callApi.
     * @param integer $idIncidence  Incidence ID.
     * @param integer $idAttachment Attachment ID.
     *
     * @return void
     */
    private function deleteIncidenceAttachment(ITSM $ITSM, int $idIncidence, int $idAttachment): void
    {
        $ITSM->callApi(
            'deleteIncidenceAttachment',
            [],
            [],
            [
                'idIncidence'  => $idIncidence,
                'idAttachment' => $idAttachment,
            ],
            'DELETE'
        );
    }


    /**
     * Download Attachment.
     *
     * @param ITSM    $ITSM         Object for callApi.
     * @param integer $idIncidence  Incidence ID.
     * @param integer $idAttachment Attachment ID.
     *
     * @return mixed
     */
    private function downloadIncidenceAttachment(ITSM $ITSM, int $idIncidence, int $idAttachment)
    {
        return $ITSM->callApi(
            'downloadIncidenceAttachment',
            [],
            [],
            [
                'idIncidence'  => $idIncidence,
                'idAttachment' => $idAttachment,
            ],
            'GET',
            null,
            true
        );
    }


    /**
     * Get Incidences group by for status.
     *
     * @param ITSM  $ITSM   Object for callApi.
     * @param array $status Status.
     *
     * @return array
     */
    private function getIncidencesGroupedByStatus(ITSM $ITSM, array $status): array
    {
        $listStatus = $ITSM->callApi('getIncidencesGroupedByStatus');
        $result = [];
        foreach ($status as $key => $value) {
            if (isset($listStatus[$key]) === false) {
                $listStatus[$key] = 0;
            }

            $result[$value] = $listStatus[$key];
        }

        return $result;
    }


    /**
     * Get Incidences group by for priorities.
     *
     * @param ITSM  $ITSM       Object for callApi.
     * @param array $priorities Priorities.
     *
     * @return array
     */
    private function getIncidencesGroupedByPriorities(ITSM $ITSM, array $priorities): array
    {
        $listPriorities = $ITSM->callApi('getIncidencesGroupedByPriorities');
        $result = [];
        foreach ($priorities as $key => $value) {
            if (isset($listPriorities[$key]) === false) {
                $listPriorities[$key] = 0;
            }

            $result[$value] = $listPriorities[$key];
        }

        return $result;
    }


    /**
     * Get Inventory.
     *
     * @param ITSM    $ITSM        Object for callApi.
     * @param integer $idInventory Inventory ID.
     *
     * @return array Data inventory
     */
    private function getInventory(ITSM $ITSM, int $idInventory): array
    {
        $result = $ITSM->callApi(
            'inventory',
            [],
            [],
            $idInventory,
            'GET'
        );

        return $result;
    }


    /**
     * Get Incidences group by for groups.
     *
     * @param ITSM $ITSM Object for callApi.
     *
     * @return array
     */
    private function getIncidencesGroupedByGroups(ITSM $ITSM): array
    {
        return $ITSM->callApi('getIncidencesGroupedByGroups');
    }


    /**
     * Get Incidences group by for owner.
     *
     * @param ITSM $ITSM Object for callApi.
     *
     * @return array
     */
    private function getIncidencesGroupedByOwners(ITSM $ITSM): array
    {
        return $ITSM->callApi('getIncidencesGroupedByOwners');
    }


    /**
     * Draw priority div.
     *
     * @param string $priority Priority incidence.
     * @param string $label    Name.
     *
     * @return string Html output.
     */
    private function priorityDiv(string $priority, string $label)
    {
        $output = '';
        switch ($priority) {
            case 'LOW':
                $color = COL_NORMAL;
            break;

            case 'INFORMATIVE':
                $color = COL_UNKNOWN;
            break;

            case 'MEDIUM':
                $color = COL_WARNING;
            break;

            case 'SERIOUS':
                $color = COL_ALERTFIRED;
            break;

            case 'VERY_SERIOUS':
                $color = COL_CRITICAL;
            break;

            default:
                $color = COL_NOTINIT;
            break;
        }

        $output = '<div class="priority" style="background: '.$color.'">';
        $output .= $label;
        $output .= '</div>';

        return $output;
    }


    /**
     * Headers tabs
     *
     * @param string  $active_tab  Section.
     * @param integer $idIncidence Id incidence.
     *
     * @return array Headers.
     */
    private function headersTabs(string $active_tab, int $idIncidence=0): array
    {
        $url_tabs = ui_get_full_url('index.php?sec=ITSM&sec2=operation/ITSM/itsm');
        $url_setup = ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=ITSM');

        $setup_tab['text'] = '<a href="'.$url_setup.'">';
        $setup_tab['text'] .= html_print_image(
            'images/configuration@svg.svg',
            true,
            [
                'title' => __('Configure Pandora ITSM'),
                'class' => 'main_menu_icon invert_filter',
            ]
        );
        $setup_tab['text'] .= '</a>';

        $list_tab['text'] = '<a href="'.$url_tabs.'&operation=list">';
        $list_tab['text'] .= html_print_image(
            'images/logs@svg.svg',
            true,
            [
                'title' => __('List'),
                'class' => 'main_menu_icon invert_filter',
            ]
        );
        $list_tab['text'] .= '</a>';

        $create_tab['text'] = '<a href="'.$url_tabs.'&operation=edit">';
        $create_tab['text'] .= html_print_image(
            'images/edit.svg',
            true,
            [
                'title' => __('New'),
                'class' => 'main_menu_icon invert_filter',
            ]
        );
        $create_tab['text'] .= '</a>';

        $dashboard_tab['text'] = '<a href="'.$url_tabs.'&operation=dashboard">';
        $dashboard_tab['text'] .= html_print_image(
            'images/item-icon.svg',
            true,
            [
                'title' => __('Dashboard'),
                'class' => 'main_menu_icon invert_filter',
            ]
        );
        $dashboard_tab['text'] .= '</a>';

        if ($idIncidence !== 0) {
            $create_tab['text'] = '<a href="'.$url_tabs.'&operation=edit&idIncidence='.$idIncidence.'">';
            $create_tab['text'] .= html_print_image(
                'images/edit.svg',
                true,
                [
                    'title' => __('Edit'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            );
            $create_tab['text'] .= '</a>';

            $view_tab['text'] = '<a href="'.$url_tabs.'&operation=detail&idIncidence='.$idIncidence.'">';
            $view_tab['text'] .= html_print_image(
                'images/enable.svg',
                true,
                [
                    'title' => __('Detail'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            );
            $view_tab['text'] .= '</a>';
        }

        switch ($active_tab) {
            case 'setup':
                $setup_tab['active'] = true;
                $list_tab['active'] = false;
                $create_tab['active'] = false;
                $dashboard_tab['active'] = false;
                if ($idIncidence !== 0) {
                    $view_tab['active'] = false;
                }
            break;

            case 'list':
                $setup_tab['active'] = false;
                $list_tab['active'] = true;
                $create_tab['active'] = false;
                $dashboard_tab['active'] = false;
                if ($idIncidence !== 0) {
                    $view_tab['active'] = false;
                }
            break;

            case 'edit':
                $setup_tab['active'] = false;
                $list_tab['active'] = false;
                $create_tab['active'] = true;
                $dashboard_tab['active'] = false;
                if ($idIncidence !== 0) {
                    $view_tab['active'] = false;
                }
            break;

            case 'detail':
                $setup_tab['active'] = false;
                $list_tab['active'] = false;
                $create_tab['active'] = false;
                $dashboard_tab['active'] = false;
                if ($idIncidence !== 0) {
                    $view_tab['active'] = true;
                }
            break;

            case 'dashboard':
                $setup_tab['active'] = false;
                $list_tab['active'] = false;
                $create_tab['active'] = false;
                $dashboard_tab['active'] = true;
                if ($idIncidence !== 0) {
                    $view_tab['active'] = false;
                }
            break;

            default:
                // Not possible.
            break;
        }

        $onheader = [];
        $onheader['configure'] = $setup_tab;
        $onheader['dashboard'] = $dashboard_tab;
        $onheader['list'] = $list_tab;
        if ($idIncidence !== 0) {
            $onheader['view'] = $view_tab;
        }

        $onheader['create'] = $create_tab;

        return $onheader;
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
        $start = (int) get_parameter('start', 1);
        $length = (int) get_parameter('length', $config['block_size']);
        $order = get_datatable_order(true);
        $filters = get_parameter('filter', []);

        $customSearch = (int) get_parameter('customSearch', 0);
        if (empty($customSearch) === false) {
            try {
                $ITSM = new ITSM();
                $customSearchData = ($ITSM->getCustomSearch($customSearch)['formValues'] ?? []);
            } catch (\Throwable $th) {
                $error = $th->getMessage();
                $customSearchData = [];
            }

            $filters = $customSearchData;
        }

        $externalIdLike = get_parameter('externalIdLike', '');
        if (empty($externalIdLike) === false) {
            $filters['externalIdLike'] = $externalIdLike;
        }

        $blocked = get_parameter('blocked', null);
        if (isset($blocked) === true) {
            $filters['blocked'] = $blocked;
        }

        if (isset($filters['status']) === true && empty($filters['status']) === true) {
            unset($filters['status']);
        }

        if (isset($filters['priority']) === true && empty($filters['priority']) === true) {
            unset($filters['priority']);
        }

        if (isset($filters['idGroup']) === true && (empty($filters['idGroup']) === true || $filters['idGroup'] < 0)) {
            unset($filters['idGroup']);
        }

        if (isset($filters['fromDate']) === true && empty($filters['fromDate']) === false) {
            $filters['fromDate'] = ($filters['fromDate'] / SECONDS_1DAY);
        }

        if (isset($filters['form_itms_list_tickets_search_bt']) === true) {
            unset($filters['form_itms_list_tickets_search_bt']);
        }

        if (isset($filters['fromDate_select']) === true) {
            unset($filters['fromDate_select']);
        }

        if (isset($filters['fromDate_text']) === true) {
            unset($filters['fromDate_text']);
        }

        if (isset($filters['fromDate_units']) === true) {
            unset($filters['fromDate_units']);
        }

        try {
            ob_start();
            $queryParams = [
                'page'          => ($start === 0) ? $start : ($start / $length),
                'sizePage'      => $length,
                'sortField'     => $order['field'],
                'sortDirection' => $order['direction'],
            ];

            $ITSM = new ITSM();
            $result = $ITSM->callApi(
                'listTickets',
                $queryParams,
                $filters
            );

            $groups = $ITSM->getGroups();
            $resolutions = $this->getResolutions($ITSM);
            $resolutions['NOTRESOLVED'] = __('None');
            $status = $ITSM->getStatus();
            $priorities = $ITSM->getPriorities();

            $usersInvolved = [];
            $usersCreators = [];
            foreach ($result['data'] as $incidence) {
                $usersCreators[$incidence['idCreator']] = $incidence['idCreator'];
                $usersInvolved[$incidence['idCreator']] = $incidence['idCreator'];
                $usersInvolved[$incidence['owner']] = $incidence['owner'];
                $usersInvolved[$incidence['closedBy']] = $incidence['closedBy'];
            }

            $users = $this->getUsers($ITSM, $usersInvolved);
            $companiesCreator = [];
            foreach ($usersCreators as $userInfo) {
                $companiesCreator[$userInfo] = $users[$userInfo]['idCompany'];
            }

            if (empty($companiesCreator) === false) {
                $companies = $this->getCompanies($ITSM, $companiesCreator);
            }

            $url = \ui_get_full_url('index.php?sec=manageTickets&sec2=operation/ITSM/itsm');

            if (empty($result['data']) === false) {
                $data = array_reduce(
                    $result['data'],
                    function (
                        $carry,
                        $item
                    ) use (
                        $groups,
                        $resolutions,
                        $status,
                        $priorities,
                        $users,
                        $companies,
                        $url
                    ) {
                        // Transforms array of arrays $data into an array
                        // of objects, making a post-process of certain fields.
                        $tmp = (object) $item;

                        $new = (object) [];
                        $new->idIncidence = $tmp->idIncidence;
                        $new->title = '<a href="'.$url.'&operation=detail&idIncidence='.$tmp->idIncidence.'">';
                        $new->title .= $tmp->title;
                        $new->title .= '</a>';
                        $new->groupCompany = $groups[$tmp->idGroup];
                        if (empty($users[$tmp->idCreator]['idCompany']) === false) {
                            $new->groupCompany .= ' / '.$companies[$users[$tmp->idCreator]['idCompany']];
                        }

                        $new->statusResolution = $status[$tmp->status].'/'.$resolutions[$tmp->resolution];
                        $new->priority = $this->priorityDiv($tmp->priority, $priorities[$tmp->priority]);
                        $new->updateDate = $tmp->updateDate;
                        $new->startDate = $tmp->startDate;
                        $new->idCreator = $users[$tmp->idCreator]['fullName'];
                        $new->owner = $users[$tmp->owner]['fullName'];

                        $new->operation = '<div class="table_action_buttons">';
                        $new->operation .= '<a href="'.$url.'&operation=edit&idIncidence='.$tmp->idIncidence.'">';
                        $new->operation .= html_print_image(
                            'images/edit.svg',
                            true,
                            [
                                'title' => __('Edit'),
                                'class' => 'main_menu_icon invert_filter',
                            ]
                        );
                        $new->operation .= '</a>';

                        $new->operation .= '<a href="'.$url.'&operation=detail&idIncidence='.$tmp->idIncidence.'">';
                        $new->operation .= html_print_image(
                            'images/enable.svg',
                            true,
                            [
                                'title' => __('Detail'),
                                'class' => 'main_menu_icon invert_filter',
                            ]
                        );
                        $new->operation .= '</a>';

                        $urlDelete = $url.'&operation=list&idIncidence='.$tmp->idIncidence;
                        $urlOnClick = 'javascript:if (!confirm(\''.__('Are you sure?').'\')) return false;';
                        $new->operation .= '<a href="'.$urlDelete.'" onClick="'.$urlOnClick.'">';
                        $new->operation .= html_print_image(
                            'images/delete.svg',
                            true,
                            [
                                'title' => __('Delete'),
                                'class' => 'main_menu_icon invert_filter',
                            ]
                        );
                        $new->operation .= '</a>';

                        $new->operation .= '</div>';

                        $carry[] = $new;
                        return $carry;
                    }
                );
            }

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
        $fieldsData = json_decode(base64_decode(get_parameter('fieldsData')), true);
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


    /**
     * Get Download.
     *
     * @return void
     */
    public function getDownloadIncidenceAttachment()
    {
        $idIncidence = (int) get_parameter('idIncidence', true);
        $idAttachment = (int) get_parameter('idAttachment', true);

        try {
            $ITSM = new ITSM();
            $result = $this->downloadIncidenceAttachment($ITSM, $idIncidence, $idAttachment);
        } catch (Throwable $e) {
            echo $e->getMessage();
            exit;
        }

        echo $result;
        exit;
    }


    /**
     * Ping API, check connection.
     *
     * @return void
     */
    public function checkConnectionApi()
    {
        $pass = (string) get_parameter('pass', '');
        $host = (string) get_parameter('host', '');
        try {
            $ITSM = new ITSM($host, $pass);
            $result = $ITSM->ping();
        } catch (Throwable $e) {
            echo $e->getMessage();
            $result = false;
            exit;
        }

        echo json_encode(['valid' => ($result !== false) ? 1 : 0]);
        exit;
    }


    /**
     * Ping API, check connection pandora ITSM to pandora.
     *
     * @return void
     */
    public function checkConnectionApiITSMToPandora()
    {
        $path = (string) get_parameter('path', '');
        try {
            $ITSM = new ITSM();
            $result = $ITSM->pingItsmtoPandora($path);
        } catch (Throwable $e) {
            echo $e->getMessage();
            $result = false;
            exit;
        }

        echo json_encode(['valid' => ($result !== false) ? 1 : 0]);
        exit;
    }


}
