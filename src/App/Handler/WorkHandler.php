<?php

declare(strict_types=1);

namespace Light\App\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WorkHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Portfolio projects data
        $projects = [
            [
                'title' => 'E-commerce Platform',
                'description' => 'A modern e-commerce platform built with PHP 8.2, featuring advanced inventory management, payment processing, and real-time analytics.',
                'image' => '/themes/main/assets/php82.jpg',
                'technologies' => ['PHP 8.2', 'Mezzio', 'MySQL', 'Redis', 'Docker'],
                'category' => 'Web Application'
            ],
            [
                'title' => 'JavaScript Framework',
                'description' => 'Custom JavaScript framework for building reactive user interfaces with minimal overhead and maximum performance.',
                'image' => '/themes/main/assets/javascript.jpg',
                'technologies' => ['JavaScript', 'TypeScript', 'Webpack', 'Jest', 'Cypress'],
                'category' => 'Open Source'
            ],
            [
                'title' => 'Digital Marketing Suite',
                'description' => 'Comprehensive digital marketing platform with campaign management, analytics, and automated reporting capabilities.',
                'image' => '/themes/main/assets/digital-marketing.jpg',
                'technologies' => ['PHP', 'Vue.js', 'PostgreSQL', 'Elasticsearch', 'AWS'],
                'category' => 'SaaS Platform'
            ],
            [
                'title' => 'Mezzio Boot Framework',
                'description' => 'Enhanced Mezzio framework with modern build system, theme support, and developer-friendly tooling.',
                'image' => '/themes/main/assets/logo.svg',
                'technologies' => ['PHP 8.2', 'Mezzio', 'Vite', 'TailwindCSS', 'Alpine.js'],
                'category' => 'Framework'
            ]
        ];

        // Vite compiled assets
        $cssUrl = '/themes/main/assets/main.css';
        $jsUrl = '/themes/main/assets/main.js';

        $html = $this->template->render('app::work', [
            'projects' => $projects,
            'cssUrl' => $cssUrl,
            'jsUrl' => $jsUrl,
        ]);

        return new HtmlResponse($html);
    }
}
