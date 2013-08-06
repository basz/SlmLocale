<?php
/**
 * Copyright (c) 2012-2013 Soflomo http://soflomo.com.
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
 * @author      Jurian Sluiman <jurian@soflomo.com>
 * @copyright   2012-2013 Soflomo http://soflomo.com.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://ensemble.github.com
 */

namespace SlmLocale\Strategy;

use SlmLocale\LocaleEvent;
use Zend\Http\PhpEnvironment\Response;

class HostStrategy extends AbstractStrategy
{
    const LOCALE_KEY           = ':locale';
    const REDIRECT_STATUS_CODE = 302;

    protected $domain;

    public function setOptions(array $options = array())
    {
        if (array_key_exists('domain', $options)) {
            $this->domain = $options['domain'];
        }
    }

    public function detect(LocaleEvent $event)
    {
        $request = $event->getRequest();
        $host    = $request->getUri()->getHost();

        $pattern = str_replace(self::LOCALE_KEY, '([a-zA-Z-_]+)', $this->domain);
        $pattern = sprintf('@%s@', $pattern);
        preg_match($pattern, $host, $matches);

        if (!array_key_exists(1, $matches)) {
            return;
        }
        $locale = $matches[1];

        if ($event->hasSupported()
            && ($supported = $event->getSupported())
            && !array_key_exists($locale, $supported)) {
            return;
        }

        return $locale;
    }

    public function found(LocaleEvent $event)
    {
        $uri     = $event->getRequest()->getUri();
        $locale  = $event->getLocale();

        if (null === $locale) {
            return;
        }

        $host = str_replace(self::LOCALE_KEY, $locale, $this->domain);

        if ($host === $uri->getHost()) {
            return;
        }

        /**
         * @todo Use factory or something? Port can be non-default, user/password can be set, query parameters are missing now
         */
        $location = $uri->getScheme() . '://' . $host . $uri->getPath();
        $response = $event->getResponse();
        $response->setStatusCode(self::REDIRECT_STATUS_CODE);
        $response->getHeaders()->addHeaderLine('Location', $location);

        $response->send();
    }
}