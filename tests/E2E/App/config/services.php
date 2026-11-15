<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
        ->load('Chapterjason\\WebsocketBundle\\Tests\\E2E\\App\\Command\\', \dirname(__DIR__).'/Command/')
        ->load('Chapterjason\\WebsocketBundle\\Tests\\E2E\\App\\Controller\\', \dirname(__DIR__).'/Controller/')
        ->load('Chapterjason\\WebsocketBundle\\Tests\\E2E\\App\\EventListener\\', \dirname(__DIR__).'/EventListener/')
        ->set(\Chapterjason\WebsocketBundle\Tests\E2E\App\WorkerIdentity::class);
};
