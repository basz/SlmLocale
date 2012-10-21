<?php
/**
 * Copyright (c) 2012 Soflomo http://soflomo.com.
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
 * @package     SlmLocale
 * @subpackage  Strategy
 * @author      Jurian Sluiman <jurian@soflomo.com>
 * @copyright   2012 Soflomo http://soflomo.com.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://ensemble.github.com
 */

namespace SlmLocale\Strategy;

use SlmLocale\LocaleEvent;
use Zend\Http\Header\Cookie;
use Zend\Http\Header\SetCookie;
use Zend\Http\Request as HttpRequest;

class CookieStrategy extends AbstractStrategy
{
    const COOKIE_NAME = 'slm_locale';

    public function detect(LocaleEvent $event)
    {
        $request = $event->getRequest();

        if (!$request instanceof HttpRequest) {
            return;
        }
        if (!$event->hasSupported()) {
            return;
        }

        $cookie = $request->getCookie();
        if (!$cookie || !$cookie->offsetExists(self::COOKIE_NAME)) {
            return;
        }

        $locale    = $cookie->offsetGet(self::COOKIE_NAME);
        $supported = $event->getSupported();

        if (in_array($locale, $supported)) {
            return $locale;
        }
    }

    public function found(LocaleEvent $event)
    {
        $locale   = $event->getLocale();
        $request  = $event->getRequest();
        $cookie   = $request->getCookie();

        // Omit Set-Cookie header when cookie is present
        if ($cookie instanceof Cookie
            && $cookie->offsetExists(self::COOKIE_NAME)
            && $locale === $cookie->offsetGet(self::COOKIE_NAME)
        ) {
            return;
        }

        $response = $event->getResponse();
        $cookies  = $response->getCookie();

        $setCookie = new SetCookie(self::COOKIE_NAME, $locale);
        $response->getHeaders()->addHeader($setCookie);
    }
}