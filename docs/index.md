# WebSocket Bundle

The bundle connects a Symfony application to the
[frankenphp-websocket](https://github.com/chapterjason/frankenphp-websocket)
extension. The extension holds the connections in Go; the bundle runs its PHP
workers on the Symfony kernel.

- [Requirements](requirements.md)
- [The WebSocket worker](worker.md)
- [The handshake](handshake.md)
- [Events](events.md)
- [Pushing to connections](websocket.md)
- [The cluster worker](cluster.md)
- [CLI participants](cli.md)
- [Errors](errors.md)
- [Services](services.md)
- [Development](development.md)
