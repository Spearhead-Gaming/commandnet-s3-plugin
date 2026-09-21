<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

/**
 * Only imported when the Discord plugin is installed (see CommandNetS3Plugin::loadExtension()),
 * because these classes depend on its BotService and must not be compiled without it.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services()->defaults();

    $services
        ->autowire()
        ->autoconfigure()
        ->load('MajesticDev\\CommandNetS3\\Discord\\', dirname(__DIR__) . '/src/Discord/');
};
