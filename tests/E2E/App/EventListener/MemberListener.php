<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E\App\EventListener;

use Chapterjason\WebsocketBundle\Cluster\ClusterInterface;
use Chapterjason\WebsocketBundle\Event\ClusterLeftEvent;
use Chapterjason\WebsocketBundle\Event\ClusterMessageEvent;
use Chapterjason\WebsocketBundle\WebSocket\WebSocketInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class MemberListener
{
    public function __construct(
        private readonly ClusterInterface $cluster,
        private readonly WebSocketInterface $webSocket,
    ) {
    }

    #[AsEventListener]
    public function onMessage(ClusterMessageEvent $event): void
    {
        $message = [
            'member' => $this->cluster->getInstanceId(),
            'from' => $event->getFrom(),
            'data' => json_decode($event->getData(), true, 512, \JSON_THROW_ON_ERROR),
        ];

        if ($event->isRequest()) {
            $event->setReply(json_encode($message, \JSON_THROW_ON_ERROR));

            return;
        }

        $this->webSocket->sendToTag('chat', json_encode(['type' => 'member'] + $message, \JSON_THROW_ON_ERROR));
    }

    #[AsEventListener]
    public function onLeft(ClusterLeftEvent $event): void
    {
        $this->webSocket->sendToTag('chat', json_encode([
            'type' => 'member-left',
            'instance' => $event->getInstance(),
            'reason' => $event->getReason()->value,
            'role' => $event->getRole()->value,
        ], \JSON_THROW_ON_ERROR));
    }
}
