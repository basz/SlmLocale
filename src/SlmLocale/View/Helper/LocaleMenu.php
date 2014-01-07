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

use Locale;
use SlmLocale\Locale\Detector;
use Zend\View\Helper\AbstractHelper;
use Zend\View\Exception\RuntimeException;

class LocaleMenu extends AbstractHelper
{
    /**
     * @var Detector $detector
     */
    protected $detector;

    /**
     * Set the class to be used on the list container
     *
     * @var string || null
     */
    protected $class;

    /**
     * Method used to construct a title for each item
     *
     * @var string || null
     */
    protected $titleMethod = 'displayLanguage';

    /**
     * Flag to specify specifies whether the title should be in the current locale
     *
     * @var boolean default false
     */
    protected $titleInCurrentLocale = false;

    /**
     * Method used to construct a label for each item
     *
     * @var string || null
     */
    protected $labelMethod = 'displayLanguage';

    /**
     * Flag to specify specifies whether the label should be in the current locale
     *
     * @var boolean default true
     */
    protected $labelInCurrentLocale = true;

    /**
     * Flag to specify the current locale should be omitted from the menu
     *
     * @var boolean default false
     */
    protected $omitCurrent = false;

    /**
     * @param Detector $detector
     */
    public function setDetector($detector)
    {
        $this->detector = $detector;
    }

    /**
     * @return Detector $detector
     */
    public function getDetector()
    {
        return $this->detector;
    }

    /**
     * @param string $class
     */
    public function setUlClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return string
     */
    public function getUlClass()
    {
        return $this->class;
    }

    /**
     * @param string $itemTitleMethod
     */
    public function setTitleMethod($titleMethod)
    {
        $this->checkLocaleMethod($titleMethod);

        $this->titleMethod = $titleMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitleMethod()
    {
        return $this->titleMethod;
    }

    /**
     * @param boolean $flag
     */
    public function setTitleInCurrentLocale($flag)
    {
        $this->titleInCurrentLocale = (bool) $flag;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getTitleInCurrentLocale()
    {
        return $this->titleInCurrentLocale;
    }

    /**
     * @param string $labelMethod
     */
    public function setLabelMethod($labelMethod)
    {
        $this->checkLocaleMethod($labelMethod);

        $this->labelMethod = $labelMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabelMethod()
    {
        return $this->labelMethod;
    }

    /**
     * @param boolean $flag
     */
    public function setLabelInCurrentLocale($flag)
    {
        $this->labelInCurrentLocale = (bool) $flag;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getLabelInCurrentLocale()
    {
        return $this->labelInCurrentLocale;
    }

    /**
     * @param boolean $omitCurrent
     */
    public function setOmitCurrent($omitCurrent)
    {
        $this->omitCurrent = (bool) $omitCurrent;
        return $this;
    }

    /**
     * @return boolean
     */
    public function omitCurrent()
    {
        return $this->omitCurrent;
    }

    public function __invoke()
    {
        return $this;
    }

    /**
     * @param array $options
     * @return string
     * @throws RuntimeException
     * @todo implement add way to completely default rendering for maximum flexibility (see Zend\View\Helper\Navigation::renderPartial)
     */
    public function __toString()
    {
        if (!($detector = $this->getDetector())) {
            throw new RuntimeException('To assemble an url, a detector is required');
        }

        $list     = '';
        $current  = Locale::getDefault();
        foreach($detector->getSupported() as $locale) {
            if ($this->omitCurrent() && $current === $locale) {
                continue;
            }

            $titleLocale = $this->getTitleInCurrentLocale() ? $locale : $current;
            $labelLocale = $this->getLabelInCurrentLocale() ? $locale : $current;

            $url   = $this->getView()->localeUrl($locale);
            $title = $this->getLocaleProperty($this->getTitleMethod(), $locale, $titleLocale);
            $label = $this->getLocaleProperty($this->getLabelMethod(), $locale, $labelLocale);

            $item = sprintf(
                '<li><a href="%s" title="%s"%s>%s</a></li>' . "\n",
                $url,
                $title,
                ($current === $locale) ? ' class="active"' : '',
                $label
            );

            $list .= $item;
        }

        $class = $this->getUlClass();
        $html  = sprintf(
            '<ul%s>%s</ul>',
            ($class) ? sprintf(' class="%s"', $class) : '',
            $list
        );

        return $html;
    }

    /**
     * Check whether method part of the Locale class is
     *
     * @param  string $method Method to check
     * @throws RuntimeException If method is not part of locale
     * @return true
     */
    protected function checkLocaleMethod($method)
    {
        $options = array(
            'displayLanguage',
            'displayName',
            'displayRegion',
            'displayScript',
            'displayVariant',
            'primaryLanguage',
            'region',
            'script'
        );

        if (!in_array($method, $options)) {
            throw new RuntimeException(sprintf(
                'Unknown method "%s" for Locale, expecting one of these: %s.',
                $method,
                implode(', ', $options)
            ));
        }
    }

    /**
     * Retrieves a value by property from Locale
     *
     * @param $property
     * @param $locale
     * @param bool $in_locale
     * @return mixed
     */
    protected function getLocaleProperty($property, $locale, $in_locale = false)
    {
        $callback = sprintf('\Locale::get%s', ucfirst($property));

        $args = array($locale);

        if ($in_locale && !in_array($property, array('primaryLanguage', 'region', 'script'))) {
            $args[] = $in_locale;
        }

        return call_user_func_array($callback, $args);
    }
}