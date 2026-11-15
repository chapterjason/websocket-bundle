<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\WebSocket;

enum WebSocketError
{
    case NotFound;
    case NotOpen;
    case InvalidArgument;
    case WriteFailed;
    case BusFailed;
    case Timeout;

    public static function fromCode(int $code): self
    {
        return match ($code) {
            \WEBSOCKET_ERROR_NOT_FOUND => self::NotFound,
            \WEBSOCKET_ERROR_NOT_OPEN => self::NotOpen,
            \WEBSOCKET_ERROR_INVALID_ARGUMENT => self::InvalidArgument,
            \WEBSOCKET_ERROR_WRITE_FAILED => self::WriteFailed,
            \WEBSOCKET_ERROR_BUS_FAILED => self::BusFailed,
            \WEBSOCKET_ERROR_TIMEOUT => self::Timeout,
            default => throw new \UnexpectedValueException(\sprintf('Unknown WebSocket error code %d.', $code)),
        };
    }
}
