<?php

namespace PandoraFMS\Modules\Users\Entities;

use PandoraFMS\Modules\Shared\Builders\Builder;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\MappeableInterface;
use PandoraFMS\Modules\Shared\Enums\LanguagesEnum;
use PandoraFMS\Modules\Shared\Repositories\Repository;
use PandoraFMS\Modules\Users\Enums\UserHomeScreenEnum;
use PandoraFMS\Modules\Users\Enums\UserMetaconsoleAccessEnum;

final class UserDataMapper extends DataMapperAbstract
{
    public const TABLE_NAME = 'tusuario';
    public const ID_USER = 'id_user';
    public const FULLNAME = 'fullname';
    public const FIRSTNAME = 'firstname';
    public const LASTNAME = 'lastname';
    public const MIDDLENAME = 'middlename';
    public const PASSWORD = 'password';
    public const COMMENTS = 'comments';
    public const LAST_CONNECT = 'last_connect';
    public const REGISTERED = 'registered';
    public const EMAIL = 'email';
    public const PHONE = 'phone';
    public const IS_ADMIN = 'is_admin';
    public const LANGUAGE = 'language';
    public const TIMEZONE = 'timezone';
    public const BLOCK_SIZE = 'block_size';
    public const ID_SKIN = 'id_skin';
    public const DISABLED = 'disabled';
    public const SHORTCUT = 'shortcut';
    public const SHORTCUT_DATA = 'shortcut_data';
    public const SECTION = 'section';
    public const DATA_SECTION = 'data_section';
    public const METACONSOLE_SECTION = 'metaconsole_section';
    public const METACONSOLE_DATA_SECTION = 'metaconsole_data_section';
    public const FORCE_CHANGE_PASS = 'force_change_pass';
    public const LAST_PASS_CHANGE = 'last_pass_change';
    public const LAST_FAILED_LOGIN = 'last_failed_login';
    public const FAILED_ATTEMPT = 'failed_attempt';
    public const LOGIN_BLOCKED = 'login_blocked';
    public const METACONSOLE_ACCESS = 'metaconsole_access';
    public const NOT_LOGIN = 'not_login';
    public const LOCAL_USER = 'local_user';
    public const METACONSOLE_AGENTS_MANAGER = 'metaconsole_agents_manager';
    public const METACONSOLE_ACCESS_NODE = 'metaconsole_access_node';
    public const STRICT_ACL = 'strict_acl';
    public const ID_FILTER = 'id_filter';
    public const SESSION_TIME = 'session_time';
    public const DEFAULT_EVENT_FILTER = 'default_event_filter';
    public const METACONSOLE_DEFAULT_EVENT_FILTER = 'metaconsole_default_event_filter';
    public const SHOW_TIPS_STARTUP = 'show_tips_startup';
    public const AUTOREFRESH_WHITE_LIST = 'autorefresh_white_list';
    public const TIME_AUTOREFRESH = 'time_autorefresh';
    public const DEFAULT_CUSTOM_VIEW = 'default_custom_view';
    public const EHORUS_USER_LEVEL_USER = 'ehorus_user_level_user';
    public const EHORUS_USER_LEVEL_PASS = 'ehorus_user_level_pass';
    public const EHORUS_USER_LEVEL_ENABLED = 'ehorus_user_level_enabled';
    public const INTEGRIA_USER_LEVEL_USER = 'integria_user_level_user';
    public const INTEGRIA_USER_LEVEL_PASS = 'integria_user_level_pass';
    public const API_TOKEN = 'api_token';
    public const ALLOWED_IP_ACTIVE = 'allowed_ip_active';
    public const ALLOWED_IP_LIST = 'allowed_ip_list';
    public const AUTH_TOKEN_SECRET = 'auth_token_secret';
    public const SESSION_MAX_TIME_EXPIRE = 'session_max_time_expire';

    public function __construct(
        private Repository $repository,
        private Builder $builder,
    ) {
        parent::__construct(
            self::TABLE_NAME,
            self::ID_USER,
        );
    }

    public function getClassName(): string
    {
        return User::class;
    }

    public function fromDatabase(array $data): User
    {
        return $this->builder->build(
            new User(),
            [
                'idUser'                        => $data[self::ID_USER],
                'fullName'                      => $this->repository->safeOutput($data[self::FULLNAME]),
                'firstName'                     => $this->repository->safeOutput($data[self::FIRSTNAME]),
                'lastName'                      => $this->repository->safeOutput($data[self::LASTNAME]),
                'middleName'                    => $this->repository->safeOutput($data[self::MIDDLENAME]),
                'password'                      => $data[self::PASSWORD],
                'comments'                      => $this->repository->safeOutput($data[self::COMMENTS]),
                'lastConnect'                   => $data[self::LAST_CONNECT],
                'registered'                    => $data[self::REGISTERED],
                'email'                         => $this->repository->safeOutput($data[self::EMAIL]),
                'phone'                         => $this->repository->safeOutput($data[self::PHONE]),
                'isAdmin'                       => $data[self::IS_ADMIN],
                'language'                      => LanguagesEnum::get($data[self::LANGUAGE]),
                'timezone'                      => $data[self::TIMEZONE],
                'blockSize'                     => $data[self::BLOCK_SIZE],
                'idSkin'                        => $data[self::ID_SKIN],
                'disabled'                      => $data[self::DISABLED],
                'shortcut'                      => $data[self::SHORTCUT],
                'shortcutData'                  => $this->repository->safeOutput($data[self::SHORTCUT_DATA]),
                'section'                       => UserHomeScreenEnum::get($data[self::SECTION]),
                'dataSection'                   => $this->repository->safeOutput($data[self::DATA_SECTION]),
                'metaconsoleSection'            => UserHomeScreenEnum::get($data[self::METACONSOLE_SECTION]),
                'metaconsoleDataSection'        => $this->repository->safeOutput($data[self::METACONSOLE_DATA_SECTION]),
                'forceChangePass'               => $data[self::FORCE_CHANGE_PASS],
                'lastPassChange'                => $data[self::LAST_PASS_CHANGE],
                'lastFailedLogin'               => $data[self::LAST_FAILED_LOGIN],
                'failedAttempt'                 => $data[self::FAILED_ATTEMPT],
                'loginBlocked'                  => $data[self::LOGIN_BLOCKED],
                'metaconsoleAccess'             => UserMetaconsoleAccessEnum::get($data[self::METACONSOLE_ACCESS]),
                'notLogin'                      => $data[self::NOT_LOGIN],
                'localUser'                     => $data[self::LOCAL_USER],
                'metaconsoleAgentsManager'      => $data[self::METACONSOLE_AGENTS_MANAGER],
                'metaconsoleAccessNode'         => $data[self::METACONSOLE_ACCESS_NODE],
                'strictAcl'                     => $data[self::STRICT_ACL],
                'idFilter'                      => $data[self::ID_FILTER],
                'sessionTime'                   => $data[self::SESSION_TIME],
                'defaultEventFilter'            => $data[self::DEFAULT_EVENT_FILTER],
                'metaconsoleDefaultEventFilter' => $data[self::METACONSOLE_DEFAULT_EVENT_FILTER],
                'showTipsStartup'               => $data[self::SHOW_TIPS_STARTUP],
                'autorefreshWhiteList'          => (empty($data[self::AUTOREFRESH_WHITE_LIST]) === false) ? json_decode($this->repository->safeOutput($data[self::AUTOREFRESH_WHITE_LIST])) : null,
                'timeAutorefresh'               => $data[self::TIME_AUTOREFRESH],
                'defaultCustomView'             => $data[self::DEFAULT_CUSTOM_VIEW],
                'ehorusUserLevelUser'           => $this->repository->safeOutput($data[self::EHORUS_USER_LEVEL_USER]),
                'ehorusUserLevelPass'           => $this->repository->safeOutput($data[self::EHORUS_USER_LEVEL_PASS]),
                'ehorusUserLevelEnabled'        => $data[self::EHORUS_USER_LEVEL_ENABLED],
                'itsmUserLevelUser'             => $this->repository->safeOutput($data[self::INTEGRIA_USER_LEVEL_USER]),
                'itsmUserLevelPass'             => $this->repository->safeOutput($data[self::INTEGRIA_USER_LEVEL_PASS]),
                'apiToken'                      => $data[self::API_TOKEN],
                'allowedIpActive'               => $data[self::ALLOWED_IP_ACTIVE],
                'allowedIpList'                 => $this->repository->safeOutput($data[self::ALLOWED_IP_LIST]),
                'authTokenSecret'               => $data[self::AUTH_TOKEN_SECRET],
                'sessionMaxTimeExpire'          => $data[self::SESSION_MAX_TIME_EXPIRE],
            ]
        );
    }

    /**
     * @param User $data
     */
    public function toDatabase(MappeableInterface $data): array
    {
        return [
            self::ID_USER                          => $data->getIdUser(),
            self::FULLNAME                         => $this->repository->safeInput($data->getFullName()),
            self::FIRSTNAME                        => $this->repository->safeInput($data->getFirstName()),
            self::LASTNAME                         => $this->repository->safeInput($data->getLastName()),
            self::MIDDLENAME                       => $this->repository->safeInput($data->getMiddleName()),
            self::PASSWORD                         => $data->getPassword(),
            self::COMMENTS                         => $this->repository->safeInput($data->getComments()),
            self::LAST_CONNECT                     => $data->getLastConnect(),
            self::REGISTERED                       => $data->getRegistered(),
            self::EMAIL                            => $this->repository->safeInput($data->getEmail()),
            self::PHONE                            => $this->repository->safeInput($data->getPhone()),
            self::IS_ADMIN                         => $data->getIsAdmin(),
            self::LANGUAGE                         => $data->getLanguage()?->value,
            self::TIMEZONE                         => $data->getTimezone(),
            self::BLOCK_SIZE                       => $data->getBlockSize(),
            self::ID_SKIN                          => $data->getIdSkin(),
            self::DISABLED                         => $data->getDisabled(),
            self::SHORTCUT                         => $data->getShortcut(),
            self::SHORTCUT_DATA                    => $this->repository->safeInput($data->getShortcutData()),
            self::SECTION                          => $data->getSection()?->value,
            self::DATA_SECTION                     => $this->repository->safeInput($data->getDataSection()),
            self::METACONSOLE_SECTION              => $data->getMetaconsoleSection()?->value,
            self::METACONSOLE_DATA_SECTION         => $this->repository->safeInput($data->getMetaconsoleDataSection()),
            self::FORCE_CHANGE_PASS                => $data->getForceChangePass(),
            self::LAST_PASS_CHANGE                 => $data->getLastPassChange(),
            self::LAST_FAILED_LOGIN                => $data->getLastFailedLogin(),
            self::FAILED_ATTEMPT                   => $data->getFailedAttempt(),
            self::LOGIN_BLOCKED                    => $data->getLoginBlocked(),
            self::METACONSOLE_ACCESS               => $data->getMetaconsoleAccess()?->value,
            self::NOT_LOGIN                        => $data->getNotLogin(),
            self::LOCAL_USER                       => $data->getLocalUser(),
            self::METACONSOLE_AGENTS_MANAGER       => $data->getMetaconsoleAgentsManager(),
            self::METACONSOLE_ACCESS_NODE          => $data->getMetaconsoleAccessNode(),
            self::STRICT_ACL                       => $data->getStrictAcl(),
            self::ID_FILTER                        => $data->getIdFilter(),
            self::SESSION_TIME                     => $data->getSessionTime(),
            self::DEFAULT_EVENT_FILTER             => $data->getDefaultEventFilter(),
            self::METACONSOLE_DEFAULT_EVENT_FILTER => $data->getMetaconsoleDefaultEventFilter(),
            self::SHOW_TIPS_STARTUP                => $data->getShowTipsStartup(),
            self::AUTOREFRESH_WHITE_LIST           => (empty($data->getAutorefreshWhiteList()) === false) ? $this->repository->safeInput(json_encode($data->getAutorefreshWhiteList())) : null,
            self::TIME_AUTOREFRESH                 => $data->getTimeAutorefresh(),
            self::DEFAULT_CUSTOM_VIEW              => $data->getDefaultCustomView(),
            self::EHORUS_USER_LEVEL_USER           => $this->repository->safeInput($data->getEhorusUserLevelUser()),
            self::EHORUS_USER_LEVEL_PASS           => $this->repository->safeInput($data->getEhorusUserLevelPass()),
            self::EHORUS_USER_LEVEL_ENABLED        => $data->getEhorusUserLevelEnabled(),
            self::INTEGRIA_USER_LEVEL_USER         => $this->repository->safeInput($data->getItsmUserLevelUser()),
            self::INTEGRIA_USER_LEVEL_PASS         => $this->repository->safeInput($data->getItsmUserLevelPass()),
            self::API_TOKEN                        => $data->getApiToken(),
            self::ALLOWED_IP_ACTIVE                => $data->getAllowedIpActive(),
            self::ALLOWED_IP_LIST                  => $this->repository->safeInput($data->getAllowedIpList()),
            self::AUTH_TOKEN_SECRET                => $data->getAuthTokenSecret(),
            self::SESSION_MAX_TIME_EXPIRE          => $data->getSessionMaxTimeExpire(),
        ];
    }
}
