<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Data another member of the cluster sent to this member.
 */
final class ClusterMessageEvent extends Event
{
    private ?string $reply = null;

    public function __construct(
        private readonly string $from,
        private readonly string $data,
        private readonly bool $request,
    ) {
    }

    /**
     * The instance ID of the member that sent the data.
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Whether the sender waits for a reply.
     */
    public function isRequest(): bool
    {
        return $this->request;
    }

    public function getReply(): ?string
    {
        return $this->reply;
    }

    /**
     * Answers a request; ignored for other messages.
     */
    public function setReply(?string $reply): void
    {
        $this->reply = $reply;
    }
}
