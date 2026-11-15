<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Worker;

use Chapterjason\WebsocketBundle\Event\CloseEvent;
use Chapterjason\WebsocketBundle\Event\ConnectionEvent;
use Chapterjason\WebsocketBundle\Event\HandshakeRequest;
use Chapterjason\WebsocketBundle\Event\MessageEvent;
use Chapterjason\WebsocketBundle\Event\OpenEvent;
use Chapterjason\WebsocketBundle\Event\PingEvent;
use Chapterjason\WebsocketBundle\Event\PongEvent;
use Chapterjason\WebsocketBundle\Event\ReplyableEvent;
use Chapterjason\WebsocketBundle\WebSocket\WebSocketInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Dispatches the events of the WebSocket worker and returns the reply to send back.
 *
 * A failing listener closes the connection with 1011, except on a close event.
 *
 * @phpstan-type HandshakeRequestPayload array{method: string, uri: string, protocol: string, remote_addr: string, headers: array<string, string>}
 * @phpstan-type OpenPayload array{event: 'open', connection: string, attributes: array<string, string>, tags: list<string>, request: HandshakeRequestPayload}
 * @phpstan-type MessagePayload array{event: 'message', connection: string, attributes: array<string, string>, tags: list<string>, request: HandshakeRequestPayload, data: string, binary: bool}
 * @phpstan-type PingPayload array{event: 'ping'|'pong', connection: string, attributes: array<string, string>, tags: list<string>, request: HandshakeRequestPayload, data: string}
 * @phpstan-type ClosePayload array{event: 'close', connection: string, attributes: array<string, string>, tags: list<string>, request: HandshakeRequestPayload, code: int, reason: string}
 * @phpstan-type WebSocketPayload OpenPayload|MessagePayload|PingPayload|ClosePayload
 */
final class WebSocketEventHandler
{
    public const int INTERNAL_ERROR = 1011;

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly WebSocketInterface $webSocket,
        ?LoggerInterface $logger = null,
        private readonly ?ResetInterface $servicesResetter = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param WebSocketPayload $payload
     */
    public function handle(array $payload): ?string
    {
        try {
            $event = $this->dispatcher->dispatch($this->createEvent($payload));

            return $event instanceof ReplyableEvent ? $event->getReply() : null;
        } catch (\Throwable $exception) {
            $this->fail($payload, $exception);

            return null;
        } finally {
            $this->servicesResetter?->reset();
        }
    }

    /**
     * @param WebSocketPayload $payload
     */
    private function createEvent(array $payload): ConnectionEvent
    {
        $connection = $payload['connection'];
        $attributes = $payload['attributes'];
        $tags = $payload['tags'];
        $request = new HandshakeRequest(
            $payload['request']['method'],
            $payload['request']['uri'],
            $payload['request']['protocol'],
            $payload['request']['remote_addr'],
            $payload['request']['headers'],
        );

        return match ($payload['event']) {
            'open' => new OpenEvent($connection, $attributes, $tags, $request),
            'message' => new MessageEvent($connection, $attributes, $tags, $request, $payload['data'], $payload['binary']),
            'ping' => new PingEvent($connection, $attributes, $tags, $request, $payload['data']),
            'pong' => new PongEvent($connection, $attributes, $tags, $request, $payload['data']),
            'close' => new CloseEvent($connection, $attributes, $tags, $request, $payload['code'], $payload['reason']),
        };
    }

    /**
     * @param WebSocketPayload $payload
     */
    private function fail(array $payload, \Throwable $exception): void
    {
        $this->logger->error('The WebSocket worker failed to handle the "{event}" event of the connection "{connection}": {message}', [
            'event' => $payload['event'],
            'connection' => $payload['connection'],
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);

        if ('close' === $payload['event']) {
            return;
        }

        try {
            $this->webSocket->close($payload['connection'], self::INTERNAL_ERROR);
        } catch (\Throwable $closeException) {
            $this->logger->error('The WebSocket worker failed to close the connection "{connection}" after a failure: {message}', [
                'connection' => $payload['connection'],
                'message' => $closeException->getMessage(),
                'exception' => $closeException,
            ]);
        }
    }
}
