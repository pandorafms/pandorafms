<?php

namespace PandoraFMS\Modules\Users\Validations;

use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Exceptions\ForbiddenACLException;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Shared\Services\Timestamp;
use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Enums\UserHomeScreenEnum;
use PandoraFMS\Modules\Users\Services\CheckOldPasswordUserService;
use PandoraFMS\Modules\Users\Services\GetUserService;

final class UserValidation
{


    public function __construct(
        private Config $config,
        private Timestamp $timestamp,
        private GetUserService $getUserService,
        private CheckOldPasswordUserService $checkOldPasswordUserService,
    ) {
    }


    public function __invoke(User $user, ?User $oldUser=null): void
    {
        $isAdmin = $this->isAdmin($this->config->get('id_user'));
        $this->validateIdUser($user);

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

        if ($user->getMetaconsoleSection() === null) {
            $user->setMetaconsoleSection(UserHomeScreenEnum::DEFAULT);
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
                $excludePassword = $this->checkExcludePassword($user->getPassword());
                if ($excludePassword === true) {
                    throw new BadRequestException(
                        __('The password provided is not valid. Please set another one.')
                    );
                }
            }

            // Only administrator users will not have to confirm the old password.
            if (($oldUser !== null) && $isAdmin === false) {
                if (empty($user->getOldPassword()) === true) {
                    throw new BadRequestException(__('The old password is required to be able to update the password'));
                }

                $this->checkOldPasswordUserService->__invoke($user);
            }

            // TODO: check validate pass.
            // if ((!is_user_admin($config['id_user']) || $config['enable_pass_policy_admin']) && $config['enable_pass_policy']) {
            // login_validate_pass
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


    private function checkExcludePassword(string $newPassword): bool
    {
        // TODO: create new service for this.
        $return = \enterprise_hook('excludedPassword', [$newPassword]);
        return $return;
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
        // TODO: create new service for this.
        if (! (bool) \events_get_event_filter($idFilter)) {
            throw new BadRequestException(__('Invalid filter search'));
        }
    }


    protected function validateCustomView(int $idView): void
    {
        // TODO: create new service for this.
        if (! (bool) \get_filters_custom_fields_view($idView)) {
            throw new BadRequestException(__('Invalid custom view'));
        }
    }


}
