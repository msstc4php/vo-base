<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Network;

/**
 * An HTTP request method as defined by RFC 9110.
 */
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';
    case CONNECT = 'CONNECT';

    /**
     * Safe methods are read-only and have no side effects on the server.
     */
    public function isSafe(): bool
    {
        return match ($this) {
            self::GET, self::HEAD, self::OPTIONS, self::TRACE => true,
            self::POST, self::PUT, self::PATCH, self::DELETE, self::CONNECT => false,
        };
    }

    /**
     * Idempotent methods yield the same effect whether applied once or many times.
     */
    public function isIdempotent(): bool
    {
        return match ($this) {
            self::GET, self::HEAD, self::OPTIONS, self::TRACE, self::PUT, self::DELETE => true,
            self::POST, self::PATCH, self::CONNECT => false,
        };
    }
}
