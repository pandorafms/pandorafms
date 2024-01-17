<?php

namespace PandoraFMS\Modules\Profiles\Entities;

use PandoraFMS\Modules\Shared\Builders\Builder;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\MappeableInterface;
use PandoraFMS\Modules\Shared\Repositories\Repository;

final class ProfileDataMapper extends DataMapperAbstract
{
    public const TABLE_NAME = 'tperfil';
    public const ID_PROFILE = 'id_perfil';
    public const NAME = 'name';
    public const IS_AGENT_VIEW = 'agent_view';
    public const IS_AGENT_EDIT = 'agent_edit';
    public const IS_ALERT_EDIT = 'alert_edit';
    public const IS_USER_MANAGEMENT = 'user_management';
    public const IS_DB_MANAGEMENT = 'db_management';
    public const IS_ALERT_MANAGEMENT = 'alert_management';
    public const IS_PANDORA_MANAGEMENT = 'pandora_management';
    public const IS_REPORT_VIEW = 'report_view';
    public const IS_REPORT_EDIT = 'report_edit';
    public const IS_REPORT_MANAGEMENT = 'report_management';
    public const IS_EVENT_VIEW = 'event_view';
    public const IS_EVENT_EDIT = 'event_edit';
    public const IS_EVENT_MANAGEMENT = 'event_management';
    public const IS_AGENT_DISABLE = 'agent_disable';
    public const IS_MAP_VIEW = 'map_view';
    public const IS_MAP_EDIT = 'map_edit';
    public const IS_MAP_MANAGEMENT = 'map_management';
    public const IS_VCONSOLE_VIEW = 'vconsole_view';
    public const IS_VCONSOLE_EDIT = 'vconsole_edit';
    public const IS_VCONSOLE_MANAGEMENT = 'vconsole_management';
    public const IS_NETWORK_CONFIG_VIEW = 'network_config_view';
    public const IS_NETWORK_CONFIG_EDIT = 'network_config_edit';
    public const IS_NETWORK_CONFIG_MANAGEMENT = 'network_config_management';

    public function __construct(
        private Repository $repository,
        private Builder $builder,
    ) {
        parent::__construct(
            self::TABLE_NAME,
            self::ID_PROFILE,
        );
    }

    public function getClassName(): string
    {
        return Profile::class;
    }

    public function fromDatabase(array $data): Profile
    {
        return $this->builder->build(new Profile(), [
            'idProfile'                 => $data[self::ID_PROFILE],
            'name'                      => $this->repository->safeOutput($data[self::NAME]),
            'isAgentView'               => $data[self::IS_AGENT_VIEW],
            'isAgentEdit'               => $data[self::IS_AGENT_EDIT],
            'isAlertEdit'               => $data[self::IS_ALERT_EDIT],
            'isUserManagement'          => $data[self::IS_USER_MANAGEMENT],
            'isDbManagement'            => $data[self::IS_DB_MANAGEMENT],
            'isAlertManagement'         => $data[self::IS_ALERT_MANAGEMENT],
            'isPandoraManagement'       => $data[self::IS_PANDORA_MANAGEMENT],
            'isReportView'              => $data[self::IS_REPORT_VIEW],
            'isReportEdit'              => $data[self::IS_REPORT_EDIT],
            'isReportManagement'        => $data[self::IS_REPORT_MANAGEMENT],
            'isEventView'               => $data[self::IS_EVENT_VIEW],
            'isEventEdit'               => $data[self::IS_EVENT_EDIT],
            'isEventManagement'         => $data[self::IS_EVENT_MANAGEMENT],
            'isAgentDisable'            => $data[self::IS_AGENT_DISABLE],
            'isMapView'                 => $data[self::IS_MAP_VIEW],
            'isMapEdit'                 => $data[self::IS_MAP_EDIT],
            'isMapManagement'           => $data[self::IS_MAP_MANAGEMENT],
            'isVconsoleView'            => $data[self::IS_VCONSOLE_VIEW],
            'isVconsoleEdit'            => $data[self::IS_VCONSOLE_EDIT],
            'isVconsoleManagement'      => $data[self::IS_VCONSOLE_MANAGEMENT],
            'isNetworkConfigView'       => $data[self::IS_NETWORK_CONFIG_VIEW],
            'isNetworkConfigEdit'       => $data[self::IS_NETWORK_CONFIG_EDIT],
            'isNetworkConfigManagement' => $data[self::IS_NETWORK_CONFIG_MANAGEMENT],
        ]);
    }

    public function toDatabase(MappeableInterface $data): array
    {
        /** @var Profile $data */
        return [
            self::ID_PROFILE                   => $data->getIdProfile(),
            self::NAME                         => $this->repository->safeInput($data->getName()),
            self::IS_AGENT_VIEW                => $data->getIsAgentView(),
            self::IS_AGENT_EDIT                => $data->getIsAgentEdit(),
            self::IS_ALERT_EDIT                => $data->getIsAlertEdit(),
            self::IS_USER_MANAGEMENT           => $data->getIsUserManagement(),
            self::IS_DB_MANAGEMENT             => $data->getIsDbManagement(),
            self::IS_ALERT_MANAGEMENT          => $data->getIsAlertManagement(),
            self::IS_PANDORA_MANAGEMENT        => $data->getIsPandoraManagement(),
            self::IS_REPORT_VIEW               => $data->getIsReportView(),
            self::IS_REPORT_EDIT               => $data->getIsReportEdit(),
            self::IS_REPORT_MANAGEMENT         => $data->getIsReportManagement(),
            self::IS_EVENT_VIEW                => $data->getIsEventView(),
            self::IS_EVENT_EDIT                => $data->getIsEventEdit(),
            self::IS_EVENT_MANAGEMENT          => $data->getIsEventManagement(),
            self::IS_AGENT_DISABLE             => $data->getIsAgentDisable(),
            self::IS_MAP_VIEW                  => $data->getIsMapView(),
            self::IS_MAP_EDIT                  => $data->getIsMapEdit(),
            self::IS_MAP_MANAGEMENT            => $data->getIsMapManagement(),
            self::IS_VCONSOLE_VIEW             => $data->getIsVconsoleView(),
            self::IS_VCONSOLE_EDIT             => $data->getIsVconsoleEdit(),
            self::IS_VCONSOLE_MANAGEMENT       => $data->getIsVconsoleManagement(),
            self::IS_NETWORK_CONFIG_VIEW       => $data->getIsNetworkConfigView(),
            self::IS_NETWORK_CONFIG_EDIT       => $data->getIsNetworkConfigEdit(),
            self::IS_NETWORK_CONFIG_MANAGEMENT => $data->getIsNetworkConfigManagement(),
        ];
    }
}
