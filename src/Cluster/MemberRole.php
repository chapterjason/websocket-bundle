<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Cluster;

enum MemberRole: string
{
    /** A server, which holds WebSocket connections. */
    case Server = 'server';

    /** A CLI participant, which holds no connections. */
    case Cli = 'cli';
}
