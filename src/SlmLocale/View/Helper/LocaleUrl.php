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
namespace SlmLocale\View\Helper;

use SlmLocale\Locale\Detector;
use Zend\Http\Request;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\View\Helper\AbstractHelper;
use Zend\View\Exception\RuntimeException;

class LocaleUrl extends AbstractHelper
{
    /**
     * @var Detector $detector
     */
    protected $detector;

    protected $match;

    /**
     * @var Request $request
     */
    protected $request;

    public function __construct(Detector $detector, Request $request, Routematch $match = null)
    {
        $this->detector = $detector;
        $this->match    = $match;
        $this->request  = $request;
    }

    protected function getDetector()
    {
        return $this->detector;
    }

    protected function getRouteMatch()
    {
        return $this->match;
    }

    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * Generates an localized url
     *
     * @see    Zend\View\Helpes\Url::__invoke()
     * @param  string  $locale             Locale
     * @param  string  $name               Name of the route
     * @param  array   $params             Parameters for the link
     * @param  array   $options            Options for the route
     * @param  boolean $reuseMatchedParams Whether to reuse matched parameters
     * @return string  Url                 For the link href attribute
     * @throws Exception\RuntimeException  If no RouteStackInterface was provided
     * @throws Exception\RuntimeException  If no RouteMatch was provided
     * @throws Exception\RuntimeException  If RouteMatch didn't contain a matched route name
     */
    public function __invoke($locale, $name = null, $params = array(), $options = array(), $reuseMatchedParams = true)
    {
        if (!$this->getDetector()) {
            throw new RuntimeException('To assemble an url, a detector is required');
        }

        /**
         * With a route match, we can use the url view helper to assemble a new url. If no
         * route match is present, we've a 404 and grab the path from the request object.
         */
        if ($this->getRouteMatch()) {

            if (!isset($options['locale'])) {
                $options['locale'] = $locale;
            }

            $url = $this->getView()->url($name, $params, $options, $reuseMatchedParams);
        } else {
            $url = $this->getRequest()->getUri()->getPath();
        }

        return $this->getDetector()->assemble($locale, $url, $this->getRequest());
    }
}
