<?php

declare(strict_types=1);

namespace Light\App\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CommunityHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Community activities data
        $activities = [
            [
                'title' => 'Open Source',
                'description' => 'Our open source libraries are an invaluable resource for thousands of developers, and we are active contributors to some of the most popular open source packages, libraries, and frameworks.',
                'image' => '/themes/main/assets/php82.jpg',
                'links' => [
                    ['name' => 'GitHub', 'url' => 'https://github.com/responsive-sk'],
                    ['name' => 'Packagist', 'url' => 'https://packagist.org/users/responsive-sk/'],
                ]
            ],
            [
                'title' => 'Speaking',
                'description' => 'You may have seen us at conferences and meetups, sharing our experiences and knowledge with anyone who\'ll listen. We speak regularly at community and professional events.',
                'image' => '/themes/main/assets/javascript.jpg',
                'links' => [
                    ['name' => 'Conference Talks', 'url' => '#'],
                    ['name' => 'Meetups', 'url' => '#'],
                ]
            ],
            [
                'title' => 'Hang out with us',
                'description' => 'Why not jump on our Discord and hang out? There\'s always plenty of lively discussion about software development and open source, plus other topics.',
                'image' => '/themes/main/assets/digital-marketing.jpg',
                'links' => [
                    ['name' => 'Discord', 'url' => '#'],
                    ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/company/responsive-sk'],
                ]
            ]
        ];

        // Vite compiled assets
        $cssUrl = '/themes/main/assets/main.css';
        $jsUrl = '/themes/main/assets/main.js';

        $html = $this->template->render('app::community', [
            'activities' => $activities,
            'cssUrl' => $cssUrl,
            'jsUrl' => $jsUrl,
        ]);

        return new HtmlResponse($html);
    }
}
