<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use FlexibleUx\VerifactuBundle\Command\GenerateSifStatementCommand;
use FlexibleUx\VerifactuBundle\Factory\AeatClientFactory;
use FlexibleUx\VerifactuBundle\Factory\AeatResponseFactory;
use FlexibleUx\VerifactuBundle\Factory\BreakdownDetailFactory;
use FlexibleUx\VerifactuBundle\Factory\CancellationRecordFactory;
use FlexibleUx\VerifactuBundle\Factory\ComputerSystemFactory;
use FlexibleUx\VerifactuBundle\Factory\FiscalIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\ForeignFiscalIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\InvoiceIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\RegistrationRecordFactory;
use FlexibleUx\VerifactuBundle\FlexibleUxVerifactuBundle;
use FlexibleUx\VerifactuBundle\Handler\AeatClientHandler;
use FlexibleUx\VerifactuBundle\Handler\QrCodeHandler;
use FlexibleUx\VerifactuBundle\Handler\XmlRecordHandler;
use FlexibleUx\VerifactuBundle\Transformer\AeatResponseTransformer;
use FlexibleUx\VerifactuBundle\Transformer\BreakdownDetailTransformer;
use FlexibleUx\VerifactuBundle\Transformer\CancellationRecordTransformer;
use FlexibleUx\VerifactuBundle\Transformer\ComputerSystemTransformer;
use FlexibleUx\VerifactuBundle\Transformer\FiscalIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\ForeignFiscalIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\InvoiceIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\RegistrationRecordTransformer;
use FlexibleUx\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Services\AeatClient;
use Symfony\Component\Serializer\SerializerInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services
        // commands
        ->set('flexible_ux_verifactu.generate_sif_statement_command', GenerateSifStatementCommand::class)
            ->args([
                abstract_arg(FlexibleUxVerifactuBundle::COMPUTER_SYSTEM_CONFIG_KEY),
                service('twig'),
            ])
            ->tag('console.command')
        // handlers
        ->set('flexible_ux_verifactu.aeat_client_handler', AeatClientHandler::class)
            ->args([
                abstract_arg(FlexibleUxVerifactuBundle::AEAT_CLIENT_KEY),
                service(AeatClient::class),
                service(RegistrationRecordFactory::class),
                service(CancellationRecordFactory::class),
                service(AeatResponseFactory::class),
            ])
            ->alias(AeatClientHandler::class, 'flexible_ux_verifactu.aeat_client_handler')
            ->public()
        ->set('flexible_ux_verifactu.qr_code_handler', QrCodeHandler::class)
            ->args([
                abstract_arg(FlexibleUxVerifactuBundle::AEAT_CLIENT_KEY),
                service(InvoiceIdentifierFactory::class),
                service(RegistrationRecordFactory::class),
                service(AeatResponseFactory::class),
            ])
            ->alias(QrCodeHandler::class, 'flexible_ux_verifactu.qr_code_handler')
            ->public()
        ->set('flexible_ux_verifactu.xml_record_handler', XmlRecordHandler::class)
            ->args([
                service(RegistrationRecordFactory::class),
                service(CancellationRecordFactory::class),
                service(ComputerSystemFactory::class),
            ])
            ->alias(XmlRecordHandler::class, 'flexible_ux_verifactu.xml_record_handler')
            ->public()
        // clients
        ->set('flexible_ux_verifactu.aeat_client', AeatClient::class)
            ->factory([service(AeatClientFactory::class), 'makeConfiguredAeatClient'])
            ->alias(AeatClient::class, 'flexible_ux_verifactu.aeat_client')
        // factories
        ->set('flexible_ux_verifactu.aeat_client_factory', AeatClientFactory::class)
            ->args([
                abstract_arg(FlexibleUxVerifactuBundle::AEAT_CLIENT_KEY),
                service(ComputerSystemFactory::class),
                service(FiscalIdentifierFactory::class),
            ])
            ->alias(AeatClientFactory::class, 'flexible_ux_verifactu.aeat_client_factory')
        ->set('flexible_ux_verifactu.aeat_response_factory', AeatResponseFactory::class)
            ->args([
                service(AeatResponseTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(AeatResponseFactory::class, 'flexible_ux_verifactu.aeat_response_factory')
        ->set('flexible_ux_verifactu.breakdown_detail_factory', BreakdownDetailFactory::class)
            ->args([
                service(BreakdownDetailTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(BreakdownDetailFactory::class, 'flexible_ux_verifactu.breakdown_detail_factory')
        ->set('flexible_ux_verifactu.cancellation_record_factory', CancellationRecordFactory::class)
            ->args([
                service(InvoiceIdentifierFactory::class),
                service(CancellationRecordTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(CancellationRecordFactory::class, 'flexible_ux_verifactu.cancellation_record_factory')
        ->set('flexible_ux_verifactu.computer_system_factory', ComputerSystemFactory::class)
            ->args([
                abstract_arg(FlexibleUxVerifactuBundle::COMPUTER_SYSTEM_CONFIG_KEY),
                service(ComputerSystemTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(ComputerSystemFactory::class, 'flexible_ux_verifactu.computer_system_factory')
        ->set('flexible_ux_verifactu.fiscal_identifier_factory', FiscalIdentifierFactory::class)
            ->args([
                abstract_arg(FlexibleUxVerifactuBundle::FISCAL_IDENTIFIER_CONFIG_KEY),
                service(FiscalIdentifierTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(FiscalIdentifierFactory::class, 'flexible_ux_verifactu.fiscal_identifier_factory')
        ->set('flexible_ux_verifactu.foreign_fiscal_identifier_factory', ForeignFiscalIdentifierFactory::class)
            ->args([
                service(ForeignFiscalIdentifierTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(ForeignFiscalIdentifierFactory::class, 'flexible_ux_verifactu.foreign_fiscal_identifier_factory')
        ->set('flexible_ux_verifactu.invoice_identifier_factory', InvoiceIdentifierFactory::class)
            ->args([
                service(InvoiceIdentifierTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(InvoiceIdentifierFactory::class, 'flexible_ux_verifactu.invoice_identifier_factory')
        ->set('flexible_ux_verifactu.registration_record_factory', RegistrationRecordFactory::class)
            ->args([
                service(InvoiceIdentifierFactory::class),
                service(BreakdownDetailFactory::class),
                service(FiscalIdentifierFactory::class),
                service(ForeignFiscalIdentifierFactory::class),
                service(RegistrationRecordTransformer::class),
                service(ContractsValidator::class),
            ])
            ->alias(RegistrationRecordFactory::class, 'flexible_ux_verifactu.registration_record_factory')
        // transformers
        ->set('flexible_ux_verifactu.aeat_response_transformer', AeatResponseTransformer::class)
            ->args([
                service(SerializerInterface::class)
            ])
            ->alias(AeatResponseTransformer::class, 'flexible_ux_verifactu.aeat_response_transformer')
        ->set('flexible_ux_verifactu.breakdown_detail_transformer', BreakdownDetailTransformer::class)
            ->alias(BreakdownDetailTransformer::class, 'flexible_ux_verifactu.breakdown_detail_transformer')
        ->set('flexible_ux_verifactu.cancellation_record_transformer', CancellationRecordTransformer::class)
            ->alias(CancellationRecordTransformer::class, 'flexible_ux_verifactu.cancellation_record_transformer')
        ->set('flexible_ux_verifactu.computer_system_transformer', ComputerSystemTransformer::class)
            ->alias(ComputerSystemTransformer::class, 'flexible_ux_verifactu.computer_system_transformer')
        ->set('flexible_ux_verifactu.fiscal_identifier_transformer', FiscalIdentifierTransformer::class)
            ->alias(FiscalIdentifierTransformer::class, 'flexible_ux_verifactu.fiscal_identifier_transformer')
        ->set('flexible_ux_verifactu.foreign_fiscal_identifier_transformer', ForeignFiscalIdentifierTransformer::class)
            ->alias(ForeignFiscalIdentifierTransformer::class, 'flexible_ux_verifactu.foreign_fiscal_identifier_transformer')
        ->set('flexible_ux_verifactu.invoice_identifier_transformer', InvoiceIdentifierTransformer::class)
            ->alias(InvoiceIdentifierTransformer::class, 'flexible_ux_verifactu.invoice_identifier_transformer')
        ->set('flexible_ux_verifactu.registration_record_transformer', RegistrationRecordTransformer::class)
            ->alias(RegistrationRecordTransformer::class, 'flexible_ux_verifactu.registration_record_transformer')
        // validators
        ->set('flexible_ux_verifactu.contracts_validator', ContractsValidator::class)
            ->args([
                service('validator'),
            ])
            ->alias(ContractsValidator::class, 'flexible_ux_verifactu.contracts_validator')
    ;
};
