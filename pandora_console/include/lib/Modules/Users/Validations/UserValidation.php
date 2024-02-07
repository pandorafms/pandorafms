<?php

namespace PandoraFMS\Modules\Users\Validations;

use Models\VisualConsole\Container as VisualConsole;
use PandoraFMS\Modules\Events\Filters\Services\GetEventFilterService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Exceptions\ForbiddenACLException;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Shared\Services\Timestamp;
use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Enums\UserHomeScreenEnum;
use PandoraFMS\Modules\Users\Services\CheckOldPasswordUserService;
use PandoraFMS\Modules\Users\Services\ExistIdUserService;
use PandoraFMS\Modules\Users\Services\GetUserService;
use PandoraFMS\Modules\Users\Services\ValidatePasswordUserService;

final class UserValidation
{
    public function __construct(
        private Config $config,
        private Timestamp $timestamp,
        private GetUserService $getUserService,
        private ExistIdUserService $existIdUserService,
        private CheckOldPasswordUserService $checkOldPasswordUserService,
        private ValidatePasswordUserService $validatePasswordUserService,
        private GetEventFilterService $getEventFilterService
    ) {
    }

    public function __invoke(User $user, ?User $oldUser = null): void
    {
        $isAdmin = $this->isAdmin($this->config->get('id_user'));
        $this->validateIdUser($user);

        if ($oldUser === null || $oldUser->getIdUser() !== $user->getIdUser()) {
            if($this->existIdUserService->__invoke($user->getIdUser()) === true) {
                throw new BadRequestException(
                    __('Id user %s is already exists', $user->getIdUser())
                );
            }
        }

        if ($isAdmin === false && $user->getIsAdmin() === true) {
            throw new ForbiddenACLException(__('User by non administrator user'));
        }

        if ($oldUser === null) {
            $this->existsUser($user->getIdUser());
        } else {
            if ($user->getIdUser() !== $oldUser->getIdUser()) {
                throw new BadRequestException(__('idUser cannot be updated'));
            }
        }

        if (empty($user->getFullName()) === true) {
            throw new BadRequestException(__('FullName is missing'));
        }

        if (empty($user->getPassword()) === true) {
            throw new BadRequestException(__('Password is missing'));
        }

        if ($oldUser === null) {
            $user->setRegistered($this->getCurrentUtimestamp());
            $user->setApiToken($this->generateApiToken());
        }

        if ($user->getFirstName() === null) {
            $user->setFirstName('');
        }

        if ($user->getLastName() === null) {
            $user->setLastName('');
        }

        if ($user->getMiddleName() === null) {
            $user->setMiddleName('');
        }

        if ($user->getIdSkin() === null) {
            $user->setIdSkin(0);
        }

        if ($user->getFailedAttempt() === null) {
            $user->setFailedAttempt(0);
        }

        if ($user->getMetaconsoleAgentsManager() === null) {
            $user->setMetaconsoleAgentsManager(0);
        }

        if ($user->getMetaconsoleAccessNode() === null) {
            $user->setMetaconsoleAccessNode(0);
        }

        if ($user->getSessionTime() === null) {
            $user->setSessionTime(0);
        }

        if ($user->getDefaultEventFilter() === null) {
            $user->setDefaultEventFilter(0);
        }

        if ($user->getMetaconsoleDefaultEventFilter() === null) {
            $user->setMetaconsoleDefaultEventFilter(0);
        }

        if ($user->getTimeAutorefresh() === null) {
            $user->setTimeAutorefresh(0);
        }

        if ($user->getSessionMaxTimeExpire() === null) {
            $user->setSessionMaxTimeExpire(0);
        }

        if ($user->getLastConnect() === null) {
            $user->setLastConnect(0);
        }

        if ($user->getIsAdmin() === null) {
            $user->setIsAdmin(false);
        }

        if ($user->getBlockSize() === null) {
            $user->setBlockSize(20);
        }

        if ($user->getDisabled() === null) {
            $user->setDisabled(false);
        }

        if ($user->getSection() === null) {
            $user->setSection(UserHomeScreenEnum::DEFAULT);
        }

        if ($user->getDataSection() === null) {
            $user->setDataSection('');
        }

        if ($user->getMetaconsoleSection() === null) {
            $user->setMetaconsoleSection(UserHomeScreenEnum::DEFAULT);
        }

        if ($user->getMetaconsoleDataSection() === null) {
            $user->setMetaconsoleDataSection('');
        }

        if ($user->getForceChangePass() === null) {
            $user->setForceChangePass(false);
        }

        if ($user->getLoginBlocked() === null) {
            $user->setLoginBlocked(false);
        }

        if ($user->getNotLogin() === null) {
            $user->setNotLogin(false);
        }

        if ($user->getLocalUser() === null) {
            $user->setLocalUser(false);
        }

        if ($user->getStrictAcl() === null) {
            $user->setStrictAcl(false);
        }

        if ($user->getShowTipsStartup() === null) {
            $user->setShowTipsStartup(false);
        }

        if ($oldUser === null || $user->getPassword() !== $oldUser->getPassword()) {
            if (empty($user->getPasswordValidate()) === true) {
                throw new BadRequestException(__('PasswordValidate is missing'));
            }

            if ($user->getPassword() !== $user->getPasswordValidate()) {
                throw new BadRequestException(__('Password and PasswordValidate not equal'));
            }

            if (\enterprise_installed() === true) {
                $this->validatePasswordUserService->__invoke($user, $oldUser);
            }

            // Only administrator users will not have to confirm the old password.
            if (($oldUser !== null) && $isAdmin === false) {
                if (empty($user->getOldPassword()) === true) {
                    throw new BadRequestException(__('The old password is required to be able to update the password'));
                }

                $this->checkOldPasswordUserService->__invoke($user);
            }

            $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));
            if ($oldUser !== null) {
                $user->setLastPassChange($this->getCurrentTimestamp());
            }
        }

        if (empty($user->getIdSkin()) === false) {
            $this->validateSkin($user->getIdSkin());
        }

        if (empty($user->getDefaultEventFilter()) === false) {
            $this->validateEventFilter($user->getDefaultEventFilter());
        }

        if (empty($user->getMetaconsoleDefaultEventFilter()) === false) {
            $this->validateEventFilter($user->getMetaconsoleDefaultEventFilter());
        }

        if (empty($user->getDefaultCustomView()) === false) {
            $this->validateCustomView($user->getDefaultCustomView());
        }

        if ($user->getSection() === UserHomeScreenEnum::DEFAULT
            || $user->getSection() === UserHomeScreenEnum::EVENT_LIST
            || $user->getSection() === UserHomeScreenEnum::TACTICAL_VIEW
            || $user->getSection() === UserHomeScreenEnum::ALERT_DETAIL
            || $user->getSection() === UserHomeScreenEnum::GROUP_VIEW
        ) {
            $user->setDataSection('');
        } else {
            if (empty($user->getDataSection()) === true) {
                throw new BadRequestException(
                    __(
                        'Section data of type %s, cannot be empty',
                        $user->getSection()->name
                    )
                );
            }

            if ($user->getSection() === UserHomeScreenEnum::VISUAL_CONSOLE) {
                $this->validateVisualConsole((int) $user->getDataSection());
            }

            if ($user->getSection() === UserHomeScreenEnum::DASHBOARD) {
                $this->validateDashboard($this->config->get('id_user'), $user->getDataSection());
            }
        }

        if ($user->getMetaconsoleSection() === UserHomeScreenEnum::DEFAULT
            || $user->getMetaconsoleSection() === UserHomeScreenEnum::EVENT_LIST
            || $user->getMetaconsoleSection() === UserHomeScreenEnum::TACTICAL_VIEW
            || $user->getMetaconsoleSection() === UserHomeScreenEnum::ALERT_DETAIL
            || $user->getMetaconsoleSection() === UserHomeScreenEnum::GROUP_VIEW
        ) {
            $user->setMetaconsoleDataSection('');
        } else {
            if (empty($user->getMetaconsoleDataSection()) === true) {
                throw new BadRequestException(
                    __(
                        'Metaconsole section data of type %s, cannot be empty',
                        $user->getMetaconsoleSection()->name
                    )
                );
            }

            if ($user->getMetaconsoleSection() === UserHomeScreenEnum::VISUAL_CONSOLE) {
                $this->validateVisualConsole((int) $user->getMetaconsoleDataSection());
            }

            if ($user->getMetaconsoleSection() === UserHomeScreenEnum::DASHBOARD) {
                $this->validateDashboard($this->config->get('id_user'), $user->getMetaconsoleDataSection());
            }
        }
    }

    private function getCurrentTimestamp(): string
    {
        return $this->timestamp->getMysqlCurrentTimestamp(0);
    }

    private function getCurrentUtimestamp(): int
    {
        return $this->timestamp->getMysqlSystemUtimestamp();
    }

    private function existsUser(string $idUser): void
    {
        $exist = true;
        try {
            $this->getUserService->__invoke($idUser);
        } catch (NotFoundException) {
            $exist = false;
        }

        if ($exist === true) {
            throw new BadRequestException(__('User %s already exists.', $idUser));
        }
    }

    private function validateIdUser(User $user): void
    {
        if ($user->getIdUser() === false) {
            throw new BadRequestException(__('idUser is missing'));
        }

        // Cannot have blanks.
        if (preg_match('/^\S.*\s.*\S$/', $user->getIdUser())) {
            throw new BadRequestException(
                __('IdUser %s is not a valid format, cannot have blanks', $user->getIdUser())
            );
        }
    }

    private function generateApiToken(): string
    {
        // TODO: create new service for this.
        return \api_token_generate();
    }

    private function isAdmin(string $idUser): bool
    {
        // TODO: create new service for this.
        return \users_is_admin($idUser);
    }

    protected function validateSkin(int $idSkin): void
    {
        // TODO: create new service for this.
        if (! (bool) \skins_search_skin_id($idSkin)) {
            throw new BadRequestException(__('Invalid id skin'));
        }
    }

    protected function validateEventFilter(int $idFilter): void
    {
        $this->getEventFilterService->__invoke($idFilter);
    }

    protected function validateCustomView(int $idView): void
    {
        // TODO: create new service for this.
        if (! (bool) \get_filters_custom_fields_view($idView)) {
            throw new BadRequestException(__('Invalid custom view'));
        }
    }

    protected function validateDashboard(string $idUser, int $idDashboard): void
    {
        // TODO: create new service for this.
        if (! (bool) \get_user_dashboards($idUser, $idDashboard)) {
            throw new BadRequestException(__('Invalid id Dashboard'));
        }
    }

    protected function validateVisualConsole(int $visualConsoleId): void
    {
        // TODO: create new service for this.
        try {
            VisualConsole::fromDB(['id' => $visualConsoleId]);
        } catch (\Throwable $e) {
            throw new BadRequestException(__('Invalid visual console id'));
        }
    }
}
