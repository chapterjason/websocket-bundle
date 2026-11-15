# Pushing to connections

`WebSocketInterface` works from the WebSocket worker and from every other
request. In a cluster, it reaches the connections of every member.

| Method | Does |
|---|---|
| `send($connection, $data, $type)` | Sends a message; `MessageType::Auto` (default), `Text` or `Binary`. |
| `sendToTag($tag, $data, $type)` | Sends to every connection with the tag; returns to how many. |
| `broadcast($data, $type)` | Sends to every connection; returns to how many. |
| `ping($connection, $payload)` | Sends a ping of at most 125 bytes; the pong arrives as a `PongEvent`. |
| `close($connection, $code, $reason)` | Starts the closing handshake. |
| `isOpen($connection)` | Whether messages can be sent to the connection; `false` for an unknown one. |
| `getInstance($connection)` | The instance ID of the member holding the connection, to address it with `ClusterInterface`. It asks no one: a connection on another member is named after it while that member is alive, open or not. |
| `connections()` | The IDs of the open connections. |
| `connectionsWithTag($tag)` | The IDs of the open connections with the tag. |
| `setAttribute()`, `removeAttribute()`, `attributes()` | The attributes of a connection. |
| `tag()`, `untag()`, `tags()` | The tags of a connection. |

Pushes to a connection on another member are fire and forget: they succeed once
the message is queued for that member.
