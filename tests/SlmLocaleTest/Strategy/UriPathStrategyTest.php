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
use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\UriPathStrategy;
use Zend\Console\Response as ConsoleResponse;
use Zend\Console\Request as ConsoleRequest;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Zend\Mvc\Router\Console\SimpleRouteStack as ConsoleRouter;
use Zend\ServiceManager\ServiceManager;
use Zend\Uri\Uri;

class UriPathStrategyTest extends TestCase
{
    public function setup()
    {
        $this->strategy = new UriPathStrategy;
        $this->strategy->setServiceLocator($this->getPluginManager());

        $this->event = new LocaleEvent();
        $this->event->setSupported(array('nl', 'de', 'en'));
    }

    public function testDetectWithConsoleRequestReturnsNull()
    {
        $this->event->setRequest(new ConsoleRequest);
        $this->event->setResponse(new ConsoleResponse);

        $locale = $this->strategy->detect($this->event);
        $this->assertNull($locale);
    }

    public function testDetectReturnsNullByDefault()
    {
        $this->event->setRequest(new HttpRequest);
        $this->event->setResponse(new HttpResponse);

        $locale = $this->strategy->detect($this->event);
        $this->assertNull($locale);
    }

    public function testDetectReturnsFirstPathSegmentAsLocale()
    {
        $request = new HttpRequest;
        $request->setUri('http://example.com/en/deep/path/');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $locale   = $this->strategy->detect($this->event);
        $expected = 'en';
        $this->assertEquals($expected, $locale);
    }

    public function testDetectReturnsNullForUnsupported()
    {
        $request = new HttpRequest;
        $request->setUri('http://example.com/fr/');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $locale = $this->strategy->detect($this->event);
        $this->assertNull($locale);
    }

    public function testDetectWithBaseUrlReturnsRightPartOfPath()
    {
        $manager = $this->getPluginManager();
        $router  = $manager->getServiceLocator()->get('router');
        $router->setBaseUrl('/some/seep/installation/path');
        $this->strategy->setServiceLocator($manager);

        $this->event->setLocale('en');

        $request = new HttpRequest;
        $request->setUri('http://example.com/some/deep/installation/path/en/some/deep/path');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $locale   = $this->strategy->detect($this->event);
        $expected = 'en';
        $this->assertEquals($expected, $locale);
    }

    public function testFoundRedirectsByDefault()
    {
        $uri     = 'http://username:password@example.com:8080/some/deep/path/some.file?withsomeparam=true';
        $request = new HttpRequest;
        $request->setUri($uri);

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $this->strategy->found($this->event);

        $statusCode = $this->event->getResponse()->getStatusCode();
        $header     = $this->event->getResponse()->getHeaders()->get('Location');
        $expected   = 'Location: http://username:password@example.com:8080/en/some/deep/path/some.file?withsomeparam=true';
        $this->assertEquals(302, $statusCode);
        $this->assertContains($expected, (string) $header);
    }

    public function testFoundShouldRespectDisabledRedirectWhenFound()
    {
        $this->strategy->setOptions(array('redirect_when_found' => false));

        $request = new HttpRequest;
        $request->setUri('http://example.com/');

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $this->strategy->found($this->event);

        $statusCode = $this->event->getResponse()->getStatusCode();
        $header     = $this->event->getResponse()->getHeaders()->has('Location');
        $this->assertNotEquals(302, $statusCode);
        $this->assertFalse($header);
    }

    // public function testFoundWithDisabledRedirectWhenFoundOptionLocaleShouldStillBeDirectedAnywayWhenPathContainsNothingFurther()
    // {
    //     $this->strategy->setOptions(array('redirect_when_found' => false));
    //     $this->strategy->setServiceLocator($this->getPluginManager());

    //     $this->event->setLocale('en');
    //     $this->event->setSupported(array('nl', 'de', 'en'));

    //     $request = new HttpRequest;
    //     $request->setUri('http://example.com/en');

    //     $this->event->setRequest($request);
    //     $this->event->setResponse(new HttpResponse);

    //     $locale = $this->strategy->found($this->event);

    //     $this->assertEquals($this->event->getResponse()->getStatusCode(), 302);
    //     $this->assertContains($this->event->getResponse()->getHeaders()->toString(), "Location: http://example.com/en/\r\n");
    // }

    // public function testFoundWithDisabledRedirectWhenFoundOptionLocaleShouldStillBeDirectedAnyway()
    // {
    //     $this->strategy->setOptions(array('redirect_when_found' => false));
    //     $this->strategy->setServiceLocator($this->getPluginManager());

    //     $this->event->setLocale('en');
    //     $this->event->setSupported(array('nl', 'de', 'en'));

    //     $request = new HttpRequest;
    //     $request->setUri('http://example.com/en/something.ext');

    //     $this->event->setRequest($request);
    //     $this->event->setResponse(new HttpResponse);

    //     $locale = $this->strategy->found($this->event);

    //     $this->assertNotEquals($this->event->getResponse()->getStatusCode(), 302);
    //     $this->assertEquals($this->event->getResponse()->getHeaders()->toString(), "");
    // }

    public function testFoundSetsBaseUrl()
    {
        $manager = $this->getPluginManager();
        $router  = $manager->getServiceLocator()->get('router');
        $this->strategy->setServiceLocator($manager);

        $request = new HttpRequest;
        $request->setUri('http://example.com/en/');

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $this->strategy->found($this->event);

        $expected = '/en';
        $actual   = $router->getBaseUrl();
        $this->assertEquals($expected, $actual);
    }

    public function testFoundAppendsExistingBaseUrl()
    {
        $manager = $this->getPluginManager();
        $router  = $manager->getServiceLocator()->get('router');
        $router->setBaseUrl('/some/deep/installation/path');
        $this->strategy->setServiceLocator($manager);

        $request = new HttpRequest;
        $request->setUri('http://example.com/some/deep/installation/path/en/');

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $this->strategy->found($this->event);

        $expected = '/some/deep/installation/path/en';
        $actual   = $router->getBaseUrl();
        $this->assertEquals($expected, $actual);
    }

    // public function testFoundWithRedirectWhenAtLocaleUrlButMissingTrailingSlash()
    // {
    //     $request = new HttpRequest;
    //     $request->setUri('http://example.com/en');

    //     $this->event->setLocale('en');
    //     $this->event->setRequest($request);
    //     $this->event->setResponse(new HttpResponse);

    //     $this->strategy->found($this->event);

    //     $statusCode = $this->event->getResponse()->getStatusCode();
    //     $header     = $this->event->getResponse()->getHeaders()->get('Location');
    //     $this->assertEquals(302, $statusCode);
    //     $this->assertEquals('http://example.com/en/', (string) $header);
    // }

    public function testFoundDoesNotRedirectWhenLocaleIsInPath()
    {
        $request = new HttpRequest;
        $request->setUri('http://example.com/en/');

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse);

        $this->strategy->found($this->event);

        $statusCode = $this->event->getResponse()->getStatusCode();
        $header     = $this->event->getResponse()->getHeaders()->has('Location');
        $this->assertNotEquals(302, $statusCode);
        $this->assertFalse($header);
    }

    public function testAssembleReplacesLocaleInPath()
    {
        $uri = new Uri('/en-US/');

        $this->event->setLocale('en-GB');
        $this->event->setUri($uri);

        $this->strategy->assemble($this->event);

        $expected = '/en-GB/';
        $actual   = $this->event->getUri()->getPath();

        $this->assertEquals($expected, $actual);
    }

    public function testAssembleReplacesLocaleInDeepPath()
    {
        $uri = new Uri('/en-US/foo/bar/baz');

        $this->event->setLocale('en-GB');
        $this->event->setUri($uri);

        $this->strategy->assemble($this->event);

        $expected = '/en-GB/foo/bar/baz';
        $actual   = $this->event->getUri()->getPath();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @todo Is this a bug in the strategy?
     */
    // public function testAssembleRespectsRouterBasePath()
    // {
    //     $manager = $this->getPluginManager();
    //     $router  = $manager->getServiceLocator()->get('router');
    //     $router->setBaseUrl('/some/deep/installation/path');
    //     $this->strategy->setServiceLocator($manager);

    //     $uri = new Uri('/some/deep/installation/path/en-US/foo/bar/baz');

    //     $this->event->setLocale('en-GB');
    //     $this->event->setUri($uri);

    //     $this->strategy->assemble($this->event);

    //     $expected = 'some/deep/installation/path/en-GB/foo/bar/baz';
    //     $actual   = $this->event->getUri()->getPath();

    //     $this->assertEquals($expected, $actual);
    // }

    public function testAssembleWorksWithAliasesToo()
    {
        $uri = new Uri('/nl/foo/bar/baz');

        $this->event->setLocale('en-US');
        $this->event->setUri($uri);

        $this->strategy->setOptions(array(
            'aliases' => array('nl' => 'nl-NL', 'en' => 'en-US'),
        ));
        $this->strategy->assemble($this->event);

        $expected = '/en/foo/bar/baz';
        $actual   = $this->event->getUri()->getPath();

        $this->assertEquals($expected, $actual);
    }

    protected function getPluginManager($console = false)
    {
        $sl = new ServiceManager;
        $sl->setService('router', $console ? new ConsoleRouter : new HttpRouter);

        $pluginManager = $this->getMock('SlmLocale\Strategy\StrategyPluginManager', array(
            'getServiceLocator'
        ));
        $pluginManager->expects($this->any())
                      ->method('getServiceLocator')
                      ->will($this->returnValue($sl));

        return $pluginManager;
    }

}
