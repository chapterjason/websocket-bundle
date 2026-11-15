<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Cluster;

use Chapterjason\WebsocketBundle\Exception\ClusterException;
use Chapterjason\WebsocketBundle\Exception\ExtensionNotLoadedException;

final class Cluster implements ClusterInterface
{
    public function getInstanceId(): string
    {
        self::ensureLoaded();

        return self::check('cluster_self', cluster_self());
    }

    public function getMembers(): array
    {
        self::ensureLoaded();

        return self::check('cluster_members', cluster_members());
    }

    public function getMemberInfo(): array
    {
        self::ensureLoaded();

        $members = [];

        foreach (self::check('cluster_member_info', cluster_member_info()) as $instance => $info) {
            $members[(string) $instance] = new MemberInfo(MemberRole::from($info['role']), $info['receives']);
        }

        return $members;
    }

    public function send(string $instance, string $data): void
    {
        self::ensureLoaded();
        self::check('cluster_send', cluster_send($instance, $data));
    }

    public function publish(string $data): int
    {
        self::ensureLoaded();

        return self::check('cluster_publish', cluster_publish($data));
    }

    public function request(string $instance, string $data, int $timeout = 0): string
    {
        self::ensureLoaded();

        return self::check('cluster_request', cluster_request($instance, $data, $timeout));
    }

    private static function ensureLoaded(): void
    {
        if (!\function_exists('cluster_last_error')) {
            throw new ExtensionNotLoadedException('cluster_last_error');
        }
    }

    /**
     * @template T of bool|int|string|list<string>|array<string, array{role: 'server'|'cli', receives: bool}>
     *
     * @param T $result
     *
     * @return T
     */
    private static function check(string $function, bool|int|string|array $result): bool|int|string|array
    {
        $code = cluster_last_error();

        if (\CLUSTER_ERROR_NONE !== $code) {
            throw new ClusterException(ClusterError::fromCode($code), $function);
        }

        return $result;
    }
}
