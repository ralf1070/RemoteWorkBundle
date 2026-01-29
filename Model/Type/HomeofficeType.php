<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Model\Type;

use KimaiPlugin\RemoteWorkBundle\Constants;
use KimaiPlugin\RemoteWorkBundle\Model\RemoteWorkType;

/**
 * @internal
 */
final class HomeofficeType extends RemoteWorkType
{
    public function __construct()
    {
        parent::__construct(
            Constants::TYPE_HOMEOFFICE,
            'homeoffice',
            'homeoffice.intro',
            'homeoffice.button',
            'fas fa-home',
            '@RemoteWork/remote-work-edit.html.twig',
            Constants::COLOR_HOMEOFFICE,
        );
    }
}
