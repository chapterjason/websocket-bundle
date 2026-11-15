# The handshake

A handshake reaches the kernel as an ordinary `GET` request to the path of the
`websocket` directive, so it needs a route. A controller that takes a
`Handshake` argument accepts it with a 2xx response; any other response,
such as a 403 or a redirect to a login page, refuses it and reaches the client
as it is.

```php
use Chapterjason\WebsocketBundle\Handshake\Handshake;
use Chapterjason\WebsocketBundle\WebSocket\WebSocketInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ChatHandshakeController
{
    public function __construct(
        private readonly WebSocketInterface $webSocket,
    ) {
    }

    #[Route('/ws', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(Handshake $handshake, #[CurrentUser] UserInterface $user): Response
    {
        $this->webSocket->setAttribute($handshake->connection, 'user', $user->getUserIdentifier());
        $this->webSocket->tag($handshake->connection, 'chat');

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
```

- `$handshake->connection` is the ID of the connection. The bundle takes it
  from the extension only in the WebSocket worker; a client cannot forge it.
- A request to the route that is no handshake gets `426 Upgrade Required`. Type
  the argument `?Handshake` to get `null` instead.
- Headers of the response go on the handshake: `Sec-WebSocket-Protocol` picks
  a subprotocol the client offered, `Sec-WebSocket-Extensions:
  permessage-deflate` turns compression on, `Set-Cookie` sets cookies.
- The body of an accepted handshake is discarded.
