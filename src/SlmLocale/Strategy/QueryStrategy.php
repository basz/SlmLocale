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

class QueryStrategy extends AbstractStrategy
{
    /**
     * Default query key
     *
     * @var string
     */
    const QUERY_KEY = 'lang';

    /**
     * Query key to use for request
     *
     * @var string
     */
    protected $query_key;

    public function setOptions(array $options = array())
    {
        if (array_key_exists('query_key', $options)) {
            $this->query_key = (string) $options['query_key'];
        }
    }

    protected function getQueryKey()
    {
        if (null === $this->query_key) {
            $this->query_key = self::QUERY_KEY;
        }

        return $this->query_key;
    }

    /**
     * {@inheritdoc }
     */
    public function detect(LocaleEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->isHttpRequest($request)) {
            return;
        }

        if (!$event->hasSupported()) {
            return;
        }

        $locale = $request->getQuery($this->getQueryKey());
        if ($locale === null) {
            return;
        }

        if (!in_array($locale, $event->getSupported())) {
            return;
        }

        return $locale;
    }

    /**
     * {@inheritdoc}
     */
    public function assemble(LocaleEvent $event)
    {
        $uri     = $event->getUri();
        $locale  = $event->getLocale();

        $query = $uri->getQueryAsArray();
        $key   = $this->getQueryKey();

        $query[$key] = $locale;

        $uri->setQuery($query);
        return $uri;
    }
}
