<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E;

use WebSocket\Connection;
use WebSocket\Message\Message;
use WebSocket\Middleware\ProcessIncomingInterface;
use WebSocket\Middleware\ProcessStack;

/**
 * Records whether incoming messages arrive compressed; must be added after the CompressionExtension, which decompresses them.
 */
final class CompressionRecorder implements ProcessIncomingInterface
{
    /** @var list<bool> */
    public array $compressed = [];

    public function processIncoming(ProcessStack $stack, Connection $connection): Message
    {
        $message = $stack->handleIncoming();
        $this->compressed[] = $message->isCompressed();

        return $message;
    }

    public function __toString(): string
    {
        return self::class;
    }
}
