<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\Integration\Fixtures;

use Chapterjason\WebsocketBundle\Cluster\ClusterInterface;
use Chapterjason\WebsocketBundle\Cluster\ClusterReceiverInterface;
use Chapterjason\WebsocketBundle\WebSocket\WebSocketInterface;

final class ServiceConsumer
{
    public function __construct(
        public readonly WebSocketInterface $webSocket,
        public readonly ClusterInterface $cluster,
        public readonly ClusterReceiverInterface $clusterReceiver,
    ) {
    }
}
