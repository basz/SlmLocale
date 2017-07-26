<?php

namespace SlmLocale\Strategy;

use Interop\Container\ContainerInterface;

class StrategyPluginManagerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new StrategyPluginManager($container);
    }
}
