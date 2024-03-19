<?php

namespace PandoraFMS\Modules\Events\Validations;

use PandoraFMS\Agent;
use PandoraFMS\Module;
use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Enums\EventSeverityEnum;
use PandoraFMS\Modules\Events\Enums\EventStatusEnum;
use PandoraFMS\Modules\Groups\Services\GetGroupService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Shared\Services\Timestamp;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
use PandoraFMS\Modules\Users\Services\GetUserService;

final class EventValidation
{
    public function __construct(
        private ValidateAclSystem $acl,
        private Config $config,
        private Timestamp $timestamp,
        private GetUserService $getUserService,
        private GetGroupService $getGroupService
    ) {
    }

    public function __invoke(Event $event, ?Event $oldEvent = null): void
    {
        if (!$event->getEvent()) {
            throw new BadRequestException(__('Event is missing'));
        }

        if (!$event->getIdGroup() && $event->getIdGroup() !== 0) {
            throw new BadRequestException(__('Id group is missing'));
        }

        if (empty($event->getIdGroup()) === false) {
            $this->validateGroup($event->getIdGroup());
            $this->acl->validate($event->getIdGroup(), 'AR', ' tried to read group');
        }

        if ($event->getIdAgent() === null) {
            $event->setIdAgent(0);
        }

        if (empty($event->getIdAgent()) === false) {
            $this->validateAgent($event->getIdAgent());
        }

        if ($event->getIdAgentModule() === null) {
            $event->setIdAgentModule(0);
        }

        if (empty($event->getIdAgentModule()) === false) {
            $this->validateAgentModule($event->getIdAgentModule(), $event->getIdAgent());
        }

        if ($event->getIdUser() === null) {
            $event->setIdUser($this->config->get('id_user'));
        }

        if (empty($event->getIdUser()) === false) {
            $this->validateUser($event->getIdUser());
        }

        if ($event->getStatus() === null) {
            $event->setStatus(EventStatusEnum::NEW);
        }

        if ($oldEvent === null) {
            $event->setTimestamp($this->getCurrentTimestamp());
            $event->setUtimestamp($this->getCurrentUtimestamp());
        }

        if ($event->getIdAlertAm() === null) {
            $event->setIdAlertAm(0);
        }

        if ($event->getSeverity() === null) {
            $event->setSeverity(EventSeverityEnum::MAINTENANCE);
        }

        if ($event->getOwnerUser() === null) {
            $event->setOwnerUser('');
        }

        if ($event->getAckUtimestamp() === null) {
            $event->setAckUtimestamp(0);
            if ($event->getStatus() === EventStatusEnum::VALIDATED
                || $event->getStatus() === EventStatusEnum::INPROCESS
            ) {
                $event->setAckUtimestamp($this->getCurrentUtimestamp());
            }
        }

        if ($event->getModuleStatus() === null) {
            $event->setModuleStatus(0);
        }

        if ($event->getSource() === null) {
            $event->setSource(\get_product_name());
        }
    }

    private function validateUser(string $idUser): void
    {
        $this->getUserService->__invoke($idUser);
    }

    protected function validateGroup(int $idGroup): void
    {
        $this->getGroupService->__invoke($idGroup);
    }

    protected function validateAgent(int $idAgent): void
    {
        // TODO: create new service for this.
        try {
            new Agent($idAgent);
        } catch (\Exception $e) {
            throw new BadRequestException(
                __('Invalid id agent: %s, %s', $idAgent, $e->getMessage())
            );
        }
    }

    protected function validateAgentModule(int $idAgentModule, ?int $idAgent = 0): void
    {
        // TODO: create new service for this.
        if(empty($idAgent) === false) {
            $agent = new Agent($idAgent);
            $existModule = $agent->searchModules(
                ['id_agente_modulo' => $idAgentModule],
                1
            );

            if (empty($existModule) === true) {
                throw new BadRequestException(
                    __(
                        'Id agent module: %s not exist in agent %s',
                        $idAgentModule,
                        io_safe_output($agent->alias())
                    )
                );
            }
        } else {
            try {
                new Module($idAgentModule);
            } catch (\Exception $e) {
                throw new BadRequestException(
                    __('Invalid id agent module, %s', $e->getMessage())
                );
            }
        }
    }

    protected function validateAlert(int $idAlert): void
    {
        // TODO: create new service for this.
        if (! (bool) \alerts_get_alert_agent_module($idAlert)) {
            throw new BadRequestException(__('Invalid id Alert template'));
        }
    }

    protected function getCurrentTimestamp(): string
    {
        return $this->timestamp->getMysqlCurrentTimestamp(0);
    }

    protected function getCurrentUtimestamp(): int
    {
        return $this->timestamp->getMysqlSystemUtimestamp();
    }
}
