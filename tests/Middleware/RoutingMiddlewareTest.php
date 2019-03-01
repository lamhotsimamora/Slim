<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Middleware;

use FastRoute\Dispatcher;
use Slim\CallableResolver;
use Slim\Middleware\ClosureMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\MiddlewareRunner;
use Slim\RoutingResults;
use Slim\Router;
use Slim\Tests\TestCase;

class RoutingMiddlewareTest extends TestCase
{
    protected function getRouter()
    {
        $callableResolver = new CallableResolver();
        $responseFactory = $this->getResponseFactory();
        $router = new Router($responseFactory, $callableResolver);
        $router->map(['GET'], '/hello/{name}', null);
        return $router;
    }

    public function testRouteIsStoredOnSuccessfulMatch()
    {
        $responseFactory = $this->getResponseFactory();
        $callable = (function ($request, $handler) use ($responseFactory) {
            // route is available
            $route = $request->getAttribute('route');
            $this->assertNotNull($route);
            $this->assertEquals('foo', $route->getArgument('name'));

            // routingResults is available
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            return $responseFactory->createResponse();
        })->bindTo($this);

        $router = $this->getRouter();
        $mw = new ClosureMiddleware($callable);
        $mw2 = new RoutingMiddleware($router);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'GET');

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $middlewareRunner->run($request);
    }

    /**
     * @expectedException \Slim\Exception\HttpMethodNotAllowedException
     */
    public function testRouteIsNotStoredOnMethodNotAllowed()
    {

        $responseFactory = $this->getResponseFactory();
        $callable = (function ($request, $handler) use ($responseFactory) {
            // route is not available
            $route = $request->getAttribute('route');
            $this->assertNull($route);

            // routingResults is available
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            $this->assertEquals(Dispatcher::METHOD_NOT_ALLOWED, $routingResults->getRouteStatus());

            return $responseFactory->createResponse();
        })->bindTo($this);

        $router = $this->getRouter();
        $mw = new ClosureMiddleware($callable);
        $mw2 = new RoutingMiddleware($router);

        $request = $this->createServerRequest('https://example.com:443/hello/foo', 'POST');

        $middlewareRunner = new MiddlewareRunner();
        $middlewareRunner->add($mw);
        $middlewareRunner->add($mw2);
        $middlewareRunner->run($request);
    }
}