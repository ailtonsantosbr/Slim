<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2018 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/4.x/LICENSE.md (MIT License)
 */
namespace Slim\Tests\Middleware;

use Closure;
use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Middleware\ClosureMiddleware;
use Slim\Middleware\Psr7MiddlewareAdapter;
use Slim\MiddlewareRunner;
use Slim\RoutingResults;
use Slim\Middleware\RoutingMiddleware;
use Slim\Router;
use Slim\Tests\TestCase;

class RoutingMiddlewareTest extends TestCase
{
    protected function getRouter()
    {
        $responseFactory = $this->getResponseFactory();
        $router = new Router($responseFactory);
        $router->map(['GET'], '/hello/{name}', null);
        return $router;
    }

    public function testRouteIsStoredOnSuccessfulMatch()
    {
        $responseFactory = $this->getResponseFactory();
        $callable = function ($request, $handler) use ($responseFactory) {
            // route is available
            $route = $request->getAttribute('route');
            $this->assertNotNull($route);
            $this->assertEquals('foo', $route->getArgument('name'));

            // routingResults is available
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            return $responseFactory->createResponse();
        };
        Closure::bind($callable, $this);

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
        $callable = function ($request, $handler) use ($responseFactory) {
            // route is not available
            $route = $request->getAttribute('route');
            $this->assertNull($route);

            // routingResults is available
            $routingResults = $request->getAttribute('routingResults');
            $this->assertInstanceOf(RoutingResults::class, $routingResults);
            $this->assertEquals(Dispatcher::METHOD_NOT_ALLOWED, $routingResults->getRouteStatus());

            return $responseFactory->createResponse();
        };
        Closure::bind($callable, $this);

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
