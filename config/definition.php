<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->arrayNode('aeat_client')
                ->info('Settings to connect with AEAT API.')
                ->isRequired()
                ->children()
                    ->booleanNode('is_prod_environment')
                        ->info('Set to true in production environment to make AEAT API real calls, only when you are 100% sure that what are you doing is correct.')
                        ->defaultValue(false)
                        ->isRequired()
                    ->end()
                    ->scalarNode('pfx_certificate_filepath')->isRequired()->end()
                    ->scalarNode('pfx_certificate_password')->isRequired()->end()
                ->end()
            ->end()
            ->arrayNode('computer_system')
                ->info('Who acts as SIF (developer) provider.')
                ->isRequired()
                ->children()
                    ->scalarNode('vendor_name')->isRequired()->end()
                    ->scalarNode('vendor_nif')->isRequired()->end()
                    ->scalarNode('name')->isRequired()->end()
                    ->scalarNode('id')->isRequired()->end()
                    ->scalarNode('version')->isRequired()->end()
                    ->scalarNode('installation_number')->isRequired()->end()
                    ->booleanNode('only_supports_verifactu')->defaultValue(false)->isRequired()->end()
                    ->booleanNode('supports_multiple_taxpayers')->defaultValue(false)->isRequired()->end()
                    ->booleanNode('has_multiple_taxpayers')->defaultValue(false)->isRequired()->end()
                ->end()
            ->end()
            ->arrayNode('fiscal_identifier')
                ->info('Who acts as AEAT legal taxpayer.')
                ->isRequired()
                ->children()
                    ->scalarNode('name')->isRequired()->end()
                    ->scalarNode('nif')->isRequired()->end()
                ->end()
            ->end()
        ->end()
    ;
};
