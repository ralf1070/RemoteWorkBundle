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
final class BusinessTripType extends RemoteWorkType
{
    public function __construct()
    {
        parent::__construct(
            Constants::TYPE_BUSINESS_TRIP,
            'business_trip',
            'business_trip.intro',
            'business_trip.button',
            'fas fa-car',
            '@RemoteWork/remote-work-edit.html.twig',
            Constants::COLOR_BUSINESS_TRIP,
        );
    }
}
