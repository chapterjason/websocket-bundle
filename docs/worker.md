# The WebSocket worker

The worker script of the `websocket` directive returns the kernel, like
`public/index.php`, and selects the `WebSocketRuntime`:

```php
<?php
// bin/websocket.php

use App\Kernel;
use Chapterjason\WebsocketBundle\Runtime\WebSocketRuntime;

$_SERVER['APP_RUNTIME'] = WebSocketRuntime::class;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static fn (array $context) => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
```

Keep the script out of `public/`, so it cannot be requested as a page:

```caddyfile
example.com {
	root public/

	websocket /ws {
		worker bin/websocket.php 4
	}

	php_server
}
```

The runtime takes the options of Symfony's runtime; `worker_loop_max`
(`FRANKENPHP_LOOP_MAX`, default 500) restarts the worker after that many
handshakes and events. Restarting a worker does not drop connections: the
extension holds them.
