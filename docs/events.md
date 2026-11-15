# Events

The events of a connection are dispatched on the event dispatcher, one at a
time and in order:

| Event | Fields | When |
|---|---|---|
| `OpenEvent` | | The connection is up. |
| `MessageEvent` | `getData()`, `isBinary()` | A complete message arrived. |
| `PingEvent` | `getData()` | The client sent a ping; the pong is already sent. |
| `PongEvent` | `getData()` | The client answered `WebSocketInterface::ping()`. |
| `CloseEvent` | `getCode()`, `getReason()` | The connection ended; 1006 when it ended without a code. A `CloseEvent` follows every `OpenEvent`, unless the server crashes; see [When a member leaves](cluster.md#when-a-member-leaves). |

Every event has `getConnection()`, `getAttributes()`, `getAttribute()`,
`getTags()` and `getRequest()`, the handshake as PHP saw it (`method`, `uri`,
`protocol`, `remoteAddr`, `headers` and `getHeader()`).

All events but `CloseEvent` take a reply, sent back to the connection: as a
text message when it is valid UTF-8, as a binary message otherwise.

```php
use Chapterjason\WebsocketBundle\Event\MessageEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class EchoListener
{
    #[AsEventListener]
    public function __invoke(MessageEvent $event): void
    {
        $event->setReply('echo: '.$event->getData());
    }
}
```

Services are reset after every event, as after every request.

A listener that throws is logged, and the connection is closed with 1011
(internal error); a throwing `CloseEvent` listener is only logged.
