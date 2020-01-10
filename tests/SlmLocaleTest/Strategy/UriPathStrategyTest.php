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

use Laminas\Console\Request as ConsoleRequest;
use Laminas\Console\Response as ConsoleResponse;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use Laminas\Mvc\Console\Router\SimpleRouteStack as ConsoleRouter;
use Laminas\Router\Http\TreeRouteStack as HttpRouter;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Uri\Uri;
use PHPUnit\Framework\TestCase;
use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\StrategyPluginManager;
use SlmLocale\Strategy\UriPathStrategy;

class UriPathStrategyTest extends TestCase
{
    /** @var UriPathStrategy */
    private $strategy;
    /** @var LocaleEvent */
    private $event;
    /** @var HttpRouter */
    private $router;

    public function setUp(): void
    {
        $this->router = new HttpRouter();

        $this->strategy = new UriPathStrategy($this->router);

        $this->event = new LocaleEvent();
        $this->event->setSupported(['nl', 'de', 'en']);
    }

    public function testDetectWithConsoleRequestReturnsNull()
    {
        $this->event->setRequest(new ConsoleRequest());
        $this->event->setResponse(new ConsoleResponse());

        $locale = $this->strategy->detect($this->event);
        $this->assertNull($locale);
    }

    public function testDetectReturnsNullByDefault()
    {
        $this->event->setRequest(new HttpRequest());
        $this->event->setResponse(new HttpResponse());

        $locale = $this->strategy->detect($this->event);
        $this->assertNull($locale);
    }

    public function testDetectReturnsFirstPathSegmentAsLocale()
    {
        $request = new HttpRequest();
        $request->setUri('http://example.com/en/deep/path/');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $locale   = $this->strategy->detect($this->event);
        $expected = 'en';
        $this->assertEquals($expected, $locale);
    }

    public function testDetectReturnsNullForUnsupported()
    {
        $request = new HttpRequest();
        $request->setUri('http://example.com/fr/');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $locale = $this->strategy->detect($this->event);
        $this->assertNull($locale);
    }

    public function testDetectWithBaseUrlReturnsRightPartOfPath()
    {
        $this->router->setBaseUrl('/some/seep/installation/path');

        $this->event->setLocale('en');

        $request = new HttpRequest();
        $request->setUri('http://example.com/some/deep/installation/path/en/some/deep/path');

        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $locale   = $this->strategy->detect($this->event);
        $this->assertSame('en', $locale);
    }

    public function testFoundRedirectsByDefault()
    {
        $uri     = 'http://username:password@example.com:8080/some/deep/path/some.file?withsomeparam=true';
        $request = new HttpRequest();
        $request->setUri($uri);

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $this->strategy->found($this->event);

        $statusCode = $this->event->getResponse()->getStatusCode();
        $header     = $this->event->getResponse()->getHeaders()->get('Location');
        $expected   = 'Location: http://username:password@example.com:8080/en/some/deep/path/some.file?withsomeparam=true';
        $this->assertEquals(302, $statusCode);
        $this->assertStringContainsString($expected, (string) $header);
    }

    public function testFoundShouldRespectDisabledRedirectWhenFound()
    {
        $this->strategy->setOptions(['redirect_when_found' => false]);

        $request = new HttpRequest();
        $request->setUri('http://example.com/');

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $this->strategy->found($this->event);

        $statusCode = $this->event->getResponse()->getStatusCode();
        $header     = $this->event->getResponse()->getHeaders()->has('Location');
        $this->assertNotEquals(302, $statusCode);
        $this->assertFalse($header);
    }

    public function testFoundRedirectsByDefaultWithBasePath()
    {
        $uri     = 'http://example.com/my-app/public/nl/some/deep/path/some.file?withsomeparam=true';
        $request = new HttpRequest();
        $request->setUri($uri);
        $request->setBasePath('/my-app/public');

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $this->strategy->found($this->event);

        $statusCode = $this->event->getResponse()->getStatusCode();
        $header     = $this->event->getResponse()->getHeaders()->get('Location');
        $expected   = 'Location: http://example.com/my-app/public/en/some/deep/path/some.file?withsomeparam=true';
        $this->assertEquals(302, $statusCode);
        $this->assertStringContainsString($expected, (string) $header);
    }

    public function testFoundRedirectsByDefaultWithBasePathDisabledRedirectWhenFound()
    {
        $this->strategy->setOptions(['redirect_when_found' => false]);
        $uri     = 'http://example.com/my-app/public/nl/some/deep/path/some.file?withsomeparam=true';
        $request = new HttpRequest();
        $request->setUri($uri);
        $request->setBasePath('/my-app/public');

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $this->strategy->found($this->event);

        $statusCode = $this->event->getResponse()->getStatusCode();
        $header     = $this->event->getResponse()->getHeaders()->has('Location');
        $this->assertNotEquals(302, $statusCode);
        $this->assertFalse($header);
    }

    // public function testFoundWithDisabledRedirectWhenFoundOptionLocaleShouldStillBeDirectedAnywayWhenPathContainsNothingFurther()
    // {
    //     $this->strategy->setOptions(['redirect_when_found' => false]);
    //     $this->strategy->setServiceLocator($this->getPluginManager());

    //     $this->event->setLocale('en');
    //     $this->event->setSupported(['nl', 'de', 'en']);

    //     $request = new HttpRequest;
    //     $request->setUri('http://example.com/en');

    //     $this->event->setRequest($request);
    //     $this->event->setResponse(new HttpResponse);

    //     $locale = $this->strategy->found($this->event);

    //     $this->assertEquals($this->event->getResponse()->getStatusCode(), 302);
    //     $this->assertStringContainsString($this->event->getResponse()->getHeaders()->toString(), "Location: http://example.com/en/\r\n");
    // }

    // public function testFoundWithDisabledRedirectWhenFoundOptionLocaleShouldStillBeDirectedAnyway()
    // {
    //     $this->strategy->setOptions(['redirect_when_found' => false]);
    //     $this->strategy->setServiceLocator($this->getPluginManager());

    //     $this->event->setLocale('en');
    //     $this->event->setSupported(['nl', 'de', 'en']);

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
        $request = new HttpRequest();
        $request->setUri('http://example.com/en/');

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $this->strategy->found($this->event);

        $expected = '/en';
        $actual   = $this->router->getBaseUrl();
        $this->assertEquals($expected, $actual);
    }

    public function testFoundSetsBaseUrlWithDefault()
    {
        $request = new HttpRequest();
        $request->setUri('http://example.com/');

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $this->strategy->setOptions([
            'default' => 'en',
        ]);
        $this->strategy->found($this->event);

        $actual   = $this->router->getBaseUrl();
        $this->assertNull($actual);
    }

    public function testFoundSetsBaseUrlWithDefaultNotMatch()
    {
        $request = new HttpRequest();
        $request->setUri('http://example.com/fr');

        $this->event->setLocale('fr');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $this->strategy->setOptions([
            'default' => 'en',
        ]);
        $this->strategy->found($this->event);

        $actual   = $this->router->getBaseUrl();
        $this->assertSame('/fr', $actual);
    }

    public function testFoundAppendsExistingBaseUrl()
    {
        $this->router->setBaseUrl('/some/deep/installation/path');

        $request = new HttpRequest();
        $request->setUri('http://example.com/some/deep/installation/path/en/');

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $this->strategy->found($this->event);

        $expected = '/some/deep/installation/path/en';
        $actual   = $this->router->getBaseUrl();
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
        $request = new HttpRequest();
        $request->setUri('http://example.com/en/');

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

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

        $this->strategy->setOptions([
            'aliases' => ['nl' => 'nl-NL', 'en' => 'en-US'],
        ]);
        $this->strategy->assemble($this->event);

        $expected = '/en/foo/bar/baz';
        $actual   = $this->event->getUri()->getPath();

        $this->assertEquals($expected, $actual);
    }

    public function testAssembleWithDefault()
    {
        $uri = new Uri('/nl/foo/bar/baz');

        $this->event->setLocale('en');
        $this->event->setUri($uri);

        $this->router->setBaseUrl('/nl');
        $this->strategy = new UriPathStrategy($this->router);
        $this->strategy->setOptions([
            'default' => 'en',
        ]);
        $this->strategy->assemble($this->event);

        $expected = '/foo/bar/baz';
        $actual   = $this->event->getUri()->getPath();

        $this->assertSame($expected, $actual);
    }

    public function testAssembleWithDefaultNotMatching()
    {
        $uri = new Uri('/nl/foo/bar/baz');

        $this->event->setLocale('en');
        $this->event->setUri($uri);

        $this->router->setBaseUrl('/nl');
        $this->strategy = new UriPathStrategy($this->router);
        $this->strategy->setOptions([
            'default' => 'fr',
        ]);
        $this->strategy->assemble($this->event);

        $this->assertSame('/en/foo/bar/baz', $this->event->getUri()->getPath());
    }

    public function testAssembleWithDefaultWithBasePath()
    {
        $uri = new Uri('/my-app/nl/foo/bar/baz');

        $this->event->setLocale('en');
        $this->event->setUri($uri);

        $this->router->setBaseUrl('/my-app/nl');
        $this->strategy = new UriPathStrategy($this->router);
        $this->strategy->setOptions([
            'default' => 'fr',
        ]);
        $this->strategy->assemble($this->event);

        $this->assertSame('/my-app/en/foo/bar/baz', $this->event->getUri()->getPath());
    }

    public function testAssembleWithDefaultWithBasePathWithMatching()
    {
        $uri = new Uri('/my-app/foo/bar/baz');

        $this->event->setLocale('en');
        $this->event->setUri($uri);

        $this->router->setBaseUrl('/my-app');
        $this->strategy = new UriPathStrategy($this->router);
        $this->strategy->setOptions([
            'default' => 'nl',
        ]);
        $this->strategy->assemble($this->event);

        $this->assertSame('/my-app/en/foo/bar/baz', $this->event->getUri()->getPath());
    }

    public function testAssembleWithDefaultWithBasePathWithMatchingPubic()
    {
        $uri = new Uri('/my-app/public/foo/bar/baz');

        $this->event->setLocale('en');
        $this->event->setUri($uri);

        $this->router->setBaseUrl('/my-app/public');
        $this->strategy = new UriPathStrategy($this->router);
        $this->strategy->setOptions([
            'default' => 'nl',
        ]);
        $this->strategy->assemble($this->event);

        $this->assertSame('/my-app/public/en/foo/bar/baz', $this->event->getUri()->getPath());
    }

    public function testAssembleWithBasePathWithMatchingLanguageName()
    {
        $uri = new Uri('/my-app/nl/nl/foo/bar/baz');

        $this->event->setLocale('en');
        $this->event->setUri($uri);

        $this->router->setBaseUrl('/my-app/nl/nl');
        $this->strategy = new UriPathStrategy($this->router);
        $this->strategy->assemble($this->event);

        $this->assertSame('/my-app/nl/en/foo/bar/baz', $this->event->getUri()->getPath());
    }

    public function testDisableUriPathStrategyPhpunit()
    {
        $_SERVER['DISABLE_URIPATHSTRATEGY'] = true;

        $uri     = 'http://username:password@example.com:8080/some/deep/path/some.file?withsomeparam=true';
        $request = new HttpRequest();
        $request->setUri($uri);

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $this->strategy->found($this->event);

        $statusCode = $this->event->getResponse()->getStatusCode();
        $header     = $this->event->getResponse()->getHeaders()->get('Location');
        $expected   = 'Location: http://username:password@example.com:8080/en/some/deep/path/some.file?withsomeparam=true';
        $this->assertEquals(200, $statusCode);

        $_SERVER['DISABLE_URIPATHSTRATEGY'] = false;
    }

    public function testAssembleWithDefaultMatchingCurrent()
    {
        $uri = new Uri('/foo/bar/baz');

        $this->event->setLocale('en');
        $this->event->setUri($uri);

        $this->strategy->setOptions([
            'default' => 'fr',
        ]);
        $this->strategy->assemble($this->event);

        $this->assertSame('/en/foo/bar/baz', $this->event->getUri()->getPath());
    }

    protected function getPluginManager($console = false)
    {
        $sl = new ServiceManager();
        $sl->setService('router', $console ? new ConsoleRouter() : new HttpRouter());

        $pluginManager = new StrategyPluginManager($sl);

        return $pluginManager;
    }
}
