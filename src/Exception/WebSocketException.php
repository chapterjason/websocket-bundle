<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Exception;

use Chapterjason\WebsocketBundle\WebSocket\WebSocketError;

final class WebSocketException extends \RuntimeException implements ExceptionInterface
{
    /**
     * @param bool|int|string|list<string>|array<string, string> $result what the function returned despite the error
     */
    public function __construct(
        private readonly WebSocketError $error,
        private readonly string $function,
        private readonly bool|int|string|array $result,
    ) {
        parent::__construct(\sprintf('The call to "%s()" failed with the error "%s".', $function, $error->name));
    }

    public function getError(): WebSocketError
    {
        return $this->error;
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * Counts and lists hold what the function could gather, such as the connections of the instances that answered before a timeout.
     *
     * @return bool|int|string|list<string>|array<string, string>
     */
    public function getResult(): bool|int|string|array
    {
        return $this->result;
    }
}
