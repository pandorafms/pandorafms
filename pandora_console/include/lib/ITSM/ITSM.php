<?php

namespace PandoraFMS\ITSM;

/**
 * Dashboard manager.
 */
class ITSM
{

    /**
     * User level conf.
     *
     * @var boolean
     */
    private ?bool $userLevelConf;

    /**
     * User level conf.
     *
     * @var string
     */
    private string $url;

    /**
     * Bearer.
     *
     * @var string
     */
    private ?string $userBearer;


    /**
     * ITSM.
     *
     * @param string|null $host  Host url.
     * @param string|null $token Token.
     */
    public function __construct(?string $host=null, ?string $token=null)
    {
        global $config;
        $user_info = \users_get_user_by_id($config['id_user']);

        $this->userLevelConf = (bool) $config['ITSM_user_level_conf'];
        $this->url = ($host ?? $config['ITSM_hostname']);
        if (isset($config['ITSM_token']) === false) {
            $config['ITSM_token'] = '';
        }

        $this->userBearer = ($token ?? $config['ITSM_token']);
        if ($this->userLevelConf === true) {
            $this->userBearer = ($token ?? $user_info['integria_user_level_pass']);
        }
    }


    /**
     * Call api ITSM.
     *
     * @param string      $action      Endpoint.
     * @param array       $queryParams Params send get.
     * @param array       $postFields  Params send post.
     * @param mixed       $id          Specific id for path.
     * @param string|null $method      Request method.
     * @param array|null  $file        Upload file.
     * @param boolean     $download    Download file.
     *
     * @return array Array result.
     * @throws \Exception On error.
     */
    public function callApi(
        string $action,
        ?array $queryParams=null,
        ?array $postFields=null,
        mixed $id=null,
        ?string $method='POST',
        ?array $file=null,
        ?bool $download=false
    ) {
        $headers = [
            'accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->userBearer,
        ];

        $path = $this->pathAction($action, $queryParams, $id);
        $url = $this->url.$path;

        $data = [];
        // Clean safe_input forms.
        if (empty($postFields) === false) {
            foreach ($postFields as $key => $field) {
                if ($field !== null) {
                    $field = io_safe_output($field);
                }

                $data[$key] = $field;
            }
        }

        if ($file !== null && file_exists($file['tmp_name']) === true) {
            $data['attachment'] = curl_file_create(
                $file['tmp_name'],
                $file['type'],
                $file['name']
            );

            $headers = [
                'Content-Type: multipart/form-data',
                'Authorization: Bearer '.$this->userBearer,
            ];
        } else {
            $data = json_encode($data);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $response = curl_exec($ch);

        if ($download === true) {
            return $response;
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(__('Invalid response').', '.$response);
        }

        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_status !== 200) {
            throw new \Exception($result['error']);
        }

        return $result;
    }


    /**
     * Convert path to endpoint ITSM.
     *
     * @param string     $action      EndPoint.
     * @param array|null $queryParams Params to url.
     * @param mixed      $id          Specific id for path.
     *
     * @return string Return path to Endpoint.
     */
    private function pathAction(string $action, ?array $queryParams=null, mixed $id=null): string
    {
        $path = '';
        switch ($action) {
            case 'ping':
                $path = '/ping';
            break;

            case 'listTickets':
                $path = '/incidence/list';
            break;

            case 'listObjectTypes':
                $path = '/incidencetype/list';
            break;

            case 'listGroups':
                $path = '/group/list';
            break;

            case 'listResolutions':
                $path = '/incidence/resolution/list';
            break;

            case 'listStatus':
                $path = '/incidence/status/list';
            break;

            case 'listPriorities':
                $path = '/incidence/priority/list';
            break;

            case 'listUsers':
                $path = '/user/list';
            break;

            case 'listCompanies':
                $path = '/company/list';
            break;

            case 'createIncidence':
                $path = '/incidence';
            break;

            case 'updateIncidence':
                $path = '/incidence/'.$id;
            break;

            case 'incidenceTypeFields':
                $path = '/incidencetype/'.$id.'/field/list';
            break;

            case 'incidence':
                $path = '/incidence/'.$id;
            break;

            case 'deleteIncidence':
                $path = '/incidence/'.$id;
            break;

            case 'incidenceWus':
                $path = '/incidence/'.$id.'/workunit/list';
            break;

            case 'incidenceFiles':
                $path = '/incidence/'.$id.'/attachment/list';
            break;

            case 'createIncidenceAttachment':
                $path = '/incidence/'.$id.'/attachment';
            break;

            case 'createIncidenceWu':
                $path = '/incidence/'.$id.'/workunit';
            break;

            case 'deleteIncidenceAttachment':
                $path = '/incidence/'.$id['idIncidence'].'/attachment/'.$id['idAttachment'];
            break;

            case 'downloadIncidenceAttachment':
                $path = '/incidence/'.$id['idIncidence'].'/attachment/'.$id['idAttachment'].'/download';
            break;

            case 'getIncidencesGroupedByStatus':
                $path = '/incidence/statistic/groupedByStatus';
            break;

            case 'getIncidencesGroupedByPriorities':
                $path = '/incidence/statistic/groupedByPriorities';
            break;

            case 'getIncidencesGroupedByGroups':
                $path = '/incidence/statistic/groupedByGroups';
            break;

            case 'getIncidencesGroupedByOwners':
                $path = '/incidence/statistic/groupedByOwners';
            break;

            case 'listCustomSearch':
                $path = '/customSearch/list';
            break;

            case 'customSearch':
                $path = '/customSearch/'.$id;
            break;

            case 'inventory':
                $path = '/inventory/'.$id;
            break;

            case 'createNode':
                $path = '/pandorafms/nodes';
            break;

            case 'getNode':
                $path = '/pandorafms/node/'.$id;
            break;

            case 'pingItsmToPandora':
                $path = '/pandorafms/node/ping';
            break;

            default:
                // Not posible.
            break;
        }

        if (empty($queryParams) === false) {
            if (isset($queryParams['field']) === true) {
                $queryParams['sortField'] = $queryParams['field'];
                unset($queryParams['field']);
            }

            if (isset($queryParams['direction']) === true) {
                $queryParams['sortDirection'] = $queryParams['direction'];
                unset($queryParams['direction']);
            }

            $path .= '?';
            $path .= http_build_query($queryParams);
        }

        return $path;
    }


    /**
     * Ping API.
     *
     * @return boolean Data incidence
     */
    public function ping(): bool
    {
        $result = $this->callApi(
            'ping',
            [],
            [],
            null,
            'GET'
        );

        return $result['valid'];
    }


    /**
     * Get Groups.
     *
     * @return array Return mode select.
     */
    public function getGroups(): array
    {
        $listGroups = $this->callApi('listGroups');
        $result = [];
        foreach ($listGroups['data'] as $group) {
            if ($group['idGroup'] > 1) {
                $result[$group['idGroup']] = $group['name'];
            }
        }

        return $result;
    }


    /**
     * Get Priorities.
     *
     * @return array Return mode select.
     */
    public function getPriorities(): array
    {
        $listPriorities = $this->callApi('listPriorities');
        return $listPriorities;
    }


    /**
     * Get Status.
     *
     * @return array Return mode select.
     */
    public function getStatus(): array
    {
        $listStatus = $this->callApi('listStatus');
        $result = [];
        foreach ($listStatus['data'] as $status) {
            $result[$status['idIncidenceStatus']] = $status['name'];
        }

        return $result;
    }


    /**
     * Get Incidences types.
     *
     * @return array Return mode select.
     */
    public function getObjectypes(): array
    {
        $listObjectTypes = $this->callApi('listObjectTypes');
        $result = [];
        foreach ($listObjectTypes['data'] as $objectType) {
            $result[$objectType['idIncidenceType']] = $objectType['name'];
        }

        return $result;
    }


    /**
     * Get fields incidence type.
     *
     * @param integer $idIncidenceType Incidence Type ID.
     *
     * @return array Fields array.
     */
    public function getObjecTypesFields(int $idIncidenceType): array
    {
        $result = $this->callApi(
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

        return $result['data'];
    }


    /**
     * List custom search.
     *
     * @return array Result.
     */
    public function listCustomSearch(): array
    {
        $listCustomSearch = $this->callApi(
            'listCustomSearch',
            [
                'page'     => 0,
                'sizePage' => 0,
            ],
            ['section' => 'incidences']
        );

        $result = [];
        foreach ($listCustomSearch['data'] as $customSearch) {
            $result[$customSearch['idCustomSearch']] = $customSearch['name'];
        }

        return $result;
    }


    /**
     * Get Custom search.
     *
     * @param integer $idCustomSearch Custom search ID.
     *
     * @return array Data custom search.
     */
    public function getCustomSearch(int $idCustomSearch): array
    {
        $result = $this->callApi(
            'customSearch',
            [],
            [],
            $idCustomSearch,
            'GET'
        );

        return $result;
    }


    /**
     * List incidences.
     *
     * @param integer $idAgent Agent id.
     *
     * @return array list Incidences.
     */
    public function listIncidenceAgents(int $idAgent, ?bool $blocked=null): array
    {
        global $config;
        $listIncidences = $this->callApi(
            'listTickets',
            [
                'page'     => 0,
                'sizePage' => 0,
            ],
            [
                'externalIdLike' => $config['metaconsole_node_id'].'-'.$idAgent,
                'blocked'        => $blocked,
            ]
        );

        return $listIncidences['data'];
    }


    /**
     * Get table incicidences for agent.
     *
     * @param integer      $idAgent Id agent.
     * @param boolean|null $mini    Visual mode mini.
     * @param integer|null $blocked Blocked.
     *
     * @return string Html output.
     */
    public function getTableIncidencesForAgent(int $idAgent, ?bool $mini=false, ?int $blocked=null)
    {
        \ui_require_css_file('pandoraitsm');
        \ui_require_javascript_file('ITSM');

        global $config;
        $columns = [
            'idIncidence',
            'title',
            'groupCompany',
            'statusResolution',
            'priority',
            'updateDate',
            'startDate',
            'idCreator',
            'owner',
        ];

        $column_names = [
            __('ID'),
            __('Title'),
            __('Group').'/'.__('Company'),
            __('Status').'/'.__('Resolution'),
            __('Priority'),
            __('Updated'),
            __('Started'),
            __('Creator'),
            __('Owner'),
        ];

        $options = [
            'id'                  => 'itms_list_tickets',
            'class'               => 'info_table',
            'style'               => 'width: 99%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => 'operation/ITSM/itsm',
            'ajax_data'           => [
                'method'         => 'getListTickets',
                'externalIdLike' => $config['metaconsole_node_id'].'-'.$idAgent,
                'blocked'        => $blocked,
            ],
            'no_sortable_columns' => [
                2,
                3,
                -1,
            ],
            'order'               => [
                'field'     => 'updateDate',
                'direction' => 'desc',
            ],
            'return'              => true,
        ];

        if ($mini === true) {
            $options['csv'] = 0;
            $options['dom_elements'] = 'frtip';
        }

        return ui_print_datatable($options);
    }


    /**
     * Create Node in pandora ITSM.
     *
     * @param array $data Info connect to node from ITSM.
     *
     * @return boolean
     */
    public function createNode(array $data): array
    {
        return $this->callApi('createNode', null, $data);
    }


    /**
     * Get info node sincronization.
     *
     * @param string $serverAuth Server Auth.
     *
     * @return array Array.
     */
    public function getNode(string $serverAuth): array
    {
        $result = $this->callApi(
            'getNode',
            [],
            [],
            $serverAuth,
            'GET'
        );

        return $result;
    }


    /**
     * Ping Itsm to pandora node.
     *
     * @param string $path Path.
     *
     * @return boolean
     */
    public function pingItsmtoPandora(string $path): bool
    {
        global $config;

        $result = $this->callApi(
            'pingItsmToPandora',
            [],
            [
                'path'       => $path,
                'apiPass'    => $config['api_password'],
                'serverAuth' => $config['server_unique_identifier'],
            ]
        );

        return (bool) $result['valid'];
    }


}
