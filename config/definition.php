<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Flux\VerifactuBundle\FluxVerifactuBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->booleanNode(FluxVerifactuBundle::IS_PROD_ENVIRONMENT_CONFIG_KEY)
                ->info('Set to true in production environment to make AEAT API real calls, only when you are 100% sure that what are you doing is correct.')
                ->defaultValue(false)
                ->isRequired()
            ->end()
            ->arrayNode('computer_system')
                ->info('Who acts as SIF (developer) provider.')
                ->isRequired()
                ->children()
                    ->stringNode('vendor_name')->isRequired()->end()
                    ->stringNode('vendor_nif')->isRequired()->end()
                    ->stringNode('name')->isRequired()->end()
                    ->stringNode('id')->isRequired()->end()
                    ->stringNode('version')->isRequired()->end()
                    ->stringNode('installation_number')->isRequired()->end()
                    ->booleanNode('only_supports_verifactu')->defaultValue(false)->isRequired()->end()
                    ->booleanNode('supports_multiple_taxpayers')->defaultValue(false)->isRequired()->end()
                    ->booleanNode('has_multiple_taxpayers')->defaultValue(false)->isRequired()->end()
                ->end()
            ->end()
            ->arrayNode('fiscal_identifier')
                ->info('Who acts as AEAT legal taxpayer.')
                ->isRequired()
                ->children()
                    ->stringNode('name')->isRequired()->end()
                    ->stringNode('nif')->isRequired()->end()
                ->end()
            ->end()
        ->end()
    ;
};
