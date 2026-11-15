<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Security\Core\User\InMemoryUser;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'session' => ['handler_id' => null],
    ]);

    $container->extension('security', [
        'password_hashers' => [InMemoryUser::class => 'plaintext'],
        'providers' => [
            'users' => [
                'memory' => [
                    'users' => [
                        'alice' => ['password' => 'alice-password', 'roles' => ['ROLE_USER']],
                        'bob' => ['password' => 'bob-password', 'roles' => ['ROLE_USER']],
                    ],
                ],
            ],
        ],
        'firewalls' => [
            'main' => [
                'lazy' => true,
                'provider' => 'users',
                'json_login' => ['check_path' => '/login'],
            ],
        ],
        'access_control' => [
            ['path' => '^/chat', 'roles' => 'ROLE_USER'],
        ],
    ]);
};
