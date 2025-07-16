<?php

declare(strict_types=1);

namespace Light\Core\Factory;

use DebugBar\StandardDebugBar;
use Light\Core\Middleware\DebugBarMiddleware;
use Psr\Container\ContainerInterface;

/**
 * Factory for DebugBar Middleware
 */
class DebugBarMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): DebugBarMiddleware
    {
        $debugBar = new StandardDebugBar();
        
        // Add custom collectors
        $debugBar->addCollector(new \DebugBar\DataCollector\ConfigCollector($this->getConfig($container)));
        
        $renderer = $debugBar->getJavascriptRenderer();
        $renderer->setBaseUrl('/debugbar');
        
        return new DebugBarMiddleware($debugBar, $renderer);
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfig(ContainerInterface $container): array
    {
        try {
            $config = $container->get('config');
            return is_array($config) ? $config : [];
        } catch (\Exception) {
            return [];
        }
    }
}
