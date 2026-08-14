<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author David Romaní <david@flux.cat>
 */
final class FlexibleUxVerifactuBundle extends AbstractBundle
{
    public const AEAT_CLIENT_KEY = 'aeat_client';
    public const COMPUTER_SYSTEM_CONFIG_KEY = 'computer_system';
    public const FISCAL_IDENTIFIER_CONFIG_KEY = 'fiscal_identifier';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import('../config/services.php');
        $container->getDefinition('flexible_ux_verifactu.aeat_client_factory')
            ->setArgument(0, $config[self::AEAT_CLIENT_KEY])
        ;
        $container->getDefinition('flexible_ux_verifactu.qr_code_handler')
            ->setArgument(0, $config[self::AEAT_CLIENT_KEY])
        ;
        $container->getDefinition('flexible_ux_verifactu.computer_system_factory')
            ->setArgument(0, $config[self::COMPUTER_SYSTEM_CONFIG_KEY])
        ;
        $container->getDefinition('flexible_ux_verifactu.generate_sif_statement_command')
            ->setArgument(0, $config[self::COMPUTER_SYSTEM_CONFIG_KEY])
        ;
        $container->getDefinition('flexible_ux_verifactu.fiscal_identifier_factory')
            ->setArgument(0, $config[self::FISCAL_IDENTIFIER_CONFIG_KEY])
        ;
    }
}
