<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Cluster;

use Chapterjason\WebsocketBundle\Exception\ClusterException;

/**
 * Takes the messages other members send to a CLI participant, run with `frankenphp php-cli`.
 *
 * Each message is dispatched as a ClusterMessageEvent, as in the cluster worker of a server.
 *
 * The participant receives from its first call on: before, requests to it fail with ClusterError::NoReceiver and publishes leave it out.
 */
interface ClusterReceiverInterface
{
    /**
     * Waits for the next message and dispatches it.
     *
     * PHP runs signal handlers only after the wait: keep the timeout short in a process that stops on a signal.
     *
     * @param int $timeout in milliseconds; 0 does not wait
     *
     * @return bool whether a message came
     *
     * @throws ClusterException when the extension reports an error other than a timeout
     * @throws \Error           in a server, where the cluster worker gets the messages
     */
    public function receive(int $timeout = 0): bool;
}
