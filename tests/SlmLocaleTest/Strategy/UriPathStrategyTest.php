<?php
/**
 * Copyright (c) 2012 Jurian Sluiman.
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
 * @package     SlmLocaleTest
 * @author      Jurian Sluiman <jurian@juriansluiman.nl>
 * @copyright   2012 Jurian Sluiman.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */
namespace SlmLocaleTest\Locale;

use PHPUnit_Framework_TestCase as TestCase;
use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\UriPathStrategy;
use Zend\Console\Response as ConsoleResponse;
use Zend\Console\Request as ConsoleRequest;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Zend\Mvc\Router\Console\SimpleRouteStack as ConsoleRouter;
use Zend\ServiceManager\ServiceManager;

class UriPathStrategyTest extends TestCase
{
    public function setup()
    {
        $this->strategy = new UriPathStrategy;
        $this->strategy->setServiceManager($this->getServiceLocator());

        $this->event = new LocaleEvent();
        $this->event->setSupported(array('nl', 'de', 'en'));
    }

    /**
     * Asserts that when the request instance does not has the getUri method detection is not possible as is the case for console requests
     */
    public function testDetect_ConsoleRequestReturnsNull()
    {
        $this->event->setRequest(new ConsoleRequest);
        $this->event->setResponse(new ConsoleResponse);

        $locale = $this->strategy->detect($this->event);

        $this->assertNull($locale);
    }

    /**
     * Asserts that no locale is detected with the default options
     */
    public function testDetect_NullByDefault()
    {
        $this->event->setRequest(new HttpRequest);
        $this->event->setResponse(new HttpResponse);

        $this->assertNull($this->strategy->detect($this->event));
    }

    /**
     * Asserts that the first segment of a path is detected as locale with the default options
     */
    public function testDetect_FirstPathSegmentAsLocale()
    {
        $request = new HttpRequest;
        $request->setUri('http://example.com/en/deep/path/');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $this->assertEquals('en', $this->strategy->detect($this->event));
    }
    
    public function testDetect_NullForUnsupported()
    {
        $request = new HttpRequest;
        $request->setUri('http://example.com/fr');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $this->assertNull($this->strategy->detect($this->event));
    }

    /**
     * @runInSeparateProcess
     * 'cause headers will be send (warning https://github.com/sebastianbergmann/phpunit/issues/254)
     */
    public function testFound_RedirectByDefault()
    {
        $this->event->setLocale('en');

        $request = new HttpRequest;
        $request->setUri('http://username:password@example.com:8080/some/deep/path/some.file?withsomeparam=true');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $locale = $this->strategy->found($this->event);

        $this->assertEquals($this->event->getResponse()->getStatusCode(), 302);
        $this->assertContains($this->event->getResponse()->getHeaders()->toString(),
            "Location: http://username:password@example.com:8080/en/some/deep/path/some.file?withsomeparam=true\r\n");
    }

    /**
     * @runInSeparateProcess
     * 'cause headers will be send (warning https://github.com/sebastianbergmann/phpunit/issues/254)
     */
     public function testFound_ShouldRespectDisabledRedirectWhenFoundOption()
     {
        $this->strategy->setOptions(array('redirect_when_found' => false));
        $this->strategy->setServiceManager($this->getServiceLocator());

        $this->event->setLocale('en');
        $this->event->setSupported(array('nl', 'de', 'en'));

        $request = new HttpRequest;
        $request->setUri('http://example.com/');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $locale = $this->strategy->found($this->event);

        $this->assertNotEquals($this->event->getResponse()->getStatusCode(), 302);
        $this->assertEquals($this->event->getResponse()->getHeaders()->toString(), "");
    }

    /**
     * @runInSeparateProcess
     * 'cause headers will be send (warning https://github.com/sebastianbergmann/phpunit/issues/254)
     */
    public function testFound_SetsBaseUrlInRouter()
    {
        $serviceManager = $this->getServiceLocator();
        $this->strategy->setServiceManager($serviceManager);

        $this->event->setLocale('en');
        
        $request = new HttpRequest;
        $request->setUri('http://example.com/en');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $locale = $this->strategy->found($this->event);

        $this->assertEquals($serviceManager->get('router')->getBaseUrl(), '/en');
    }

    /**
     * @runInSeparateProcess
     * 'cause headers will be send (warning https://github.com/sebastianbergmann/phpunit/issues/254)
     */
    public function testFound_RedirectWhenAtLocaleUrlButMissingTrailingSlash()
    {
        $serviceManager = $this->getServiceLocator();
        $this->strategy->setServiceManager($serviceManager);

        $this->event->setLocale('en');
        
        $request = new HttpRequest;
        $request->setUri('http://example.com/en');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $locale = $this->strategy->found($this->event);

        $this->assertEquals($this->event->getResponse()->getStatusCode(), 302);
    }

    /**
     * @runInSeparateProcess
     * 'cause headers will be send (warning https://github.com/sebastianbergmann/phpunit/issues/254)
     */
    public function testFound_DoesNotRedirectWhenAtLocaleUrl()
    {
        $serviceManager = $this->getServiceLocator();
        $this->strategy->setServiceManager($serviceManager);

        $this->event->setLocale('en');
        
        $request = new HttpRequest;
        $request->setUri('http://example.com/en/');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $locale = $this->strategy->found($this->event);

        $this->assertNotEquals($this->event->getResponse()->getStatusCode(), 302);
    }

    protected function getServiceLocator($withConsoleRouter=false)
    {
        $serviceLocator = new ServiceManager;
        $serviceLocator->setService('router', $withConsoleRouter ? new ConsoleRouter : new HttpRouter);

        return $serviceLocator;
    }

}
