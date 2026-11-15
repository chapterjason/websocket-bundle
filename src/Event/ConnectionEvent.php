<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class ConnectionEvent extends Event
{
    /**
     * @param array<string, string> $attributes
     * @param list<string>          $tags
     */
    public function __construct(
        private readonly string $connection,
        private readonly array $attributes,
        private readonly array $tags,
        private readonly HandshakeRequest $request,
    ) {
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * The attributes of the connection when the event was emitted.
     *
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name): ?string
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * The tags of the connection when the event was emitted.
     *
     * @return list<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getRequest(): HandshakeRequest
    {
        return $this->request;
    }
}
