<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\Unit\EventListener;

use Chapterjason\WebsocketBundle\Cluster\ClusterError;
use Chapterjason\WebsocketBundle\EventListener\ExceptionListener;
use Chapterjason\WebsocketBundle\Exception\ClusterException;
use Chapterjason\WebsocketBundle\Exception\WebSocketException;
use Chapterjason\WebsocketBundle\WebSocket\WebSocketError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ExceptionListenerTest extends TestCase
{
    /**
     * @return iterable<string, array{\Throwable, int}>
     */
    public static function provideExceptions(): iterable
    {
        yield 'websocket not found' => [new WebSocketException(WebSocketError::NotFound, 'websocket_send', false), 404];
        yield 'websocket not open' => [new WebSocketException(WebSocketError::NotOpen, 'websocket_send', false), 409];
        yield 'websocket invalid argument' => [new WebSocketException(WebSocketError::InvalidArgument, 'websocket_send', false), 400];
        yield 'websocket write failed' => [new WebSocketException(WebSocketError::WriteFailed, 'websocket_send', false), 502];
        yield 'websocket bus failed' => [new WebSocketException(WebSocketError::BusFailed, 'websocket_send', false), 503];
        yield 'websocket timeout' => [new WebSocketException(WebSocketError::Timeout, 'websocket_connections', []), 504];
        yield 'cluster not joined' => [new ClusterException(ClusterError::NotJoined, 'cluster_send'), 503];
        yield 'cluster invalid argument' => [new ClusterException(ClusterError::InvalidArgument, 'cluster_send'), 400];
        yield 'cluster bus failed' => [new ClusterException(ClusterError::BusFailed, 'cluster_send'), 503];
        yield 'cluster timeout' => [new ClusterException(ClusterError::Timeout, 'cluster_request'), 504];
        yield 'cluster no receiver' => [new ClusterException(ClusterError::NoReceiver, 'cluster_request'), 503];
    }

    #[DataProvider('provideExceptions')]
    public function testMapsTheErrorToAnHttpError(\Throwable $exception, int $statusCode): void
    {
        $event = self::event($exception);

        (new ExceptionListener())($event);

        $mapped = $event->getThrowable();
        self::assertInstanceOf(HttpException::class, $mapped);
        self::assertSame($statusCode, $mapped->getStatusCode());
        self::assertSame($exception->getMessage(), $mapped->getMessage());
        self::assertSame($exception, $mapped->getPrevious());
    }

    public function testLeavesOtherExceptionsAlone(): void
    {
        $exception = new \RuntimeException('Other.');
        $event = self::event($exception);

        (new ExceptionListener())($event);

        self::assertSame($exception, $event->getThrowable());
    }

    private static function event(\Throwable $exception): ExceptionEvent
    {
        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): never
            {
                throw new \LogicException('Not used.');
            }
        };

        return new ExceptionEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);
    }
}
