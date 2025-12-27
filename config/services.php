<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Flux\VerifactuBundle\Factory\AeatResponseFactory;
use Flux\VerifactuBundle\Factory\BreakdownDetailFactory;
use Flux\VerifactuBundle\Factory\ComputerSystemFactory;
use Flux\VerifactuBundle\Factory\FiscalIdentifierFactory;
use Flux\VerifactuBundle\Factory\InvoiceIdentifierFactory;
use Flux\VerifactuBundle\Factory\RegistrationRecordFactory;
use Flux\VerifactuBundle\FluxVerifactuBundle;
use Flux\VerifactuBundle\Handler\AeatClientHandler;
use Flux\VerifactuBundle\Transformer\AeatResponseTransformer;
use Flux\VerifactuBundle\Transformer\BreakdownDetailTransformer;
use Flux\VerifactuBundle\Transformer\ComputerSystemTransformer;
use Flux\VerifactuBundle\Transformer\FiscalIdentifierTransformer;
use Flux\VerifactuBundle\Transformer\InvoiceIdentifierTransformer;
use Flux\VerifactuBundle\Transformer\RegistrationRecordTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services
        // handlers
        ->set('flux_verifactu.aeat_client_handler', AeatClientHandler::class)
            ->args([
                abstract_arg(FluxVerifactuBundle::AEAT_CLIENT_KEY),
                service(RegistrationRecordFactory::class),
                service(ComputerSystemFactory::class),
                service(FiscalIdentifierFactory::class),
                service(AeatResponseFactory::class),
            ])
            ->alias(AeatClientHandler::class, 'flux_verifactu.aeat_client_handler')
            ->public()
        // factories
        ->set('flux_verifactu.aeat_response_factory', AeatResponseFactory::class)
            ->args([
                service(AeatResponseTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(AeatResponseFactory::class, 'flux_verifactu.aeat_response_factory')
        ->set('flux_verifactu.breakdown_detail_factory', BreakdownDetailFactory::class)
            ->args([
                service(BreakdownDetailTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(BreakdownDetailFactory::class, 'flux_verifactu.breakdown_detail_factory')
        ->set('flux_verifactu.computer_system_factory', ComputerSystemFactory::class)
            ->args([
                abstract_arg(FluxVerifactuBundle::COMPUTER_SYSTEM_CONFIG_KEY),
                service(ComputerSystemTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(ComputerSystemFactory::class, 'flux_verifactu.computer_system_factory')
        ->set('flux_verifactu.fiscal_identifier_factory', FiscalIdentifierFactory::class)
            ->args([
                abstract_arg(FluxVerifactuBundle::FISCAL_IDENTIFIER_CONFIG_KEY),
                service(FiscalIdentifierTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(FiscalIdentifierFactory::class, 'flux_verifactu.fiscal_identifier_factory')
        ->set('flux_verifactu.invoice_identifier_factory', InvoiceIdentifierFactory::class)
            ->args([
                abstract_arg(FluxVerifactuBundle::FISCAL_IDENTIFIER_CONFIG_KEY),
                service(InvoiceIdentifierTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(InvoiceIdentifierFactory::class, 'invoice_identifier_factory.invoice_identifier_factory')
        ->set('flux_verifactu.registration_record_factory', RegistrationRecordFactory::class)
            ->args([
                service(InvoiceIdentifierFactory::class),
                service(BreakdownDetailTransformer::class),
                service(FiscalIdentifierTransformer::class),
                service(RegistrationRecordTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(RegistrationRecordFactory::class, 'flux_verifactu.registration_record_factory')
        // transformers
        ->set('flux_verifactu.aeat_response_transformer', AeatResponseTransformer::class)
            ->alias(AeatResponseTransformer::class, 'flux_verifactu.aeat_response_transformer')
        ->set('flux_verifactu.breakdown_detail_transformer', BreakdownDetailTransformer::class)
            ->alias(BreakdownDetailTransformer::class, 'flux_verifactu.breakdown_detail_transformer')
        ->set('flux_verifactu.computer_system_transformer', ComputerSystemTransformer::class)
            ->alias(ComputerSystemTransformer::class, 'flux_verifactu.computer_system_transformer')
        ->set('flux_verifactu.fiscal_identifier_transformer', FiscalIdentifierTransformer::class)
            ->alias(FiscalIdentifierTransformer::class, 'flux_verifactu.fiscal_identifier_transformer')
        ->set('flux_verifactu.invoice_identifier_transformer', InvoiceIdentifierTransformer::class)
            ->alias(InvoiceIdentifierTransformer::class, 'flux_verifactu.invoice_identifier_transformer')
        ->set('flux_verifactu.registration_record_transformer', RegistrationRecordTransformer::class)
            ->alias(RegistrationRecordTransformer::class, 'flux_verifactu.registration_record_transformer')
        // validators
        ->set('flux_verifactu.contracts_validator', ContractsValidator::class)
            ->args([
                service('validator'),
            ])
            ->alias(ContractsValidator::class, 'flux_verifactu.contracts_validator')
    ;
};
