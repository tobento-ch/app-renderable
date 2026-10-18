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

namespace Tobento\App\Renderable\Migration;

use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Dir\DirsInterface;

/**
 * Renderable migration.
 */
class Renderable implements MigrationInterface
{
    /**
     * @var array The config files.
     */
    protected array $configFiles;
    
    /**
     * @var array The view files.
     */
    protected array $viewFiles;
    
    /**
     * Create a new Renderable instance.
     *
     * @param DirsInterface $dirs
     */
    public function __construct(
        protected DirsInterface $dirs,
    ) {
        $vendor = realpath(__DIR__.'/../../');
        
        $this->configFiles = [
            $this->dirs->get('config') => [
                $vendor.'/resources/config/renderable.php',
            ],
        ];
        
        // The default picture definitions used by the Picture, File and Files renderables:
        $this->viewFiles = [
            $this->dirs->get('views').'picture-definitions/' => [
                $vendor.'/resources/views/picture-definitions/renderable-image.json',
                $vendor.'/resources/views/picture-definitions/renderable-file.json',
                $vendor.'/resources/views/picture-definitions/renderable-files.json',
            ],
        ];
    }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Renderable config and picture definitions.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        return new Actions(
            new FilesCopy(
                files: $this->configFiles,
                type: 'config',
                description: 'Renderable config file.',
            ),
            new FilesCopy(
                files: $this->viewFiles,
                type: 'views',
                description: 'Renderable picture definitions.',
            ),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */
    public function uninstall(): ActionsInterface
    {
        return new Actions(
            new FilesDelete(
                files: $this->configFiles,
                type: 'config',
                description: 'Renderable config file.',
            ),
            new FilesDelete(
                files: $this->viewFiles,
                type: 'views',
                description: 'Renderable picture definitions.',
            ),
        );
    }
}