<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Flux\VerifactuBundle\Factory\ComputerSystemFactory;
use Flux\VerifactuBundle\Factory\FiscalIdentifierFactory;
use Flux\VerifactuBundle\Factory\RegistrationRecordFactory;
use Flux\VerifactuBundle\FluxVerifactuBundle;
use Flux\VerifactuBundle\Handler\AeatClientHandler;
use Flux\VerifactuBundle\Validator\ContractsValidator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services
        ->set('flux_verifactu.aeat_client_handler', AeatClientHandler::class)
            ->args([
                abstract_arg(FluxVerifactuBundle::AEAT_CLIENT_KEY),
                abstract_arg(FluxVerifactuBundle::COMPUTER_SYSTEM_CONFIG_KEY),
                abstract_arg(FluxVerifactuBundle::FISCAL_IDENTIFIER_CONFIG_KEY),
                service(RegistrationRecordFactory::class),
                service(ComputerSystemFactory::class),
                service(FiscalIdentifierFactory::class),
                service(ContractsValidator::class),
            ])
            ->alias(AeatClientHandler::class, 'flux_verifactu.aeat_client_handler')
            ->public()

        ->set('flux_verifactu.registration_record_factory', RegistrationRecordFactory::class)
            ->alias(RegistrationRecordFactory::class, 'flux_verifactu.registration_record_factory')

        ->set('flux_verifactu.computer_system_factory', ComputerSystemFactory::class)
            ->alias(ComputerSystemFactory::class, 'flux_verifactu.computer_system_factory')

        ->set('flux_verifactu.fiscal_identifier_factory', FiscalIdentifierFactory::class)
            ->alias(FiscalIdentifierFactory::class, 'flux_verifactu.fiscal_identifier_factory')

        ->set('flux_verifactu.contracts_validator', ContractsValidator::class)
            ->args([
                service('validator'),
            ])
            ->alias(ContractsValidator::class, 'flux_verifactu.contracts_validator')
    ;
};
