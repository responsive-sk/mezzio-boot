<?php

declare(strict_types=1);

namespace Light\App\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WhatWeOfferHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Services data
        $services = [
            [
                'title' => 'Team Augment',
                'description' => 'Bringing in extra hands for particular projects – whether it be because you need some expert advice on something really complex, or you\'ve got a lengthy build that you need some extra people for.',
                'image' => '/themes/main/assets/php82.jpg',
                'features' => [
                    'Expert developers on demand',
                    'Short-term or long-term projects',
                    'Complex technical challenges',
                    'Scalable team solutions'
                ]
            ],
            [
                'title' => 'Developer Mentoring',
                'description' => 'Our team members have been in the field for years. We\'re here to share our expertise with your developers to help them improve their knowledge, skills and best practices.',
                'image' => '/themes/main/assets/javascript.jpg',
                'features' => [
                    'Knowledge transfer',
                    'Best practices training',
                    'Code review sessions',
                    'Professional development'
                ]
            ],
            [
                'title' => 'Modernization of Software',
                'description' => 'If your software is a bit out of date, we can help to make sure it\'s up to scratch and supported by a network of developers in the community.',
                'image' => '/themes/main/assets/digital-marketing.jpg',
                'features' => [
                    'Legacy system updates',
                    'Security improvements',
                    'Performance optimization',
                    'Modern architecture'
                ]
            ]
        ];

        // Vite compiled assets
        $cssUrl = '/themes/main/assets/main.css';
        $jsUrl = '/themes/main/assets/main.js';

        $html = $this->template->render('app::what-we-offer', [
            'services' => $services,
            'cssUrl' => $cssUrl,
            'jsUrl' => $jsUrl,
        ]);

        return new HtmlResponse($html);
    }
}
