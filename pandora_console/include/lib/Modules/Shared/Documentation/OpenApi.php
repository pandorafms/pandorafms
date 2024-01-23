<?php

namespace PandoraFMS\Modules\Shared\Documentation;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   title="Nueva API de Pandora FMS",
 *   description="Lorem ipsum dolor sit amet consectetur, adipisicing elit. Libero, quibusdam esse commodi rem nisi cumque quos ut, exercitationem recusandae ipsam fuga qui veritatis non temporibus perferendis earum amet cupiditate eum nam corrupti! Dicta tempora, debitis molestiae corrupti sequi asperiores libero perferendis ut aperiam laboriosam repudiandae neque, rem quidem consectetur. Magnam illum perferendis aspernatur quibusdam esse? Libero eius veritatis quae perspiciatis. Sit recusandae aspernatur possimus autem! Corporis ipsa voluptatem placeat quasi praesentium esse doloremque magni, error, cumque vel, consequatur quam saepe iusto accusantium tempore ab dignissimos alias sint officia fuga voluptas. Vel repudiandae dicta ipsum repellat reprehenderit. Molestiae, ullam dolorum voluptatem necessitatibus itaque officiis ducimus consectetur aut facilis atque aliquid reiciendis voluptas sit incidunt, repellendus soluta quod obcaecati unde quas. Error officiis cumque vero minima amet modi enim, placeat consectetur cupiditate, fugiat odit sunt a earum natus dicta, labore id dolor! Quis laboriosam a quasi fuga! Ullam consectetur, voluptates repellat eveniet delectus officia nostrum amet obcaecati adipisci natus voluptas explicabo dolores similique doloribus. Rerum voluptatibus aperiam quidem necessitatibus, sint dignissimos natus delectus dolorem dicta sunt eum doloribus eligendi similique a at in repellat fuga voluptatem atque consectetur, ratione sit! Magni tenetur quos laborum, excepturi eveniet laboriosam optio aperiam eaque sit iusto.",
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
 *   name="Users",
 *   description="API Endpoints of users"
 * ),
 * @OA\Tag(
 *   name="Profiles",
 *   description="API Endpoints of profiles"
 * ),
 * @OA\Tag(
 *   name="Events",
 *   description="API Endpoints of events"
 * ),
 * @OA\OpenApi(
 *   x={
 *     "tagGroups"= {
 *       {
 *         "name"="Users",
 *         "tags"={"Users"}
 *       },
 *       {
 *         "name"="Profiles",
 *         "tags"={"Profiles"}
 *       },
 *       {
 *         "name"="Events",
 *         "tags"={"Events"}
 *       },
 *     }
 *   }
 * ),
 *
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
