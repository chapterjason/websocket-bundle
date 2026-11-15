<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Event;

final class CloseEvent extends ConnectionEvent
{
    /**
     * @param array<string, string> $attributes
     * @param list<string>          $tags
     * @param int                   $code       the close code of the client or the server, 1006 when the connection ended without one
     */
    public function __construct(
        string $connection,
        array $attributes,
        array $tags,
        HandshakeRequest $request,
        private readonly int $code,
        private readonly string $reason,
    ) {
        parent::__construct($connection, $attributes, $tags, $request);
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
