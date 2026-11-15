# Changelog

All notable changes are documented in this file. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `WebSocketRuntime` and `ClusterRuntime`, which run the worker scripts of the
  `websocket` directive and of `cluster member` on the Symfony kernel, with the
  `worker_loop_max` runtime option.
- Handshakes through the kernel as ordinary requests, so routing, sessions and
  the firewall apply; the `Handshake` controller argument, and `426 Upgrade
  Required` for requests that are no handshake.
- `OpenEvent`, `MessageEvent`, `PingEvent`, `PongEvent` and `CloseEvent`, with
  replies sent back to the connection; a failing listener closes the
  connection with 1011.
- `ClusterMessageEvent` for the messages of other members, with answers to
  requests.
- `ClusterLeftEvent` for the members that left the cluster, with the
  `LeaveReason` and the `MemberRole`.
- `ClusterReceiverInterface`, which dispatches the messages of a CLI
  participant run with `frankenphp php-cli` as `ClusterMessageEvent`.
- `WebSocketInterface::getInstance()`, the member holding a connection, and
  `ClusterInterface::getMemberInfo()`, the role of each member and whether it
  receives.
- `WebSocketInterface` and `ClusterInterface`, which throw
  `WebSocketException` and `ClusterException` with the error of the extension.
- A `kernel.exception` listener mapping `WebSocketException` and
  `ClusterException` to HTTP errors.
- Services reset after every event, as after every request.
