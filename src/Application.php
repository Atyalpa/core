<?php

declare(strict_types=1);

namespace Atyalpa\Core;

use Dotenv\Dotenv;

use Atyalpa\Http\RequestHandler;
use Atyalpa\Http\ResponseHandler;
use Atyalpa\Routing\Handlers\MiddlewareHandler;
use Atyalpa\Routing\Router;

use Atyalpa\Core\Services\Service;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Fig\Http\Message\StatusCodeInterface;

use FastRoute\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;

class Application implements RequestHandlerInterface
{
    public const VERSION = "0.1";

    protected string $basePath;

    protected static $instance;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(protected Container $container, ?string $basePath = null)
    {
        $this->container->set(Container::class, $this->container);
        $this->basePath = rtrim($basePath, '\/');
        $this->loadEnvironment();
        $this->loadServices();
    }

    public function routePath(): string
    {
        return $this->basePath . '/web/routes.php';
    }

    public function servicePath(): string
    {
        return $this->basePath . '/app/Services.php';
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->container->set(RequestHandler::class, new RequestHandler($request));

        /** @var Router $router */
        $router = $this->container->make(Router::class);

        $route = $router->group(fn (Router $router) => require $this->routePath())
            ->dispatch(
                $request->getMethod(),
                $request->getUri()->getPath()
            );

        switch ($route[0]) {
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $route[1];
                return (new ResponseHandler(
                    StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED,
                    [],
                    json_encode(['error' => 'Supported methods are ' . implode(', ', $allowedMethods) . '.'])
                ))->send();
            case Dispatcher::FOUND:
                $controller = $route[1]['controller'];
                $parameters = $route[2];

                $middlewares = $route[1]['middleware'];
                $middlewares[] = function () use ($controller, $parameters) {
                    $response = $this->container->call($controller, $parameters);

                    if ($response instanceof ResponseHandler) {
                        return $response->send();
                    }

                    throw new \Exception('The response must of type ' . ResponseHandler::class);
                };

                $middlewares = (new MiddlewareHandler($middlewares))->handle();
                $relay = new Relay($middlewares);

                return $relay->handle($request);
            case Dispatcher::NOT_FOUND:
            default:
                return (new ResponseHandler(
                    StatusCodeInterface::STATUS_NOT_FOUND,
                    [],
                    json_encode(['error' => 'Resource not found.'])
                ))->send();
        }
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function loadServices(): void
    {
        if (! file_exists($this->servicePath())) {
            return;
        }

        $services = require $this->servicePath();

        array_walk($services, function (string $service): void {
            if (is_subclass_of($service, Service::class) && method_exists($service, 'load')) {
                $this->container->make($service)->load();
            }
        });
    }

    protected function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable($this->basePath);
        $dotenv->load();
    }
}
