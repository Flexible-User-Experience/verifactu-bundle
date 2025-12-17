<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Flux\VerifactuBundle\Handler\TestHandler;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('flux_verifactu.test_handler', TestHandler::class)
            ->args([
                param('kernel.default_locale'),
            ])
        ->alias(TestHandler::class, 'flux_verifactu.test_handler')
        ->public()
    ;
};
