<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Event;

final class MessageEvent extends ReplyableEvent
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
        private readonly bool $binary,
    ) {
        parent::__construct($connection, $attributes, $tags, $request);
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function isBinary(): bool
    {
        return $this->binary;
    }
}
