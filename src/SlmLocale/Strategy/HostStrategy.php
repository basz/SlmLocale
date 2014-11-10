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
use Zend\Uri\Uri;

class HostStrategy extends AbstractStrategy
{
    const LOCALE_KEY           = ':locale';
    const REDIRECT_STATUS_CODE = 302;

    protected $domain;
    protected $aliases;
    protected $redirect_to_canonical;

    public function setOptions(array $options = array())
    {
        if (array_key_exists('domain', $options)) {
            $this->domain = (string) $options['domain'];
        }
        if (array_key_exists('aliases', $options)) {
            $this->aliases = (array) $options['aliases'];
        }
        if (array_key_exists('redirect_to_canonical', $options)) {
            $this->redirect_to_canonical = (bool) $options['redirect_to_canonical'];
        }
    }

    protected function getDomain()
    {
        return $this->domain;
    }

    protected function getAliases()
    {
        return $this->aliases;
    }

    protected function redirectToCanonical()
    {
        return $this->redirect_to_canonical;
    }

    public function detect(LocaleEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->isHttpRequest($request)) {
            return;
        }

        if (!$event->hasSupported()) {
            return;
        }

        $domain = $this->getDomain();
        if (!null === $domain) {
            throw new Exception\InvalidArgumentException(
                'The strategy must be configured with a domain option'
            );
        }
        if (strpos($domain, self::LOCALE_KEY) === false) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The domain %s must contain a locale key part "%s"', $domain, self::LOCALE_KEY
            ));
        }

        $host    = $request->getUri()->getHost();
        $pattern = str_replace(self::LOCALE_KEY, '([a-zA-Z-_.]+)', $domain);
        $pattern = sprintf('@%s@', $pattern);
        $result  = preg_match($pattern, $host, $matches);

        if (!$result) {
            return;
        }

        $locale = $matches[1];

        $aliases = $this->getAliases();
        if (null !== $aliases && array_key_exists($locale, $aliases)) {
            $locale = $aliases[$locale];
        }

        if (!in_array($locale, $event->getSupported())) {
            return;
        }

        return $locale;
    }

    public function found(LocaleEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->isHttpRequest($request)) {
            return;
        }

        if (!$event->hasSupported()) {
            return;
        }

        $locale  = $event->getLocale();
        if (null === $locale) {
            return;
        }

        // By default, use the alias to redirect to
        if (!$this->redirectToCanonical()) {
            $locale = $this->getAliasForLocale($locale);
        }

        $host = str_replace(self::LOCALE_KEY, $locale, $this->getDomain());
        $uri  = $request->getUri();
        if ($host === $uri->getHost()) {
            return;
        }

        $uri->setHost($host);

        $response = $event->getResponse();
        $response->setStatusCode(self::REDIRECT_STATUS_CODE);
        $response->getHeaders()->addHeaderLine('Location', $uri->toString());

        return $response;
    }

    protected function getAliasForLocale($locale)
    {
        foreach ($this->getAliases() as $alias => $item) {
            if ($item === $locale) {
                return $alias;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function assemble(LocaleEvent $event)
    {
        $locale = $event->getLocale();

        foreach ($this->getAliases() as $alias => $item) {
            if ($item == $locale) {
                $tld = $alias;
            }
        }

        if (!isset($tld)) {
            throw new InvalidArgumentException('No matching tld found for current locale');
        }

        $port = $event->getRequest()->getServer()->get('SERVER_PORT');
        $hostname = str_replace(self::LOCALE_KEY, $tld, $this->getDomain());

        if (null !== $port && 80 != $port) {
            $hostname .= ':' . $port;
        }

        $uri = $event->getUri();
        $uri->setHost($hostname);

        return $uri;
    }
}
