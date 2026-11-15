<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Event;

/**
 * The client sent a ping; the pong is already sent.
 */
final class PingEvent extends ReplyableEvent
{
    /**
     * @param array<string, string> $attributes
     * @param list<string>          $tags
     */
    public function __construct(
        string $connection,
        array $attributes,
        array $tags,
        HandshakeRequest $request,
        private readonly string $data,
    ) {
        parent::__construct($connection, $attributes, $tags, $request);
    }

    public function getData(): string
    {
        return $this->data;
    }
}
