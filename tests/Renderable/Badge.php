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

use Tobento\Service\Renderable\RenderableInterface;
use Tobento\Service\Support\Str;

final class Badge implements RenderableInterface
{
    public function __construct(
        private string $text,
    ) {}

    public function render(): string
    {
        return '<span class="badge">'.Str::esc($this->text).'</span>';
    }

    public function value(): mixed
    {
        return $this->text;
    }

    public function toHtml(): string
    {
        return $this->render();
    }

    public function __toString(): string
    {
        return $this->render();
    }
}