<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Exception;

final class ExtensionNotLoadedException extends \LogicException implements ExceptionInterface
{
    public function __construct(string $function)
    {
        parent::__construct(\sprintf('The function "%s()" is not available: run this code in FrankenPHP built with the frankenphp-websocket extension.', $function));
    }
}
