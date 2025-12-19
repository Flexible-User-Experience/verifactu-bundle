<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Flux\VerifactuBundle\Factory\ComputerSystemFactory;
use Flux\VerifactuBundle\FluxVerifactuBundle;
use Flux\VerifactuBundle\Handler\AeatClientHandler;
use Flux\VerifactuBundle\Handler\TestHandler;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('flux_verifactu.aeat_client_handler', AeatClientHandler::class)
            ->args([
                abstract_arg(FluxVerifactuBundle::COMPUTER_SYSTEM_CONFIG_KEY),
                service(ComputerSystemFactory::class),
            ])
        ->alias(AeatClientHandler::class, 'flux_verifactu.aeat_client_handler')
        ->public()
    ;
    $container->services()
        ->set('flux_verifactu.computer_system_factory', ComputerSystemFactory::class)
            ->args([
                service('validator'),
            ])
        ->alias(ComputerSystemFactory::class, 'flux_verifactu.computer_system_factory')
    ;
    $container->services()
        ->set('flux_verifactu.test_handler', TestHandler::class)
            ->args([
                abstract_arg(FluxVerifactuBundle::IS_PROD_ENVIRONMENT_CONFIG_KEY),
            ])
        ->alias(TestHandler::class, 'flux_verifactu.test_handler')
        ->public()
    ;
};
