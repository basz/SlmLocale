<?php
/**
 * Copyright (c) 2012 Jurian Sluiman.
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
 * @package     SlmLocaleTest
 * @author      Jurian Sluiman <jurian@juriansluiman.nl>
 * @copyright   2012 Jurian Sluiman.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */
namespace SlmLocaleTest\Locale\View\Helper;

use PHPUnit_Framework_TestCase as TestCase;

use SlmLocale\View\Helper\LocaleUri;
use Zend\View\Renderer\PhpRenderer;

class LocaleUriTest extends TestCase
{
    public function testProxiesToUrlHelperAndDetector()
    {
        $url = $this->getMock('Zend\View\Helper\Url', array('__invoke'));
        $url->expects($this->once())
            ->method('__invoke')
            ->with('foo/bar')
            ->will($this->returnValue('baz/bat'));

        $renderer = new PhpRenderer;
        $renderer->getHelperPluginManager()->setService('url', $url);

        $detector = $this->getMock('SlmLocale\Locale\Detector', array('assemble'));
        $detector->expects($this->once())
            ->method('assemble')
            ->with('en-GB', 'baz/bat')
            ->will($this->returnValue('/en/baz/bat'));

        $helper = new LocaleUri;
        $helper->setView($renderer);
        $helper->setDetector($detector);

        // foo/bar converts into baz/bat; baz/bat converts into /en/baz/bat
        $this->assertEquals('/en/baz/bat', $helper('en-GB', 'foo/bar'));
    }
}
