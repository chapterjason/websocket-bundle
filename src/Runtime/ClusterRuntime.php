<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Runtime;

use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The runtime of the worker script of the cluster member option.
 */
final class ClusterRuntime extends WorkerRuntime
{
    protected function createRunner(KernelInterface $kernel, int $loopMax): WorkerRunner
    {
        return new ClusterWorkerRunner($kernel, $loopMax);
    }
}
