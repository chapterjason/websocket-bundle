# WebSocket Bundle

Symfony integration for [frankenphp-websocket](https://github.com/chapterjason/frankenphp-websocket),
the FrankenPHP extension that gives PHP applications WebSocket support.

- Handshakes go through the Symfony kernel as ordinary requests, so routing,
  sessions and the firewall apply to them
- Connection events (`open`, `message`, `ping`, `pong`, `close`) become Symfony
  events with a reply
- `WebSocketInterface` pushes to connections from anywhere in the application,
  across the cluster
- `ClusterInterface` and `ClusterMessageEvent` for PHP messaging between the
  members of a cluster, and `ClusterLeftEvent` for the members that left
- `ClusterReceiverInterface` for commands that receive the messages of other
  members as CLI participants

Installation
============

Applications that use Symfony Flex
----------------------------------

    composer require chapterjason/websocket-bundle

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

    composer require chapterjason/websocket-bundle

### Step 2: Enable the Bundle

Add the bundle to `config/bundles.php`:

    // config/bundles.php
    return [
        // ...
        Chapterjason\WebsocketBundle\ChapterjasonWebsocketBundle::class => ['all' => true],
    ];

Documentation
=============

See [docs/index.md](docs/index.md).

License
=======

MIT, see [LICENSE](LICENSE).
