<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Event;

/**
 * The handshake request of a connection, as PHP saw it.
 */
final readonly class HandshakeRequest
{
    /**
     * @param array<string, string> $headers keyed by lowercased name
     */
    public function __construct(
        public string $method,
        public string $uri,
        public string $protocol,
        public string $remoteAddr,
        public array $headers,
    ) {
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
