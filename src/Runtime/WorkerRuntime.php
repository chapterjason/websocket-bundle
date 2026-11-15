<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Runtime;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;

/**
 * A runtime for an extension worker script; the script must return a closure creating the kernel.
 *
 * @internal
 */
abstract class WorkerRuntime extends SymfonyRuntime
{
    public function getRunner(?object $application): RunnerInterface
    {
        if (!$application instanceof KernelInterface) {
            throw new \LogicException(\sprintf('The worker script must return a closure creating a "%s", "%s" given.', KernelInterface::class, get_debug_type($application)));
        }

        $loopMax = $this->options['worker_loop_max'];

        if (!\is_int($loopMax)) {
            throw new \LogicException(\sprintf('The "worker_loop_max" runtime option must be an integer, "%s" given.', get_debug_type($loopMax)));
        }

        return $this->createRunner($application, $loopMax);
    }

    abstract protected function createRunner(KernelInterface $kernel, int $loopMax): WorkerRunner;
}
