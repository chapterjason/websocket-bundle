<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\WebSocket;

use Chapterjason\WebsocketBundle\Exception\ExtensionNotLoadedException;
use Chapterjason\WebsocketBundle\Exception\WebSocketException;

final class WebSocket implements WebSocketInterface
{
    public function send(string $connection, string $data, MessageType $type = MessageType::Auto): void
    {
        self::ensureLoaded();
        self::check('websocket_send', websocket_send($connection, $data, $type->toConstant()));
    }

    public function sendToTag(string $tag, string $data, MessageType $type = MessageType::Auto): int
    {
        self::ensureLoaded();

        return self::check('websocket_send_to_tag', websocket_send_to_tag($tag, $data, $type->toConstant()));
    }

    public function broadcast(string $data, MessageType $type = MessageType::Auto): int
    {
        self::ensureLoaded();

        return self::check('websocket_broadcast', websocket_broadcast($data, $type->toConstant()));
    }

    public function ping(string $connection, string $payload = ''): void
    {
        self::ensureLoaded();
        self::check('websocket_ping', websocket_ping($connection, $payload));
    }

    public function close(string $connection, int $code = 1000, string $reason = ''): void
    {
        self::ensureLoaded();
        self::check('websocket_close', websocket_close($connection, $code, $reason));
    }

    public function isOpen(string $connection): bool
    {
        self::ensureLoaded();

        try {
            return self::check('websocket_is_open', websocket_is_open($connection));
        } catch (WebSocketException $exception) {
            if (WebSocketError::NotFound === $exception->getError() || WebSocketError::NotOpen === $exception->getError()) {
                return false;
            }

            throw $exception;
        }
    }

    public function getInstance(string $connection): string
    {
        self::ensureLoaded();

        return self::check('websocket_instance', websocket_instance($connection));
    }

    public function connections(): array
    {
        self::ensureLoaded();

        return self::check('websocket_connections', websocket_connections());
    }

    public function connectionsWithTag(string $tag): array
    {
        self::ensureLoaded();

        return self::check('websocket_connections_with_tag', websocket_connections_with_tag($tag));
    }

    public function setAttribute(string $connection, string $name, string $value): void
    {
        self::ensureLoaded();
        self::check('websocket_set_attribute', websocket_set_attribute($connection, $name, $value));
    }

    public function removeAttribute(string $connection, string $name): void
    {
        self::ensureLoaded();
        self::check('websocket_remove_attribute', websocket_remove_attribute($connection, $name));
    }

    public function attributes(string $connection): array
    {
        self::ensureLoaded();

        return self::check('websocket_attributes', websocket_attributes($connection));
    }

    public function tag(string $connection, string $tag): void
    {
        self::ensureLoaded();
        self::check('websocket_tag', websocket_tag($connection, $tag));
    }

    public function untag(string $connection, string $tag): void
    {
        self::ensureLoaded();
        self::check('websocket_untag', websocket_untag($connection, $tag));
    }

    public function tags(string $connection): array
    {
        self::ensureLoaded();

        return self::check('websocket_tags', websocket_tags($connection));
    }

    private static function ensureLoaded(): void
    {
        if (!\function_exists('websocket_last_error')) {
            throw new ExtensionNotLoadedException('websocket_last_error');
        }
    }

    /**
     * @template T of bool|int|string|list<string>|array<string, string>
     *
     * @param T $result
     *
     * @return T
     */
    private static function check(string $function, bool|int|string|array $result): bool|int|string|array
    {
        $code = websocket_last_error();

        if (\WEBSOCKET_ERROR_NONE !== $code) {
            throw new WebSocketException(WebSocketError::fromCode($code), $function, $result);
        }

        return $result;
    }
}
