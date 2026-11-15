# Development

| Command | Does |
|---|---|
| `composer test` | Runs the unit and integration tests. |
| `composer test:e2e` | Starts a cluster of two members with `compose.yaml` and runs the e2e tests against it, which start CLI participants with `frankenphp php-cli`, in Docker. The image (`IMAGE`, default `chapterjason/frankenphp-websocket:edge`) is FrankenPHP with the extension. |
| `composer phpstan` | Runs PHPStan at the maximum level; `stubs/frankenphp.php` declares the functions of FrankenPHP and the extension. |
| `composer cs` | Checks the code style; `composer cs:fix` fixes it. |
