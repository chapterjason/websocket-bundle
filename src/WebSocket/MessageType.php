<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\WebSocket;

enum MessageType
{
    /** Text when the data is valid UTF-8, binary otherwise. */
    case Auto;
    case Text;
    case Binary;

    public function toConstant(): int
    {
        return match ($this) {
            self::Auto => \WEBSOCKET_AUTO,
            self::Text => \WEBSOCKET_TEXT,
            self::Binary => \WEBSOCKET_BINARY,
        };
    }
}
