# Services

| Service | Alias |
|---|---|
| `chapterjason_websocket.websocket` | `Chapterjason\WebsocketBundle\WebSocket\WebSocketInterface` |
| `chapterjason_websocket.cluster` | `Chapterjason\WebsocketBundle\Cluster\ClusterInterface` |
| `chapterjason_websocket.cluster_receiver` | `Chapterjason\WebsocketBundle\Cluster\ClusterReceiverInterface` |

All are private; inject them by their interface. The workers log on the
`websocket` channel when MonologBundle is installed.
