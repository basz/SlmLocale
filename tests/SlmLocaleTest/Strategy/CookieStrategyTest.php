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

use SlmLocale\Strategy\CookieStrategy;
use SlmLocale\LocaleEvent;

use Zend\Http\Header\Cookie;
use Zend\Http\Header\SetCookie;
use Zend\Http\Request  as HttpRequest;
use Zend\Http\Response as HttpResponse;

class CookieStrategyTest extends TestCase
{
    protected $strategy;
    protected $event;

    public function setUp()
    {
        $this->strategy = new CookieStrategy;
        $this->event    = new LocaleEvent;

        $request  = new HttpRequest;
        $response = new HttpResponse;
        $this->event->setRequest($request);
        $this->event->setResponse($response);
    }

    public function testReturnsVoidWhenNoSupportedLocalesAreGiven()
    {
        $event    = $this->event;
        $strategy = $this->strategy;

        $locale = $strategy->detect($event);
        $this->assertNull($locale);
    }

    public function testReturnsVoidWhenNoCookieIsNotSet()
    {
        $event    = $this->event;
        $strategy = $this->strategy;
        $event->setSupported(array('foo'));

        $locale = $strategy->detect($event);
        $this->assertNull($locale);
    }

    public function testLocaleInCookieIsReturned()
    {
        $cookie = new Cookie;
        $cookie->offsetSet(CookieStrategy::COOKIE_NAME, 'foo');

        $event = $this->event;
        $event->setSupported(array('foo'));
        $event->getRequest()
              ->getHeaders()->addHeader($cookie);

        $strategy = $this->strategy;

        $locale   = $strategy->detect($event);
        $this->assertEquals('foo', $locale);
    }

    public function testLocaleInSetCookieHeaderWhenFound()
    {
        $strategy = $this->strategy;
        $event    = $this->event;
        $headers  = $event->getResponse()->getHeaders();
        $event->setLocale('foo');

        $strategy->found($event);

        $this->assertTrue($headers->has('Set-Cookie'));

        $cookies = $headers->get('Set-Cookie');
        $cookie  = $cookies[0];
        $name    = CookieStrategy::COOKIE_NAME;
        $this->assertEquals($name, $cookie->getName());
        $this->assertEquals('foo', $cookie->getValue());
    }

    public function testSetCookieHeaderSkippedWhenLocaleInRequestHeader()
    {
        $cookie = new Cookie;
        $cookie->offsetSet(CookieStrategy::COOKIE_NAME, 'foo');

        $event = $this->event;
        $event->getRequest()
              ->getHeaders()->addHeader($cookie);

        $strategy = $this->strategy;
        $headers  = $event->getResponse()->getHeaders();
        $event->setLocale('foo');

        $strategy->found($event);

        $this->assertFalse($headers->has('Set-Cookie'));
    }

    public function testLocaleInSetCookieHeaderWhenLocaleInRequestIsDifferent()
    {
        $cookie = new Cookie;
        $cookie->offsetSet(CookieStrategy::COOKIE_NAME, 'foo');

        $event = $this->event;
        $event->getRequest()
              ->getHeaders()->addHeader($cookie);

        $strategy = $this->strategy;
        $headers  = $event->getResponse()->getHeaders();
        $event->setLocale('bar');

        $strategy->found($event);

        $this->assertTrue($headers->has('Set-Cookie'));

        $cookies = $headers->get('Set-Cookie');
        $cookie  = $cookies[0];
        $name    = CookieStrategy::COOKIE_NAME;
        $this->assertEquals($name, $cookie->getName());
        $this->assertEquals('bar', $cookie->getValue());
    }

    public function testLocaleInCookieIsReturnedIfNameChanged()
    {
        $cookie = new Cookie;
        $cookie->offsetSet('foo_cookie', 'foo');

        $event = $this->event;
        $event->setSupported(array('foo'));
        $event->getRequest()
            ->getHeaders()->addHeader($cookie);

        $strategy = $this->strategy;
        $strategy->setCookieName('foo_cookie');

        $locale   = $strategy->detect($event);
        $this->assertEquals('foo', $locale);
    }

    /**
     * @expectedException \SlmLocale\Strategy\Exception\InvalidArgumentException
     */
    public function testInvalidCookieNameFails()
    {
        $strategy = $this->strategy;
        $strategy->setCookieName('$ThisIsAnInvalidCookieName');
    }
}
