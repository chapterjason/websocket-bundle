<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\WebSocket;

use Chapterjason\WebsocketBundle\Exception\WebSocketException;

/**
 * Reaches the WebSocket connections of this server and, in a cluster, of every member.
 *
 * Pushes to a connection on another member are fire and forget: they succeed once the message is queued for that member.
 *
 * @throws WebSocketException from every method when the extension reports an error
 */
interface WebSocketInterface
{
    public function send(string $connection, string $data, MessageType $type = MessageType::Auto): void;

    /**
     * @return int the number of connections the message was sent to
     */
    public function sendToTag(string $tag, string $data, MessageType $type = MessageType::Auto): int;

    /**
     * @return int the number of connections the message was sent to
     */
    public function broadcast(string $data, MessageType $type = MessageType::Auto): int;

    /**
     * Sends a ping of at most 125 bytes; the pong arrives as a PongEvent.
     */
    public function ping(string $connection, string $payload = ''): void;

    /**
     * Starts the closing handshake.
     *
     * @param int    $code   1000 to 1003, 1007 to 1014 or 3000 to 4999
     * @param string $reason at most 123 bytes
     */
    public function close(string $connection, int $code = 1000, string $reason = ''): void;

    /**
     * Whether messages can be sent to the connection; false for an unknown connection.
     */
    public function isOpen(string $connection): bool;

    /**
     * The instance ID of the member holding the connection, to address it with ClusterInterface.
     *
     * It asks no one: a connection on another member is named after it while that member is known to be alive, open or not.
     */
    public function getInstance(string $connection): string;

    /**
     * @return list<string> the IDs of the open connections
     */
    public function connections(): array;

    /**
     * @return list<string> the IDs of the open connections with the tag
     */
    public function connectionsWithTag(string $tag): array;

    public function setAttribute(string $connection, string $name, string $value): void;

    public function removeAttribute(string $connection, string $name): void;

    /**
     * @return array<string, string>
     */
    public function attributes(string $connection): array;

    public function tag(string $connection, string $tag): void;

    public function untag(string $connection, string $tag): void;

    /**
     * @return list<string>
     */
    public function tags(string $connection): array;
}
