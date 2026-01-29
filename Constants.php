<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle;

final class Constants
{
    // Types
    public const TYPE_HOMEOFFICE = 'homeoffice';
    public const TYPE_BUSINESS_TRIP = 'business_trip';

    // Status
    public const STATUS_NEW = 'new';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    // Colors
    public const COLOR_HOMEOFFICE = '#228be6';
    public const COLOR_BUSINESS_TRIP = '#f76707';

    // Icons
    public const ICON_HOMEOFFICE = 'fas fa-home';
    public const ICON_BUSINESS_TRIP = 'fas fa-car';
}
