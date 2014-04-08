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

use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\Exception\InvalidArgumentException;
use Zend\Http\Header\Cookie;
use Zend\Http\Header\SetCookie;

class CookieStrategy extends AbstractStrategy
{
    const COOKIE_NAME = 'slm_locale';

    /**
     * The name of the cookie.
     *
     * @var string
     */
    protected $cookieName;

    public function setOptions(array $options = array())
    {
        if (array_key_exists('cookie_name', $options)) {
            $this->setCookieName($options['cookie_name']);
        }
    }

    public function detect(LocaleEvent $event)
    {
        $request    = $event->getRequest();
        $cookieName = $this->getCookieName();

        if (!$this->isHttpRequest($request)) {
            return;
        }
        if (!$event->hasSupported()) {
            return;
        }

        $cookie = $request->getCookie();
        if (!$cookie || !$cookie->offsetExists($cookieName)) {
            return;
        }

        $locale    = $cookie->offsetGet($cookieName);
        $supported = $event->getSupported();

        if (!in_array($locale, $supported)) {
            return;
        }

        return $locale;
    }

    public function found(LocaleEvent $event)
    {
        $locale     = $event->getLocale();
        $request    = $event->getRequest();
        $cookieName = $this->getCookieName();

        if (!$this->isHttpRequest($request)) {
            return;
        }

        $cookie   = $request->getCookie();

        // Omit Set-Cookie header when cookie is present
        if ($cookie instanceof Cookie
            && $cookie->offsetExists($cookieName)
            && $locale === $cookie->offsetGet($cookieName)
        ) {
            return;
        }

        $path = '/';

        if (method_exists($request, 'getBasePath')) {
            $path = rtrim($request->getBasePath(), '/') . '/';
        }

        $response  = $event->getResponse();
        $setCookie = new SetCookie($cookieName, $locale, null, $path);

        $response->getHeaders()->addHeader($setCookie);
    }

    /**
     * @return string
     */
    public function getCookieName()
    {
        if (null === $this->cookieName) {
            return self::COOKIE_NAME;
        }

        return (string) $this->cookieName;
    }

    /**
     * @param string $cookieName
     * @throws InvalidArgumentException
     */
    public function setCookieName($cookieName)
    {
        if(!preg_match("/^(?!\\$)[!-~]+$/", $cookieName)) {
            throw new InvalidArgumentException($cookieName . " is not a vaild cookie name.");
        }

        $this->cookieName = $cookieName;
    }
}
