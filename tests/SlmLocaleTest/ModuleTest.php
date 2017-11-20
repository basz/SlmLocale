<?php

namespace SlmLocaleTest\Locale;

use PHPUnit_Framework_TestCase as TestCase;
use SlmLocale\Locale\Detector;
use SlmLocale\Module;
use Zend\EventManager\EventManager;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\Request;
use Zend\Stdlib\Response;

class ModuleTest extends TestCase
{
    public function testCanGetConfig()
    {
        $module = new Module();
        $this->assertTrue(is_array($module->getConfig()));
    }

    public function testCanDetectAndSetLocale()
    {
        $detector = $this->prophesize(Detector::class);
        $detector->detect($request = new Request(), $response = new Response())->willReturn($locale = 'lt');

        $serviceManager = new ServiceManager();
        $serviceManager->setService(Detector::class, $detector->reveal());

        $app = new Application($serviceManager, new EventManager(), $request, $response);

        $event = new MvcEvent();
        $event->setApplication($app);

        $module = new Module();
        $module->onBootstrap($event);

        $this->assertEquals($locale, \Locale::getDefault());
    }

    /**
     * this needed for example when redirect required to stop event and return early response to redirect
     */
    public function testWillOvertakeRouteEventIfResponseChanged()
    {
        $detector = $this->prophesize(Detector::class);
        $detector->detect($request = new Request(), $response = new Response())->willReturn($redirectResponse = new Response());

        $serviceManager = new ServiceManager();
        $serviceManager->setService(Detector::class, $detector->reveal());

        $app = new Application($serviceManager, $eventManager = new EventManager(), $request, $response);

        $event = new MvcEvent();
        $event->setName(MvcEvent::EVENT_ROUTE);
        $event->setApplication($app);

        $module = new Module();
        $module->onBootstrap($event);

        $eventManager->attach(MvcEvent::EVENT_ROUTE, function ($e) {
            return 'test';
        });

        $response = $eventManager->triggerEvent($event);
        $this->assertSame($redirectResponse, $response->first());
        $response->next();
        $this->assertSame('test', $response->last());
    }
}
