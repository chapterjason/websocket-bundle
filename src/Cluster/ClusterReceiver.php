<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Cluster;

use Chapterjason\WebsocketBundle\Exception\ClusterException;
use Chapterjason\WebsocketBundle\Exception\ExtensionNotLoadedException;
use Chapterjason\WebsocketBundle\Worker\ClusterEventHandler;

final class ClusterReceiver implements ClusterReceiverInterface
{
    public function __construct(
        private readonly ClusterEventHandler $handler,
    ) {
    }

    public function receive(int $timeout = 0): bool
    {
        if (!\function_exists('cluster_receive')) {
            throw new ExtensionNotLoadedException('cluster_receive');
        }

        // After a handled message, cluster_last_error() holds the code of the last call of the listeners.
        if (cluster_receive($this->handler->handle(...), $timeout)) {
            return true;
        }

        $code = cluster_last_error();

        if (\CLUSTER_ERROR_TIMEOUT === $code) {
            return false;
        }

        throw new ClusterException(ClusterError::fromCode($code), 'cluster_receive');
    }
}
