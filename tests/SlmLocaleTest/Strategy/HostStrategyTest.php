<?php

namespace SlmLocaleTest\Strategy;

use Laminas\Http\PhpEnvironment\Request;
use Laminas\Stdlib\Parameters;
use Laminas\Stdlib\RequestInterface;
use Laminas\Uri\Uri;
use PHPUnit\Framework\TestCase;
use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\Exception\InvalidArgumentException;
use SlmLocale\Strategy\HostStrategy;

class HostStrategyTest extends TestCase
{
    public function testDetectNonHttpRequestReturnsNull()
    {
        $event = new LocaleEvent();
        $event->setRequest($this->getMockForAbstractClass(RequestInterface::class));

        $strategy = new HostStrategy();
        $this->assertNull($strategy->detect($event));
    }

    public function testDetectWithoutSupportedReturnsNull()
    {
        $event = new LocaleEvent();
        $event->setRequest($this->getMockForAbstractClass(\Laminas\Http\Request::class));
        $event->setSupported([]);

        $strategy = new HostStrategy();
        $this->assertNull($strategy->detect($event));
    }

    public function testDetectWithoutDomainThrowsInvalidArgumentException()
    {
        $event = new LocaleEvent();
        $event->setRequest($this->getMockForAbstractClass(\Laminas\Http\Request::class));
        $event->setSupported(['en_GB', 'de_DE']);

        $strategy = new HostStrategy();
        $strategy->setOptions(['domain' => 'test']);

        $this->expectException(InvalidArgumentException::class);
        $this->assertNull($strategy->detect($event));
    }

    public function testDetectUnsupportedReturnsNull()
    {
        $request = new Request();
        $request->setUri('http://test.fr');
        $event = new LocaleEvent();
        $event->setRequest($request);
        $event->setSupported(['en_GB', 'de_DE']);

        $strategy = new HostStrategy();
        $strategy->setOptions([
            'domain'  => 'test.:locale',
            'aliases' => ['de' => 'de_DE', 'co.uk' => 'en_GB'],
        ]);
        $result = $strategy->detect($event);

        $this->assertNull($result);
    }

    public function testDetect()
    {
        $request = new Request();
        $request->setUri('http://test.de');
        $event = new LocaleEvent();
        $event->setRequest($request);
        $event->setSupported(['en_GB', 'de_DE']);

        $strategy = new HostStrategy();
        $strategy->setOptions([
            'domain'  => 'test.:locale',
            'aliases' => ['de' => 'de_DE', 'co.uk' => 'en_GB'],
        ]);
        $result = $strategy->detect($event);

        $this->assertSame('de_DE', $result);
    }

    public function testAssemble()
    {
        $params  = new Parameters(['SERVER_NAME' => 'test.co.uk']);
        $request = new Request();
        $request->setServer($params);

        $event = new LocaleEvent();
        $event->setLocale('de_DE');
        $event->setUri(new Uri('http://test.co.uk'));
        $event->setRequest($request);

        $strategy = new HostStrategy();
        $strategy->setOptions([
            'domain'  => 'test.:locale',
            'aliases' => ['de' => 'de_DE', 'co.uk' => 'en_GB'],
        ]);

        $result = $strategy->assemble($event)->getHost();
        $this->assertSame('test.de', $result);
    }

    public function testAssembleWithPort()
    {
        $params  = new Parameters(['SERVER_NAME' => 'test.co.uk', 'SERVER_PORT' => 8080]);
        $request = new Request();
        $request->setServer($params);

        $event = new LocaleEvent();
        $event->setLocale('de_DE');
        $event->setUri(new Uri('http://test.co.uk'));
        $event->setRequest($request);

        $strategy = new HostStrategy();
        $strategy->setOptions([
            'domain'  => 'test.:locale',
            'aliases' => ['de' => 'de_DE', 'co.uk' => 'en_GB'],
        ]);

        $result = $strategy->assemble($event)->getHost();
        $this->assertSame('test.de:8080', $result);
    }
}
