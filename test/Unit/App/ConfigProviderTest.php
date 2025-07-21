<?php

declare(strict_types=1);

namespace LightTest\Unit\App;

use Light\App\ConfigProvider;
use Light\App\Handler\GetIndexViewHandler;
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

    public function testDependenciesHasFactories(): void
    {
        $this->assertIsArray($this->config['dependencies']);
        $this->assertArrayHasKey('factories', $this->config['dependencies']);
        $this->assertIsArray($this->config['dependencies']['factories']);
        $this->assertArrayHasKey(GetIndexViewHandler::class, $this->config['dependencies']['factories']);
    }

    public function testConfigHasTemplates(): void
    {
        $this->assertArrayHasKey('templates', $this->config);
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
