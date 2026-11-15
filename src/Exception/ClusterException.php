<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Exception;

use Chapterjason\WebsocketBundle\Cluster\ClusterError;

final class ClusterException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
        private readonly ClusterError $error,
        private readonly string $function,
    ) {
        parent::__construct(\sprintf('The call to "%s()" failed with the error "%s".', $function, $error->name));
    }

    public function getError(): ClusterError
    {
        return $this->error;
    }

    public function getFunction(): string
    {
        return $this->function;
    }
}
