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

use Laminas\Http\Header\Cookie;
use Laminas\Http\Header\SetCookie;
use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\Exception\InvalidArgumentException;

class CookieStrategy extends AbstractStrategy
{
    const COOKIE_NAME = 'slm_locale';
    
    /**
     * The name of the cookie.
     *
     * @var string
     */
    protected $cookieName;
    
    protected $cookieHttpOnly = false;
    
    protected $cookieSecure = false;
    
    public function setOptions(array $options = [])
    {
        if (array_key_exists('cookie_name', $options)) {
            $this->setCookieName($options['cookie_name']);
        }
        if (array_key_exists('cookie_http_only', $options)) {
            $this->setCookieHttpOnly($options['cookie_http_only']);
        }
        if (array_key_exists('cookie_secure', $options)) {
            $this->setCookieSecure($options['cookie_secure']);
        }
    }
    
    public function detect(LocaleEvent $event)
    {
        $request    = $event->getRequest();
        $cookieName = $this->getCookieName();
        
        if (! $this->isHttpRequest($request)) {
            return;
        }
        if (! $event->hasSupported()) {
            return;
        }
        
        $cookie = $request->getCookie();
        if (! $cookie || ! $cookie->offsetExists($cookieName)) {
            return;
        }
        
        $locale    = $cookie->offsetGet($cookieName);
        $supported = $event->getSupported();
        
        if (! in_array($locale, $supported)) {
            return;
        }
        
        return $locale;
    }
    
    public function found(LocaleEvent $event)
    {
        $locale     = $event->getLocale();
        $request    = $event->getRequest();
        $cookieName = $this->getCookieName();
        
        if (! $this->isHttpRequest($request)) {
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
            
            $secure = $this->getCookieSecure();
            $httpOnly = $this->getCookieHttpOnly();
            
            $response  = $event->getResponse();
            $setCookie = new SetCookie($cookieName, $locale, null, $path, null, $secure, $httpOnly);
            
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
     * @return self
     */
    public function setCookieName($cookieName)
    {
        if (! preg_match('/^(?!\$)[!-~]+$/', $cookieName)) {
            throw new InvalidArgumentException($cookieName . ' is not a vaild cookie name.');
        }
        
        $this->cookieName = $cookieName;
        return $this;
    }
    
    /**
     * @return bool
     */
    public function getCookieHttpOnly()
    {
        return (bool) $this->cookieHttpOnly;
    }
    
    /**
     * @param bool $cookieHttpOnly
     * @throws InvalidArgumentException
     * @return self
     */
    public function setCookieHttpOnly($cookieHttpOnly)
    {
        if (! is_bool($cookieHttpOnly)) {
            throw new InvalidArgumentException($cookieHttpOnly . ' is not a vaild cookie http only setting.');
        }
        
        $this->cookieHttpOnly = $cookieHttpOnly;
        return $this;
    }
    
    /**
     * @return bool
     */
    public function getCookieSecure()
    {
        return (bool) $this->cookieSecure;
    }
    
    /**
     * @param bool $cookieSecure
     * @throws InvalidArgumentException
     * @return self
     */
    public function setCookieSecure($cookieSecure)
    {
        if (! is_bool($cookieSecure)) {
            throw new InvalidArgumentException($cookieSecure . ' is not a vaild cookie secure setting.');
        }
        
        $this->cookieSecure = $cookieSecure;
        return $this;
    }
}
