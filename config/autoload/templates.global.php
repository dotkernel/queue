<?php

declare(strict_types=1);

use Mezzio\Template\TemplateRendererInterface;
use Mezzio\Twig\TwigEnvironmentFactory;
use Mezzio\Twig\TwigRendererFactory;
use Twig\Environment;

return [
    'dependencies' => [
        'factories' => [
            Environment::class               => TwigEnvironmentFactory::class,
            TemplateRendererInterface::class => TwigRendererFactory::class,
        ],
    ],
    'debug'        => false,
    'templates'    => [
        'extension' => 'html.twig',
    ],
    'twig'         => [
        'assets_url'      => '/',
        'assets_version'  => null,
        'auto_reload'     => true,
        'autoescape'      => 'html',
        'cache_dir'       => 'data/cache/twig',
        'extensions'      => [],
        'globals'         => [
            'appName' => $app['name'] ?? '',
        ],
        'optimizations'   => -1,
        'runtime_loaders' => [],
//        'timezone'        => '',
    ],
];
