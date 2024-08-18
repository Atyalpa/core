<?php

use Atyalpa\Core\Application;
use Atyalpa\Http\ResponseHandler;
use Atyalpa\Routing\Router;
use DI\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class ApplicationTest extends TestCase
{
    private Application $app;
    private MockObject $containerMock;
    private string $basePath = __DIR__ . '/fixtures';

    public function setUp(): void
    {
        $this->containerMock = $this->createMock(Container::class);

        $this->app = new Application($this->containerMock, $this->basePath);
    }

    #[Test]
    public function it_returns_the_route_path(): void
    {
        $this->assertSame($this->basePath . '/web/routes.php', $this->app->routePath());
    }

    #[Test]
    public function it_returns_the_services_path(): void
    {
        $this->assertSame($this->basePath . '/app/Services.php', $this->app->servicePath());
    }

    #[Test]
    public function it_returns_response_404_not_found_error_if_route_sends_not_found_code(): void
    {
        $routerMock = $this->createMock(Router::class);
        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')
            ->willReturn('/');

        $serverRequestMock->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $serverRequestMock->expects($this->once())
            ->method('getUri')
            ->willReturn($uriInterfaceMock);

        $this->containerMock->expects($this->once())
            ->method('make')
            ->with($this->identicalTo(Router::class))
            ->willReturn($routerMock);

        $routerMock->expects($this->once())
            ->method('group')
            ->willReturnSelf();

        $routerMock->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo('GET'), $this->equalTo('/'))
            ->willReturn([0]);

        $response = $this->app->handle($serverRequestMock);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(json_encode(['error' => 'Resource not found.']), $response->getBody()->getContents());
    }

    #[Test]
    public function it_returns_response_405_method_not_allowed_error_if_route_sends_method_not_allowed_code(): void
    {
        $routerMock = $this->createMock(Router::class);
        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')
            ->willReturn('/');

        $serverRequestMock->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $serverRequestMock->expects($this->once())
            ->method('getUri')
            ->willReturn($uriInterfaceMock);

        $this->containerMock->expects($this->once())
            ->method('make')
            ->with($this->identicalTo(Router::class))
            ->willReturn($routerMock);

        $routerMock->expects($this->once())
            ->method('group')
            ->willReturnSelf();

        $routerMock->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo('GET'), $this->equalTo('/'))
            ->willReturn([2, ['POST', 'DELETE']]);

        $response = $this->app->handle($serverRequestMock);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame(
            json_encode(['error' => 'Supported methods are POST, DELETE.']),
            $response->getBody()->getContents()
        );
    }

    #[Test]
    public function it_returns_response_for_valid_route(): void
    {
        $routerMock = $this->createMock(Router::class);
        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')
            ->willReturn('/');

        $serverRequestMock->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $serverRequestMock->expects($this->once())
            ->method('getUri')
            ->willReturn($uriInterfaceMock);

        $this->containerMock->expects($this->once())
            ->method('make')
            ->with($this->identicalTo(Router::class))
            ->willReturn($routerMock);

        $routerMock->expects($this->once())
            ->method('group')
            ->willReturnSelf();

        $routerMock->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo('GET'), $this->equalTo('/'))
            ->willReturn([
                1,
                [
                    'controller' => 'SomeController',
                    'middleware' => [],
                ],
                ['foo' => 'bar']
            ]);

        $responseHandlerMock = $this->createMock(ResponseHandler::class);
        $this->containerMock->expects($this->once())
            ->method('call')
            ->with(
                $this->identicalTo('SomeController'),
                $this->identicalTo(['foo' => 'bar'])
            )
            ->willReturn($responseHandlerMock);

        $this->app->handle($serverRequestMock);
    }

    #[Test]
    public function it_throws_exception_if_controller_response_is_not_of_type_ResponseHandler(): void
    {
        $routerMock = $this->createMock(Router::class);
        $uriInterfaceMock = $this->createMock(UriInterface::class);
        $serverRequestMock = $this->createMock(ServerRequestInterface::class);

        $uriInterfaceMock->expects($this->once())
            ->method('getPath')
            ->willReturn('/');

        $serverRequestMock->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $serverRequestMock->expects($this->once())
            ->method('getUri')
            ->willReturn($uriInterfaceMock);

        $this->containerMock->expects($this->once())
            ->method('make')
            ->with($this->identicalTo(Router::class))
            ->willReturn($routerMock);

        $routerMock->expects($this->once())
            ->method('group')
            ->willReturnSelf();

        $routerMock->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo('GET'), $this->equalTo('/'))
            ->willReturn([
                1,
                [
                    'controller' => 'SomeController',
                    'middleware' => [],
                ],
                ['foo' => 'bar']
            ]);

        $this->containerMock->expects($this->once())
            ->method('call')
            ->with(
                $this->identicalTo('SomeController'),
                $this->identicalTo(['foo' => 'bar'])
            )
            ->willReturn('some-random-string');

        $this->expectException(Exception::class);

        $this->app->handle($serverRequestMock);
    }
}
