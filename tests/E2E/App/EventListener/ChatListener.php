<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E\App\EventListener;

use Chapterjason\WebsocketBundle\Cluster\ClusterInterface;
use Chapterjason\WebsocketBundle\Event\CloseEvent;
use Chapterjason\WebsocketBundle\Event\MessageEvent;
use Chapterjason\WebsocketBundle\Event\OpenEvent;
use Chapterjason\WebsocketBundle\Event\PingEvent;
use Chapterjason\WebsocketBundle\Event\PongEvent;
use Chapterjason\WebsocketBundle\Tests\E2E\App\WorkerIdentity;
use Chapterjason\WebsocketBundle\WebSocket\WebSocketInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class ChatListener
{
    public function __construct(
        private readonly WebSocketInterface $webSocket,
        private readonly ClusterInterface $cluster,
        private readonly WorkerIdentity $worker,
    ) {
    }

    #[AsEventListener]
    public function onOpen(OpenEvent $event): void
    {
        $event->setReply(self::encode([
            'type' => 'welcome',
            'connection' => $event->getConnection(),
            'name' => $event->getAttribute('name'),
            'tags' => $event->getTags(),
            'uri' => $event->getRequest()->uri,
            'member' => $this->cluster->getInstanceId(),
        ]));
    }

    #[AsEventListener]
    public function onMessage(MessageEvent $event): void
    {
        if ($event->isBinary()) {
            $event->setReply(self::encode(['type' => 'received', 'bytes' => \strlen($event->getData()), 'worker' => $this->worker->id]));

            return;
        }

        $message = json_decode($event->getData(), true, 512, \JSON_THROW_ON_ERROR);

        if (!\is_array($message) || !\is_string($message['text'] ?? null)) {
            throw new \UnexpectedValueException('A chat message needs a text.');
        }

        $this->webSocket->sendToTag('chat', self::encode([
            'type' => 'said',
            'name' => $event->getAttribute('name'),
            'text' => $message['text'],
        ]));
    }

    #[AsEventListener]
    public function onPing(PingEvent $event): void
    {
        $event->setReply(self::encode(['type' => 'pinged', 'data' => $event->getData()]));
    }

    #[AsEventListener]
    public function onPong(PongEvent $event): void
    {
        $event->setReply(self::encode(['type' => 'ponged', 'data' => $event->getData()]));
    }

    #[AsEventListener]
    public function onClose(CloseEvent $event): void
    {
        $this->webSocket->sendToTag('chat', self::encode([
            'type' => 'left',
            'name' => $event->getAttribute('name'),
            'code' => $event->getCode(),
            'reason' => $event->getReason(),
        ]));
    }

    /**
     * @param array<string, mixed> $message
     */
    private static function encode(array $message): string
    {
        return json_encode($message, \JSON_THROW_ON_ERROR);
    }
}
