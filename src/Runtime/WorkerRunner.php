<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Runtime;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * Loops up to $loopMax times over the events FrankenPHP hands to an extension worker; 0 or less loops indefinitely.
 *
 * @internal
 */
abstract class WorkerRunner implements RunnerInterface
{
    private ?\Throwable $terminationException = null;

    /** @var array<mixed> */
    private array $server = [];

    public function __construct(
        protected readonly KernelInterface $kernel,
        private readonly int $loopMax,
    ) {
    }

    public function run(): int
    {
        if (!\function_exists('frankenphp_handle_request')) {
            throw new \LogicException(\sprintf('"%s" must run as a FrankenPHP worker script.', static::class));
        }

        ignore_user_abort(true);

        $this->server = array_filter($_SERVER, static fn (string $key): bool => !str_starts_with($key, 'HTTP_'), \ARRAY_FILTER_USE_KEY);
        $this->server['APP_RUNTIME_MODE'] = 'web=1&worker=1';

        $this->kernel->boot();
        $callback = $this->createCallback();

        $loops = 0;
        do {
            $continue = frankenphp_handle_request($callback);

            if (null !== $this->terminationException) {
                $exception = $this->terminationException;
                $this->terminationException = null;

                throw $exception;
            }

            gc_collect_cycles();
        } while ($continue && (0 >= $this->loopMax || ++$loops < $this->loopMax));

        return 0;
    }

    /**
     * The callback must call restoreServer() first.
     */
    abstract protected function createCallback(): \Closure;

    /**
     * Merges the environment of the worker into $_SERVER, which FrankenPHP resets for every request and event.
     */
    protected function restoreServer(): void
    {
        $_SERVER += $this->server;
    }

    /**
     * FrankenPHP reports an exception escaping the callback instead of propagating it, so it is kept to be thrown after the callback.
     */
    protected function deferException(\Throwable $exception): void
    {
        $this->terminationException = $exception;
    }
}
