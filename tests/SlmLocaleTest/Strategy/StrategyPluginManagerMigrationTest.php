<?php

namespace SlmLocaleTest\Locale;

use PHPUnit_Framework_TestCase as TestCase;
use RuntimeException;
use SlmLocale\Strategy\StrategyInterface;
use SlmLocale\Strategy\StrategyPluginManager;
use Zend\Router\Http\TreeRouteStack;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Test\CommonPluginManagerTrait;

class StrategyPluginManagerMigrationTest extends TestCase
{
    use CommonPluginManagerTrait;

    protected $smMock;

    protected function getPluginManager()
    {
        $serviceLocator = $this->createMock(ServiceManager::class);
        $router = new TreeRouteStack();
        $serviceLocator
            ->method('get')
            ->with($this->equalTo('router'))
            ->willReturn($router);

        return new StrategyPluginManager($serviceLocator);
    }

    protected function getV2InvalidPluginException()
    {
        return RuntimeException::class;
    }

    protected function getInstanceOf()
    {
        return StrategyInterface::class;
    }
}
