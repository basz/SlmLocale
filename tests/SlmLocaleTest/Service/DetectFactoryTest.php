<?php
/**
 * Copyright (c) 2012-2013 Jurian Sluiman.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of the
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author      Jurian Sluiman <jurian@juriansluiman.nl>
 * @copyright   2012-2013 Jurian Sluiman.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */
namespace SlmLocaleTest\Locale;

use Laminas\EventManager\EventManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use SlmLocale\Locale\Detector;
use SlmLocale\Service\DetectorFactory;
use SlmLocale\Strategy\Factory\StrategyPluginManagerFactory;
use SlmLocale\Strategy\StrategyInterface;
use SlmLocale\Strategy\StrategyPluginManager;

class DetectFactoryTest extends TestCase
{
    public function testFactoryInstantiatesDetector()
    {
        $sl       = $this->getServiceLocator();
        $detector = $sl->get(Detector::class);

        $this->assertInstanceOf(Detector::class, $detector);
    }

    public function testDefaultLocaleIsOptional()
    {
        $sl       = $this->getServiceLocator();
        $detector = $sl->get(Detector::class);

        $this->assertNull($detector->getDefault());
    }

    public function testDefaultLocaleIsSet()
    {
        $sl = $this->getServiceLocator([
            'default' => 'Foo',
        ]);
        $detector = $sl->get(Detector::class);

        $this->assertEquals('Foo', $detector->getDefault());
    }

    public function testSupportedLocalesAreOptional()
    {
        $sl       = $this->getServiceLocator();
        $detector = $sl->get(Detector::class);

        $this->assertNull($detector->getSupported());
    }

    public function testSupportedLocalesAreSet()
    {
        $sl = $this->getServiceLocator([
            'supported' => ['Foo', 'Bar'],
        ]);
        $detector = $sl->get(Detector::class);

        $this->assertEquals(['Foo', 'Bar'], $detector->getSupported());
    }

    public function testLocaleMappingsAreOptional()
    {
        $sl       = $this->getServiceLocator();
        $detector = $sl->get('SlmLocale\Locale\Detector');

        $this->assertNull($detector->getMappings());
    }

    public function testLocaleMappingsAreSet()
    {
        $sl = $this->getServiceLocator([
            'mappings' => ['Foo' => 'Bar'],
        ]);
        $detector = $sl->get('SlmLocale\Locale\Detector');

        $this->assertEquals(['Foo' => 'Bar'], $detector->getMappings());
    }

    public function testUseServiceLocatorToInstantiateStrategy()
    {
        $sl = $this->getServiceLocator([
            'strategies' => ['TestStrategy'],
        ]);

        $self    = $this;
        $called  = false;
        $plugins = $sl->get(StrategyPluginManager::class);
        $plugins->setFactory('TestStrategy', function () use ($self, &$called) {
            $called = true;

            return $self->createMock(StrategyInterface::class);
        });

        $detector = $sl->get(Detector::class);
        $this->assertTrue($called);
    }

    public function testConfigurationCanHoldMultipleStrategies()
    {
        $sl = $this->getServiceLocator([
            'strategies' => ['TestStrategy1', 'TestStrategy2'],
        ]);

        $self    = $this;
        $called1 = false;
        $plugins = $sl->get(StrategyPluginManager::class);
        $plugins->setFactory('TestStrategy1', function () use ($self, &$called1) {
            $called1 = true;

            return $self->createMock(StrategyInterface::class);
        });

        $called2 = false;
        $plugins->setFactory('TestStrategy2', function () use ($self, &$called2) {
            $called2 = true;

            return $self->createMock(StrategyInterface::class);
        });

        $detector = $sl->get(Detector::class);
        $this->assertTrue($called1);
        $this->assertTrue($called2);
    }

    public function testStrategyConfigurationCanBeAnArray()
    {
        $sl = $this->getServiceLocator([
            'strategies' => [
                ['name' => 'TestStrategy'],
            ],
        ]);

        $self    = $this;
        $called  = false;
        $plugins = $sl->get(StrategyPluginManager::class);
        $plugins->setFactory('TestStrategy', function () use ($self, &$called) {
            $called = true;

            return $self->createMock(StrategyInterface::class);
        });

        $detector = $sl->get(Detector::class);
        $this->assertTrue($called);
    }

    public function testStrategyCanBeAttachedWithPriorities()
    {
        $sl = $this->getServiceLocator([
            'strategies' => [
                ['name' => 'TestStrategy', 'priority' => 100],
            ],
        ]);
        $em = $sl->get('EventManager');

        $strategy = $this->createMock(StrategyInterface::class, ['attach', 'detach']);
        $strategy->expects($this->once())
                 ->method('attach')
                 ->with($em, 100);
        $plugins = $sl->get(StrategyPluginManager::class);
        $plugins->setService('TestStrategy', $strategy);

        $detector = $sl->get(Detector::class);
    }

    public function testStrategyCanBeInstantiatedWithOptions()
    {
        $sl = $this->getServiceLocator([
            'strategies' => [
                ['name' => 'TestStrategy', 'options' => 'Foo'],
            ],
        ]);

        $strategy = $this->getMockBuilder(StrategyInterface::class)
            ->setMethods(['attach', 'detach', 'setOptions'])
            ->getMock();
        $strategy->expects($this->once())
                 ->method('setOptions')
                 ->with('Foo');
        $plugins = $sl->get(StrategyPluginManager::class);
        $plugins->setService('TestStrategy', $strategy);

        $detector = $sl->get(Detector::class);
    }

    public function getServiceLocator(array $config = [])
    {
        $config = [
            'slm_locale' => $config + [
                'strategies' => [],
            ],
        ];
        $serviceLocator = new ServiceManager();
        $serviceLocator->setFactory(Detector::class, DetectorFactory::class);
        $serviceLocator->setFactory(StrategyPluginManager::class, StrategyPluginManagerFactory::class);
        $serviceLocator->setService('EventManager', new EventManager());
        $serviceLocator->setService('config', $config);

        return $serviceLocator;
    }
}
