<?php

namespace PHPSTORM_META {

    expectedArguments(
        \Yiisoft\Yii\Web\Data\DataResponseFactoryInterface::createResponse(),
        1,
        argumentsSet('\Yiisoft\Http\Status::statuses'),
    );


    registerArgumentsSet(
        '\Yiisoft\Http\Status::statuses',
        // 1xx
        \Yiisoft\Http\Status::CONTINUE,
        \Yiisoft\Http\Status::SWITCHING_PROTOCOLS,
        \Yiisoft\Http\Status::PROCESSING,
        \Yiisoft\Http\Status::EARLY_HINTS,
        // 2xx
        \Yiisoft\Http\Status::OK,
        \Yiisoft\Http\Status::CREATED,
        \Yiisoft\Http\Status::ACCEPTED,
        \Yiisoft\Http\Status::NON_AUTHORITATIVE_INFORMATION,
        \Yiisoft\Http\Status::NO_CONTENT,
        \Yiisoft\Http\Status::RESET_CONTENT,
        \Yiisoft\Http\Status::PARTIAL_CONTENT,
        \Yiisoft\Http\Status::MULTI_STATUS,
        \Yiisoft\Http\Status::ALREADY_REPORTED,
        \Yiisoft\Http\Status::IM_USED,
        // 3xx
        \Yiisoft\Http\Status::MULTIPLE_CHOICES,
        \Yiisoft\Http\Status::MOVED_PERMANENTLY,
        \Yiisoft\Http\Status::FOUND,
        \Yiisoft\Http\Status::SEE_OTHER,
        \Yiisoft\Http\Status::NOT_MODIFIED,
        \Yiisoft\Http\Status::USE_PROXY,
        \Yiisoft\Http\Status::TEMPORARY_REDIRECT,
        \Yiisoft\Http\Status::PERMANENT_REDIRECT,
        // 4xx
        \Yiisoft\Http\Status::BAD_REQUEST,
        \Yiisoft\Http\Status::UNAUTHORIZED,
        \Yiisoft\Http\Status::PAYMENT_REQUIRED,
        \Yiisoft\Http\Status::FORBIDDEN,
        \Yiisoft\Http\Status::NOT_FOUND,
        \Yiisoft\Http\Status::METHOD_NOT_ALLOWED,
        \Yiisoft\Http\Status::NOT_ACCEPTABLE,
        \Yiisoft\Http\Status::PROXY_AUTHENTICATION_REQUIRED,
        \Yiisoft\Http\Status::REQUEST_TIMEOUT,
        \Yiisoft\Http\Status::CONFLICT,
        \Yiisoft\Http\Status::GONE,
        \Yiisoft\Http\Status::LENGTH_REQUIRED,
        \Yiisoft\Http\Status::PRECONDITION_FAILED,
        \Yiisoft\Http\Status::PAYLOAD_TOO_LARGE,
        \Yiisoft\Http\Status::URI_TOO_LONG,
        \Yiisoft\Http\Status::UNSUPPORTED_MEDIA_TYPE,
        \Yiisoft\Http\Status::RANGE_UNSATISFIABLE,
        \Yiisoft\Http\Status::EXPECTATION_FAILED,
        \Yiisoft\Http\Status::I_AM_A_TEAPOT,
        \Yiisoft\Http\Status::MISDIRECTED_REQUEST,
        \Yiisoft\Http\Status::UNPROCESSABLE_ENTITY,
        \Yiisoft\Http\Status::LOCKED,
        \Yiisoft\Http\Status::FAILED_DEPENDENCY,
        \Yiisoft\Http\Status::TOO_EARLY,
        \Yiisoft\Http\Status::UPGRADE_REQUIRED,
        \Yiisoft\Http\Status::PRECONDITION_REQUIRED,
        \Yiisoft\Http\Status::TOO_MANY_REQUESTS,
        \Yiisoft\Http\Status::REQUEST_HEADER_FIELDS_TOO_LARGE,
        \Yiisoft\Http\Status::UNAVAILABLE_FOR_LEGAL_REASONS,
        // 5xx
        \Yiisoft\Http\Status::INTERNAL_SERVER_ERROR,
        \Yiisoft\Http\Status::NOT_IMPLEMENTED,
        \Yiisoft\Http\Status::BAD_GATEWAY,
        \Yiisoft\Http\Status::SERVICE_UNAVAILABLE,
        \Yiisoft\Http\Status::GATEWAY_TIMEOUT,
        \Yiisoft\Http\Status::HTTP_VERSION_NOT_SUPPORTED,
        \Yiisoft\Http\Status::INSUFFICIENT_STORAGE,
        \Yiisoft\Http\Status::LOOP_DETECTED,
        \Yiisoft\Http\Status::NOT_EXTENDED,
        \Yiisoft\Http\Status::NETWORK_AUTHENTICATION_REQUIRED,
    );
}
