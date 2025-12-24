<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @author David Romaní <david@flux.cat>
 */
final class FluxVerifactuBundle extends AbstractBundle
{
    public const AEAT_CLIENT_KEY = 'aeat_client';
    public const COMPUTER_SYSTEM_CONFIG_KEY = 'computer_system';
    public const FISCAL_IDENTIFIER_CONFIG_KEY = 'fiscal_identifier';

    protected string $extensionAlias = 'flux_verifactu';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
        $builder->getDefinition('flux_verifactu.aeat_client_handler')
            ->setArgument(0, $config[self::AEAT_CLIENT_KEY])
            ->setArgument(1, $config[self::FISCAL_IDENTIFIER_CONFIG_KEY])
        ;
        $builder->getDefinition('flux_verifactu.computer_system_factory')
            ->setArgument(0, $config[self::COMPUTER_SYSTEM_CONFIG_KEY])
        ;
    }
}
