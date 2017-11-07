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

namespace SlmLocale\Strategy;

use SlmLocale\Strategy\Factory\UriPathStrategyFactory;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;

class StrategyPluginManager extends AbstractPluginManager
{
    protected $instanceOf = StrategyInterface::class;

    /**
     * {@inheritdoc}
     */
    protected $aliases = [
        'cookie'         => CookieStrategy::class,
        'host'           => HostStrategy::class,
        'acceptlanguage' => HttpAcceptLanguageStrategy::class,
        'query'          => QueryStrategy::class,
        'uripath'        => UriPathStrategy::class,
        'asset'          => AssetStrategy::class
    ];

    /**
     * {@inheritdoc}
     */
    protected $factories = [
        CookieStrategy::class                         => InvokableFactory::class,
        HostStrategy::class                           => InvokableFactory::class,
        HttpAcceptLanguageStrategy::class             => InvokableFactory::class,
        QueryStrategy::class                          => InvokableFactory::class,
        UriPathStrategy::class                        => UriPathStrategyFactory::class,
        AssetStrategy::class                          => InvokableFactory::class,
        'slmlocalestrategycookiestrategy'             => InvokableFactory::class,
        'slmlocalestrategyhoststrategy'               => InvokableFactory::class,
        'slmlocalestrategyhttpacceptlanguagestrategy' => InvokableFactory::class,
        'slmlocalestrategyquerystrategy'              => InvokableFactory::class,
        'slmlocalestrategyuripathstrategy'            => UriPathStrategyFactory::class,
        'slmlocalestrategyassetstrategy'              => InvokableFactory::class
    ];

    /**
     * Validate the plugin
     *
     * Checks that the helper loaded is an instance of StrategyInterface.
     *
     * @param  mixed $instance
     * @return void
     * @throws Exception\InvalidStrategyException if invalid
     */
    public function validate($instance)
    {
        if ($instance instanceof $this->instanceOf) {
            // we're okay
            return;
        }

        // fixme: should throw Zend\ServiceManager\Exception\InvalidServiceException but we can't because of BC
        throw new Exception\InvalidStrategyException(sprintf(
            'Plugin of type %s is invalid; must implement %s\StrategyInterface',
            (is_object($instance) ? get_class($instance) : gettype($instance)),
            __NAMESPACE__
        ));
    }

    /**
     * @deprecated to support ServiceManager v2
     */
    public function validatePlugin($instance)
    {
        $this->validate($instance);
    }
}
