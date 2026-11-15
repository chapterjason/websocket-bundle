<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Cluster;

final readonly class MemberInfo
{
    public function __construct(
        public MemberRole $role,
        /** Whether the member takes what is sent to it: a server with a cluster worker, a CLI participant once it called receive. */
        public bool $receives,
    ) {
    }
}
