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

namespace SlmLocale;

use Zend\EventManager\Event;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;
use Zend\Uri\Uri;

class LocaleEvent extends Event
{
    const EVENT_DETECT   = 'detect';
    const EVENT_FOUND    = 'found';
    const EVENT_ASSEMBLE = 'assemble';

    protected $request;
    protected $response;
    protected $supported;
    protected $locale;
    protected $uri;

    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest(RequestInterface $request)
    {
        $this->setParam('request', $request);
        $this->request = $request;
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse(ResponseInterface $response)
    {
        $this->setParam('response', $response);
        $this->response = $response;
        return $this;
    }

    public function getSupported()
    {
        return $this->supported;
    }

    public function setSupported(array $supported)
    {
        $this->setParam('supported', $supported);
        $this->supported = $supported;
        return $this;
    }

    public function hasSupported()
    {
        return is_array($this->supported) && count($this->supported);
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->setParam('locale', $locale);
        $this->locale = $locale;
        return $this;
    }

    /**
     * Get uri for assemble event
     *
     * @return Uri
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set uri for assemble event
     *
     * @param  Uri $uri
     * @return self
     */
    public function setUri(Uri $uri)
    {
        $this->setParam('uri', $uri);
        $this->uri = $uri;
        return $this;
    }
}