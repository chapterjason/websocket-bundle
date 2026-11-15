# CLI participants

Commands run with `frankenphp php-cli`, such as a Messenger consumer or a
scheduler, take part in the cluster as CLI participants when
`FRANKENPHP_CLUSTER_CONFIG` names the configuration of the servers:

```console
FRANKENPHP_CLUSTER_CONFIG=/etc/frankenphp/Caddyfile frankenphp php-cli bin/console messenger:consume async
```

They join on their first call that needs the cluster, and use
`WebSocketInterface` and `ClusterInterface` as any request does. They hold no
connections, and receive nothing until they ask for it.

## Receiving

`ClusterReceiverInterface::receive($timeout)` waits up to `$timeout`
milliseconds for a message, and dispatches it as a `ClusterMessageEvent`, with
the same listeners and answers as the [cluster worker](cluster.md). It returns
whether a message came.

```php
use Chapterjason\WebsocketBundle\Cluster\ClusterReceiverInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand('app:receive')]
final class ReceiveCommand
{
    public function __construct(
        private readonly ClusterReceiverInterface $receiver,
    ) {
    }

    public function __invoke(): int
    {
        $until = time() + 3600;

        while (time() < $until) {
            $this->receiver->receive(1000);
        }

        return Command::SUCCESS;
    }
}
```

- A participant receives from its first call of `receive()` on: before,
  requests to it fail with `ClusterError::NoReceiver`, and publishes leave it
  out. A command that only sends never receives.
- PHP runs signal handlers only after the wait: keep the timeout short in a
  command that stops on a signal.
- Services are reset after every message, as after every request.
- In a server, `receive()` throws an `Error`: the cluster worker gets the
  messages there.

When the command ends, the participant leaves with a goodbye; one server
dispatches a `ClusterLeftEvent` with `MemberRole::Cli`.
