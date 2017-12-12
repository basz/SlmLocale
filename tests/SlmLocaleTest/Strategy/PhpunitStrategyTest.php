<?php
/**
 * Created by PhpStorm.
 * User: mfuesslin
 * Date: 12.12.17
 * Time: 16:15
 */

namespace SlmLocaleTest\Strategy;

use PHPUnit\Framework\TestCase;
use SlmLocale\LocaleEvent;
use SlmLocale\Strategy\PhpunitStrategy;
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

        $this->strategy = new PhpunitStrategy();
    }

    public function testPreventStrategiesExecutionIfPhpunit()
    {
        $_SERVER['SLMLOCALE_DISABLE_STRATEGIES'] = true;

        $event = new LocaleEvent();
        $event->setSupported(['nl', 'de', 'en']);

        $uri     = 'http://username:password@example.com:8080/some/deep/path/some.file?withsomeparam=true';
        $request = new HttpRequest();
        $request->setUri($uri);

        $event->setLocale('en');
        $event->setRequest($request);
        $event->setResponse(new HttpResponse());

        $this->strategy->found($event);

        $statusCode = $event->getResponse()->getStatusCode();
        $this->assertEquals(200, $statusCode);

        $_SERVER['SLMLOCALE_DISABLE_STRATEGIES'] = false;
    }
}
