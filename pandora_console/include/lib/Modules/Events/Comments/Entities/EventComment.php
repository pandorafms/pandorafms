<?php

namespace PandoraFMS\Modules\Events\Comments\Entities;

use PandoraFMS\Modules\Shared\Entities\Entity;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="EventComment",
 *   type="object",
 *   @OA\Property(
 *     property="idEventComment",
 *     type="integer",
 *     nullable=false,
 *     description="id event comment",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="idEvent",
 *     type="integer",
 *     nullable=false,
 *     description="id event comment",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="utimestamp",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Event comment utimestamp create",
 *     example="1704898868",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="comment",
 *     type="string",
 *     nullable=false,
 *     default=null,
 *     description="content of the comment"
 *   ),
 *   @OA\Property(
 *     property="idUser",
 *     type="string",
 *     nullable=false,
 *     default=null,
 *     description="User id create comment",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="action",
 *     type="string",
 *     nullable=false,
 *     default=null,
 *     description="content of the action"
 *   )
 * )
 *
 * @OA\Response(
 *   response="ResponseEventComment",
 *   description="Event comment object",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         ref="#/components/schemas/EventComment",
 *         description="Event comment object"
 *       ),
 *     )
 *   }
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterIdEventComment",
 *   name="idComment",
 *   in="path",
 *   description="Event comment id",
 *   required=true,
 *   @OA\Schema(
 *     type="integer",
 *     default=1
 *   ),
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyEventComment",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/EventComment")
 *   ),
 * )
 */
final class EventComment extends Entity
{
    private ?int $idEventComment = null;
    private ?int $idEvent = null;
    private ?int $utimestamp = null;
    private ?string $comment = null;
    private ?string $idUser = null;
    private ?string $action = null;

    public function __construct()
    {
    }

    public function fieldsReadOnly(): array
    {
        return [
            'idEventComment' => 1,
            'idEvent'        => 1,
            'idUser'         => 1,
            'utimestamp'     => 1,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'idEventComment' => $this->getIdEventComment(),
            'idEvent'        => $this->getIdEvent(),
            'utimestamp'     => $this->getUtimestamp(),
            'comment'        => $this->getComment(),
            'idUser'         => $this->getIdUser(),
            'action'         => $this->getAction(),
        ];
    }

    public function getValidations(): array
    {
        return [
            'idEventComment' => [
                Validator::INTEGER,
                Validator::GREATERTHAN,
            ],
            'idEvent' => [
                Validator::INTEGER,
                Validator::GREATERTHAN,
            ],
            'utimestamp' => [
                Validator::INTEGER,
                Validator::GREATEREQUALTHAN,
            ],
            'comment' => Validator::STRING,
            'idUser'  => Validator::STRING,
            'action'  => Validator::STRING,
        ];
    }

    public function validateFields(array $filters): array
    {
        return (new Validator())->validate($filters);
    }

    public function getIdEventComment(): ?int
    {
        return $this->idEventComment;
    }
    public function setIdEventComment(?int $idEventComment): self
    {
        $this->idEventComment = $idEventComment;
        return $this;
    }

    public function getIdEvent(): ?int
    {
        return $this->idEvent;
    }
    public function setIdEvent(?int $idEvent): self
    {
        $this->idEvent = $idEvent;
        return $this;
    }

    public function getUtimestamp(): ?int
    {
        return $this->utimestamp;
    }
    public function setUtimestamp(?int $utimestamp): self
    {
        $this->utimestamp = $utimestamp;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
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

    public function getAction(): ?string
    {
        return $this->action;
    }
    public function setAction(?string $action): self
    {
        $this->action = $action;
        return $this;
    }
}
