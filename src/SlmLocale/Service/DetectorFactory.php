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

namespace SlmLocale\Service;

use Interop\Container\ContainerInterface;
use SlmLocale\Locale\Detector;
use SlmLocale\Strategy\StrategyPluginManager;

class DetectorFactory
{
    /**
     * @param  ContainerInterface $container
     *
     * @return Detector
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $config = $config['slm_locale'];

        $detector = new Detector();
        $events   = $container->get('EventManager');
        $detector->setEventManager($events);

        $this->addStrategies($detector, $config['strategies'], $container);

        if (array_key_exists('default', $config)) {
            $detector->setDefault($config['default']);
        }

        if (array_key_exists('supported', $config)) {
            $detector->setSupported($config['supported']);
        }

        if (array_key_exists('mappings', $config)) {
            $detector->setMappings($config['mappings']);
        }

        return $detector;
    }

    protected function addStrategies(Detector $detector, array $strategies, ContainerInterface $container)
    {
        $plugins = $container->get(StrategyPluginManager::class);

        foreach ($strategies as $strategy) {
            if (is_string($strategy)) {
                $class = $plugins->get($strategy);
                $detector->addStrategy($class);
            } elseif (is_array($strategy)) {
                $name     = $strategy['name'];
                $class    = $plugins->get($name);

                if (array_key_exists('options', $strategy) && method_exists($class, 'setOptions')) {
                    $class->setOptions($strategy['options']);
                }

                $priority = 1;
                if (array_key_exists('priority', $strategy)) {
                    $priority = $strategy['priority'];
                }

                $detector->addStrategy($class, $priority);
            } else {
                throw new Exception\StrategyConfigurationException(
                    'Strategy configuration must be a string or an array'
                );
            }
        }
    }
}
