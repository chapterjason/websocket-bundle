<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E;

final class ClusterTest extends E2ETestCase
{
    public function testMembersKnowEachOther(): void
    {
        $a = self::http('a')->request('GET', '/health')->toArray();
        $b = self::http('b')->request('GET', '/health')->toArray();

        self::assertNotSame($a['instance'], $b['instance']);
        self::assertSame([$b['instance']], $a['members']);
        self::assertSame([$a['instance']], $b['members']);
    }

    public function testRequestIsAnsweredByTheOtherMember(): void
    {
        $a = self::instanceId('a');
        $b = self::instanceId('b');

        $answer = self::http('a')->request('POST', '/members/'.$b.'/requests', ['body' => '"status"'])->toArray();

        self::assertSame(['member' => $b, 'from' => $a, 'data' => 'status'], $answer);
    }

    public function testFailingListenerLeavesTheRequestWithoutAnswer(): void
    {
        $b = self::instanceId('b');

        self::assertSame('', self::http('a')->request('POST', '/members/'.$b.'/requests', ['body' => 'not json'])->getContent());
    }

    public function testRequestToUnknownMemberTimesOut(): void
    {
        self::assertSame(504, self::http('a')->request('POST', '/members/unknown/requests', ['body' => '"status"'])->getStatusCode());
    }

    public function testSentAndPublishedMessagesReachTheOtherMember(): void
    {
        $a = self::instanceId('a');
        $b = self::instanceId('b');
        [$alice] = self::join('b', 'alice');

        self::assertSame(202, self::http('a')->request('POST', '/members/'.$b.'/messages', ['body' => '"sent"'])->getStatusCode());
        self::assertSame(['type' => 'member', 'member' => $b, 'from' => $a, 'data' => 'sent'], self::receive($alice));

        self::assertSame(['delivered' => 1], self::http('a')->request('POST', '/members/messages', ['body' => '"published"'])->toArray());
        self::assertSame(['type' => 'member', 'member' => $b, 'from' => $a, 'data' => 'published'], self::receive($alice));

        $alice->close();
    }
}
