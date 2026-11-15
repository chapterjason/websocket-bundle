<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E\App\Controller;

use Chapterjason\WebsocketBundle\WebSocket\MessageType;
use Chapterjason\WebsocketBundle\WebSocket\WebSocketInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ConnectionController
{
    public function __construct(
        private readonly WebSocketInterface $webSocket,
    ) {
    }

    #[Route('/connections', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse($this->webSocket->connections());
    }

    #[Route('/tags/{tag}/connections', methods: ['GET'])]
    public function listWithTag(string $tag): JsonResponse
    {
        return new JsonResponse($this->webSocket->connectionsWithTag($tag));
    }

    #[Route('/connections/{connection}', methods: ['GET'])]
    public function show(string $connection): JsonResponse
    {
        if (!$this->webSocket->isOpen($connection)) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse([
            'instance' => $this->webSocket->getInstance($connection),
            'attributes' => $this->webSocket->attributes($connection),
            'tags' => $this->webSocket->tags($connection),
        ]);
    }

    #[Route('/connections/{connection}/messages', methods: ['POST'])]
    public function notify(Request $request, string $connection): Response
    {
        $type = 'application/octet-stream' === $request->headers->get('Content-Type') ? MessageType::Binary : MessageType::Auto;

        $this->webSocket->send($connection, $request->getContent(), $type);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/connections/{connection}/pings', methods: ['POST'])]
    public function ping(Request $request, string $connection): Response
    {
        $this->webSocket->ping($connection, $request->getContent());

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/connections/{connection}', methods: ['DELETE'])]
    public function kick(string $connection): Response
    {
        $this->webSocket->close($connection, 4001, 'kicked');

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/connections/{connection}/attributes/{name}', methods: ['PUT'])]
    public function setAttribute(Request $request, string $connection, string $name): Response
    {
        $this->webSocket->setAttribute($connection, $name, $request->getContent());

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/connections/{connection}/attributes/{name}', methods: ['DELETE'])]
    public function removeAttribute(string $connection, string $name): Response
    {
        $this->webSocket->removeAttribute($connection, $name);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/connections/{connection}/tags/{tag}', methods: ['DELETE'])]
    public function untag(string $connection, string $tag): Response
    {
        $this->webSocket->untag($connection, $tag);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/announcements', methods: ['POST'])]
    public function announce(Request $request): JsonResponse
    {
        return new JsonResponse(['delivered' => $this->webSocket->sendToTag('chat', $request->getContent())]);
    }

    #[Route('/broadcasts', methods: ['POST'])]
    public function broadcast(Request $request): JsonResponse
    {
        return new JsonResponse(['delivered' => $this->webSocket->broadcast($request->getContent())]);
    }
}
