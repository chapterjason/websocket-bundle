<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Runtime;

use Chapterjason\WebsocketBundle\Worker\ClusterEventHandler;

/**
 * Runs the worker of the cluster member option: the messages of other members and the members that left go through the event dispatcher.
 *
 * @phpstan-import-type ClusterPayload from ClusterEventHandler
 */
final class ClusterWorkerRunner extends WorkerRunner
{
    public const string HANDLER_SERVICE = 'chapterjason_websocket.worker.cluster';

    private ClusterEventHandler $handler;

    protected function createCallback(): \Closure
    {
        $handler = $this->kernel->getContainer()->get(self::HANDLER_SERVICE);

        if (!$handler instanceof ClusterEventHandler) {
            throw new \LogicException(\sprintf('The service "%s" must be a "%s".', self::HANDLER_SERVICE, ClusterEventHandler::class));
        }

        $this->handler = $handler;

        return $this->handle(...);
    }

    /**
     * @param ClusterPayload $event
     */
    private function handle(array $event): ?string
    {
        $this->restoreServer();

        return $this->handler->handle($event);
    }
}
