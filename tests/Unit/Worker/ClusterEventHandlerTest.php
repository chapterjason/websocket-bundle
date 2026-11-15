<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\Unit\Worker;

use Chapterjason\WebsocketBundle\Cluster\LeaveReason;
use Chapterjason\WebsocketBundle\Cluster\MemberRole;
use Chapterjason\WebsocketBundle\Event\ClusterLeftEvent;
use Chapterjason\WebsocketBundle\Event\ClusterMessageEvent;
use Chapterjason\WebsocketBundle\Worker\ClusterEventHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Service\ResetInterface;

final class ClusterEventHandlerTest extends TestCase
{
    public function testAnswersRequests(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ClusterMessageEvent::class, static function (ClusterMessageEvent $event): void {
            $event->setReply(\sprintf('%s:%s:%s', $event->getFrom(), $event->getData(), $event->isRequest() ? 'request' : 'message'));
        });

        $handler = new ClusterEventHandler($dispatcher);

        self::assertSame('b:hi:request', $handler->handle(['event' => 'message', 'from' => 'b', 'data' => 'hi', 'request' => true]));
        self::assertNull($handler->handle(['event' => 'message', 'from' => 'b', 'data' => 'hi', 'request' => false]));
    }

    public function testFailingListenerIsLoggedAndLeavesTheRequestWithoutAnswer(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ClusterMessageEvent::class, static function (): void {
            throw new \RuntimeException('Boom.');
        });
        $logger = new RecordingLogger();

        self::assertNull((new ClusterEventHandler($dispatcher, $logger))->handle(['event' => 'message', 'from' => 'b', 'data' => 'hi', 'request' => true]));
        self::assertSame('Boom.', $logger->records[0]['context']['message']);
    }

    public function testDispatchesMembersThatLeft(): void
    {
        $events = [];
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ClusterLeftEvent::class, static function (ClusterLeftEvent $event) use (&$events): void {
            $events[] = $event;
        });
        $handler = new ClusterEventHandler($dispatcher);

        self::assertNull($handler->handle(['event' => 'left', 'instance' => 'b', 'reason' => 'leave', 'role' => 'server']));
        self::assertNull($handler->handle(['event' => 'left', 'instance' => 'c', 'reason' => 'timeout', 'role' => 'cli']));

        self::assertSame(
            [['b', LeaveReason::Leave, MemberRole::Server], ['c', LeaveReason::Timeout, MemberRole::Cli]],
            array_map(static fn (ClusterLeftEvent $event): array => [$event->getInstance(), $event->getReason(), $event->getRole()], $events),
        );
    }

    public function testFailingLeftListenerIsLoggedWithTheMember(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ClusterLeftEvent::class, static function (): void {
            throw new \RuntimeException('Boom.');
        });
        $logger = new RecordingLogger();

        self::assertNull((new ClusterEventHandler($dispatcher, $logger))->handle(['event' => 'left', 'instance' => 'b', 'reason' => 'timeout', 'role' => 'server']));
        self::assertSame('left', $logger->records[0]['context']['event']);
        self::assertSame('b', $logger->records[0]['context']['member']);
        self::assertSame('Boom.', $logger->records[0]['context']['message']);
    }

    public function testResetsTheServicesAfterEveryEvent(): void
    {
        $resetter = $this->createMock(ResetInterface::class);
        $resetter->expects(self::exactly(2))->method('reset');

        $handler = new ClusterEventHandler(new EventDispatcher(), null, $resetter);
        $handler->handle(['event' => 'message', 'from' => 'b', 'data' => 'hi', 'request' => false]);
        $handler->handle(['event' => 'left', 'instance' => 'b', 'reason' => 'leave', 'role' => 'server']);
    }
}
