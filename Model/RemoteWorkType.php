<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Model;

use KimaiPlugin\RemoteWorkBundle\Constants;

/**
 * @internal
 */
class RemoteWorkType
{
    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $intro,
        private readonly string $button,
        private readonly string $icon,
        private readonly string $template,
        private readonly string $color = Constants::COLOR_HOMEOFFICE,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getIntro(): string
    {
        return $this->intro;
    }

    public function getButton(): string
    {
        return $this->button;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
