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

use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\SimpleRouteStack;
use Laminas\Stdlib\RequestInterface;
use Laminas\Uri\Uri;
use SlmLocale\LocaleEvent;

class UriPathStrategy extends AbstractStrategy
{
    const REDIRECT_STATUS_CODE = 302;

    protected $redirect_when_found = true;
    protected $aliases;
    protected $redirect_to_canonical;
    protected $sl;
    /** @var string|null */
    protected $default;
    /**
     * @var SimpleRouteStack
     */
    protected $router;

    public function __construct(SimpleRouteStack $router = null)
    {
        $this->router = $router;
    }

    public function setOptions(array $options = [])
    {
        if (array_key_exists('redirect_when_found', $options)) {
            $this->redirect_when_found = (bool) $options['redirect_when_found'];
        }
        if (array_key_exists('aliases', $options)) {
            $this->aliases = (array) $options['aliases'];
        }
        if (array_key_exists('redirect_to_canonical', $options)) {
            $this->redirect_to_canonical = (bool) $options['redirect_to_canonical'];
        }
        if (array_key_exists('default', $options)) {
            $this->default = (string) $options['default'];
        }
    }

    protected function redirectWhenFound()
    {
        return $this->redirect_when_found;
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
        if (! $this->isHttpRequest($request)) {
            return;
        }

        $base   = $this->getBasePath($request);
        $locale = $this->getFirstSegmentInPath($request->getUri(), $base);
        if (! $locale) {
            return;
        }

        $aliases = $this->getAliases();
        if (null !== $aliases && array_key_exists($locale, $aliases)) {
            $locale = $aliases[$locale];
        }

        if (! $event->hasSupported() || ! in_array($locale, $event->getSupported())) {
            return;
        }

        return $locale;
    }

    public function found(LocaleEvent $event)
    {
        if (array_key_exists('DISABLE_URIPATHSTRATEGY', $_SERVER) && true === $_SERVER['DISABLE_URIPATHSTRATEGY']) {
            return;
        }

        $request = $event->getRequest();
        if (! $this->isHttpRequest($request)) {
            return;
        }

        $locale = $event->getLocale();
        if (null === $locale || (null !== $this->default && $locale === $this->default)) {
            return;
        }

        if (! $this->redirectToCanonical() && null !== $this->getAliases()) {
            $alias = $this->getAliasForLocale($locale);
            if (null !== $alias) {
                $locale = $alias;
            }
        }

        $base  = $this->getBasePath($request);
        $found = $this->getFirstSegmentInPath($request->getUri(), $base);

        if ($this->router instanceof TreeRouteStack) {
            $this->router->setBaseUrl($base . '/' . $locale);
        }

        if ($locale === $found) {
            return;
        }

        if (! $this->redirectWhenFound()) {
            return;
        }

        $uri  = $request->getUri();
        $path = $uri->getPath();

        if (! $found || ($event->hasSupported() && ! in_array($found, $event->getSupported()))) {
            $path = '/' . $locale . $path;
        } else {
            $path = str_replace($found, $locale, $path);
        }

        $uri->setPath($path);

        $response = $event->getResponse();
        $response->setStatusCode(self::REDIRECT_STATUS_CODE);
        $response->getHeaders()->addHeaderLine('Location', $uri->toString());

        return $response;
    }

    public function assemble(LocaleEvent $event)
    {
        $uri     = $event->getUri();
        $base    = $this->getBasePath($event->getRequest());
        $locale  = $event->getLocale();

        if (! $this->redirectToCanonical() && null !== $this->getAliases()) {
            $alias = $this->getAliasForLocale($locale);
            if (null !== $alias) {
                $locale = $alias;
            }
        }

        $path = $uri->getPath();

        // Last part of base is now always locale, remove that
        $parts       = explode('/', trim($base, '/'));
        $lastElement = count($parts) - 1;

        $removeFirstLocale = true;
        if (null !== $this->default &&
            isset($parts[$lastElement]) &&
            ! in_array($parts[$lastElement], $event->getSupported(), true) &&
            $parts[$lastElement] !== $this->default
        ) {
            $removeFirstLocale = false;
        }

        if (true === $removeFirstLocale) {
            // Remove first part
            array_pop($parts);
        }

        $base  = implode('/', $parts);

        if ($base) {
            $path = substr(trim($path, '/'), strlen($base));
        }

        $parts  = explode('/', trim($path, '/'));
        if (true === $removeFirstLocale) {
            // Remove first part
            array_shift($parts);
        }

        if ($locale === $this->default) {
            $locale = '';
        } else {
            $locale .= '/';
        }

        $path = ($base ? '/' : '') . trim($base, '/') . '/' . $locale . implode('/', $parts);
        $uri->setPath($path);

        return $uri;
    }

    protected function getFirstSegmentInPath(Uri $uri, $base = null)
    {
        $path = $uri->getPath();

        if ($base) {
            $path = substr($path, strlen($base));
        }

        $parts  = explode('/', trim($path, '/'));
        $locale = array_shift($parts);

        return $locale;
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
     * @param RequestInterface|null $request
     * @return string|null
     */
    protected function getBasePath(RequestInterface $request = null)
    {
        $result = null;
        if ($this->router instanceof TreeRouteStack) {
            $result = $this->router->getBaseUrl();
        }

        if (null === $result && null !== $request && method_exists($request, 'getBasePath')) {
            $result = $request->getBasePath();
        }

        return $result;
    }
}
