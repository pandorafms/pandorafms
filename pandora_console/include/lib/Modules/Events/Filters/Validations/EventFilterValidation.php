<?php

namespace PandoraFMS\Modules\Events\Filters\Validations;

use PandoraFMS\Agent;
use PandoraFMS\Module;
use PandoraFMS\Modules\Events\Enums\EventSeverityEnum;
use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterAlertEnum;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterGroupByEnum;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterStatusEnum;
use PandoraFMS\Modules\Events\Filters\Services\ExistNameEventFilterService;
use PandoraFMS\Modules\Groups\Services\GetGroupService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Tags\Services\GetTagService;
use PandoraFMS\Modules\Users\Services\GetUserService;

final class EventFilterValidation
{
    public function __construct(
        private ExistNameEventFilterService $existNameEventFilterService,
        private GetUserService $getUserService,
        private GetGroupService $getGroupService,
        private GetTagService $getTagService
    ) {
    }

    public function __invoke(EventFilter $eventFilter, ?EventFilter $oldEventFilter = null): void
    {
        if (!$eventFilter->getName()) {
            throw new BadRequestException(__('Name is missing'));
        }

        if($oldEventFilter === null || $oldEventFilter->getName() !== $eventFilter->getName()) {
            if($this->existNameEventFilterService->__invoke($eventFilter->getName()) === true) {
                throw new BadRequestException(
                    __('Name %s is already exists', $eventFilter->getName())
                );
            }
        }

        if ($eventFilter->getIdGroupFilter() === null) {
            $eventFilter->setIdGroupFilter(0);
        }

        if ($eventFilter->getIdGroup() === null) {
            $eventFilter->setIdGroup(0);
        }

        if ($eventFilter->getStatus() === null) {
            $eventFilter->setStatus(EventFilterStatusEnum::ALL);
        }

        if ($eventFilter->getIsNotSearch() === null) {
            $eventFilter->setIsNotSearch(false);
        }

        if ($eventFilter->getPagination() === null) {
            $eventFilter->setPagination(0);
        }

        if ($eventFilter->getSlice() === null) {
            $eventFilter->setSlice(0);
        }

        if ($eventFilter->getGroupBy() === null) {
            $eventFilter->setGroupBy(EventFilterGroupByEnum::ALL);
        }

        if ($eventFilter->getFilterOnlyAlert() === null) {
            $eventFilter->setFilterOnlyAlert(EventFilterAlertEnum::ALL);
        }

        if ($eventFilter->getSearchSecondaryGroups() === null) {
            $eventFilter->setSearchSecondaryGroups(false);
        }

        if ($eventFilter->getSearchRecursiveGroups() === null) {
            $eventFilter->setSearchRecursiveGroups(false);
        }

        if ($eventFilter->getTagWith() === null) {
            $eventFilter->setTagWith([]);
        }

        if ($eventFilter->getTagWithout() === null) {
            $eventFilter->setTagWithout([]);
        }

        if (empty($eventFilter->getIdUserAck()) === false) {
            $this->validateUser($eventFilter->getIdUserAck());
        }

        if (empty($eventFilter->getOwnerUser()) === false) {
            $this->validateUser($eventFilter->getOwnerUser());
        }

        if (empty($eventFilter->getIdGroup()) === false) {
            $this->validateGroup($eventFilter->getIdGroup());
        }

        if (empty($eventFilter->getIdGroupFilter()) === false) {
            $this->validateGroup($eventFilter->getIdGroupFilter());
        }

        if (empty($eventFilter->getTagWith()) === false) {
            $this->validateTags($eventFilter->getTagWith());
        }

        if (empty($eventFilter->getTagWithout()) === false) {
            $this->validateTags($eventFilter->getTagWithout());
        }

        if (empty($eventFilter->getIdAgent()) === false) {
            $this->validateAgent($eventFilter->getIdAgent());
        }

        if (empty($eventFilter->getIdAgentModule()) === false) {
            $this->validateAgentModule(
                $eventFilter->getIdAgentModule(),
                $eventFilter->getIdAgent()
            );
        }

        if (empty($eventFilter->getServerId()) === false) {
            $this->validateNodes($eventFilter->getServerId());
        }

        if (empty($eventFilter->getSeverity()) === false) {
            $this->validateSeverities($eventFilter->getSeverity());
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

    protected function validateTags(array $tags): void
    {
        foreach ($tags as $tag) {
            $this->getTagService->__invoke((int) $tag);
        }
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

    protected function validateNodes(array $nodes): void
    {
        // TODO: create new service for this.
    }

    protected function validateSeverities(array $severities): void
    {
        foreach ($severities as $severity) {
            if($severity !== null) {
                $result = EventSeverityEnum::get(strtoupper($severity));
                if (empty($result) === true) {
                    throw new BadRequestException(__('Invalid severity: %s', $severity));
                }
            }
        }
    }
}
