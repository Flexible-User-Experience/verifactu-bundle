<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Flux\VerifactuBundle\FluxVerifactuBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->booleanNode(FluxVerifactuBundle::IS_PROD_ENVIRONMENT_CONFIG_KEY)
                ->info('Set to true in production environment to make AEAT API real calls, only when you are sure that what are you doing is correct.')
                ->defaultValue(false)
                ->isRequired()
            ->end()
        ->end()
    ;
};
