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

use SlmLocale\Strategy\HttpAcceptLanguageStrategy;
use SlmLocale\LocaleEvent;

use Zend\Http\Header\AcceptLanguage;
use Zend\Http\Request as HttpRequest;

class HttpAcceptLanguageStrategyTest extends TestCase
{
    protected $strategy;
    protected $event;

    public function setUp()
    {
        $this->strategy = new HttpAcceptLanguageStrategy;
        $this->event    = new LocaleEvent;
        $this->event->setRequest(new HttpRequest);
    }

    public function testReturnsVoidWhenHeaderIsNotPresent()
    {
        $strategy = $this->strategy;
        $event    = $this->event;

        $locale = $strategy->detect($event);
        $this->assertNull($locale);
    }

    public function testReturnsFirstLanguageByDefault()
    {
        $strategy = $this->strategy;
        $event    = $this->event;

        $header   = new AcceptLanguage;
        $header->addLanguage('foo');

        $event->getRequest()
              ->getHeaders()
              ->addHeader($header);

        $locale = $strategy->detect($event);
        $this->assertEquals('foo', $locale);
    }

    public function testDetectsLanguageInPriorityOrder()
    {
        $strategy = $this->strategy;
        $event    = $this->event;

        $header   = new AcceptLanguage;
        $header->addLanguage('foo', 0.6);
        $header->addLanguage('bar', 1);
        $header->addLanguage('baz', 0.8);

        $event->getRequest()
              ->getHeaders()
              ->addHeader($header);

        $locale = $strategy->detect($event);
        $this->assertEquals('bar', $locale);
    }

    public function testSelectsOnlyLanguageFromSupportedList()
    {
        $strategy = $this->strategy;
        $event    = $this->event;

        $header   = new AcceptLanguage;
        $header->addLanguage('foo', 1);
        $header->addLanguage('bar', 0.8);
        $header->addLanguage('baz', 0.6);

        $event->getRequest()
              ->getHeaders()
              ->addHeader($header);

        $event->setSupported(array('bar'));

        $locale = $strategy->detect($event);
        $this->assertEquals('bar', $locale);
    }
    
    public function testSelectsLanguageViaLocaleLookup()
    {
        $strategy = $this->strategy;
        $event    = $this->event;

        $header   = new AcceptLanguage;
        $header->addLanguage('de-DE', 1);
        $header->addLanguage('en-US', 0.8);
        $header->addLanguage('en', 0.6);

        $event->getRequest()
            ->getHeaders()
            ->addHeader($header);

        $event->setSupported(array('en', 'de'));

        $locale = $strategy->detect($event);
        $this->assertEquals('de', $locale);
    }    
}
