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
use SlmLocale\Locale\Detector;
use Zend\View\Helper\AbstractHelper;
use Zend\View\Model\ViewModel;

class LocaleMenu extends AbstractHelper
{
    /**
     * @var Detector $detector
     */
    protected $detector;

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
     * @param array $options
     * @return string
     * @throws RuntimeException
     * @todo add ulClass options
     * @todo add liClass options
     * @todo add translate options
     * @todo add translate options
     * @todo extend Zend\I18n\View\Helper\AbstractTranslatorHelper to have acces to translator for labels
     * @todo implement add way to completely default rendering for maximum flexibility (see Zend\View\Helper\Navigation::renderPartial)
     * $todo create concrete implementation of Zend\Stdlib\AbstractOptions options
     */
    public function __invoke(array $options=array()) {
        if (!$this->getDetector()) {
            throw new RuntimeException('To assemble an url, a detector is required');
        }

        $defaults = array('ul_class' => '', 'li_class' => '', 'skip_current' => false, 'use_display_language' => false);

        if (isset($options['ul_class']) && !is_string($options['ul_class'])) {
            $options['ul_class'] = $defaults['ul_class'];
        }

        if (isset($options['li_class']) && !is_string($options['li_class'])) {
            $options['li_class'] = $defaults['li_class'];
        }

        if (isset($options['skip_current']) && !is_bool($options['skip_current'])) {
            $options['skip_current'] = $defaults['skip_current'];
        }

        if (isset($options['use_display_language']) && !is_bool($options['use_display_language'])) {
            $options['use_display_language'] = $defaults['use_display_language'];
        }

        $options = array_merge($defaults, $options);

        $model = new ViewModel;
        $model->default = Locale::getDefault();
        $model->supported = $this->getDetector()->getSupported();

        return $this->render($model, $options);
    }

    protected function render(ViewModel $model, array $options) {
        $default = Locale::getDefault();

        $html = sprintf('<ul%s>', strlen($options['ul_class']) ? sprintf(' class="%s"', $options['ul_class']) : '');

        foreach($model->supported as $supported) {
            if ($options['skip_current'] && $model->default === $supported) {
                continue;
            }

            $uri = $this->getView()->localeUri($supported, null);

            $html = sprintf('<li%s>', strlen($options['li_class']) ? sprintf(' class="%s"', $options['li_class']) : '');

            $html .= sprintf('<a href="%s"%s title="%s">%s</a></li>',
                $uri,
                $default === $supported ? ' class="active"' : '',
                Locale::getDisplayLanguage($supported, $supported),
                Locale::getDisplayLanguage($supported, \Locale::getDefault()));

            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

}