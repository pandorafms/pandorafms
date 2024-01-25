<?php

namespace PandoraFMS\Modules\EventFilters\Validations;

use PandoraFMS\Modules\EventFilters\Entities\EventFilter;
use PandoraFMS\Modules\EventFilters\Enums\EventFilterAlertEnum;
use PandoraFMS\Modules\EventFilters\Enums\EventFilterGroupByEnum;
use PandoraFMS\Modules\EventFilters\Enums\EventFilterStatusEnum;
use PandoraFMS\Modules\EventFilters\Services\ExistNameEventFilterService;
use PandoraFMS\Modules\Events\Enums\EventSeverityEnum;
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
            $this->validateAgentModule($eventFilter->getIdAgentModule());
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
    }

    protected function validateAgentModule(int $idAgentModule): void
    {
        // TODO: create new service for this.
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
