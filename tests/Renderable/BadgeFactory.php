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

namespace Tobento\App\Renderable\Test\Renderable;

use Tobento\Service\Renderable\RenderableFactoryInterface;
use Tobento\Service\Renderable\RenderableInterface;
use Tobento\Service\Renderable\RenderablesInterface;

final class BadgeFactory implements RenderableFactoryInterface
{
    public function createRenderable(
        string $name,
        null|string $type,
        mixed $value,
        RenderablesInterface $renderables,
    ): RenderableInterface {
        return new Badge(text: is_string($value) ? $value : '');
    }
}