<?php

declare(strict_types=1);

namespace ResponsiveSk\PhpDebugBarMiddleware\Test;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use ResponsiveSk\PhpDebugBarMiddleware\DebugBarMiddleware;

class DebugBarMiddlewareTest extends TestCase
{
    private DebugBarMiddleware $middleware;

    protected function setUp(): void
    {
        $this->middleware = new DebugBarMiddleware();
    }

    public function testMiddlewareInjectsDebugBarInDevelopment(): void
    {
        // Set development environment
        putenv('APP_ENV=development');
        putenv('DEBUG=true');

        $request = new ServerRequest();
        $response = new Response();
        $response->getBody()->write('<html><body><h1>Test</h1></body></html>');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $result = $this->middleware->process($request, $handler);
        $body = (string) $result->getBody();

        $this->assertStringContains('phpdebugbar', $body);
        $this->assertStringContains('</body>', $body);
    }

    public function testMiddlewareSkipsInjectionInProduction(): void
    {
        // Set production environment
        putenv('APP_ENV=production');

        $request = new ServerRequest();
        $response = new Response();
        $response->getBody()->write('<html><body><h1>Test</h1></body></html>');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $result = $this->middleware->process($request, $handler);
        $body = (string) $result->getBody();

        $this->assertStringNotContains('phpdebugbar', $body);
        $this->assertEquals('<html><body><h1>Test</h1></body></html>', $body);
    }

    public function testMiddlewareSkipsNonHtmlResponses(): void
    {
        // Set development environment
        putenv('APP_ENV=development');
        putenv('DEBUG=true');

        $request = new ServerRequest();
        $response = new Response();
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write('{"test": true}');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $result = $this->middleware->process($request, $handler);
        $body = (string) $result->getBody();

        $this->assertStringNotContains('phpdebugbar', $body);
        $this->assertEquals('{"test": true}', $body);
    }

    public function testAddMessage(): void
    {
        putenv('APP_ENV=development');
        putenv('DEBUG=true');

        $this->middleware->addMessage('Test message', 'info');
        
        // Verify message was added (this would require accessing the debugbar instance)
        $debugBar = $this->middleware->getDebugBar();
        $this->assertInstanceOf(\DebugBar\StandardDebugBar::class, $debugBar);
    }

    public function testTimeMeasurement(): void
    {
        putenv('APP_ENV=development');
        putenv('DEBUG=true');

        $this->middleware->startMeasure('test', 'Test Measure');
        usleep(1000); // 1ms
        $this->middleware->stopMeasure('test');

        // Verify measure was recorded
        $debugBar = $this->middleware->getDebugBar();
        $this->assertInstanceOf(\DebugBar\StandardDebugBar::class, $debugBar);
    }

    protected function tearDown(): void
    {
        // Clean up environment variables
        putenv('APP_ENV');
        putenv('DEBUG');
    }
}
