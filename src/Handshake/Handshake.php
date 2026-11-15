<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Handshake;

/**
 * A WebSocket handshake that reached the application through the WebSocket worker.
 *
 * A 2xx response accepts the connection; any other response reaches the client as it is.
 */
final readonly class Handshake
{
    public const string REQUEST_ATTRIBUTE = '_chapterjason_websocket_handshake';

    public function __construct(
        public string $connection,
    ) {
    }
}
