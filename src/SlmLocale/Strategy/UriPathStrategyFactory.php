<?php

namespace SlmLocale\Strategy;

use Interop\Container\ContainerInterface;

class UriPathStrategyFactory
{
    /**
     * @param ContainerInterface $container
     * @return UriPathStrategy
     */
    public function __invoke(ContainerInterface $container)
    {
        return new UriPathStrategy($container->get('router'));
    }
}
