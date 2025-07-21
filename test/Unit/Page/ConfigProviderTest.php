<?php

declare(strict_types=1);

namespace LightTest\Unit\Page;

use Light\Page\ConfigProvider;
use Light\Page\RoutesDelegator;
use Light\Page\Service\PageService;
use Light\Page\Service\PageServiceInterface;
use Mezzio\Application;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /** @var array<string, mixed> */
    protected array $config = [];

    protected function setup(): void
    {
        parent::setUp();

        /** @var array<string, mixed> $config */
        $config       = (new ConfigProvider())();
        $this->config = $config;
    }

    public function testConfigHasDependencies(): void
    {
        $this->assertArrayHasKey('dependencies', $this->config);
    }

    public function testConfigHasTemplates(): void
    {
        $this->assertArrayHasKey('templates', $this->config);
    }

    public function testDependenciesHasDelegators(): void
    {
        $this->assertIsArray($this->config['dependencies']);
        $this->assertArrayHasKey('delegators', $this->config['dependencies']);
        $dependencies = $this->config['dependencies'];
        $this->assertIsArray($dependencies);
        $this->assertIsArray($dependencies['delegators']);
        $this->assertArrayHasKey(Application::class, $dependencies['delegators']);
        $this->assertIsArray($dependencies['delegators'][Application::class]);
        $this->assertContainsEquals(
            RoutesDelegator::class,
            $dependencies['delegators'][Application::class]
        );
    }

    public function testDependenciesHasFactories(): void
    {
        $this->assertIsArray($this->config['dependencies']);
        $this->assertArrayHasKey('factories', $this->config['dependencies']);
        $this->assertIsArray($this->config['dependencies']['factories']);
        $this->assertArrayHasKey(PageService::class, $this->config['dependencies']['factories']);
    }

    public function testDependenciesHasAliases(): void
    {
        $this->assertIsArray($this->config['dependencies']);
        $this->assertArrayHasKey('aliases', $this->config['dependencies']);
        $this->assertIsArray($this->config['dependencies']['aliases']);
        $this->assertArrayHasKey(PageServiceInterface::class, $this->config['dependencies']['aliases']);
    }

    public function testGetTemplates(): void
    {
        $this->assertIsArray($this->config['templates']);
        $this->assertArrayHasKey('paths', $this->config['templates']);
        $this->assertIsArray($this->config['templates']['paths']);
        // Template paths are now managed by TemplatePathProvider
        // No hardcoded paths in ConfigProvider anymore
    }
}
