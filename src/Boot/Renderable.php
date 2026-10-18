<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Renderable\Boot;

use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Boot\Functions;
use Tobento\App\Migration\Boot\Migration;

/**
 * Renderable boot
 */
class Renderable extends Boot
{
    public const INFO = [
        'boot' => [
            'installs and loads renderable config file',
            'installs default picture definitions',
            'implements renderable interfaces based on renderable config',
        ],
    ];

    public const BOOT = [
        Config::class,
        Migration::class,
        Functions::class,
        \Tobento\App\View\Boot\View::class,
        \Tobento\App\FileStorage\Boot\FileStorage::class,
        \Tobento\App\Language\Boot\Language::class,
        \Tobento\App\HtmlSanitizer\Boot\HtmlSanitizer::class,
        \Tobento\App\Media\Boot\Media::class,
    ];

    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param Config $config
     * @return void
     */
    public function boot(Migration $migration, Config $config): void
    {
        // install migration:
        $migration->install(\Tobento\App\Renderable\Migration\Renderable::class);
        
        // load the renderable config:
        $config = $config->load('renderable.php');
        
        // setting interfaces:
        foreach ($config['interfaces'] ?? [] as $interface => $implementation) {
            $this->app->set($interface, $implementation);
        }
    }
}