<?php

namespace SlmLocaleTest\Strategy;

use Laminas\Console\Request as ConsoleRequest;
use Laminas\Console\Response as ConsoleResponse;
use Laminas\EventManager\EventManager;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\Http\PhpEnvironment\Response as HttpResponse;
use PHPUnit\Framework\TestCase;
use SlmLocale\Locale\Detector;
use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\AssetStrategy;

class AssetStrategyTest extends TestCase
{
    /** @var AssetStrategy */
    private $strategy;

    /** @var LocaleEvent */
    private $event;

    public function setUp(): void
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

    /**
     * @dataProvider uriProvider
     */
    public function testDetectShouldStopPropagationIfFileExtensionWasConfigured($uri, $isAsset)
    {
        $request = new HttpRequest();
        $request->setUri($uri);

        $this->event->setRequest($request);

        // should stop event propagation if extension was configured to ignore.
        $result = $this->strategy->detect($this->event);
        $this->assertNull($result);
        $this->assertEquals($isAsset, $this->event->propagationIsStopped());
    }

    /**
     * @dataProvider uriProvider
     */
    public function testFoundShouldStopPropagationIfFileExtensionWasConfigured($uri, $isAsset)
    {
        $request = new HttpRequest();
        $request->setUri($uri);

        $this->event->setRequest($request);

        // should stop event propagation if extension was configured to ignore.
        $result = $this->strategy->found($this->event);
        $this->assertNull($result);
        $this->assertEquals($isAsset, $this->event->propagationIsStopped());
    }

    public function uriProvider()
    {
        return [
            ['http://example.com/css/style.css', true],
            ['http://example.com/css/style.css?ver=123456', true],
            ['http://example.com/css/script.js', true],
            ['http://example.com/image/image.jpg', false],
            ['http://example.com/article/new-asset-strategy', false],
        ];
    }

    public function testAssetStrategyCanPreventOtherStrategiesExecution()
    {
        $request     = new HttpRequest();
        $request->setUri('http://example.com/css/style.css');
        $query       = $request->getQuery();
        $query->lang = 'de';
        $request->setQuery($query);
        $this->event->setRequest($request);

        $detector = new Detector();
        $detector->setEventManager(new EventManager());
        $detector->setSupported(['nl', 'de', 'en']);
        $detector->setDefault('en');
        $detector->addStrategy($this->strategy);
        $detector->addStrategy(new \SlmLocale\Strategy\QueryStrategy());
        $response = new HttpResponse();

        $result = $detector->detect($request, $response);
        $this->assertEquals('en', $result);
    }
}
