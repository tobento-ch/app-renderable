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

namespace Tobento\App\Renderable;

use DateTimeImmutable;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Renderable\Collection\Item;
use Tobento\Service\Routing\RouterInterface;

/**
 * Generates file urls using the app-media file download features.
 *
 * Public storages get a download url, private storages a signed
 * download url, only if the file has a "signed_url_expires" attribute
 * (in minutes). Make sure the storages are supported by the file
 * download features in the media config.
 */
class FileUrlGenerator
{
    /**
     * Create a new FileUrlGenerator.
     *
     * @param StoragesInterface $storages
     * @param RouterInterface $router
     * @param string $routeName The route name for public storages.
     * @param string $signedRouteName The route name for private storages.
     */
    public function __construct(
        protected StoragesInterface $storages,
        protected RouterInterface $router,
        protected string $routeName = 'media.file.download',
        protected string $signedRouteName = 'media.file.download.signed',
    ) {}

    /**
     * Returns the file url, or an empty string if not available.
     *
     * @param string $storage The storage name.
     * @param string $path The file path.
     * @param Item $file The file item.
     * @return string
     */
    public function __invoke(string $storage, string $path, Item $file): string
    {
        if (! $this->storages->has(name: $storage)) {
            return '';
        }

        $parameters = ['storage' => $storage, 'path' => $path];

        if (! $this->storages->get(name: $storage)->isPrivate()) {
            return (string)$this->router->url(
                name: $this->routeName,
                parameters: $parameters,
                throw: false,
            );
        }

        // Private storages need a signed url with an expiration (in minutes):
        $minutes = $file->raw(name: 'signed_url_expires');

        if (! is_int($minutes) || $minutes <= 0) {
            return '';
        }

        return (string)$this->router->url(
            name: $this->signedRouteName,
            parameters: $parameters,
            throw: false,
        )->sign(expiration: new DateTimeImmutable('now +'.$minutes.' minutes'));
    }
}