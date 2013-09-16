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

use PHPUnit_Framework_TestCase as TestCase;

use Zend\EventManager\EventManager;
use Zend\ServiceManager\ServiceManager;

class DetectorFactoryTest extends TestCase
{
    public function testFactoryInstantiatesDetector()
    {
        $sl = $this->getServiceLocator();
        $detector = $sl->get('SlmLocale\Locale\Detector');

        $this->assertInstanceOf('SlmLocale\Locale\Detector', $detector);
    }

    public function testDefaultLocaleIsOptional()
    {
        $sl = $this->getServiceLocator();
        $detector = $sl->get('SlmLocale\Locale\Detector');

        $this->assertNull($detector->getDefault());
    }

    public function testDefaultLocaleIsSet()
    {
        $sl = $this->getServiceLocator(array(
            'default' => 'Foo'
        ));
        $detector = $sl->get('SlmLocale\Locale\Detector');

        $this->assertEquals('Foo', $detector->getDefault());
    }

    public function testSupportedLocalesAreOptional()
    {
        $sl = $this->getServiceLocator();
        $detector = $sl->get('SlmLocale\Locale\Detector');

        $this->assertNull($detector->getSupported());
    }

    public function testSupportedLocalesAreSet()
    {
        $sl = $this->getServiceLocator(array(
            'supported' => array('Foo', 'Bar')
        ));
        $detector = $sl->get('SlmLocale\Locale\Detector');

        $this->assertEquals(array('Foo', 'Bar'), $detector->getSupported());
    }

    public function testUseServiceLocatorToInstantiateStrategy()
    {
        $sl = $this->getServiceLocator(array(
            'strategies' => array('TestStrategy')
        ));

        $self    = $this;
        $called  = false;
        $plugins = $sl->get('SlmLocale\Strategy\StrategyPluginManager');
        $plugins->setFactory('TestStrategy', function() use ($self, &$called) {
            $called = true;
            return $self->getMock('SlmLocale\Strategy\StrategyInterface');
        });

        $detector = $sl->get('SlmLocale\Locale\Detector');
        $this->assertTrue($called);
    }

    public function testConfigurationCanHoldMultipleStrategies()
    {
        $sl = $this->getServiceLocator(array(
            'strategies' => array('TestStrategy1', 'TestStrategy2')
        ));

        $self    = $this;
        $called1 = false;
        $plugins = $sl->get('SlmLocale\Strategy\StrategyPluginManager');
        $plugins->setFactory('TestStrategy1', function() use ($self, &$called1) {
            $called1 = true;
            return $self->getMock('SlmLocale\Strategy\StrategyInterface');
        });

        $called2 = false;
        $plugins->setFactory('TestStrategy2', function() use ($self, &$called2) {
            $called2 = true;
            return $self->getMock('SlmLocale\Strategy\StrategyInterface');
        });

        $detector = $sl->get('SlmLocale\Locale\Detector');
        $this->assertTrue($called1);
        $this->assertTrue($called2);
    }

    public function testStrategyConfigurationCanBeAnArray()
    {
        $sl = $this->getServiceLocator(array(
            'strategies' => array(
                array('name' => 'TestStrategy')
            ),
        ));

        $self    = $this;
        $called  = false;
        $plugins = $sl->get('SlmLocale\Strategy\StrategyPluginManager');
        $plugins->setFactory('TestStrategy', function() use ($self, &$called) {
            $called = true;
            return $self->getMock('SlmLocale\Strategy\StrategyInterface');
        });

        $detector = $sl->get('SlmLocale\Locale\Detector');
        $this->assertTrue($called);
    }

    public function testStrategyCanBeAttachedWithPriorities()
    {
        $sl = $this->getServiceLocator(array(
            'strategies' => array(
                array('name' => 'TestStrategy', 'priority' => 100)
            ),
        ));
        $em = $sl->get('EventManager');

        $strategy = $this->getMock('SlmLocale\Strategy\StrategyInterface', array('attach', 'detach'));
        $strategy->expects($this->once())
                 ->method('attach')
                 ->with($em, 100);
        $plugins = $sl->get('SlmLocale\Strategy\StrategyPluginManager');
        $plugins->setService('TestStrategy', $strategy);

        $detector = $sl->get('SlmLocale\Locale\Detector');
    }

    public function testStrategyCanBeInstantiatedWithOptions()
    {
        $sl = $this->getServiceLocator(array(
            'strategies' => array(
                array('name' => 'TestStrategy', 'options' => 'Foo')
            ),
        ));
        $strategy = $this->getMock('SlmLocale\Strategy\StrategyInterface', array('attach', 'detach', 'setOptions'));
        $strategy->expects($this->once())
                 ->method('setOptions')
                 ->with('Foo');
        $plugins = $sl->get('SlmLocale\Strategy\StrategyPluginManager');
        $plugins->setService('TestStrategy', $strategy);

        $detector = $sl->get('SlmLocale\Locale\Detector');
    }

    public function getServiceLocator(array $config = array())
    {
        $config = array(
            'slm_locale' => $config + array(
                'strategies' => array()
            ),
        );
        $serviceLocator = new ServiceManager;
        $serviceLocator->setFactory('SlmLocale\Locale\Detector', 'SlmLocale\Service\DetectorFactory');
        $serviceLocator->setInvokableClass('SlmLocale\Strategy\StrategyPluginManager', 'SlmLocale\Strategy\StrategyPluginManager');
        $serviceLocator->setService('EventManager', new EventManager);
        $serviceLocator->setService('config', $config);

        return $serviceLocator;
    }
}
