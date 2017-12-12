<?php
/**
 * Created by PhpStorm.
 * User: mfuesslin
 * Date: 12.12.17
 * Time: 16:15
 */

namespace SlmLocaleTest\Strategy;

use PHPUnit\Framework\TestCase;
use SlmLocale\Locale\Detector;
use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\PhpunitStrategy;
use Zend\EventManager\EventManager;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use Zend\Router\Http\TreeRouteStack as HttpRouter;

class PhpunitStrategyTest extends TestCase
{
    /** @var PhpunitStrategy */
    private $strategy;
    /** @var LocaleEvent */
    private $event;
    /** @var HttpRouter */
    private $router;

    public function setup()
    {
        $this->router = new HttpRouter();

        $this->event = new LocaleEvent();
        $this->event->setSupported(['nl', 'de', 'en']);

        $this->strategy = new PhpunitStrategy();
    }

    public function testPreventStrategiesExecutionIfPhpunit()
    {
        $_SERVER['SLMLOCALE_DISABLE_STRATEGIES'] = true;

        $uri     = 'http://username:password@example.com:8080/some/deep/path/some.file?withsomeparam=true';
        $request = new HttpRequest();
        $request->setUri($uri);

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $this->strategy->found($this->event);

        $statusCode = $this->event->getResponse()->getStatusCode();
        $this->assertEquals(200, $statusCode);

        $_SERVER['SLMLOCALE_DISABLE_STRATEGIES'] = false;
    }

    public function testPhpunitStrategyCanPreventOtherStrategiesExecution()
    {
        $_SERVER['SLMLOCALE_DISABLE_STRATEGIES'] = true;

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

        $_SERVER['SLMLOCALE_DISABLE_STRATEGIES'] = false;
    }

    public function testPhpunitStrategyDoesNotPreventOtherStrategiesExecution()
    {
        // can also be null / not set
        $_SERVER['SLMLOCALE_DISABLE_STRATEGIES'] = false;

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
        $this->assertEquals('de', $result);

        $_SERVER['SLMLOCALE_DISABLE_STRATEGIES'] = false;
    }
}
