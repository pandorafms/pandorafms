<?php

namespace PandoraFMS\Modules\Shared\Enums;

enum HttpCodesEnum: int
{
public const CONTINUE = 100;
public const SWITCHING_PROTOCOLS = 101;
public const PROCESSING = 102;
// RFC2518
public const EARLY_HINTS = 103;
// RFC8297
public const OK = 200;
public const CREATED = 201;
public const ACCEPTED = 202;
public const NON_AUTHORITATIVE_INFORMATION = 203;
public const NO_CONTENT = 204;
public const RESET_CONTENT = 205;
public const PARTIAL_CONTENT = 206;
public const MULTI_STATUS = 207;
// RFC4918
public const ALREADY_REPORTED = 208;
// RFC5842
public const IM_USED = 226;
// RFC3229
public const MULTIPLE_CHOICES = 300;
public const MOVED_PERMANENTLY = 301;
public const FOUND = 302;
public const SEE_OTHER = 303;
public const NOT_MODIFIED = 304;
public const USE_PROXY = 305;
public const RESERVED = 306;
public const TEMPORARY_REDIRECT = 307;
public const PERMANENTLY_REDIRECT = 308;
// RFC7238
public const BAD_REQUEST = 400;
public const UNAUTHORIZED = 401;
public const PAYMENT_REQUIRED = 402;
public const FORBIDDEN = 403;
public const NOT_FOUND = 404;
public const METHOD_NOT_ALLOWED = 405;
public const NOT_ACCEPTABLE = 406;
public const PROXY_AUTHENTICATION_REQUIRED = 407;
public const REQUEST_TIMEOUT = 408;
public const CONFLICT = 409;
public const GONE = 410;
public const LENGTH_REQUIRED = 411;
public const PRECONDITION_FAILED = 412;
public const REQUEST_ENTITY_TOO_LARGE = 413;
public const REQUEST_URI_TOO_LONG = 414;
public const UNSUPPORTED_MEDIA_TYPE = 415;
public const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
public const EXPECTATION_FAILED = 417;
public const I_AM_A_TEAPOT = 418;
// RFC2324
public const MISDIRECTED_REQUEST = 421;
// RFC7540
public const UNPROCESSABLE_ENTITY = 422;
// RFC4918
public const LOCKED = 423;
// RFC4918
public const FAILED_DEPENDENCY = 424;
// RFC4918
public const TOO_EARLY = 425;
// RFC-ietf-httpbis-replay-04
public const UPGRADE_REQUIRED = 426;
// RFC2817
public const PRECONDITION_REQUIRED = 428;
// RFC6585
public const TOO_MANY_REQUESTS = 429;
// RFC6585
public const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
// RFC6585
public const UNAVAILABLE_FOR_LEGAL_REASONS = 451;
// RFC7725
public const INTERNAL_SERVER_ERROR = 500;
public const NOT_IMPLEMENTED = 501;
public const BAD_GATEWAY = 502;
public const SERVICE_UNAVAILABLE = 503;
public const GATEWAY_TIMEOUT = 504;
public const VERSION_NOT_SUPPORTED = 505;
public const VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;
// RFC2295
public const INSUFFICIENT_STORAGE = 507;
// RFC4918
public const LOOP_DETECTED = 508;
// RFC5842
public const NOT_EXTENDED = 510;
// RFC2774
public const NETWORK_AUTHENTICATION_REQUIRED = 511;
// RFC6585
}
