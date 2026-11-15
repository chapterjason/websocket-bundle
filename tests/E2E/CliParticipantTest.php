<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E;

/**
 * Runs commands of the app as CLI participants of the cluster of "a" and "b".
 */
final class CliParticipantTest extends E2ETestCase
{
    /** @var list<array{process: resource, log: string}> */
    private array $participants = [];

    protected function tearDown(): void
    {
        foreach ($this->participants as $participant) {
            if (proc_get_status($participant['process'])['running']) {
                proc_terminate($participant['process'], 9);
            }

            proc_close($participant['process']);
            unlink($participant['log']);
        }

        $this->participants = [];
    }

    public function testParticipantAnswersRequestsAndLeavesWithAGoodbye(): void
    {
        [$alice] = self::join('a', 'alice');
        $a = self::instanceId('a');
        $participant = $this->startParticipant(['app:receive', '--limit=1']);
        $cli = self::waitForParticipant(true);

        $answer = self::http('a')->request('POST', '/members/'.$cli.'/requests', ['body' => '"status"'])->toArray();

        self::assertSame(['member' => $cli, 'from' => $a, 'data' => 'status'], $answer);
        self::assertSame(['type' => 'member-left', 'instance' => $cli, 'reason' => 'leave', 'role' => 'cli'], self::receiveType($alice, 'member-left'));
        self::assertSame(0, self::waitForExit($participant), (string) file_get_contents($participant['log']));

        $alice->close();
    }

    public function testParticipantThatDoesNotReceiveIsLeftOut(): void
    {
        [$alice] = self::join('a', 'alice');
        $a = self::instanceId('a');
        $b = self::instanceId('b');
        $participant = $this->startParticipant(['app:members', '--stay=5']);
        $cli = self::waitForParticipant(false);
        $http = self::http('a');

        self::assertSame(503, $http->request('POST', '/members/'.$cli.'/requests', ['body' => '"status"'])->getStatusCode());
        self::assertSame(503, $http->request('POST', '/members/'.$cli.'/messages', ['body' => '"sent"'])->getStatusCode());
        self::assertSame(['delivered' => 1], $http->request('POST', '/members/messages', ['body' => '"published"'])->toArray());

        self::assertSame(0, self::waitForExit($participant), (string) file_get_contents($participant['log']));
        self::assertEquals(
            [$a => ['role' => 'server', 'receives' => true], $b => ['role' => 'server', 'receives' => true]],
            json_decode(strtok((string) file_get_contents($participant['log']), "\n") ?: '', true),
        );
        self::assertSame(['type' => 'member-left', 'instance' => $cli, 'reason' => 'leave', 'role' => 'cli'], self::receiveType($alice, 'member-left'));

        $alice->close();
    }

    public function testKilledParticipantIsReportedAfterATimeout(): void
    {
        [$alice] = self::join('a', 'alice', 15);
        $participant = $this->startParticipant(['app:receive']);
        $cli = self::waitForParticipant(true);

        proc_terminate($participant['process'], 9);

        self::assertSame(['type' => 'member-left', 'instance' => $cli, 'reason' => 'timeout', 'role' => 'cli'], self::receiveType($alice, 'member-left'));

        $alice->close();
    }

    /**
     * @param list<string> $arguments the command and its options
     *
     * @return array{process: resource, log: string}
     */
    private function startParticipant(array $arguments): array
    {
        $log = tempnam(sys_get_temp_dir(), 'cli-participant-');
        self::assertIsString($log);

        $process = proc_open(
            ['frankenphp', 'php-cli', __DIR__.'/App/bin/console.php', ...$arguments],
            [0 => ['file', '/dev/null', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']],
            $pipes,
            null,
            ['FRANKENPHP_CLUSTER_CONFIG' => __DIR__.'/App/Caddyfile', 'E2E_PEER' => 'a:7070 b:7070'] + getenv(),
        );
        self::assertIsResource($process);

        return $this->participants[] = ['process' => $process, 'log' => $log];
    }

    /**
     * Waits until both servers know the only CLI participant, receiving or not, and returns its instance ID.
     */
    private static function waitForParticipant(bool $receives): string
    {
        $expected = ['role' => 'cli', 'receives' => $receives];
        $deadline = microtime(true) + 15;

        do {
            $participants = array_keys(array_filter(self::memberInfo('a'), static fn (mixed $info): bool => $expected === $info));

            if (1 === \count($participants) && $expected === (self::memberInfo('b')[$participants[0]] ?? null)) {
                return (string) $participants[0];
            }

            usleep(100_000);
        } while (microtime(true) < $deadline);

        self::fail('The CLI participant did not join the cluster.');
    }

    /**
     * @return array<mixed>
     */
    private static function memberInfo(string $member): array
    {
        return self::http($member)->request('GET', '/members')->toArray();
    }

    /**
     * @param array{process: resource, log: string} $participant
     */
    private static function waitForExit(array $participant): int
    {
        $deadline = microtime(true) + 10;

        do {
            $status = proc_get_status($participant['process']);

            if (!$status['running']) {
                return $status['exitcode'];
            }

            usleep(100_000);
        } while (microtime(true) < $deadline);

        self::fail('The CLI participant did not exit.');
    }
}
