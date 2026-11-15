<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Event;

/**
 * The reply is sent back to the connection: as a text message when it is valid UTF-8, as a binary message otherwise.
 */
abstract class ReplyableEvent extends ConnectionEvent
{
    private ?string $reply = null;

    public function getReply(): ?string
    {
        return $this->reply;
    }

    public function setReply(?string $reply): void
    {
        $this->reply = $reply;
    }
}
