<?php

namespace PandoraFMS\Modules\Shared\Documentation;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   title="API Pandora FMS",
 *   description="Documentacion para desarrolladores de la nueva api de pandora fms.",
 *   termsOfService="https://example.com/terms/",
 *   @OA\Contact(
 *     name="Pandorafms",
 *     url="https://pandorafms.com/",
 *     email="info@pandorafms.com"
 *   ),
 *   @OA\License(
 *     name="Apache 2.0",
 *     url="https://www.apache.org/licenses/LICENSE-2.0.html"
 *   ),
 *   version="0.0.1"
 * ),
 * @OA\Schemes(
 *   format="http",
 *   format="https"
 * ),
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   in="header",
 *   name="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="pandoraBearer",
 * ),
 * @OA\Server(
 *   url="/api/v1",
 *   description="PandoraFMS API Server"
 * ),
 * @OA\Tag(
 *   name="Authentication",
 *   description="API Endpoints of authentication"
 * ),
 * @OA\Tag(
 *   name="Events",
 *   description="API Endpoints of events"
 * ),
 * @OA\Tag(
 *   name="Groups",
 *   description="API Endpoints of Groups"
 * ),
 * @OA\Tag(
 *   name="Profiles",
 *   description="API Endpoints of profiles"
 * ),
 * @OA\Tag(
 *   name="Tags",
 *   description="API Endpoints of tags"
 * ),
 * @OA\Tag(
 *   name="Users",
 *   description="API Endpoints of users"
 * ),
 * @OA\Parameter(
 *   parameter="parameterPage",
 *   name="page",
 *   in="query",
 *   description="page",
 *   required=false,
 *   @OA\Schema(
 *     type="integer",
 *     default=0
 *   ),
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterSizePage",
 *   name="sizePage",
 *   in="query",
 *   description="Size page",
 *   required=false,
 *   @OA\Schema(
 *     type="integer",
 *     default=0
 *   ),
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterSortField",
 *   name="sortField",
 *   in="query",
 *   description="sort field",
 *   required=false,
 *   @OA\Schema(
 *     type="string",
 *     default=""
 *   ),
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterSortDirection",
 *   name="sortDirection",
 *   in="query",
 *   description="sort direction",
 *   required=false,
 *   @OA\Schema(
 *     type="string",
 *     enum={
 *       "ascending",
 *       "descending"
 *     },
 *     default=""
 *   ),
 * )
 *
 * @OA\Response(
 *   response="BadRequest",
 *   description="Bad request",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         description="Error",
 *         @OA\Property(
 *           property="error",
 *           type="string",
 *           default="Message error"
 *         )
 *       )
 *     )
 *   }
 * )
 *
 * @OA\Response(
 *   response="Unauthorized",
 *   description="Unauthorized",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         description="Error",
 *         @OA\Property(
 *           property="error",
 *           type="string",
 *           default="Message error"
 *         )
 *       )
 *     )
 *   }
 * )
 *
 * @OA\Response(
 *   response="Forbidden",
 *   description="Forbidden",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         description="Error",
 *         @OA\Property(
 *           property="error",
 *           type="string",
 *           default="Message error"
 *         )
 *       )
 *     )
 *   }
 * )
 *
 * @OA\Response(
 *   response="NotFound",
 *   description="Not found",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         description="Error",
 *         @OA\Property(
 *           property="error",
 *           type="string",
 *           default="Message error"
 *         )
 *       )
 *     )
 *   }
 * )
 *
 * @OA\Response(
 *   response="InternalServerError",
 *   description="Internal server error",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         description="Error",
 *         @OA\Property(
 *           property="error",
 *           type="string",
 *           default="Message error"
 *         )
 *       )
 *     )
 *   }
 * )
 *
 * @OA\Response(
 *   response="successfullyDeleted",
 *   description="Successfully deleted",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         @OA\Property(
 *           property="result",
 *           type="string",
 *           default="Successfully deleted"
 *         )
 *       )
 *     )
 *   }
 * )
 *
 * @OA\Schema(
 *   schema="paginationData",
 *   type="object",
 *   description="Info pagination data",
 *   @OA\Property(
 *     property="totalPages",
 *     type="integer",
 *     nullable=true,
 *     description="Number of pages",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="sizePage",
 *     type="integer",
 *     nullable=true,
 *     description="Items per page",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="totalRegisters",
 *     type="integer",
 *     nullable=true,
 *     description="Number of items",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="totalRegistersPage",
 *     type="integer",
 *     nullable=true,
 *     description="Number of items this page",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="currentPage",
 *     type="integer",
 *     nullable=true,
 *     description="Number of current page",
 *     readOnly=true
 *   )
 * )
 */
class OpenApi
{
}
