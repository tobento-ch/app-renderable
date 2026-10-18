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

namespace Tobento\App\Renderable\Test\Feature;

use Tobento\App\AppInterface;
use Tobento\App\FileStorage\FilesystemStorageFactory;
use Tobento\App\Media\Feature\FileDownload;
use Tobento\App\Media\Feature\FileDownloadSigned;
use Tobento\App\Renderable\Boot\Renderable;
use Tobento\App\Renderable\Test\Renderable\Badge;
use Tobento\App\Renderable\Test\Renderable\BadgeFactory;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Renderable\CompositeRenderableFactoryInterface;
use Tobento\Service\Renderable\RenderableFactoryInterface;
use Tobento\Service\Renderable\Renderables;
use function Tobento\App\{directory};

class RenderableTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(Renderable::class);
        
        $app->on(
            StoragesInterface::class,
            function(StoragesInterface $storages, FilesystemStorageFactory $factory) {
                $storage = $factory->createStorage(name: 'downloads', config: [
                    'location' => directory('app').'storage/downloads/',
                    'storage_type' => 'public',
                ]);
                
                $storage->write(path: 'manual.pdf', content: 'content');
                $storages->add($storage);
                
                $storage = $factory->createStorage(name: 'private', config: [
                    'location' => directory('app').'storage/private/',
                    'storage_type' => 'private',
                ]);
                
                $storage->write(path: 'manual.pdf', content: 'content');
                $storages->add($storage);
            }
        );
        
        return $app;
    }
    
    public function testInterfacesAreAvailable()
    {
        $app = $this->bootingApp();
        
        $factory = $app->get(RenderableFactoryInterface::class);
        
        $this->assertInstanceof(CompositeRenderableFactoryInterface::class, $factory);
        $this->assertSame($factory, $app->get(CompositeRenderableFactoryInterface::class));
    }
    
    public function testConfigAndPictureDefinitionsAreInstalled()
    {
        $app = $this->bootingApp();
        
        $this->assertFileExists($app->dir('config').'renderable.php');
        $this->assertFileExists($app->dir('views').'picture-definitions/renderable-image.json');
        $this->assertFileExists($app->dir('views').'picture-definitions/renderable-file.json');
        $this->assertFileExists($app->dir('views').'picture-definitions/renderable-files.json');
    }
    
    public function testRenderablesUseCurrentLanguageKey()
    {
        $app = $this->bootingApp();
        $key = $app->get(LanguagesInterface::class)->current()->key();
        
        $renderables = new Renderables(
            mapping: ['website' => 'link'],
            attributes: [
                'website' => [
                    'url' => 'https://example.com',
                    'label' => [$key => 'Current', 'other-key' => 'Other'],
                ],
            ],
        );
        
        $this->assertSame('<a href="https://example.com">Current</a>', $renderables->get('website')->render());
    }
    
    public function testHtmlIsSanitized()
    {
        $this->bootingApp();
        
        $renderables = new Renderables(
            mapping: ['body' => 'html'],
            attributes: ['body' => '<p>Lorem<script>alert(1)</script></p>'],
        );
        
        $this->assertSame('<p>Lorem</p>', $renderables->get('body')->render());
    }
    
    public function testFileUrlForPublicStorage()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownload(supportedStorages: ['downloads']),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        
        $this->bootingApp();
        
        $renderables = new Renderables(
            mapping: ['manual' => 'file'],
            attributes: ['manual' => ['src' => 'manual.pdf', 'storage' => 'downloads']],
        );
        
        $this->assertStringContainsString('media/download/downloads/manual.pdf', $renderables->file('manual')->url());
    }
    
    public function testFileUrlForPrivateStorageIsSigned()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownloadSigned(supportedStorages: ['private']),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        
        $this->bootingApp();
        
        $renderables = new Renderables(
            mapping: ['manual' => 'file', 'unsigned' => 'file'],
            attributes: [
                'manual' => ['src' => 'manual.pdf', 'storage' => 'private', 'signed_url_expires' => 60],
                'unsigned' => ['src' => 'manual.pdf', 'storage' => 'private'],
            ],
        );
        
        $this->assertStringContainsString('media/s/download/private/manual.pdf', $renderables->file('manual')->url());
        
        // without expiration, no url:
        $this->assertSame('', $renderables->file('unsigned')->url());
    }
    
    public function testNoFileUrlIfFeatureIsNotEnabled()
    {
        $this->fakeConfig()->with('media.features', []);
        
        $this->bootingApp();
        
        $renderables = new Renderables(
            mapping: ['manual' => 'file'],
            attributes: ['manual' => ['src' => 'manual.pdf', 'storage' => 'downloads']],
        );
        
        $this->assertSame('', $renderables->file('manual')->url());
        $this->assertSame('<span class="renderable-file">manual.pdf</span>', $renderables->file('manual')->render());
    }
    
    public function testNoFileUrlIfStorageDoesNotExist()
    {
        $this->fakeConfig()->with('media.features', [
            new FileDownload(supportedStorages: ['downloads']),
        ]);
        
        $this->bootingApp();
        
        $renderables = new Renderables(
            mapping: ['manual' => 'file'],
            attributes: ['manual' => ['src' => 'manual.pdf', 'storage' => 'not-exists']],
        );
        
        $this->assertSame('', $renderables->file('manual')->url());
    }
    
    public function testAddingFactoryUsingAppOn()
    {
        $app = $this->getApp();
        
        $app->on(
            RenderableFactoryInterface::class,
            static function (RenderableFactoryInterface $factory): void {
                if ($factory instanceof CompositeRenderableFactoryInterface) {
                    $factory->addFactory(type: 'badge', factory: BadgeFactory::class);
                }
            }
        );
        
        $this->bootingApp();
        
        $renderables = new Renderables(mapping: ['status' => 'badge'], attributes: ['status' => 'New']);
        
        $this->assertInstanceof(Badge::class, $renderables->get('status'));
        $this->assertSame('<span class="badge">New</span>', $renderables->get('status')->render());
    }
}