<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Worker;

use Chapterjason\WebsocketBundle\Cluster\LeaveReason;
use Chapterjason\WebsocketBundle\Cluster\MemberRole;
use Chapterjason\WebsocketBundle\Event\ClusterLeftEvent;
use Chapterjason\WebsocketBundle\Event\ClusterMessageEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Dispatches the events of the cluster worker and of cluster_receive(), and returns the answer to a request.
 *
 * A failing listener leaves a request without an answer, which reaches the sender as an empty string.
 *
 * @phpstan-type ClusterMessagePayload array{event: 'message', from: string, data: string, request: bool}
 * @phpstan-type ClusterLeftPayload array{event: 'left', instance: string, reason: string, role: string}
 * @phpstan-type ClusterPayload ClusterMessagePayload|ClusterLeftPayload
 */
final class ClusterEventHandler
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        ?LoggerInterface $logger = null,
        private readonly ?ResetInterface $servicesResetter = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param ClusterPayload $payload
     */
    public function handle(array $payload): ?string
    {
        try {
            if ('left' === $payload['event']) {
                $this->dispatcher->dispatch(new ClusterLeftEvent($payload['instance'], LeaveReason::from($payload['reason']), MemberRole::from($payload['role'])));

                return null;
            }

            $event = $this->dispatcher->dispatch(new ClusterMessageEvent($payload['from'], $payload['data'], $payload['request']));

            return $event->isRequest() ? $event->getReply() : null;
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to handle the "{event}" cluster event of the member "{member}": {message}', [
                'event' => $payload['event'],
                'member' => 'left' === $payload['event'] ? $payload['instance'] : $payload['from'],
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return null;
        } finally {
            $this->servicesResetter?->reset();
        }
    }
}
