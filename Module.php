<?php
/**
 * Copyright (c) 2012-2013 Jurian Sluiman http://juriansluiman.nl.
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
 * @copyright   2012-2013 Jurian Sluiman http://juriansluiman.nl.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */

namespace SlmLocale;

use Locale;

use Zend\ModuleManager\Feature;
use Zend\EventManager\EventInterface;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\ResponseInterface;

class Module implements
    Feature\AutoloaderProviderInterface,
    Feature\ConfigProviderInterface,
    Feature\BootstrapListenerInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(EventInterface $e)
    {
        $app = $e->getApplication();
        $sm  = $app->getServiceManager();

        $detector = $sm->get('SlmLocale\Locale\Detector');
        $result   = $detector->detect($app->getRequest(), $app->getResponse());

        if ($result instanceof ResponseInterface) {
            /**
             * When the detector returns a response, a strategy has updated the response
             * to reflect the found locale.
             *
             * To redirect the user to this new URI, we short-circuit the route event. There
             * is no option to short-circuit the bootstrap event, so we attach a listener to
             * the route and let the application finish the bootstrap first.
             *
             * The listener is attached at PHP_INT_MAX to return the response as early as
             * possible.
             */
            $em = $app->getEventManager();
            $em->attach(MvcEvent::EVENT_ROUTE, function($e) use ($result) {
                return $result;
            }, PHP_INT_MAX);
        }

        Locale::setDefault($result);
    }
}
