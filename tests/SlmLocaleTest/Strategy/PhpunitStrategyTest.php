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
use SlmLocale\Strategy\UriPathStrategy;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use Zend\Router\Http\TreeRouteStack as HttpRouter;

class PhpunitStrategyTest extends TestCase
{
    /** @var UriPathStrategy */
    private $strategy;
    /** @var LocaleEvent */
    private $event;
    /** @var HttpRouter */
    private $router;

    public function setup()
    {
        $this->router = new HttpRouter();

        $this->strategy = new PhpunitStrategy();

        $this->event = new LocaleEvent();
        $this->event->setSupported(['nl', 'de', 'en']);
    }

    public function testDisableUriPathStrategyPhpunit()
    {
        $_SERVER['DISABLE_URIPATHSTRATEGY'] = true;

        $uri     = 'http://username:password@example.com:8080/some/deep/path/some.file?withsomeparam=true';
        $request = new HttpRequest();
        $request->setUri($uri);

        $this->event->setLocale('en');
        $this->event->setRequest($request);
        $this->event->setResponse(new HttpResponse());

        $this->strategy->found($this->event);

        $statusCode = $this->event->getResponse()->getStatusCode();
        $this->assertEquals(200, $statusCode);

        $_SERVER['DISABLE_URIPATHSTRATEGY'] = false;
    }
}
