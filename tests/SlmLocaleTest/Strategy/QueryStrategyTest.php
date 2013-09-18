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

use SlmLocale\Strategy\QueryStrategy;
use SlmLocale\LocaleEvent;

use Zend\Http\Request  as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Uri\Uri;

class QueryStrategyTest extends TestCase
{
    /**
     * @var QueryStrategy $strategy
     */
    protected $strategy;
    /**
     * @var LocaleEvent $event
     */
    protected $event;

    public function setUp()
    {
        $this->strategy = new QueryStrategy;
        $this->event    = new LocaleEvent;

        $request  = new HttpRequest;
        $response = new HttpResponse;
        $this->event->setRequest($request);
        $this->event->setResponse($response);
    }

    public function testReturnsNull()
    {
        $strategy = $this->strategy;
        $event    = $this->event;

        $locale = $strategy->detect($event);
        $this->assertNull($locale);
    }

    public function testNoSupportedReturnNull()
    {
        $strategy = $this->strategy;
        $event    = $this->event;
        $request  = $event->getRequest();
        $query    = $request->getQuery();

        $query->lang = 'locale';
        $request->setQuery($query);

        $locale = $strategy->detect($event);
        $this->assertNull($locale);
    }

    public function testSetQueryKeyReturnsLocale()
    {
        $strategy = $this->strategy;
        $event    = $this->event;

        $event->setSupported(array('locale'));

        $request = $event->getRequest();
        $query = $request->getQuery();
        $query->lang = 'locale';
        $request->setQuery($query);

        $locale = $strategy->detect($event);
        $this->assertEquals('locale', $locale);
    }

    public function testQueryKeyCanBeModifiedAndHaveLocaleReturned()
    {
        $strategy = $this->strategy;
        $event    = $this->event;

        $strategy->setOptions(array('query_key' => 'language'));
        $event->setSupported(array('locale'));

        $request = $event->getRequest();
        $query = $request->getQuery();
        $query->language = 'locale';
        $request->setQuery($query);

        $locale = $strategy->detect($event);
        $this->assertEquals('locale', $locale);
    }

    public function testAssemblingAddsQueryParameter()
    {
        $strategy = $this->strategy;
        $event    = $this->event;
        $uri      = new Uri('/');

        $event->setLocale('en-US');
        $event->setUri($uri);
        $strategy->assemble($event);

        $query    = $event->getUri()->getQuery();
        $expected = 'lang=en-US';
        $this->assertEquals($expected, $query);
    }

    public function testAssemblingReplacesExistingQueryParameter()
    {
        $strategy = $this->strategy;
        $event    = $this->event;
        $uri      = new Uri('/?lang=nl-NL');

        $event->setLocale('en-US');
        $event->setUri($uri);
        $strategy->assemble($event);

        $query    = $event->getUri()->getQuery();
        $expected = 'lang=en-US';
        $this->assertEquals($expected, $query);
    }

    public function testAsssemblingUsesQueryKeyParamter()
    {
        $strategy = $this->strategy;
        $event    = $this->event;
        $uri      = new Uri('/');

        $event->setLocale('en-US');
        $event->setUri($uri);
        $strategy->setOptions(array('query_key' => 'language'));
        $strategy->assemble($event);

        $query    = $event->getUri()->getQuery();
        $expected = 'language=en-US';
        $this->assertEquals($expected, $query);
    }
}
