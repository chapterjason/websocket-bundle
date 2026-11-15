<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Runtime;

use Chapterjason\WebsocketBundle\Handshake\Handshake;
use Chapterjason\WebsocketBundle\Worker\WebSocketEventHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * Runs the worker of the websocket directive: handshakes go through the kernel as HTTP requests, events through the event dispatcher.
 *
 * @phpstan-import-type WebSocketPayload from WebSocketEventHandler
 */
final class WebSocketWorkerRunner extends WorkerRunner
{
    public const string HANDLER_SERVICE = 'chapterjason_websocket.worker.websocket';

    private WebSocketEventHandler $handler;

    protected function createCallback(): \Closure
    {
        $handler = $this->kernel->getContainer()->get(self::HANDLER_SERVICE);

        if (!$handler instanceof WebSocketEventHandler) {
            throw new \LogicException(\sprintf('The service "%s" must be a "%s".', self::HANDLER_SERVICE, WebSocketEventHandler::class));
        }

        $this->handler = $handler;

        return $this->handle(...);
    }

    /**
     * @param WebSocketPayload|null $event
     */
    private function handle(?array $event = null): ?string
    {
        $this->restoreServer();

        if (null === $event) {
            $this->handshake();

            return null;
        }

        return $this->handler->handle($event);
    }

    private function handshake(): void
    {
        // Only the extension sets this header for the worker; a client cannot.
        $connection = $_SERVER['HTTP_WEBSOCKET_CONNECTION'] ?? null;

        if (!\is_string($connection)) {
            throw new \LogicException('The WebSocket handshake has no connection ID: is this script the worker of a websocket directive?');
        }

        $request = Request::createFromGlobals();
        $request->attributes->set(Handshake::REQUEST_ATTRIBUTE, new Handshake($connection));

        $response = $this->kernel->handle($request);

        try {
            $response->send();
        } finally {
            if ($this->kernel instanceof TerminableInterface) {
                try {
                    $this->kernel->terminate($request, $response);
                } catch (\Throwable $exception) {
                    $this->deferException($exception);
                }
            }
        }
    }
}
