<?php

namespace SlmLocaleTest\Strategy;

use PHPUnit_Framework_TestCase as TestCase;
use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\HostStrategy;
use Zend\Http\PhpEnvironment\Request;
use Zend\Stdlib\Parameters;
use Zend\Uri\Uri;

class HostStrategyTest extends TestCase
{
    public function testDetectNonHttpRequestReturnsNull()
    {
        $event = new LocaleEvent();
        $event->setRequest($this->getMockForAbstractClass('Zend\Stdlib\RequestInterface'));

        $strategy = new HostStrategy();
        $this->assertNull($strategy->detect($event));
    }

    public function testDetectWithoutSupportedReturnsNull()
    {
        $event = new LocaleEvent();
        $event->setRequest($this->getMockForAbstractClass('Zend\Http\Request'));
        $event->setSupported(array());

        $strategy = new HostStrategy();
        $this->assertNull($strategy->detect($event));
    }

    /**
     * @expectedException \SlmLocale\Strategy\Exception\InvalidArgumentException
     */
    public function testDetectWithoutDomainThrowsInvalidArgumentException()
    {
        $event = new LocaleEvent();
        $event->setRequest($this->getMockForAbstractClass('Zend\Http\Request'));
        $event->setSupported(array('en_GB', 'de_DE'));

        $strategy = new HostStrategy();
        $strategy->setOptions(array('domain' => 'test'));
        $this->assertNull($strategy->detect($event));
    }

    public function testDetectUnsupportedReturnsNull()
    {
        $request = new Request();
        $request->setUri('http://test.fr');
        $event = new LocaleEvent();
        $event->setRequest($request);
        $event->setSupported(array('en_GB', 'de_DE'));

        $strategy = new HostStrategy();
        $strategy->setOptions(array(
            'domain' => 'test.:locale',
            'aliases' => array('de' => 'de_DE', 'co.uk' => 'en_GB')
        ));
        $result = $strategy->detect($event);

        $this->assertNull($result);
    }

    public function testDetect()
    {
        $request = new Request();
        $request->setUri('http://test.de');
        $event = new LocaleEvent();
        $event->setRequest($request);
        $event->setSupported(array('en_GB', 'de_DE'));

        $strategy = new HostStrategy();
        $strategy->setOptions(array(
            'domain' => 'test.:locale',
            'aliases' => array('de' => 'de_DE', 'co.uk' => 'en_GB')
        ));
        $result = $strategy->detect($event);

        $this->assertSame('de_DE', $result);
    }

    public function testAssemble()
    {
        $params = new Parameters(array('SERVER_NAME' => 'test.co.uk'));
        $request = new Request();
        $request->setServer($params);

        $event = new LocaleEvent();
        $event->setLocale('de_DE');
        $event->setUri(new Uri('http://test.co.uk'));
        $event->setRequest($request);

        $strategy = new HostStrategy();
        $strategy->setOptions(array(
            'domain' => 'test.:locale',
            'aliases' => array('de' => 'de_DE', 'co.uk' => 'en_GB')
        ));

        $result = $strategy->assemble($event)->getHost();
        $this->assertSame('test.de', $result);
    }

    public function testAssembleWithPort()
    {
        $params = new Parameters(array('SERVER_NAME' => 'test.co.uk', 'SERVER_PORT' => 8080));
        $request = new Request();
        $request->setServer($params);

        $event = new LocaleEvent();
        $event->setLocale('de_DE');
        $event->setUri(new Uri('http://test.co.uk'));
        $event->setRequest($request);

        $strategy = new HostStrategy();
        $strategy->setOptions(array(
            'domain' => 'test.:locale',
            'aliases' => array('de' => 'de_DE', 'co.uk' => 'en_GB')
        ));

        $result = $strategy->assemble($event)->getHost();
        $this->assertSame('test.de:8080', $result);
    }
}
