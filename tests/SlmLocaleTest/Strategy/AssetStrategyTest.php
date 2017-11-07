<?php
/**
 * Created by PhpStorm.
 * User: mfuesslin
 * Date: 07.11.17
 * Time: 17:14
 */

namespace SlmLocaleTest\Strategy;

use PHPUnit_Framework_TestCase as TestCase;
use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\AssetStrategy;
use Zend\Console\Request as ConsoleRequest;
use Zend\Console\Response as ConsoleResponse;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Http\PhpEnvironment\Response as HttpResponse;

class AssetStrategyTest extends TestCase
{
    /** @var AssetStrategy */
    private $strategy;

    /** @var LocaleEvent */
    private $event;

    public function setup()
    {
        $this->strategy = new AssetStrategy();
        $this->strategy->setOptions(['file_extensions' => ['css', 'js']]);

        $this->event = new LocaleEvent();
        $this->event->setSupported(['nl', 'de', 'en']);
    }

    public function testDetectReturnsNullByDefault()
    {
        $this->event->setRequest(new HttpRequest());
        $this->event->setResponse(new HttpResponse());

        $locale = $this->strategy->detect($this->event);
        $this->assertNull($locale);
    }

    public function testDetectWithConsoleRequestReturnsNull()
    {
        $this->event->setRequest(new ConsoleRequest());
        $this->event->setResponse(new ConsoleResponse());

        $locale = $this->strategy->detect($this->event);
        $this->assertNull($locale);
    }

    public function testFoundShouldReturnFalseIfFileExtensionWasConfigured()
    {
        $request = new HttpRequest();
        $request->setUri('http://example.com/css/style.css');

        $this->event->setRequest($request);

        // should be false if extension was configured to ignore.
        $result = $this->strategy->found($this->event);
        $this->assertFalse($result);

        $request = new HttpRequest();
        $request->setUri('http://example.com/css/style.css?ver=123456');

        $this->event->setRequest($request);

        // should be false if extension was configured to ignore.
        $result = $this->strategy->found($this->event);
        $this->assertFalse($result);

        $request = new HttpRequest();
        $request->setUri('http://example.com/css/script.js');

        $this->event->setRequest($request);

        // should be false if extension was configured to ignore.
        $result = $this->strategy->found($this->event);
        $this->assertFalse($result);

        $request = new HttpRequest();
        $request->setUri('http://example.com/image/image.jpg');

        $this->event->setRequest($request);

        // should be null if extension was NOT configured to ignore (the next strategy will take care of locale extraction).
        $result = $this->strategy->found($this->event);
        $this->assertNull($result);
    }
}
