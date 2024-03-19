<?php

namespace PandoraFMS\Modules\Profiles\Entities;

use PandoraFMS\Modules\Shared\Entities\Entity;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="Profile",
 *   type="object",
 *   @OA\Property(
 *     property="idProfile",
 *     type="integer",
 *     nullable=false,
 *     description="Id Profile",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     nullable=false,
 *     default=null,
 *     description="Name of the profile"
 *   ),
 *   @OA\Property(
 *     property="isAgentView",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Agent Read"
 *   ),
 *   @OA\Property(
 *     property="isAgentEdit",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Agent Write"
 *   ),
 *   @OA\Property(
 *     property="isAlertEdit",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Alert write"
 *   ),
 *   @OA\Property(
 *     property="isUserManagement",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="User Management"
 *   ),
 *   @OA\Property(
 *     property="isDbManagement",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Database Management"
 *   ),
 *   @OA\Property(
 *     property="isAlertManagement",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Alert Management"
 *   ),
 *   @OA\Property(
 *     property="isPandoraManagement",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Pandora Management"
 *   ),
 *   @OA\Property(
 *     property="isReportView",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Report view"
 *   ),
 *   @OA\Property(
 *     property="isReportEdit",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Report Edit"
 *   ),
 *   @OA\Property(
 *     property="isReportManagement",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Report Management"
 *   ),
 *   @OA\Property(
 *     property="isEventView",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Event Read"
 *   ),
 *   @OA\Property(
 *     property="isEventEdit",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Event write"
 *   ),
 *   @OA\Property(
 *     property="isEventManagement",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Event Management"
 *   ),
 *   @OA\Property(
 *     property="isAgentDisable",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Agent Disable"
 *   ),
 *   @OA\Property(
 *     property="isMapView",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Map Read"
 *   ),
 *   @OA\Property(
 *     property="isMapEdit",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Map Write"
 *   ),
 *   @OA\Property(
 *     property="isMapManagement",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Map Management"
 *   ),
 *   @OA\Property(
 *     property="isVconsoleView",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Visual console Read"
 *   ),
 *   @OA\Property(
 *     property="isVconsoleEdit",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Visual console Write"
 *   ),
 *   @OA\Property(
 *     property="isVconsoleManagement",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Visual console Management"
 *   ),
 *   @OA\Property(
 *     property="isNetworkConfigView",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Network config read"
 *   ),
 *   @OA\Property(
 *     property="isNetworkConfigEdit",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Network config Write"
 *   ),
 *   @OA\Property(
 *     property="isNetworkConfigManagement",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Network config Management"
 *   )
 * )
 *
 * @OA\Response(
 *   response="ResponseProfile",
 *   description="Profile object",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         ref="#/components/schemas/Profile",
 *         description="Profile object"
 *       ),
 *     )
 *   }
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterIdProfile",
 *   name="idProfile",
 *   in="path",
 *   description="Profile id",
 *   required=true,
 *   @OA\Schema(
 *     type="integer",
 *     default=1
 *   ),
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyProfile",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/Profile")
 *   ),
 * )
 */
final class Profile extends Entity
{
    private ?int $idProfile = null;
    private ?string $name = null;
    private ?bool $isAgentView = null;
    private ?bool $isAgentEdit = null;
    private ?bool $isAlertEdit = null;
    private ?bool $isUserManagement = null;
    private ?bool $isDbManagement = null;
    private ?bool $isAlertManagement = null;
    private ?bool $isPandoraManagement = null;
    private ?bool $isReportView = null;
    private ?bool $isReportEdit = null;
    private ?bool $isReportManagement = null;
    private ?bool $isEventView = null;
    private ?bool $isEventEdit = null;
    private ?bool $isEventManagement = null;
    private ?bool $isAgentDisable = null;
    private ?bool $isMapView = null;
    private ?bool $isMapEdit = null;
    private ?bool $isMapManagement = null;
    private ?bool $isVconsoleView = null;
    private ?bool $isVconsoleEdit = null;
    private ?bool $isVconsoleManagement = null;
    private ?bool $isNetworkConfigView = null;
    private ?bool $isNetworkConfigEdit = null;
    private ?bool $isNetworkConfigManagement = null;

    public function __construct()
    {
    }

    public function fieldsReadOnly(): array
    {
        return ['idProfile' => 1];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'idProfile'                 => $this->getIdProfile(),
            'name'                      => $this->getName(),
            'isAgentView'               => $this->getIsAgentView(),
            'isAgentEdit'               => $this->getIsAgentEdit(),
            'isAlertEdit'               => $this->getIsAlertEdit(),
            'isUserManagement'          => $this->getIsUserManagement(),
            'isDbManagement'            => $this->getIsDbManagement(),
            'isAlertManagement'         => $this->getIsAlertManagement(),
            'isPandoraManagement'       => $this->getIsPandoraManagement(),
            'isReportView'              => $this->getIsReportView(),
            'isReportEdit'              => $this->getIsReportEdit(),
            'isReportManagement'        => $this->getIsReportManagement(),
            'isEventView'               => $this->getIsEventView(),
            'isEventEdit'               => $this->getIsEventEdit(),
            'isEventManagement'         => $this->getIsEventManagement(),
            'isAgentDisable'            => $this->getIsAgentDisable(),
            'isMapView'                 => $this->getIsMapView(),
            'isMapEdit'                 => $this->getIsMapEdit(),
            'isMapManagement'           => $this->getIsMapManagement(),
            'isVconsoleView'            => $this->getIsVconsoleView(),
            'isVconsoleEdit'            => $this->getIsVconsoleEdit(),
            'isVconsoleManagement'      => $this->getIsVconsoleManagement(),
            'isNetworkConfigView'       => $this->getIsNetworkConfigView(),
            'isNetworkConfigEdit'       => $this->getIsNetworkConfigEdit(),
            'isNetworkConfigManagement' => $this->getIsNetworkConfigManagement(),
        ];
    }

    public function getValidations(): array
    {
        return [
            'idProfile' => [
                Validator::INTEGER,
                Validator::GREATERTHAN,
            ],
            'name'                      => Validator::STRING,
            'isAgentView'               => Validator::BOOLEAN,
            'isAgentEdit'               => Validator::BOOLEAN,
            'isAlertEdit'               => Validator::BOOLEAN,
            'isUserManagement'          => Validator::BOOLEAN,
            'isDbManagement'            => Validator::BOOLEAN,
            'isAlertManagement'         => Validator::BOOLEAN,
            'isPandoraManagement'       => Validator::BOOLEAN,
            'isReportView'              => Validator::BOOLEAN,
            'isReportEdit'              => Validator::BOOLEAN,
            'isReportManagement'        => Validator::BOOLEAN,
            'isEventView'               => Validator::BOOLEAN,
            'isEventEdit'               => Validator::BOOLEAN,
            'isEventManagement'         => Validator::BOOLEAN,
            'isAgentDisable'            => Validator::BOOLEAN,
            'isMapView'                 => Validator::BOOLEAN,
            'isMapEdit'                 => Validator::BOOLEAN,
            'isMapManagement'           => Validator::BOOLEAN,
            'isVconsoleView'            => Validator::BOOLEAN,
            'isVconsoleEdit'            => Validator::BOOLEAN,
            'isVconsoleManagement'      => Validator::BOOLEAN,
            'isNetworkConfigView'       => Validator::BOOLEAN,
            'isNetworkConfigEdit'       => Validator::BOOLEAN,
            'isNetworkConfigManagement' => Validator::BOOLEAN,
        ];
    }

    public function validateFields(array $filters): array
    {
        return (new Validator())->validate($filters);
    }

    public function getIdProfile(): ?int
    {
        return $this->idProfile;
    }
    public function setIdProfile(?int $idProfile): self
    {
        $this->idProfile = $idProfile;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getIsAgentView(): ?bool
    {
        return $this->isAgentView;
    }
    public function setIsAgentView(?bool $isAgentView): self
    {
        $this->isAgentView = $isAgentView;
        return $this;
    }

    public function getIsAgentEdit(): ?bool
    {
        return $this->isAgentEdit;
    }
    public function setIsAgentEdit(?bool $isAgentEdit): self
    {
        $this->isAgentEdit = $isAgentEdit;
        return $this;
    }

    public function getIsAlertEdit(): ?bool
    {
        return $this->isAlertEdit;
    }
    public function setIsAlertEdit(?bool $isAlertEdit): self
    {
        $this->isAlertEdit = $isAlertEdit;
        return $this;
    }

    public function getIsUserManagement(): ?bool
    {
        return $this->isUserManagement;
    }
    public function setIsUserManagement(?bool $isUserManagement): self
    {
        $this->isUserManagement = $isUserManagement;
        return $this;
    }

    public function getIsDbManagement(): ?bool
    {
        return $this->isDbManagement;
    }
    public function setIsDbManagement(?bool $isDbManagement): self
    {
        $this->isDbManagement = $isDbManagement;
        return $this;
    }

    public function getIsAlertManagement(): ?bool
    {
        return $this->isAlertManagement;
    }
    public function setIsAlertManagement(?bool $isAlertManagement): self
    {
        $this->isAlertManagement = $isAlertManagement;
        return $this;
    }

    public function getIsPandoraManagement(): ?bool
    {
        return $this->isPandoraManagement;
    }
    public function setIsPandoraManagement(?bool $isPandoraManagement): self
    {
        $this->isPandoraManagement = $isPandoraManagement;
        return $this;
    }

    public function getIsReportView(): ?bool
    {
        return $this->isReportView;
    }
    public function setIsReportView(?bool $isReportView): self
    {
        $this->isReportView = $isReportView;
        return $this;
    }

    public function getIsReportEdit(): ?bool
    {
        return $this->isReportEdit;
    }
    public function setIsReportEdit(?bool $isReportEdit): self
    {
        $this->isReportEdit = $isReportEdit;
        return $this;
    }

    public function getIsReportManagement(): ?bool
    {
        return $this->isReportManagement;
    }
    public function setIsReportManagement(?bool $isReportManagement): self
    {
        $this->isReportManagement = $isReportManagement;
        return $this;
    }

    public function getIsEventView(): ?bool
    {
        return $this->isEventView;
    }
    public function setIsEventView(?bool $isEventView): self
    {
        $this->isEventView = $isEventView;
        return $this;
    }

    public function getIsEventEdit(): ?bool
    {
        return $this->isEventEdit;
    }
    public function setIsEventEdit(?bool $isEventEdit): self
    {
        $this->isEventEdit = $isEventEdit;
        return $this;
    }

    public function getIsEventManagement(): ?bool
    {
        return $this->isEventManagement;
    }
    public function setIsEventManagement(?bool $isEventManagement): self
    {
        $this->isEventManagement = $isEventManagement;
        return $this;
    }

    public function getIsAgentDisable(): ?bool
    {
        return $this->isAgentDisable;
    }
    public function setIsAgentDisable(?bool $isAgentDisable): self
    {
        $this->isAgentDisable = $isAgentDisable;
        return $this;
    }

    public function getIsMapView(): ?bool
    {
        return $this->isMapView;
    }
    public function setIsMapView(?bool $isMapView): self
    {
        $this->isMapView = $isMapView;
        return $this;
    }

    public function getIsMapEdit(): ?bool
    {
        return $this->isMapEdit;
    }
    public function setIsMapEdit(?bool $isMapEdit): self
    {
        $this->isMapEdit = $isMapEdit;
        return $this;
    }

    public function getIsMapManagement(): ?bool
    {
        return $this->isMapManagement;
    }
    public function setIsMapManagement(?bool $isMapManagement): self
    {
        $this->isMapManagement = $isMapManagement;
        return $this;
    }

    public function getIsVconsoleView(): ?bool
    {
        return $this->isVconsoleView;
    }
    public function setIsVconsoleView(?bool $isVconsoleView): self
    {
        $this->isVconsoleView = $isVconsoleView;
        return $this;
    }

    public function getIsVconsoleEdit(): ?bool
    {
        return $this->isVconsoleEdit;
    }
    public function setIsVconsoleEdit(?bool $isVconsoleEdit): self
    {
        $this->isVconsoleEdit = $isVconsoleEdit;
        return $this;
    }

    public function getIsVconsoleManagement(): ?bool
    {
        return $this->isVconsoleManagement;
    }
    public function setIsVconsoleManagement(?bool $isVconsoleManagement): self
    {
        $this->isVconsoleManagement = $isVconsoleManagement;
        return $this;
    }

    public function getIsNetworkConfigView(): ?bool
    {
        return $this->isNetworkConfigView;
    }
    public function setIsNetworkConfigView(?bool $isNetworkConfigView): self
    {
        $this->isNetworkConfigView = $isNetworkConfigView;
        return $this;
    }

    public function getIsNetworkConfigEdit(): ?bool
    {
        return $this->isNetworkConfigEdit;
    }
    public function setIsNetworkConfigEdit(?bool $isNetworkConfigEdit): self
    {
        $this->isNetworkConfigEdit = $isNetworkConfigEdit;
        return $this;
    }

    public function getIsNetworkConfigManagement(): ?bool
    {
        return $this->isNetworkConfigManagement;
    }
    public function setIsNetworkConfigManagement(?bool $isNetworkConfigManagement): self
    {
        $this->isNetworkConfigManagement = $isNetworkConfigManagement;
        return $this;
    }
}
