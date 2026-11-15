<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E\App\Controller;

use Chapterjason\WebsocketBundle\Handshake\Handshake;
use Chapterjason\WebsocketBundle\WebSocket\WebSocketInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ChatController
{
    public function __construct(
        private readonly WebSocketInterface $webSocket,
    ) {
    }

    #[Route('/chat', methods: ['GET'])]
    public function join(Request $request, Handshake $handshake, #[CurrentUser] UserInterface $user): Response
    {
        $this->webSocket->setAttribute($handshake->connection, 'name', $user->getUserIdentifier());
        $this->webSocket->tag($handshake->connection, 'chat');

        $response = new Response(null, Response::HTTP_NO_CONTENT);

        $protocols = array_map(trim(...), explode(',', $request->headers->get('Sec-WebSocket-Protocol', '')));

        if (\in_array('chat.v1', $protocols, true)) {
            $response->headers->set('Sec-WebSocket-Protocol', 'chat.v1');
        }

        if (str_contains($request->headers->get('Sec-WebSocket-Extensions', ''), 'permessage-deflate')) {
            $response->headers->set('Sec-WebSocket-Extensions', 'permessage-deflate');
        }

        return $response;
    }
}
