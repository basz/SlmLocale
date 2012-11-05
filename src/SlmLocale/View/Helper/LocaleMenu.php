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

use Locale;

use SlmLocale\Exception\RuntimeException;
use SlmLocale\Locale\Detector;

use Zend\View\Helper\AbstractHelper;

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
     * method used to construct a label for each item
     *
     * @var string || null
     */
    protected $itemLabelMethod = 'displayName';

    /**
     * method used to construct an info class for each item
     *
     * @var string || null
     */
    protected $itemInfoMethod = 'displayName';

    /**
     * method used to construct a class for each item
     *
     * @var string || null
     */
    protected $itemClassMethod;

    /**
     * Flag to specify specifies whether the label should be in the current locale
     *
     * @var boolean default false
     */
    protected $labelInCurrentLocale = 'false';

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
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $itemClassMethod
     */
    public function setItemClassMethod($itemClassMethod = null)
    {
        if ($itemClassMethod && !in_array($itemClassMethod, array('displayLanguage', 'displayName', 'displayRegion', 'displayScript', 'displayVariant', 'primaryLanguage', 'region', 'script'))) {
            throw new RuntimeException('Unknown method "%s" for "%s" option.', $itemClassMethod, 'itemClassMethod');
        }

        $this->itemClassMethod = $itemClassMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemClassMethod()
    {
        return $this->itemClassMethod;
    }

    /**
     * @param string $itemInfoMethod
     */
    public function setItemInfoMethod($itemInfoMethod)
    {
        if ($itemInfoMethod !== null && !in_array($itemInfoMethod, array('displayLanguage', 'displayName', 'displayRegion', 'displayScript', 'displayVariant', 'primaryLanguage', 'region', 'script'))) {
            throw new RuntimeException('Unknown method "%s" for "%s" option.', $itemInfoMethod, 'itemInfoMethod');
        }

        $this->itemInfoMethod = $itemInfoMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemInfoMethod()
    {
        return $this->itemInfoMethod;
    }

    /**
     * @param string $itemLabelMethod
     */
    public function setItemLabelMethod($itemLabelMethod)
    {
        if ($itemLabelMethod !== null && !in_array($itemLabelMethod, array('displayLanguage', 'displayName', 'displayRegion', 'displayScript', 'displayVariant', 'primaryLanguage', 'region', 'script'))) {
            throw new RuntimeException('Unknown method "%s" for "%s" option.', $itemLabelMethod, 'itemLabelMethod');
        }

        $this->itemLabelMethod = $itemLabelMethod;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemLabelMethod()
    {
        return $this->itemLabelMethod;
    }

    /**
     * @param boolean $labelInCurrentLocale
     */
    public function setLabelInCurrentLocale($labelInCurrentLocale)
    {
        $this->labelInCurrentLocale = (bool) $labelInCurrentLocale;
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
    public function getOmitCurrent()
    {
        return $this->omitCurrent;
    }

    /**
     * @param array $options
     * @return string
     * @throws RuntimeException
     * @todo implement add way to completely default rendering for maximum flexibility (see Zend\View\Helper\Navigation::renderPartial)
     */
    public function __invoke(array $options=array()) {
        if (!($detector = $this->getDetector())) {
            throw new RuntimeException('To assemble an url, a detector is required');
        }

        foreach ($options as $key => $value) {
            $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (!method_exists($this, $setter)) {
                throw new \RuntimeException('The option "' . $key . '" does not exist for the locale menu helper.');
            }
            $this->{$setter}($value);
        }

        $model = array();
        $model['current'] = $current = Locale::getDefault();
        $model['class'] = $this->getClass() ?: false;
        $model['supported'] = array();

        foreach($detector->getSupported() as $locale) {
            if ($this->getOmitCurrent() && $current == $locale) {
                continue;
            }

            $item = array();
            $item['locale'] = $locale;
            $item['uri'] = $this->getView()->localeUri($locale);

            $inLocale = $this->getLabelInCurrentLocale() ? $locale : $current;
            $item['label'] = $this->callLocale($this->getItemLabelMethod(), $locale, $inLocale) ?: false;

            $inLocale = !$this->getLabelInCurrentLocale() ? $locale : $current;
            $item['info'] = $this->callLocale($this->getItemInfoMethod(), $locale, $current) ?: false;
            $item['class'] = $this->callLocale($this->getItemClassMethod(), $locale, $inLocale) ?: false;

            $model['supported'][] = $item;
        }

        return $this->render($model);
    }

    protected function callLocale($method, $locale, $in_locale = null) {
        $callback = sprintf('\Locale::get%s', ucfirst($method));

        $callback_args = array($locale);

        if ($in_locale && !in_array($method, array('region', 'script'))) {
            $callback_args[] = $in_locale;
        }

        return call_user_func_array($callback, $callback_args);
    }

    protected function render(array $model) {
        $html = sprintf('<ul%s>', $model['class'] ? sprintf(' class="%s"', $model['class']) : '');
        foreach($model['supported'] as $item) {
            $html .= sprintf('<li%s>', $item['class'] ? sprintf(' class="%s"', $item['class']) : '');

            $html .= sprintf('<a href="%s"%s title="%s">%s</a>',
                $item['uri'],
                $model['current'] == $item['locale'] ? ' class="active"' : '',
                $this->getView()->escapeHtmlAttr($item['info']),
                $item['label']
            );

            $html .= '</li>' . PHP_EOL;
        }

        $html .= '</ul>' . PHP_EOL;

        return $html;
    }
}