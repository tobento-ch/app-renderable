<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Psr\Container\ContainerInterface;
use Tobento\App\HtmlSanitizer\HtmlSanitizerInterface;
use Tobento\App\Renderable\FileUrlGenerator;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Renderable;

return [
    
    /*
    |--------------------------------------------------------------------------
    | Interfaces
    |--------------------------------------------------------------------------
    |
    | Do not change the interface's names as it may be used in other app bundles!
    |
    */
    
    'interfaces' => [
        Renderable\RenderableFactoryInterface::class => static function (
            ContainerInterface $container,
            LanguagesInterface $languages,
        ): Renderable\RenderableFactoryInterface {
            return new Renderable\RenderableFactory(
                container: $container,
                
                // Custom renderable factories by type:
                factories: [
                    // 'badge' => BadgeFactory::class,
                ],
                
                // Whether to generate images in background (queue).
                // Requires a running queue worker, otherwise images are not generated.
                // If false, images are generated on the first request.
                generateImagesInBackground: false,
                
                // Sanitizes untrusted HTML:
                htmlSanitizer: static fn (string $html): string => $container->get(HtmlSanitizerInterface::class)->sanitize($html),
                
                // Generates the file urls using the app-media file download features.
                // Make sure the storages are supported by the file download features in the media config.
                fileUrlGenerator: $container->get(FileUrlGenerator::class),
                
                // The language key used for translated values, as repositories store translations by language key:
                locale: $languages->current()->key(),
                localeFallbacks: $languages->fallbacks('key'),
            );
        },
        
        Renderable\CompositeRenderableFactoryInterface::class => static function (
            Renderable\RenderableFactoryInterface $factory,
        ): Renderable\CompositeRenderableFactoryInterface {
            // ensure the same instance is used:
            return $factory;
        },
    ],
];