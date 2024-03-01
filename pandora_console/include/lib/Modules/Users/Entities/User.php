<?php

namespace PandoraFMS\Modules\Users\Entities;

use PandoraFMS\Modules\Shared\Entities\Entity;
use PandoraFMS\Modules\Shared\Enums\LanguagesEnum;
use PandoraFMS\Modules\Users\Enums\UserHomeScreenEnum;
use PandoraFMS\Modules\Users\Enums\UserMetaconsoleAccessEnum;
use PandoraFMS\Modules\Users\Validators\UserValidator;

/**
 * @OA\Schema(
 *  schema="User",
 *  type="object",
 *  @OA\Property(
 *    property="idUser",
 *    type="string",
 *    nullable=false,
 *    description="Id user"
 *  ),
 *  @OA\Property(
 *    property="fullName",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="Full name"
 *  ),
 *  @OA\Property(
 *    property="firstName",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="First name"
 *  ),
 *  @OA\Property(
 *    property="lastName",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="Last name"
 *  ),
 *  @OA\Property(
 *    property="middleName",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="Middle name"
 *  ),
 *  @OA\Property(
 *    property="password",
 *    type="string",
 *    nullable=true,
 *    default="password",
 *    writeOnly=true
 *  ),
 *  @OA\Property(
 *    property="passwordValidate",
 *    type="string",
 *    nullable=true,
 *    default="password",
 *    writeOnly=true
 *  ),
 *  @OA\Property(
 *    property="oldPassword",
 *    type="string",
 *    nullable=true,
 *    default="oldPassword",
 *    writeOnly=true,
 *    description="Only administrator users will not have to confirm the old password"
 *  ),
 *  @OA\Property(
 *    property="comments",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="Comments for user",
 *    example="The user is allergic"
 *  ),
 *  @OA\Property(
 *    property="lastConnect",
 *    type="integer",
 *    nullable=true,
 *    default=null,
 *    description="User last connect utimestamp",
 *    example="1704898868"
 *  ),
 *  @OA\Property(
 *    property="registered",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="User registration date",
 *    example="2023-02-21 08:34:16",
 *    readOnly=true
 *  ),
 *  @OA\Property(
 *    property="email",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="User email address",
 *    example="nobody@samplecompany.com"
 *  ),
 *  @OA\Property(
 *    property="phone",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="User phone number",
 *    example="555-555-555"
 *  ),
 *  @OA\Property(
 *    property="isAdmin",
 *    type="boolean",
 *    nullable=false,
 *    default="false",
 *    description="User is administrator"
 *  ),
 *  @OA\Property(
 *    property="language",
 *    type="string",
 *    nullable=false,
 *    enum={
 *     "catalonian",
 *     "english",
 *     "spanish",
 *     "french",
 *     "japanese",
 *     "russian",
 *     "chinese"
 *    },
 *    default="english",
 *    description="User section, the available sections are: default, visual_console, event_list, group_view, group_view, tactical_view, alert_detail, external_link, other, dashboard"
 *  ),
 *  @OA\Property(
 *    property="timezone",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="User timezone, https://www.php.net/manual/en/datetimezone.listidentifiers.php"
 *  ),
 *  @OA\Property(
 *    property="blockSize",
 *    type="integer",
 *    nullable=true,
 *    default=20,
 *    description="User default block pagination size",
 *    example="20"
 *  ),
 *  @OA\Property(
 *    property="idSkin",
 *    type="integer",
 *    nullable=true,
 *    default=null,
 *    description="User id skin",
 *  ),
 *  @OA\Property(
 *    property="disabled",
 *    type="boolean",
 *    nullable=false,
 *    default=false,
 *    description="Disabling this user"
 *  ),
 *  @OA\Property(
 *    property="shortcut",
 *    type="integer",
 *    nullable=true,
 *    default=null,
 *    description="User shortcut",
 *  ),
 *  @OA\Property(
 *    property="shortcutData",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="User shorcut data"
 *  ),
 *  @OA\Property(
 *    property="section",
 *    type="string",
 *    nullable=false,
 *    enum={
 *     "default",
 *     "visual_console",
 *     "event_list",
 *     "group_view",
 *     "group_view",
 *     "tactical_view",
 *     "alert_detail",
 *     "external_link",
 *     "other",
 *     "dashboard"
 *    },
 *    default="default",
 *    description="User section, the available sections are: default, visual_console, event_list, group_view, group_view, tactical_view, alert_detail, external_link, other, dashboard"
 *  ),
 *  @OA\Property(
 *    property="dataSection",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="User section data"
 *  ),
 *  @OA\Property(
 *    property="metaconsoleSection",
 *    type="string",
 *    nullable=false,
 *    enum={
 *     "default",
 *     "visual_console",
 *     "event_list",
 *     "group_view",
 *     "group_view",
 *     "tactical_view",
 *     "alert_detail",
 *     "external_link",
 *     "other",
 *     "dashboard"
 *    },
 *    default="default",
 *    description="User Metaconsole section, the available sections are: default, visual_console, event_list, group_view, group_view, tactical_view, alert_detail, external_link, other, dashboard"
 *  ),
 *  @OA\Property(
 *    property="metaconsoleDataSection",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="User metaconsole section data"
 *  ),
 *  @OA\Property(
 *    property="forceChangePass",
 *    type="boolean",
 *    nullable=false,
 *    default=false,
 *    description="Force the user to change the password on next login"
 *  ),
 *  @OA\Property(
 *    property="lastPassChange",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="Date of last password change",
 *    example="2023-02-21 08:34:16",
 *    readOnly=true
 *  ),
 *  @OA\Property(
 *    property="lastFailedLogin",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="Date of last login failure",
 *    example="2023-02-21 08:34:16",
 *    readOnly=true
 *  ),
 *  @OA\Property(
 *    property="failedAttempt",
 *    type="integer",
 *    nullable=false,
 *    default=0,
 *    description="Number of failed login attempts by a user",
 *    readOnly=true
 *  ),
 *  @OA\Property(
 *    property="loginBlocked",
 *    type="boolean",
 *    nullable=false,
 *    default=false,
 *    description="Blocked user"
 *  ),
 *  @OA\Property(
 *    property="metaconsoleAccess",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="User metaconsole access"
 *  ),
 *  @OA\Property(
 *    property="notLogin",
 *    type="boolean",
 *    nullable=false,
 *    default=false,
 *    description="The user with no access authorization can only access the API."
 *  ),
 *  @OA\Property(
 *    property="localUser",
 *    type="boolean",
 *    nullable=false,
 *    default=false,
 *    description="The user with local authentication enabled will always use local authentication."
 *  ),
 *  @OA\Property(
 *    property="metaconsoleAgentsManager",
 *    type="integer",
 *    nullable=true,
 *    default=null,
 *    description="Agents manager",
 *  ),
 *  @OA\Property(
 *    property="metaconsoleAccessNode",
 *    type="integer",
 *    nullable=true,
 *    default=null,
 *    description="Access node",
 *  ),
 *  @OA\Property(
 *    property="strictAcl",
 *    type="boolean",
 *    nullable=false,
 *    default=false,
 *    description="User with ACL strict"
 *  ),
 *  @OA\Property(
 *    property="idFilter",
 *    type="integer",
 *    nullable=true,
 *    default=null,
 *    description="Assign a specific filter for user"
 *  ),
 *  @OA\Property(
 *    property="sessionTime",
 *    type="integer",
 *    nullable=true,
 *    default=null,
 *    description="This is defined in minutes. If you want a permanent session, introduce -1 in this field."
 *  ),
 *  @OA\Property(
 *    property="defaultEventFilter",
 *    type="integer",
 *    nullable=true,
 *    default=null,
 *    description="Assign a specific filter in events for user"
 *  ),
 *  @OA\Property(
 *    property="metaconsoleDefaultEventFilter",
 *    type="integer",
 *    nullable=true,
 *    default=null,
 *    description="Assign a specific filter in events for user metaconsole"
 *  ),
 *  @OA\Property(
 *    property="showTipsStartup",
 *    type="boolean",
 *    nullable=false,
 *    default=true,
 *    description="User show tips startup"
 *  ),
 *  @OA\Property(
 *    property="autorefreshWhiteList",
 *    type="string",
 *    nullable=false,
 *    default=null,
 *    description="User pages autorefresh"
 *  ),
 *  @OA\Property(
 *    property="timeAutorefresh",
 *    type="integer",
 *    nullable=false,
 *    default=null,
 *    description="Interval of autorefresh of the elements, by default they are 30 seconds, needing to enable the autorefresh first"
 *  ),
 *  @OA\Property(
 *    property="defaultCustomView",
 *    type="integer",
 *    nullable=true,
 *    default=null,
 *    description="default custom view for user"
 *  ),
 *  @OA\Property(
 *    property="ehorusUserLevelUser",
 *    type="string",
 *    nullable=false,
 *    default=null,
 *    description="user access ehorus whit user name"
 *  ),
 *  @OA\Property(
 *    property="ehorusUserLevelPass",
 *    type="string",
 *    nullable=false,
 *    default=null,
 *    description="user access ehorus whit user token"
 *  ),
 *  @OA\Property(
 *    property="ehorusUserLevelEnabled",
 *    type="boolean",
 *    nullable=false,
 *    default=false,
 *    description="User access ehorus level"
 *  ),
 *  @OA\Property(
 *    property="itsmUserLevelUser",
 *    type="string",
 *    nullable=false,
 *    default=null,
 *    description="user access ITSM whit user name"
 *  ),
 *  @OA\Property(
 *    property="itsmUserLevelPass",
 *    type="string",
 *    nullable=false,
 *    default=null,
 *    description="user access ITSM whit user token"
 *  ),
 *  @OA\Property(
 *    property="apiToken",
 *    type="string",
 *    description="Api key",
 *    readOnly=true
 *  ),
 *  @OA\Property(
 *    property="allowedIpActive",
 *    type="boolean",
 *    nullable=false,
 *    default=false,
 *    description="User allowed ip active"
 *  ),
 *  @OA\Property(
 *    property="allowedIpList",
 *    type="string",
 *    nullable=true,
 *    default=null,
 *    description="User allowed Ip List"
 *  ),
 *  @OA\Property(
 *    property="sessionMaxTimeExpire",
 *    type="integer",
 *    nullable=true,
 *    default=null,
 *    description="user expire session time"
 *  ),
 *  @OA\Property(
 *    property="authTokenSecret",
 *    type="string",
 *    nullable=false,
 *    default=null,
 *    description="Auth token",
 *    readOnly=true
 *  )
 * )
 *
 * @OA\Response(
 *   response="ResponseUser",
 *   description="User object",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         ref="#/components/schemas/User",
 *         description="User object"
 *       ),
 *     )
 *   }
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterIdUser",
 *   name="idUser",
 *   in="path",
 *   description="User id",
 *   required=true,
 *   @OA\Schema(
 *     type="string",
 *     default="admin"
 *   ),
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyUser",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/User")
 *   ),
 * )
 */
final class User extends Entity
{
    private ?string $idUser = null;
    private ?string $fullName = null;
    private ?string $firstName = null;
    private ?string $lastName = null;
    private ?string $middleName = null;
    private ?string $password = null;
    private ?string $passwordValidate = null;
    private ?string $oldPassword = null;
    private ?string $comments = null;
    private ?int $lastConnect = null;
    private ?int $registered = null;
    private ?string $email = null;
    private ?string $phone = null;
    private ?bool $isAdmin = null;
    private ?LanguagesEnum $language = null;
    private ?string $timezone = null;
    private ?int $blockSize = null;
    private ?int $idSkin = null;
    private ?bool $disabled = null;
    private ?int $shortcut = null;
    private ?string $shortcutData = null;
    private ?UserHomeScreenEnum $section = null;
    private ?string $dataSection = null;
    private ?UserHomeScreenEnum $metaconsoleSection = null;
    private ?string $metaconsoleDataSection = null;
    private ?bool $forceChangePass = null;
    private ?string $lastPassChange = null;
    private ?string $lastFailedLogin = null;
    private ?int $failedAttempt = null;
    private ?bool $loginBlocked = null;
    private ?UserMetaconsoleAccessEnum $metaconsoleAccess = null;
    private ?bool $notLogin = null;
    private ?bool $localUser = null;
    private ?int $metaconsoleAgentsManager = null;
    private ?int $metaconsoleAccessNode = null;
    private ?bool $strictAcl = null;
    private ?int $idFilter = null;
    private ?int $sessionTime = null;
    private ?int $defaultEventFilter = null;
    private ?int $metaconsoleDefaultEventFilter = null;
    private ?bool $showTipsStartup = null;
    private ?array $autorefreshWhiteList = null;
    private ?int $timeAutorefresh = null;
    private ?int $defaultCustomView = null;
    private ?string $ehorusUserLevelUser = null;
    private ?string $ehorusUserLevelPass = null;
    private ?bool $ehorusUserLevelEnabled = null;
    private ?string $itsmUserLevelUser = null;
    private ?string $itsmUserLevelPass = null;
    private ?string $apiToken = null;
    private ?bool $allowedIpActive = null;
    private ?string $allowedIpList = null;
    private ?string $authTokenSecret = null;
    private ?string $sessionMaxTimeExpire = null;

    public function __construct()
    {
    }

    public function fieldsReadOnly(): array
    {
        return [
            'registered'      => 1,
            'pwdhash'         => 1,
            'lastPassChange'  => 1,
            'lastFailedLogin' => 1,
            'apiToken'        => 1,
            'authTokenSecret' => 1,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'idUser'                        => $this->getIdUser(),
            'fullName'                      => $this->getFullName(),
            'firstName'                     => $this->getFirstName(),
            'lastName'                      => $this->getLastName(),
            'middleName'                    => $this->getMiddleName(),
            'comments'                      => $this->getComments(),
            'lastConnect'                   => $this->getLastConnect(),
            'registered'                    => $this->getRegistered(),
            'email'                         => $this->getEmail(),
            'phone'                         => $this->getPhone(),
            'isAdmin'                       => $this->getIsAdmin(),
            'language'                      => $this->getLanguage()?->name,
            'timezone'                      => $this->getTimezone(),
            'blockSize'                     => $this->getBlockSize(),
            'idSkin'                        => $this->getIdSkin(),
            'disabled'                      => $this->getDisabled(),
            'shortcut'                      => $this->getShortcut(),
            'shortcutData'                  => $this->getShortcutData(),
            'section'                       => $this->getSection()?->name,
            'dataSection'                   => $this->getDataSection(),
            'metaconsoleSection'            => $this->getMetaconsoleSection()?->name,
            'metaconsoleDataSection'        => $this->getMetaconsoleDataSection(),
            'forceChangePass'               => $this->getForceChangePass(),
            'lastPassChange'                => $this->getLastPassChange(),
            'lastFailedLogin'               => $this->getLastFailedLogin(),
            'failedAttempt'                 => $this->getFailedAttempt(),
            'loginBlocked'                  => $this->getLoginBlocked(),
            'metaconsoleAccess'             => $this->getMetaconsoleAccess()?->name,
            'notLogin'                      => $this->getNotLogin(),
            'localUser'                     => $this->getLocalUser(),
            'metaconsoleAgentsManager'      => $this->getMetaconsoleAgentsManager(),
            'metaconsoleAccessNode'         => $this->getMetaconsoleAccessNode(),
            'strictAcl'                     => $this->getStrictAcl(),
            'idFilter'                      => $this->getIdFilter(),
            'sessionTime'                   => $this->getSessionTime(),
            'defaultEventFilter'            => $this->getDefaultEventFilter(),
            'metaconsoleDefaultEventFilter' => $this->getMetaconsoleDefaultEventFilter(),
            'showTipsStartup'               => $this->getShowTipsStartup(),
            'autorefreshWhiteList'          => $this->getAutorefreshWhiteList(),
            'timeAutorefresh'               => $this->getTimeAutorefresh(),
            'defaultCustomView'             => $this->getDefaultCustomView(),
            'ehorusUserLevelUser'           => $this->getEhorusUserLevelUser(),
            'ehorusUserLevelPass'           => $this->getEhorusUserLevelPass(),
            'ehorusUserLevelEnabled'        => $this->getEhorusUserLevelEnabled(),
            'itsmUserLevelUser'             => $this->getItsmUserLevelUser(),
            'itsmUserLevelPass'             => $this->getItsmUserLevelPass(),
            'allowedIpActive'               => $this->getAllowedIpActive(),
            'allowedIpList'                 => $this->getAllowedIpList(),
            'sessionMaxTimeExpire'          => $this->getSessionMaxTimeExpire(),
        ];
    }

    public function getValidations(): array
    {
        return [
            'idUser'           => UserValidator::STRING,
            'fullName'         => UserValidator::STRING,
            'firstName'        => UserValidator::STRING,
            'lastName'         => UserValidator::STRING,
            'middleName'       => UserValidator::STRING,
            'password'         => UserValidator::STRING,
            'passwordValidate' => UserValidator::STRING,
            'oldPassword'      => UserValidator::STRING,
            'comments'         => UserValidator::STRING,
            'lastConnect'      => [
                UserValidator::INTEGER,
                UserValidator::GREATERTHAN,
            ],
            'registered' => [
                UserValidator::INTEGER,
                UserValidator::GREATERTHAN,
            ],
            'email'     => UserValidator::MAIL,
            'phone'     => UserValidator::STRING,
            'isAdmin'   => UserValidator::BOOLEAN,
            'language'  => UserValidator::LANGUAGE,
            'timezone'  => UserValidator::TIMEZONE,
            'blockSize' => [
                UserValidator::INTEGER,
                UserValidator::GREATERTHAN,
            ],
            'idSkin' => [
                UserValidator::INTEGER,
                UserValidator::GREATERTHAN,
            ],
            'disabled'               => UserValidator::BOOLEAN,
            'shortcut'               => UserValidator::INTEGER,
            'shortcutData'           => UserValidator::STRING,
            'section'                => UserValidator::VALIDSECTION,
            'dataSection'            => UserValidator::STRING,
            'metaconsoleSection'     => UserValidator::VALIDSECTION,
            'metaconsoleDataSection' => UserValidator::STRING,
            'forceChangePass'        => UserValidator::BOOLEAN,
            'lastPassChange'         => UserValidator::DATETIME,
            'lastFailedLogin'        => UserValidator::DATETIME,
            'failedAttempt'          => [
                UserValidator::INTEGER,
                UserValidator::GREATERTHAN,
            ],
            'loginBlocked'             => UserValidator::BOOLEAN,
            'metaconsoleAccess'        => UserValidator::VALIDMETACONSOLEACCESS,
            'notLogin'                 => UserValidator::BOOLEAN,
            'localUser'                => UserValidator::BOOLEAN,
            'metaconsoleAgentsManager' => UserValidator::INTEGER,
            'metaconsoleAccessNode'    => UserValidator::INTEGER,
            'strictAcl'                => UserValidator::BOOLEAN,
            'idFilter'                 => [
                UserValidator::INTEGER,
                UserValidator::GREATERTHAN,
            ],
            'sessionTime'        => UserValidator::INTEGER,
            'defaultEventFilter' => [
                UserValidator::INTEGER,
                UserValidator::GREATERTHAN,
            ],
            'metaconsoleDefaultEventFilter' => [
                UserValidator::INTEGER,
                UserValidator::GREATERTHAN,
            ],
            'showTipsStartup'      => UserValidator::BOOLEAN,
            'autorefreshWhiteList' => UserValidator::ARRAY,
            'timeAutorefresh'      => UserValidator::INTEGER,
            'defaultCustomView'    => [
                UserValidator::INTEGER,
                UserValidator::GREATERTHAN,
            ],
            'ehorusUserLevelUser'    => UserValidator::STRING,
            'ehorusUserLevelPass'    => UserValidator::STRING,
            'ehorusUserLevelEnabled' => UserValidator::BOOLEAN,
            'itsmUserLevelUser'      => UserValidator::STRING,
            'itsmUserLevelPass'      => UserValidator::STRING,
            'apiToken'               => UserValidator::STRING,
            'allowedIpActive'        => UserValidator::BOOLEAN,
            'allowedIpList'          => UserValidator::STRING,
            'authTokenSecret'        => UserValidator::STRING,
            'sessionMaxTimeExpire'   => UserValidator::INTEGER,
        ];
    }

    public function validateFields(array $filters): array
    {
        return (new UserValidator())->validate($filters);
    }

    public function getIdUser(): ?string
    {
        return $this->idUser;
    }

    public function setIdUser(?string $idUser): self
    {
        $this->idUser = $idUser;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): self
    {
        $this->middleName = $middleName;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getLastConnect(): ?int
    {
        return $this->lastConnect;
    }

    public function setLastConnect(?int $lastConnect): self
    {
        $this->lastConnect = $lastConnect;

        return $this;
    }

    public function getRegistered(): ?int
    {
        return $this->registered;
    }

    public function setRegistered(?int $registered): self
    {
        $this->registered = $registered;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getIsAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(?bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function getLanguage(): ?LanguagesEnum
    {
        return $this->language;
    }

    public function setLanguage(null|string|LanguagesEnum $language): self
    {
        if (is_string($language) === true) {
            $this->language = LanguagesEnum::get(strtoupper($language));
        } else {
            $this->language = $language;
        }

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getBlockSize(): ?int
    {
        return $this->blockSize;
    }

    public function setBlockSize(?int $blockSize): self
    {
        $this->blockSize = $blockSize;

        return $this;
    }

    public function getIdSkin(): ?int
    {
        return $this->idSkin;
    }

    public function setIdSkin(?int $idSkin): self
    {
        $this->idSkin = $idSkin;

        return $this;
    }

    public function getDisabled(): ?bool
    {
        return $this->disabled;
    }

    public function setDisabled(?bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function getShortcut(): ?int
    {
        return $this->shortcut;
    }

    public function setShortcut(?int $shortcut): self
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    public function getShortcutData(): ?string
    {
        return $this->shortcutData;
    }

    public function setShortcutData(?string $shortcutData): self
    {
        $this->shortcutData = $shortcutData;

        return $this;
    }

    public function getSection(): ?UserHomeScreenEnum
    {
        return $this->section;
    }

    public function setSection(null|string|UserHomeScreenEnum $section): self
    {
        if (is_string($section) === true) {
            $this->section = UserHomeScreenEnum::get(strtoupper($section));
        } else {
            $this->section = $section;
        }

        return $this;
    }

    public function getDataSection(): ?string
    {
        return $this->dataSection;
    }

    public function setDataSection(?string $dataSection): self
    {
        $this->dataSection = $dataSection;

        return $this;
    }

    public function getMetaconsoleSection(): ?UserHomeScreenEnum
    {
        return $this->metaconsoleSection;
    }

    public function setMetaconsoleSection(null|string|UserHomeScreenEnum $metaconsoleSection): self
    {
        if (is_string($metaconsoleSection) === true) {
            $this->metaconsoleSection = UserHomeScreenEnum::get(strtoupper($metaconsoleSection));
        } else {
            $this->metaconsoleSection = $metaconsoleSection;
        }

        return $this;
    }

    public function getMetaconsoleDataSection(): ?string
    {
        return $this->metaconsoleDataSection;
    }

    public function setMetaconsoleDataSection(?string $metaconsoleDataSection): self
    {
        $this->metaconsoleDataSection = $metaconsoleDataSection;

        return $this;
    }

    public function getForceChangePass(): ?bool
    {
        return $this->forceChangePass;
    }

    public function setForceChangePass(?bool $forceChangePass): self
    {
        $this->forceChangePass = $forceChangePass;

        return $this;
    }

    public function getLastPassChange(): ?string
    {
        return $this->lastPassChange;
    }

    public function setLastPassChange(?string $lastPassChange): self
    {
        $this->lastPassChange = $lastPassChange;

        return $this;
    }

    public function getLastFailedLogin(): ?string
    {
        return $this->lastFailedLogin;
    }

    public function setLastFailedLogin(?string $lastFailedLogin): self
    {
        $this->lastFailedLogin = $lastFailedLogin;

        return $this;
    }

    public function getFailedAttempt(): ?int
    {
        return $this->failedAttempt;
    }

    public function setFailedAttempt(?int $failedAttempt): self
    {
        $this->failedAttempt = $failedAttempt;

        return $this;
    }

    public function getLoginBlocked(): ?bool
    {
        return $this->loginBlocked;
    }

    public function setLoginBlocked(?bool $loginBlocked): self
    {
        $this->loginBlocked = $loginBlocked;

        return $this;
    }

    public function getMetaconsoleAccess(): ?UserMetaconsoleAccessEnum
    {
        return $this->metaconsoleAccess;
    }

    public function setMetaconsoleAccess(null|string|UserMetaconsoleAccessEnum $metaconsoleAccess): self
    {
        if (is_string($metaconsoleAccess) === true) {
            $this->metaconsoleAccess = UserMetaconsoleAccessEnum::get(strtoupper($metaconsoleAccess));
        } else {
            $this->metaconsoleAccess = $metaconsoleAccess;
        }

        $this->metaconsoleAccess = $metaconsoleAccess;

        return $this;
    }

    public function getNotLogin(): ?bool
    {
        return $this->notLogin;
    }

    public function setNotLogin(?bool $notLogin): self
    {
        $this->notLogin = $notLogin;

        return $this;
    }

    public function getLocalUser(): ?bool
    {
        return $this->localUser;
    }

    public function setLocalUser(?bool $localUser): self
    {
        $this->localUser = $localUser;

        return $this;
    }

    public function getMetaconsoleAgentsManager(): ?int
    {
        return $this->metaconsoleAgentsManager;
    }

    public function setMetaconsoleAgentsManager(?int $metaconsoleAgentsManager): self
    {
        $this->metaconsoleAgentsManager = $metaconsoleAgentsManager;

        return $this;
    }

    public function getMetaconsoleAccessNode(): ?int
    {
        return $this->metaconsoleAccessNode;
    }

    public function setMetaconsoleAccessNode(?int $metaconsoleAccessNode): self
    {
        $this->metaconsoleAccessNode = $metaconsoleAccessNode;

        return $this;
    }

    public function getStrictAcl(): ?bool
    {
        return $this->strictAcl;
    }

    public function setStrictAcl(?bool $strictAcl): self
    {
        $this->strictAcl = $strictAcl;

        return $this;
    }

    public function getIdFilter(): ?int
    {
        return $this->idFilter;
    }

    public function setIdFilter(?int $idFilter): self
    {
        $this->idFilter = $idFilter;

        return $this;
    }

    public function getSessionTime(): ?int
    {
        return $this->sessionTime;
    }

    public function setSessionTime(?int $sessionTime): self
    {
        $this->sessionTime = $sessionTime;

        return $this;
    }

    public function getDefaultEventFilter(): ?int
    {
        return $this->defaultEventFilter;
    }

    public function setDefaultEventFilter(?int $defaultEventFilter): self
    {
        $this->defaultEventFilter = $defaultEventFilter;

        return $this;
    }

    public function getMetaconsoleDefaultEventFilter(): ?int
    {
        return $this->metaconsoleDefaultEventFilter;
    }

    public function setMetaconsoleDefaultEventFilter(?int $metaconsoleDefaultEventFilter): self
    {
        $this->metaconsoleDefaultEventFilter = $metaconsoleDefaultEventFilter;

        return $this;
    }

    public function getShowTipsStartup(): ?bool
    {
        return $this->showTipsStartup;
    }

    public function setShowTipsStartup(?bool $showTipsStartup): self
    {
        $this->showTipsStartup = $showTipsStartup;

        return $this;
    }

    public function getAutorefreshWhiteList(): ?array
    {
        return $this->autorefreshWhiteList;
    }

    public function setAutorefreshWhiteList(array|string|null $autorefreshWhiteList): self
    {
        if (is_string($autorefreshWhiteList) === true) {
            $autorefreshWhiteList = json_decode(
                \io_safe_output($autorefreshWhiteList)
            );
        }

        $this->autorefreshWhiteList = $autorefreshWhiteList;

        return $this;
    }

    public function getTimeAutorefresh(): ?int
    {
        return $this->timeAutorefresh;
    }

    public function setTimeAutorefresh(?int $timeAutorefresh): self
    {
        $this->timeAutorefresh = $timeAutorefresh;

        return $this;
    }

    public function getDefaultCustomView(): ?int
    {
        return $this->defaultCustomView;
    }

    public function setDefaultCustomView(?int $defaultCustomView): self
    {
        $this->defaultCustomView = $defaultCustomView;

        return $this;
    }

    public function getEhorusUserLevelUser(): ?string
    {
        return $this->ehorusUserLevelUser;
    }

    public function setEhorusUserLevelUser(?string $ehorusUserLevelUser): self
    {
        $this->ehorusUserLevelUser = $ehorusUserLevelUser;

        return $this;
    }

    public function getEhorusUserLevelPass(): ?string
    {
        return $this->ehorusUserLevelPass;
    }

    public function setEhorusUserLevelPass(?string $ehorusUserLevelPass): self
    {
        $this->ehorusUserLevelPass = $ehorusUserLevelPass;

        return $this;
    }

    public function getEhorusUserLevelEnabled(): ?bool
    {
        return $this->ehorusUserLevelEnabled;
    }

    public function setEhorusUserLevelEnabled(?bool $ehorusUserLevelEnabled): self
    {
        $this->ehorusUserLevelEnabled = $ehorusUserLevelEnabled;

        return $this;
    }

    public function getItsmUserLevelUser(): ?string
    {
        return $this->itsmUserLevelUser;
    }

    public function setItsmUserLevelUser(?string $itsmUserLevelUser): self
    {
        $this->itsmUserLevelUser = $itsmUserLevelUser;

        return $this;
    }

    public function getItsmUserLevelPass(): ?string
    {
        return $this->itsmUserLevelPass;
    }

    public function setItsmUserLevelPass(?string $itsmUserLevelPass): self
    {
        $this->itsmUserLevelPass = $itsmUserLevelPass;

        return $this;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): self
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function getAllowedIpActive(): ?bool
    {
        return $this->allowedIpActive;
    }

    public function setAllowedIpActive(?bool $allowedIpActive): self
    {
        $this->allowedIpActive = $allowedIpActive;

        return $this;
    }

    public function getAllowedIpList(): ?string
    {
        return $this->allowedIpList;
    }

    public function setAllowedIpList(?string $allowedIpList): self
    {
        $this->allowedIpList = $allowedIpList;

        return $this;
    }

    public function getAuthTokenSecret(): ?string
    {
        return $this->authTokenSecret;
    }

    public function setAuthTokenSecret(?string $authTokenSecret): self
    {
        $this->authTokenSecret = $authTokenSecret;

        return $this;
    }

    public function getSessionMaxTimeExpire(): ?string
    {
        return $this->sessionMaxTimeExpire;
    }

    public function setSessionMaxTimeExpire(?string $sessionMaxTimeExpire): self
    {
        $this->sessionMaxTimeExpire = $sessionMaxTimeExpire;

        return $this;
    }

    public function getPasswordValidate(): ?string
    {
        return $this->passwordValidate;
    }

    public function setPasswordValidate(?string $passwordValidate): self
    {
        $this->passwordValidate = $passwordValidate;

        return $this;
    }

    public function getOldPassword(): ?string
    {
        return $this->oldPassword;
    }

    public function setOldPassword(?string $oldPassword): self
    {
        $this->oldPassword = $oldPassword;

        return $this;
    }
}
