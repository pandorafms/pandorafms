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
     */
    public function __construct()
    {
        global $config;
        $user_info = \users_get_user_by_id($config['id_user']);

        $this->userLevelConf = (bool) $config['integria_user_level_conf'];
        $this->url = $config['integria_hostname'];

        $this->userBearer = $config['integria_pass'];
        if ($this->userLevelConf === true) {
            $this->userBearer = $user_info['integria_user_level_pass'];
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

            case 'deleteIncidenceAttachment':
                $path = '/incidence/'.$id['idIncidence'].'/attachment/'.$id['idAttachment'];
            break;

            case 'downloadIncidenceAttachment':
                $path = '/incidence/'.$id['idIncidence'].'/attachment/'.$id['idAttachment'].'/download';
            break;

            default:
                // Not posible.
            break;
        }

        if (empty($queryParams) === false) {
            if (isset($queryParams['direction']) === true) {
                $queryParams['sortDirection'] = $queryParams['direction'];
                unset($queryParams['direction']);
            }

            if (isset($queryParams['field']) === true) {
                $queryParams['sortField'] = $queryParams['field'];
                unset($queryParams['field']);
            }

            $path .= '?';
            $path .= http_build_query($queryParams);
        }

        return $path;
    }


}
