<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Event;

/**
 * The connection is up; a CloseEvent follows every OpenEvent, unless the server holding the connection crashes.
 */
final class OpenEvent extends ReplyableEvent
{
}
