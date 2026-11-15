<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Chapterjason\WebsocketBundle\Cluster\Cluster;
use Chapterjason\WebsocketBundle\Cluster\ClusterInterface;
use Chapterjason\WebsocketBundle\Cluster\ClusterReceiver;
use Chapterjason\WebsocketBundle\Cluster\ClusterReceiverInterface;
use Chapterjason\WebsocketBundle\EventListener\ExceptionListener;
use Chapterjason\WebsocketBundle\Handshake\HandshakeValueResolver;
use Chapterjason\WebsocketBundle\Runtime\ClusterWorkerRunner;
use Chapterjason\WebsocketBundle\Runtime\WebSocketWorkerRunner;
use Chapterjason\WebsocketBundle\WebSocket\WebSocket;
use Chapterjason\WebsocketBundle\WebSocket\WebSocketInterface;
use Chapterjason\WebsocketBundle\Worker\ClusterEventHandler;
use Chapterjason\WebsocketBundle\Worker\WebSocketEventHandler;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('chapterjason_websocket.websocket', WebSocket::class);
    $services->alias(WebSocketInterface::class, 'chapterjason_websocket.websocket');

    $services->set('chapterjason_websocket.cluster', Cluster::class);
    $services->alias(ClusterInterface::class, 'chapterjason_websocket.cluster');

    $services->set('chapterjason_websocket.cluster_receiver', ClusterReceiver::class)
        ->args([service(ClusterWorkerRunner::HANDLER_SERVICE)]);
    $services->alias(ClusterReceiverInterface::class, 'chapterjason_websocket.cluster_receiver');

    $services->set('.chapterjason_websocket.exception_listener', ExceptionListener::class)
        ->tag('kernel.event_listener', ['event' => 'kernel.exception']);

    $services->set('.chapterjason_websocket.handshake_value_resolver', HandshakeValueResolver::class)
        ->tag('controller.argument_value_resolver', ['priority' => 150]);

    // Public as the worker runners fetch them from the booted kernel.
    $services->set(WebSocketWorkerRunner::HANDLER_SERVICE, WebSocketEventHandler::class)
        ->public()
        ->args([
            service('event_dispatcher'),
            service('chapterjason_websocket.websocket'),
            service('logger')->nullOnInvalid(),
            service('services_resetter')->nullOnInvalid(),
        ])
        ->tag('monolog.logger', ['channel' => 'websocket']);

    $services->set(ClusterWorkerRunner::HANDLER_SERVICE, ClusterEventHandler::class)
        ->public()
        ->args([
            service('event_dispatcher'),
            service('logger')->nullOnInvalid(),
            service('services_resetter')->nullOnInvalid(),
        ])
        ->tag('monolog.logger', ['channel' => 'websocket']);
};
