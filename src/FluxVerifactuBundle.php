<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle;

use Flux\VerifactuBundle\Factory\ComputerSystemFactory;
use Flux\VerifactuBundle\Factory\FiscalIdentifierFactory;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author David Romaní <david@flux.cat>
 */
final class FluxVerifactuBundle extends AbstractBundle
{
    public const IS_PROD_ENVIRONMENT_CONFIG_KEY = 'is_prod_environment';
    public const COMPUTER_SYSTEM_CONFIG_KEY = 'computer_system';
    public const FISCAL_IDENTIFIER_CONFIG_KEY = 'fiscal_identifier';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
        $builder->getDefinition('flux_verifactu.aeat_client_handler')
            ->setArgument(0, $config[self::COMPUTER_SYSTEM_CONFIG_KEY])
            ->setArgument(1, ComputerSystemFactory::class)
            ->setArgument(2, $config[self::FISCAL_IDENTIFIER_CONFIG_KEY])
            ->setArgument(3, FiscalIdentifierFactory::class)
        ;
        $builder->getDefinition('flux_verifactu.test_handler')
            ->setArgument(0, $config[self::IS_PROD_ENVIRONMENT_CONFIG_KEY])
        ;
    }
}
