<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E\App;

/**
 * Identifies the worker that booted the container, which changes when the worker restarts.
 */
final class WorkerIdentity
{
    public readonly string $id;

    public function __construct()
    {
        $this->id = bin2hex(random_bytes(8));
    }
}
