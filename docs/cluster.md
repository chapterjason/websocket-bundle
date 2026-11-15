# The cluster worker

The worker of `cluster member` gets what the other members send, and the
members that left. Its script selects the `ClusterRuntime`:

```php
<?php
// bin/cluster.php

use App\Kernel;
use Chapterjason\WebsocketBundle\Runtime\ClusterRuntime;

$_SERVER['APP_RUNTIME'] = ClusterRuntime::class;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static fn (array $context) => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
```

```caddyfile
{
	cluster mesh {
		listen :7070
		dns members.internal:7070
		secret {env.CLUSTER_SECRET}
	}

	cluster member {
		worker bin/cluster.php 2
	}
}
```

Every message is dispatched as a `ClusterMessageEvent` with `getFrom()`,
`getData()` and `isRequest()`; the reply of a request is its answer:

```php
use Chapterjason\WebsocketBundle\Event\ClusterMessageEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class StatusListener
{
    #[AsEventListener]
    public function __invoke(ClusterMessageEvent $event): void
    {
        if ($event->isRequest() && 'status' === $event->getData()) {
            $event->setReply('ok');
        }
    }
}
```

`ClusterInterface` sends to the other members from anywhere in the application:

| Method | Does |
|---|---|
| `getInstanceId()` | The instance ID of this member. |
| `getMembers()` | The instance IDs of the other members known to be alive, [CLI participants](cli.md) included. |
| `getMemberInfo()` | The same members, by instance ID, as a `MemberInfo`: its `role` (`MemberRole::Server` or `Cli`) and whether it `receives`. |
| `send($instance, $data)` | Fire and forget to one member. |
| `publish($data)` | To all other members that receive; returns for how many it was queued. |
| `request($instance, $data, $timeout)` | Waits for the answer of one member; `$timeout` in milliseconds, `0` waits the `query_timeout`. |

A server receives when it has a cluster worker, a CLI participant once it
called `ClusterReceiverInterface::receive()`. `send()` and `request()` to a
member that does not receive fail with `ClusterError::NoReceiver`, and
`publish()` leaves it out.

A listener that throws is logged; a request then gets an empty answer.
Instance IDs change when a member reloads its configuration.

## When a member leaves

A member that leaves is dispatched once, as a `ClusterLeftEvent`, in the
cluster worker of one server:

| Method | Returns |
|---|---|
| `getInstance()` | The instance ID of the member. |
| `getReason()` | `LeaveReason::Leave`: it said goodbye, as a server that stops or reloads and a CLI participant that ends do. `LeaveReason::Timeout`: it fell silent, as a server that crashed or was cut off. |
| `getRole()` | `MemberRole::Server`, or `MemberRole::Cli` for a [CLI participant](cli.md). |

A server that times out runs no `CloseEvent` for its connections. To clean up
after it, store the instance of each connection when it opens, with
`WebSocketInterface::getInstance()`, and remove what is stored under the
instance of a `ClusterLeftEvent` with `LeaveReason::Timeout`:

```php
use Chapterjason\WebsocketBundle\Cluster\LeaveReason;
use Chapterjason\WebsocketBundle\Cluster\MemberRole;
use Chapterjason\WebsocketBundle\Event\ClusterLeftEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class PresenceCleanup
{
    #[AsEventListener]
    public function __invoke(ClusterLeftEvent $event): void
    {
        if (LeaveReason::Timeout === $event->getReason() && MemberRole::Server === $event->getRole()) {
            // remove the connections stored under $event->getInstance()
        }
    }
}
```

After a network split, both sides may report the same member: keep what a
listener does safe to repeat.
