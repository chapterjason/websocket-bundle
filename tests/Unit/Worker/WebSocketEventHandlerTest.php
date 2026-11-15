<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\Unit\Worker;

use Chapterjason\WebsocketBundle\Event\CloseEvent;
use Chapterjason\WebsocketBundle\Event\ConnectionEvent;
use Chapterjason\WebsocketBundle\Event\MessageEvent;
use Chapterjason\WebsocketBundle\Event\OpenEvent;
use Chapterjason\WebsocketBundle\Event\PingEvent;
use Chapterjason\WebsocketBundle\Event\PongEvent;
use Chapterjason\WebsocketBundle\Event\ReplyableEvent;
use Chapterjason\WebsocketBundle\WebSocket\WebSocketInterface;
use Chapterjason\WebsocketBundle\Worker\WebSocketEventHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @phpstan-import-type WebSocketPayload from WebSocketEventHandler
 * @phpstan-import-type OpenPayload from WebSocketEventHandler
 * @phpstan-import-type MessagePayload from WebSocketEventHandler
 * @phpstan-import-type PingPayload from WebSocketEventHandler
 * @phpstan-import-type ClosePayload from WebSocketEventHandler
 * @phpstan-import-type HandshakeRequestPayload from WebSocketEventHandler
 */
final class WebSocketEventHandlerTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    /**
     * @return iterable<string, array{WebSocketPayload, class-string<ConnectionEvent>}>
     */
    public static function provideEvents(): iterable
    {
        yield 'open' => [self::open(), OpenEvent::class];
        yield 'message' => [self::message('hello', false), MessageEvent::class];
        yield 'ping' => [self::ping('ping', 'p'), PingEvent::class];
        yield 'pong' => [self::ping('pong', 'p'), PongEvent::class];
        yield 'close' => [self::close(1000, 'bye'), CloseEvent::class];
    }

    /**
     * @param WebSocketPayload              $payload
     * @param class-string<ConnectionEvent> $class
     */
    #[DataProvider('provideEvents')]
    public function testDispatchesTheEventOfThePayload(array $payload, string $class): void
    {
        $dispatched = null;
        $this->dispatcher->addListener($class, static function (ConnectionEvent $event) use (&$dispatched): void {
            $dispatched = $event;

            if ($event instanceof ReplyableEvent) {
                $event->setReply('reply');
            }
        });

        $reply = $this->handler()->handle($payload);

        self::assertInstanceOf($class, $dispatched);
        self::assertSame('a.1', $dispatched->getConnection());
        self::assertSame(['user' => 'alice'], $dispatched->getAttributes());
        self::assertSame('alice', $dispatched->getAttribute('user'));
        self::assertNull($dispatched->getAttribute('missing'));
        self::assertSame(['users'], $dispatched->getTags());
        self::assertSame('/ws', $dispatched->getRequest()->uri);
        self::assertSame('websocket', $dispatched->getRequest()->getHeader('Upgrade'));
        self::assertSame(CloseEvent::class === $class ? null : 'reply', $reply);
    }

    public function testMapsTheFieldsOfEachEvent(): void
    {
        $events = [];
        foreach ([MessageEvent::class, PingEvent::class, PongEvent::class, CloseEvent::class] as $class) {
            $this->dispatcher->addListener($class, static function (ConnectionEvent $event) use (&$events): void {
                $events[] = $event;
            });
        }

        $handler = $this->handler();
        $handler->handle(self::message("\x00", true));
        $handler->handle(self::ping('ping', 'ping data'));
        $handler->handle(self::ping('pong', 'pong data'));
        $handler->handle(self::close(1006, ''));

        [$message, $ping, $pong, $close] = $events;
        self::assertInstanceOf(MessageEvent::class, $message);
        self::assertSame("\x00", $message->getData());
        self::assertTrue($message->isBinary());
        self::assertInstanceOf(PingEvent::class, $ping);
        self::assertSame('ping data', $ping->getData());
        self::assertInstanceOf(PongEvent::class, $pong);
        self::assertSame('pong data', $pong->getData());
        self::assertInstanceOf(CloseEvent::class, $close);
        self::assertSame(1006, $close->getCode());
        self::assertSame('', $close->getReason());
    }

    public function testReturnsNullWithoutReply(): void
    {
        self::assertNull($this->handler()->handle(self::open()));
    }

    public function testFailingListenerClosesTheConnectionWithInternalError(): void
    {
        $this->dispatcher->addListener(MessageEvent::class, static function (): void {
            throw new \RuntimeException('Boom.');
        });
        $webSocket = $this->createMock(WebSocketInterface::class);
        $webSocket->expects(self::once())->method('close')->with('a.1', 1011);
        $logger = new RecordingLogger();

        $reply = $this->handler($webSocket, $logger)->handle(self::message('x', false));

        self::assertNull($reply);
        self::assertCount(1, $logger->records);
        self::assertSame('error', $logger->records[0]['level']);
        self::assertSame('Boom.', $logger->records[0]['context']['message']);
    }

    public function testFailingCloseListenerDoesNotCloseAgain(): void
    {
        $this->dispatcher->addListener(CloseEvent::class, static function (): void {
            throw new \RuntimeException('Boom.');
        });
        $webSocket = $this->createMock(WebSocketInterface::class);
        $webSocket->expects(self::never())->method('close');
        $logger = new RecordingLogger();

        $this->handler($webSocket, $logger)->handle(self::close(1000, ''));

        self::assertCount(1, $logger->records);
    }

    public function testFailureToCloseIsLogged(): void
    {
        $this->dispatcher->addListener(OpenEvent::class, static function (): void {
            throw new \RuntimeException('Boom.');
        });
        $webSocket = self::createStub(WebSocketInterface::class);
        $webSocket->method('close')->willThrowException(new \RuntimeException('Gone.'));
        $logger = new RecordingLogger();

        $this->handler($webSocket, $logger)->handle(self::open());

        self::assertSame(['Boom.', 'Gone.'], array_map(static fn (array $record): mixed => $record['context']['message'], $logger->records));
    }

    public function testResetsTheServicesAfterEveryEvent(): void
    {
        $this->dispatcher->addListener(MessageEvent::class, static function (): void {
            throw new \RuntimeException('Boom.');
        });
        $resetter = $this->createMock(ResetInterface::class);
        $resetter->expects(self::exactly(2))->method('reset');

        $handler = new WebSocketEventHandler($this->dispatcher, self::createStub(WebSocketInterface::class), null, $resetter);
        $handler->handle(self::open());
        $handler->handle(self::message('x', false));
    }

    private function handler(?WebSocketInterface $webSocket = null, ?RecordingLogger $logger = null): WebSocketEventHandler
    {
        return new WebSocketEventHandler($this->dispatcher, $webSocket ?? self::createStub(WebSocketInterface::class), $logger);
    }

    /**
     * @return OpenPayload
     */
    private static function open(): array
    {
        return ['event' => 'open'] + self::connection();
    }

    /**
     * @return MessagePayload
     */
    private static function message(string $data, bool $binary): array
    {
        return ['event' => 'message', 'data' => $data, 'binary' => $binary] + self::connection();
    }

    /**
     * @param 'ping'|'pong' $event
     *
     * @return PingPayload
     */
    private static function ping(string $event, string $data): array
    {
        return ['event' => $event, 'data' => $data] + self::connection();
    }

    /**
     * @return ClosePayload
     */
    private static function close(int $code, string $reason): array
    {
        return ['event' => 'close', 'code' => $code, 'reason' => $reason] + self::connection();
    }

    /**
     * @return array{connection: string, attributes: array<string, string>, tags: list<string>, request: HandshakeRequestPayload}
     */
    private static function connection(): array
    {
        return [
            'connection' => 'a.1',
            'attributes' => ['user' => 'alice'],
            'tags' => ['users'],
            'request' => [
                'method' => 'GET',
                'uri' => '/ws',
                'protocol' => 'HTTP/1.1',
                'remote_addr' => '127.0.0.1:1234',
                'headers' => ['upgrade' => 'websocket'],
            ],
        ];
    }
}
