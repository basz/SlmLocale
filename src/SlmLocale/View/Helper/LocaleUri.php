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

namespace SlmLocale\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\View\Helper\HeadScript;
use Zend\Mvc\Router\Http\TreeRouteStack;
use Zend\Http\PhpEnvironment\Request as HttpRequest;

class LocaleUri extends AbstractHelper
{
    /**
     * @var \SlmLocale/Locale/Detector $detector
     */
    protected $detector;

    /**
     * @param \SlmLocale\View\Helper\SlmLocale $detector
     */
    public function setDetector($detector)
    {
        $this->detector = $detector;
    }

    /**
     * @return \SlmLocale\View\Helper\SlmLocale
     */
    public function getDetector()
    {
        return $this->detector;
    }
    /**
     * Generates an localized url
     *
     * @see    Zend\View\Helpes\Url::__invoke()
     * @param  null    $locale             Locale
     * @param  string  $name               Name of the route
     * @param  array   $params             Parameters for the link
     * @param  array   $options            Options for the route
     * @param  boolean $reuseMatchedParams Whether to reuse matched parameters
     * @return string  Url                 For the link href attribute
     * @throws Exception\RuntimeException  If no RouteStackInterface was provided
     * @throws Exception\RuntimeException  If no RouteMatch was provided
     * @throws Exception\RuntimeException  If RouteMatch didn't contain a matched route name
     */
    public function __invoke($locale = null, $name = null, array $params = array(), $options = array(), $reuseMatchedParams = true)
    {
        $url = $this->detector->assemble($locale,
            $this->getView()->url($name, $params, $options, $reuseMatchedParams)
        );

        return $url;
    }

}