# Errors

Every method of `WebSocketInterface` throws a `WebSocketException` when the
extension reports an error; `getError()` tells which:

| `WebSocketError` | Meaning |
|---|---|
| `NotFound` | There is no such connection. |
| `NotOpen` | The connection is not open yet, or closing. |
| `InvalidArgument` | An argument is invalid, such as forced text that is not UTF-8. |
| `WriteFailed` | The write failed; the connection ends. |
| `BusFailed` | The queue to another member was full. |
| `Timeout` | A member did not answer within `query_timeout`. |

For methods returning a count or a list, `getResult()` holds what the
extension could gather, such as the connections of the members that answered
before a timeout.

`ClusterInterface` and `ClusterReceiverInterface` throw a `ClusterException`
with a `ClusterError`:

| `ClusterError` | Meaning |
|---|---|
| `NotJoined` | This server or command is not a member. |
| `InvalidArgument` | An empty instance ID, or a negative timeout. |
| `BusFailed` | The queue to a member was full. |
| `Timeout` | The member did not answer in time. |
| `NoReceiver` | The member does not receive: a server without a cluster worker, or a CLI participant that has not called `receive()` yet. |

A `kernel.exception` listener maps both when they escape a controller:

| Error | HTTP status |
|---|---|
| `NotFound` | 404 |
| `NotOpen` | 409 |
| `InvalidArgument` | 400 |
| `WriteFailed` | 502 |
| `BusFailed`, `NotJoined`, `NoReceiver` | 503 |
| `Timeout` | 504 |

Outside FrankenPHP, both throw an `ExtensionNotLoadedException`. All
exceptions implement `Chapterjason\WebsocketBundle\Exception\ExceptionInterface`.
