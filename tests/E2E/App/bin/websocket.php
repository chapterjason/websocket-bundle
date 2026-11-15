<?php

declare(strict_types=1);

use Chapterjason\WebsocketBundle\ChapterjasonWebsocketBundle;
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\SecurityBundle\SecurityBundle;

$_SERVER['APP_RUNTIME'] = Chapterjason\WebsocketBundle\Runtime\WebSocketRuntime::class;
$_SERVER['APP_RUNTIME_OPTIONS'] = ['worker_loop_max' => 10];

require_once dirname(__DIR__, 4).'/vendor/autoload_runtime.php';

return static function (): TestKernel {
    $kernel = new TestKernel('test', true);
    $kernel->addTestBundle(SecurityBundle::class);
    $kernel->addTestBundle(ChapterjasonWebsocketBundle::class);
    $kernel->addTestConfig(dirname(__DIR__).'/config/security.php');
    $kernel->addTestConfig(dirname(__DIR__).'/config/services.php');
    $kernel->addTestRoutingFile(dirname(__DIR__).'/config/routes.php');

    return $kernel;
};
